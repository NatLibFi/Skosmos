#!/usr/bin/env python3

import requests

from flask import Flask, render_template
from flask import request
from flask import jsonify
from flask_cors import CORS

import rdflib

import json

app = Flask(__name__)
CORS(app)

api_base_url = 'https://api.dev.finto.fi/rest/v1/' #'https://vocabularies.unesco.org/browser/rest/v1/'

def _jsonpify(obj):
    """
    Helper to support JSONP
    """
    try:
        callback = request.args['callback']
        response = app.make_response("%s(%s)" % (callback, json.dumps(obj)))
        response.mimetype = "text/javascript"
        return response
    except KeyError:
        return jsonify(obj)

def _make_sparql_query(data, query):
    g=rdflib.Graph()
    g.parse(data=data, format="xml")

    qres = g.query(query)

    res = []
    for row in qres:
        d = {}
        for l in row.labels:
            d[l] = str(row[l])
        res += [d]

    return res

def _get_properties():
    return [
        {
            'id': "uri",
            'name': "URI"
        },
        {
            'id': "narrower",
            'name': "narrower"
        },
        {
            'id': "broader",
            'name': "broader"
        },
        {
            'id': "altLabel",
            'name': "altLabels"
        },
        {
            'id': "prefLabel",
            'name': "prefLabels in other languages"
        },
    ]

def _search(raw_query, vocid, limit, lang, query_type=""):
    print('search', raw_query, query_type)

    params = {'query': raw_query + "*", 'maxhits': limit, 'type': query_type, 'unique': "true", 'lang': lang}
    search_results = requests.get(api_base_url + vocid + "/search/", params=params).json()['results']

    results = [{'id': res['uri'],
                'name': res['prefLabel'],
                'score': 1,
                'match': res['prefLabel'] == raw_query,
                'type': [{
                    'id': type.replace('skos:', 'http://www.w3.org/2004/02/skos/core#'),
                    'name': type.replace('skos:', 'http://www.w3.org/2004/02/skos/core#')
                } for type in res['type']]}
            for res in search_results]
    return results


def _metadata(vocid, lang):
    vocab = requests.get(api_base_url + vocid + "/", params={'lang': lang})
    title = vocab.json()['title']
    concept_schemes = vocab.json()['conceptschemes']

    types = requests.get(api_base_url + vocid + "/types/", params={'lang': lang}).json()['types']
    query_types = [{'id': type['uri'],
                    'name': type['label']}
                    for type in types]

    service_metadata = {
        'name': "Reconciliation service for " + title + " (" + lang + ")",
        'identifierSpace': concept_schemes[0]['uri'],
        'schemaSpace': "",
        'defaultTypes': query_types,
        'view': {
            'url': "{{id}}"
        },
        'suggest': {
            'entity': {
                'service_path': "/suggest/entity",
                'service_url': request.url_root + vocid + "/" + lang + "/reconcile"
            }
        },
        'preview': {
            'url': request.url_root + vocid + "/" + lang + "/reconcile/preview?id={{id}}",
            'width': 300,
            'height': 100
        },
        'extend': {
            'property_settings': [
                {
                    'name': "limit",
                    'label': "Limit",
                    'type': "number",
                    'default': 0,
                    'help_text': "Maximum number of values to return per row"
                }
            ],
            'propose_properties': {
                'service_path': "/propose_properties",
                'service_url': request.url_root + vocid + "/" + lang + "/reconcile"
            }
        }
    }

    return _jsonpify(service_metadata)

def _reconcile(queries, vocid, lang):
    queries = json.loads(queries)
    results = {}
    for (key, query) in queries.items():
        qtype = query.get('type')
        limit = query.get('limit')
        data = _search(query['query'], vocid=vocid, limit=limit, lang=lang, query_type=qtype)
        results[key] = {'result': data}
    return _jsonpify(results)

def _extend(extend, vocid, lang):
    extend = json.loads(extend)

    rows = {}
    for uri in extend['ids']:
        params = {'uri': uri, 'lang': lang, 'format': "application/rdf+xml"}
        data = requests.get(api_base_url + vocid + "/data", params=params).text

        result = {}
        for prop in extend['properties']:
            limit = int(prop.get('settings').get('limit')) if prop.get('settings') else None

            if prop['id'] == 'uri':
                result[prop['id']] = [{'str': uri}]

            elif prop['id'] == 'narrower':
                query = """
                    PREFIX skos:<http://www.w3.org/2004/02/skos/core#>
                    SELECT ?label (?narrower as ?uri)
                    WHERE {
                        <%s> skos:narrower ?narrower .
                        ?narrower skos:prefLabel ?label .

                        FILTER (lang(?label) = '%s')
                    }
                """ % (uri, lang)
                narrower = _make_sparql_query(data, query)
                result[prop['id']] = [{"id": n["uri"], "name": n["label"]} for n in narrower[:limit]]

            elif prop['id'] == 'broader':
                query = """
                    PREFIX skos:<http://www.w3.org/2004/02/skos/core#>
                    SELECT ?label (?broader as ?uri)
                    WHERE {
                        <%s> skos:broader ?broader .
                        ?broader skos:prefLabel ?label .

                        FILTER (lang(?label) = '%s')
                    }
                """ % (uri, lang)
                broader = _make_sparql_query(data, query)
                result[prop['id']] = [{"id": b["uri"], "name": b["label"]} for b in broader[:limit]]

            elif prop['id'] == 'altLabel':
                query = """
                    PREFIX skos:<http://www.w3.org/2004/02/skos/core#>
                    SELECT ?label
                    WHERE {
                        <%s> skos:altLabel ?label .

                        FILTER (lang(?label) = '%s')
                    }
                """ % (uri, lang)
                alt = _make_sparql_query(data, query)
                result[prop['id']] = [{"str": a["label"]} for a in alt[:limit]]

            elif prop['id'] == 'prefLabel':
                query = """
                    PREFIX skos:<http://www.w3.org/2004/02/skos/core#>
                    SELECT ?label (lang(?label) as ?lang)
                    WHERE {
                        <%s> skos:prefLabel ?label .

                        FILTER (lang(?label) != '%s')
                    }
                """ % (uri, lang)
                pref = _make_sparql_query(data, query)
                result[prop['id']] = [{"str": l["label"]} for l in pref[:limit]]
            
        rows[uri] = result

    meta = []
    for p in _get_properties():
        if {'id': p['id']} in extend['properties']:
            meta += [p]

    ret = {"meta": meta, "rows": rows}
    
    return _jsonpify(ret)

@app.route("/<vocid>/<lang>/reconcile", methods=['POST', 'GET'])
def reconcile(lang, vocid):
    # If a 'queries' parameter is supplied, it is a dictionary
    # of (key, query) pairs representing a batch of queries.
    # Returns a dictionary of (key, results) pairs.
    queries = request.form.get('queries') if request.form.get('queries') else request.args.get('queries')
    # If a 'extend' parameter is supplied, it is a dictionary
    # with a list of entity uris and a list of properties
    # Returns a dictionary with a list of properties and a dictionary of (uri, property_value) pairs.
    extend = request.form.get('extend') if request.form.get('extend') else request.args.get('extend')

    if queries:
        return _reconcile(queries, vocid, lang)
    elif extend:
        return _extend(extend, vocid, lang)
    else:
    # If no 'queries' or 'extend' parameter is supplied,
    # return the service metadata.
        return _metadata(vocid, lang)

@app.route("/<vocid>/<lang>/reconcile/suggest/entity", methods=['GET'])
def suggest(vocid, lang):
    prefix = request.args.get('prefix')
    cursor = int(request.args.get('cursor')) if request.args.get('cursor') else 0
    limit = cursor + 20

    result = _search(prefix, vocid=vocid, limit=limit, lang=lang)

    results = [{'id': res['id'], 'name': res['name'], 'notable': res['type']} for res in result]
    return {'result': results[cursor:]}

@app.route("/<vocid>/<lang>/reconcile/propose_properties", methods=['GET'])
def propose_properties(vocid, lang):
    qtype = request.args.get('type')
    limit = int(request.args.get('limit')) if request.args.get('limit') else None

    properties = _get_properties()

    ret = {
        "type": qtype,
        "properties": properties[:limit]
    }
    if (limit):
        ret['limit'] = limit

    return ret

@app.route("/<vocid>/<lang>/reconcile/preview", methods=['GET'])
def preview(vocid, lang):
    uri = request.args.get('id')

    params = {'uri': uri, 'lang': lang, 'format': "application/rdf+xml"}
    data = requests.get(api_base_url + vocid + "/data", params=params).text
    print(data)

    context = {'uri': uri, 'lang': lang}

    pref_label_query = """
        PREFIX skos:<http://www.w3.org/2004/02/skos/core#>
        SELECT ?label
        WHERE {
            <%s> skos:prefLabel ?label .

            FILTER (lang(?label) = '%s')
        }
    """ % (uri, lang)
    context['pref_label'] = _make_sparql_query(data, pref_label_query)

    other_pref_labels_query = """
        PREFIX skos:<http://www.w3.org/2004/02/skos/core#>
        SELECT ?label (lang(?label) as ?lang)
        WHERE {
            <%s> skos:prefLabel ?label .

            FILTER (lang(?label) != '%s')
        }
    """ % (uri, lang)
    context['other_pref_labels'] = _make_sparql_query(data, other_pref_labels_query)

    alt_labels_query = """
        PREFIX skos:<http://www.w3.org/2004/02/skos/core#>
        SELECT ?label
        WHERE {
            <%s> skos:altLabel ?label .

            FILTER (lang(?label) = '%s')
        }
    """ % (uri, lang)
    context['alt_labels'] = _make_sparql_query(data, alt_labels_query)

    broader_query = """
        PREFIX skos:<http://www.w3.org/2004/02/skos/core#>
        SELECT ?label (?broader as ?uri)
        WHERE {
            <%s> skos:broader ?broader .
            ?broader skos:prefLabel ?label .

            FILTER (lang(?label) = '%s')
        }
    """ % (uri, lang)
    context['broader'] = _make_sparql_query(data, broader_query)

    narrower_query = """
        PREFIX skos:<http://www.w3.org/2004/02/skos/core#>
        SELECT ?label (?narrower as ?uri)
        WHERE {
            <%s> skos:narrower ?narrower .
            ?narrower skos:prefLabel ?label .

            FILTER (lang(?label) = '%s')
        }
    """ % (uri, lang)
    context['narrower'] = _make_sparql_query(data, narrower_query)

    definition_query = """
        PREFIX skos:<http://www.w3.org/2004/02/skos/core#>
        SELECT ?definition
        WHERE {
            <%s> skos:definition ?definition .

            FILTER (lang(?definition) = '%s')
        }
    """ % (uri, lang)
    context['definition'] = _make_sparql_query(data, definition_query)

    print(json.dumps(context, indent=2))
    
    return render_template("preview.html", context=context)

if __name__ == '__main__':
    from optparse import OptionParser
    oparser = OptionParser()
    oparser.add_option('-d', '--debug', action='store_true', default=False)
    opts, args = oparser.parse_args()
    app.debug = opts.debug
    app.run(host='0.0.0.0')

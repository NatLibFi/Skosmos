#!/usr/bin/env python3

import requests


from flask import Flask
from flask import request
from flask import jsonify
from flask_cors import CORS

import json

app = Flask(__name__)
CORS(app)

api_base_url = 'http://api.dev.finto.fi/rest/v1/'

def jsonpify(obj):
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


def search(raw_query, query_type, limit, vocid):
    print("search", raw_query, query_type)

    params = {"query": raw_query + "*", "maxhits": limit, "type": query_type, "unique": "true"}
    search_results = requests.get(api_base_url + vocid + '/search', params=params).json()['results']

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


def metadata(vocid):
    vocab = requests.get(api_base_url + vocid)
    title = vocab.json()['title']
    concept_schemes = vocab.json()['conceptschemes']

    types = requests.get(api_base_url + vocid + '/types').json()['types']
    query_types = [{'id': type['uri'],
                    'name': type['label']}
                    for type in types]

    service_metadata = {
        "name": "Skosmos reconciliation service for " + title,
        "identifierSpace": concept_schemes[0]["uri"],
        "schemaSpace": "",
        "defaultTypes": query_types,
        "view": {
            "url": "{{id}}"
        }
    }

    return service_metadata

@app.route("/<vocid>/reconcile", methods=['POST', 'GET'])
def reconcile(vocid):
    # If a 'queries' parameter is supplied then it is a dictionary
    # of (key, query) pairs representing a batch of queries. We
    # should return a dictionary of (key, results) pairs.
    queries = request.form.get('queries') if request.form.get('queries') else request.args.get('queries')

    if queries:
        queries = json.loads(queries)
        results = {}
        for (key, query) in queries.items():
            qtype = query.get('type')
            limit = query.get('limit')
            data = search(query['query'], query_type=qtype, limit=limit, vocid=vocid)
            results[key] = {"result": data}
        return jsonpify(results)
    # If no 'queries' parameter is supplied then
    # we should return the service metadata.
    return jsonpify(metadata(vocid))

if __name__ == '__main__':
    from optparse import OptionParser
    oparser = OptionParser()
    oparser.add_option('-d', '--debug', action='store_true', default=False)
    opts, args = oparser.parse_args()
    app.debug = opts.debug
    app.run(host='0.0.0.0')

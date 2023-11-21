<div class="col-md-8 ps-3">
  <div class="pt-3" id="main-content">

    <div class="main-content-section p-5">

      <div class="row" id="concept-heading">
        <div class="col-sm-4 px-0" id="concept-property-label">{{ "skos:prefLabel" | trans }}</div>
        <div class="col-sm-8" id="concept-label">
          <h1 class="mb-0">{{ concept.label }}</h1>
          <button class="btn btn-default copy-clipboard" type="button" data-bs-toggle="tooltip" data-bs-placement="button" title="{{ "Copy to clipboard" | trans }}">
          <i class="fa-regular fa-copy"></i>
          </button>
        </div>
      </div>

      {% for property in concept.properties %}
      <div class="row property prop-{{property.ID}}">
        <div class="col-sm-4 px-0 property-label"><h2>{{ property.label}}</h2></div>
        <div class="col-sm-8 align-self-center property-value">
          <ul class="align-bottom">
          {% for propval in property.values %}
            {% if propval.uri and property.type != 'rdf:type' %} {# resources with URI #}
            <li><a href="{{ propval.uri | link_url(propval.vocab, request.lang, 'page', request.contentLang) }}">{% if propval.notation %}<span class="versal">{{ propval.notation }}</span> {% endif %}{{ propval.label(request.contentLang) }}</a></li>
            {% else %} {# literals, e.g. altLabels #}
            <li>{{ propval.label }}</li>
            {% endif %}
          {% endfor %}
          </ul>
        </div>
      </div>
      {% endfor %}
      {% set foreignLabels = concept.foreignLabels %}
      {% if foreignLabels %}
      <div class="row property prop-foreignlabels">
        <div class="col-sm-4 px-0 property-label"><h2>{{ 'foreign prefLabels'|trans }}</h2></div>
        <div class="col-sm-8" id="concept-other-languages">
          {% for language,labels in foreignLabels %}
          <div class="row">
            <div class="col-sm-6 order-last"><h3>{{ language }}</h3></div>
            <div class="col-sm-6">
              <ul>
                {% for value in labels.prefLabel|default([])|merge(labels.altLabel|default([])) %}
                {% if value.type == "skos:prefLabel" and value.lang in request.vocab.config.languages %}
                <li><a href='{{ concept.uri|link_url(request.vocabid,request.lang, 'page', value.lang) }}' hreflang='{{ value.lang }}'>{{ value.label }}</a></li>
                {% else %}
                <li class="altlabel">{{ value.label }}</li>
                {% endif %}
                {% endfor %}
              </ul>
            </div>
          </div>
          {% endfor %}
        </div>
      </div>
      {% endif %}
      <div class="row property prop-uri">
        <div class="col-sm-4 px-0 property-label"><h2>URI</h2></div>
        <div class="col-sm-8" id="concept-uri"><a href="#">{{ concept.uri }}</a></div>
      </div>
      <div class="row property prop-download">
        <div class="col-sm-4 px-0 property-label"><h2><i class="fa-solid fa-download"></i> {{ "Download this concept" | trans }}</h2></div>
        <div class="col-sm-8" id="download-links">
          <ul>
            <li><a class="me-3" href="rest/v1/{% if concept.vocab %}{{ concept.vocab.id }}{% else %}{{ vocab.id }}{% endif %}/data?uri={{ concept.uri|url_encode }}&amp;format=application/rdf%2Bxml">RDF/XML</a></li>
            <li><a class="me-3" href="rest/v1/{% if concept.vocab %}{{ concept.vocab.id }}{% else %}{{ vocab.id }}{% endif %}/data?uri={{ concept.uri|url_encode }}&amp;format=text/turtle">TURTLE</a></li>
            <li><a href="rest/v1/{% if concept.vocab %}{{ concept.vocab.id }}{% else %}{{ vocab.id }}{% endif %}/data?uri={{ concept.uri|url_encode }}&amp;format=application/ld%2Bjson">JSON-LD</a></li>
          </ul>
        </div>
      </div>

    </div>

  </div>
  <!-- appendix / concept mapping properties -->
  <div id="concept-mappings">
  </div>
</div>

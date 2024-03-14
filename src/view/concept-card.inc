<div class="col-md-8 ps-3">
  <div class="pt-3" id="main-content">

    <div class="main-content-section px-5 py-4">

      {% if visible_breadcrumbs and visible_breadcrumbs[0]|length > 1 %}
      <nav class="row" id="concept-breadcrumbs" aria-label='{{ "Breadcrumbs" | trans }}'>
        {% for path in visible_breadcrumbs %}
        {% set path_idx = loop.index0 %}
        <ol>
          {% for crumb in path %}
            {% if crumb.hiddenLabel %}
              <li class="breadcrumb-toggle"><a href=".breadcrumb-collapse" data-bs-target=".breadcrumb-collapse" data-bs-toggle="collapse" role="button" aria-expanded="false">...</a></li>
              {% for hiddenCrumb in hidden_breadcrumbs[path_idx] %}
              <li class="breadcrumb collapse multi-collapse breadcrumb-collapse"><a href="{{ hiddenCrumb.uri|link_url(vocab,request.lang,'page',request.contentLang) }}">{{ hiddenCrumb.hiddenLabel }}</a></li>
              {% endfor %}
            {% else %}
              <li class="breadcrumb"><a
                  {% if loop.last %}aria-current="page" class="breadcrumb-current"
                  {% else %}href="{{ crumb.uri|link_url(vocab,request.lang,'page',request.contentLang) }}"
                  {% endif %}
                  >{{ crumb.prefLabel }}</a></li>
            {% endif %}
          {% endfor %}
        </ol>
        {% endfor %}
      </nav>
      {% endif %}

      <div class="row" id="concept-heading">
        <div class="col-sm-4 px-0" id="concept-property-label">{{ "skos:prefLabel" | trans }}</div>
        <div class="col-sm-8" id="concept-label">
          <h1 id="concept-preflabel"
              class="mb-0 user-select-all">{{ concept.label }}</h1><button
                  class="btn btn-default copy-clipboard px-1" type="button" id="copy-preflabel"
                  data-bs-toggle="tooltip" data-bs-placement="button" title="{{ 'Copy to clipboard' | trans }}">
            <i class="fa-regular fa-copy"></i>
          </button>
        </div>
      </div>

      {% for property in concept.properties %}{% if property.getSubPropertyOf != 'skos:hiddenLabel' %}
      <div class="row property prop-{{property.ID}}">
        <div class="col-sm-4 px-0 property-label"><h2>{{ property.label}}</h2></div>
        <div class="col-sm-8 align-self-center property-value">
          <ul class="align-bottom">
          {% for propval in property.values %}
            {% if propval.uri and property.type != 'rdf:type' %} {# resources with URI #}
            <li>
              <a href="{{ propval.uri | link_url(propval.vocab, request.lang, 'page', request.contentLang) }}">
                {{- propval.label(request.contentLang) -}}
              </a>
            </li>
            {% else %} {# literals, e.g. altLabels #}
            <li>{% if propval.containsHtml %}{{ propval.label|raw }}{% else %}{{ propval.label }}{% endif %}</li>
            {% endif %}
          {% endfor %}
          </ul>
        </div>
      </div>
      {% endif %}{% endfor %}
      {% set foreignLabels = concept.foreignLabels %}
      {% if foreignLabels %}
      <div class="row property prop-foreignlabels">
        <div class="col-sm-4 px-0 property-label"><h2>{{ 'foreign prefLabels'|trans }}</h2></div>
        <div class="col-sm-8" id="concept-other-languages">
          {% for language,labels in foreignLabels %}
          <div class="row">
            <div class="col-sm-7 order-last"><h3>{{ language }}</h3></div>
            <div class="col-sm-5">
              <ul>
                {% for value in labels.prefLabel|default([])|merge(labels.altLabel|default([])) %}
                {% if value.type == "skos:prefLabel" and value.lang in request.vocab.config.languages %}
                <li>
                  <a href="{{ concept.uri|link_url(request.vocabid,request.lang, 'page', value.lang) }}"
                     hreflang="{{ value.lang }}">{{ value.label }}</a>
                </li>
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
        <div class="col-sm-8">
          <span id="concept-uri"
                class="user-select-all">{{ concept.uri }}</span><button
                  class="btn btn-default copy-clipboard px-1" type="button" id="copy-uri"
                  data-bs-toggle="tooltip" data-bs-placement="button" title="{{ 'Copy to clipboard' | trans }}">
            <i class="fa-regular fa-copy"></i>
          </button>
        </div>
      </div>
      <div class="row property prop-download">
        <div class="col-sm-4 px-0 property-label">
          <h2><i class="fa-solid fa-download"></i> {{ "Download this concept" | trans }}</h2>
        </div>
        <div class="col-sm-8" id="download-links">
          <ul>
            <li>
              <a class="me-3" href="rest/v1/{{ vocab.id }}/data?uri={{ concept.uri|url_encode }}&amp;format=application/rdf%2Bxml">RDF/XML</a>
            </li>
            <li>
              <a class="me-3"href="rest/v1/{{ vocab.id }}/data?uri={{ concept.uri|url_encode }}&amp;format=text/turtle">TURTLE</a>
            </li>
            <li>
              <a href="rest/v1/{{ vocab.id }}/data?uri={{ concept.uri|url_encode }}&amp;format=application/ld%2Bjson">JSON-LD</a>
            </li>
          </ul>
          {% if concept.date %}
          <div id="date-info">{{ concept.date }}</div>
          {% endif %}
        </div>
      </div>
    </div>
    <!-- appendix / concept mapping properties -->
    <div id="concept-mappings">
    </div>

  </div>
</div>

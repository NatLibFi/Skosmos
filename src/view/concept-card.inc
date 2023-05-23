<div class="col-md-8">
  {% if search_results %}
    {% for concept in search_results %} {# loop through the hits #}
    <h2 class="visually-hidden">Concept information</h2>
    {% if concept.deprecated %}
    <div class="alert alert-danger">
      <p class="deprecated-alert">deprecated</p>
    </div>
    {% endif %}
    {% if concept.label.lang != request.contentLang %}
    <div class="alert alert-lang">
      <p class="language-alert">There is no term for this concept in this language</p>
    </div>
    {% endif %}
    <div class="container concept-info{% if concept.deprecated %} deprecated-concept{% endif %}">
      <div class="bg-light py-5 px-5 concept-main">
      {% spaceless %}
      <div id="term-heading" class="d-flex">
          <span>
            {% set subPrefLabelTranslation = concept.preferredSubpropertyLabelTranslation(request.lang) %}
            {% if subPrefLabelTranslation %}
              {{ subPrefLabelTranslation }}
            {% elseif custom_labels['skos:prefLabel']['label'][request.lang] %}
              {{ custom_labels['skos:prefLabel']['label'][request.lang] }}
            {% else %}
              skos:prefLabel
            {% endif %}
          </span>
        {% if concept.foundBy %} {# hit has been found through an alternative label #}
        <span class="versal">{{ concept.foundBy }} ></span>
        {% if concept.ExVocab is defined %}
        <span class="prefLabel conceptlabel redirected-vocab-id"> @{{ concept.ExVocab }}</span>
        {% endif %}
      {% else %}
        {% apply spaceless %}
        {% if concept.notation %}<span class="notation" id="notation">{{ concept.notation }}</span>{% endif %}
        {% if concept.hasXlLabel %}
        <span class="reified-property-value xl-pref-label tooltip-html">
          <img src="resource/pics/about.png">
            <div class="reified-tooltip tooltip-html-content">
              {% for key, val in concept.xlLabel.properties %}
              <p><span class="tooltip-prop">{{ key }}</span>:
                <span class="versal">{{ val }}</span>
              </p>
              {% endfor %}
            </div>
        </span>
        <h1 id="pref-label">{{ concept.xlLabel }}</h1>
        {% else %}
        <h1 id="pref-label">{{ concept.label }}</h1>
        {% if concept.label.lang != request.contentLang and concept.label.lang != '' %}
        <span class="prefLabelLang"> ({{ concept.label.lang }})</span>
        {% endif %}
        {% endif %}
        &nbsp;
        <button type="button" data-bs-toggle="tooltip" data-bs-placement="button" title="Copy to clipboard" class="btn btn-default btn-xs copy-clipboard" for="#{% if concept.notation %}notation{% else %}pref-label{% endif %}">
          <span class="fa-regular fa-copy"></span>
        </button>
        {% endapply %}
      {% endif %}
      </div>
      {% endspaceless %}
      <table id="main-table" class="table">
        <tbody>
        {% for property in concept.properties %} {# loop through ConceptProperty objects #}
          {% if property.getSubPropertyOf != 'skos:hiddenLabel' %}
          <tr class="{% if property.type == 'dc:isReplacedBy' %}replaced-by {% endif%}property prop-{{property.ID}}">
            <td class="main-table-label">
                {% if custom_labels[property.type]['label'][request.lang] %}
                  {{ custom_labels[property.type]['label'][request.lang] }}
                {% else %}
                  {{ property.label }}
                {% endif %}
            </td>
          {% for propval in property.values %} {# loop through ConceptPropertyValue objects #}
          {% if not loop.first %}</tr><tr><td class="main-table-label"></td>{% endif %}
          <td class="align-middle">
            {% if propval.uri and propval.type != 'rdf:type' %} {# resources with URI #}
              {% if propval.label %}
                {% if propval.isExternal %}
                <a href="{{ propval.uri | link_url(propval.exvocab, request.lang, 'page', request.contentLang) }}">{{ propval.label }}</a>{% if propval.exvocab %} ({{ propval.vocabname }}){% endif %}
                  {% else %}
                  {% if propval.isReified %} {# e.g. skos:definition's with resource values #}
                  <span class="versal reified-property-value tooltip-html">
                    <img alt="Information" src="resource/pics/about.png">{% if propval.notation %}<span class="versal">{{ propval.notation }} </span>{% endif %} {{ propval.label(request.contentLang) }}
                    <div class="reified-tooltip tooltip-html-content">{% for key, val in propval.reifiedPropertyValues %}<p><span class="tooltip-prop">{{ key }}</span>: <a href="{{ val.uri | link_url(val.exvocab, request.lang, 'page', request.contentLang) }}">{{ val.label(request.contentLang) }}</a></p>{% endfor %}</div>
                  </span>
                  {% else %}
                      <a href="{{ propval.uri | link_url(propval.vocab, request.lang, 'page', request.contentLang) }}">{% if propval.notation %}<span class="versal">{{ propval.notation }} </span>{% endif %} {{ propval.label(request.contentLang) }}</a>
                  {% endif %}
                  {% endif %}
                  {% if propval.label.lang and (propval.label.lang != request.contentLang or explicit_langcodes) %}<span class="versal"> ({{ propval.label(request.contentLang).lang }})</span>{% endif %}
                  {% if propval.SubMembers %}<div class="subvalue"> {# if property is a group concept that has sub properties #}
                    {% for sub_member in propval.SubMembers %}
                      <a class="propertyvalue" href="{{ sub_member.uri | link_url(propval.vocab,request.lang) }}">{{ sub_member.label(request.contentLang) }}</a>
                      {% if sub_member.lang and (sub_member.lang != request.lang or explicit_langcodes) %}<span class="propertyvalue"> ({{ sub_member.lang }})</span>{% endif %}
                      <br />
                    {% endfor %}
                    </div>
                  {% endif %}
                {% endif %}
              {% elseif property.type == 'rdf:type' %}<p>{{ propval.label }}</p>
              {% else %} {# Literals (no URI), eg. alternative labels as properties #}
                  {% if propval.lang == request.contentLang or propval.lang == null or not request.contentLang and propval.lang == request.lang %}
                    {% if propval.hasXlProperties %}
                    <span class="reified-property-value xl-label tooltip-html">
                      <img alt="Information" src="resource/pics/about.png">
                      <div class="reified-tooltip tooltip-html-content">
                      {% for key, val in propval.xlLabel.properties %}
                        <p><span class="tooltip-prop">{{ key }}</span>:
                          <span class="versal">{{ val }}</span>
                        </p>
                      {% endfor %}
                      </div>
                    </span>
                    {% endif %}
                    <span{% if property.type == 'skos:altLabel' %} class="replaced"{% endif %}>
                      {%- if propval.containsHtml %}{{ propval.label|raw }}{% else %}{{ propval.label }}{% endif %}
                      {%- if propval.lang and (request.contentLang and propval.lang != request.contentLang or explicit_langcodes) %} ({{ propval.lang }}){% endif %}
                      {%- if propval.datatype %} ({{ propval.datatype }}){% endif -%}
                    </span>
                {% endif %}
              {% endif %}
              </td>
            </tr>
          {% endfor %}
          {% endif %}
        {% endfor %}
        </tr>
      {% set foreignLabels = concept.foreignLabels %}
      {% if foreignLabels %}
      <tr class="prop-other-languages">
        <td class="main-table-label">foreign prefLabels</td>
        <td class="align-middle">
            {% for language,labels in foreignLabels %}
              {% for value in labels.prefLabel|default([])|merge(labels.altLabel|default([])) %}
                <div class="col-6 versal{% if value.type == "skos:altLabel" %} replaced{%else %} versal-pref{% endif %}">
                  {% if value.hasXlProperties %}
                  <span class="reified-property-value xl-label tooltip-html">
                    <img alt="Information" src="resource/pics/about.png">
                    <div class="reified-tooltip tooltip-html-content">
                    {% for key, val in value.xlLabel.properties %}
                    {% if key != 'rdf:type' and key != 'skosxl:literalForm' %}
                      <p><span class="tooltip-prop">{{ key }}</span>:
                        <span class="versal">{{ val }}</span>
                      </p>
                    {% endif %}
                    {% endfor %}
                    </div>
                  </span>
                  {% endif %}
                  {% if value.type == "skos:prefLabel" and value.lang in request.vocab.config.languages %}
                  <a href='{{ concept.uri|link_url(request.vocabid,request.lang, 'page', value.lang) }}' hreflang='{{ value.lang }}'>{{ value.label }}</a>
                  {% else %}{{ value.label }}
                  {% endif %}
                </div>
                <div class="col-6 versal">{% if loop.first %}<p>{{ language }}</p>{% endif %}</div>
              {% endfor %}
            {% endfor %}
          </div>
        </td>
      </tr>
      {% endif %}
      <tr>
        <td class="main-table-label"><h3>URI</h3></td>
        <td class="align-middle">{{ concept.uri }}</td>
      </tr>
      </tbody>
    </table>
        <div class="row">
            <div class="property-label"><h3 class="versal">Download this concept in SKOS format:</h3></div>
            <div class="property-value-column">
<span class="versal concept-download-links"><a href="rest/v1/{% if concept.vocab %}{{ concept.vocab.id }}{% else %}{{ vocab.id }}{% endif %}/data?uri={{ concept.uri|url_encode }}&amp;format=application/rdf%2Bxml">RDF/XML</a>
          <a href="rest/v1/{% if concept.vocab %}{{ concept.vocab.id }}{% else %}{{ vocab.id }}{% endif %}/data?uri={{ concept.uri|url_encode }}&amp;format=text/turtle">
            TURTLE</a>
          <a href="rest/v1/{% if concept.vocab %}{{ concept.vocab.id }}{% else %}{{ vocab.id }}{% endif %}/data?uri={{ concept.uri|url_encode }}&amp;format=application/ld%2Bjson">JSON-LD</a>
        </span>{% if concept.date %}<span class="versal date-info">{{ concept.date }}</span>{% endif %}
            </div>
        </div>
      </div>
      <!-- appendix / concept mapping properties -->
      <div
          class="concept-appendix hidden"
          data-concept-uri="{{ concept.uri }}"
          data-concept-type="{{ concept.type[0] }}"
          >
      </div>
    </div>
    {% endfor %}

  {% else %}
  <div class="alert alert-danger">
    <p>Error: Term "{{ term }}" not found in vocabulary!</p>
  </div>
  {% endif %}
</div>

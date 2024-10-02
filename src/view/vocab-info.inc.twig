<div class="col-md-8" id="main-content"> <!-- id for partial page load -->
  <div class="main-content-section py-5 px-5" id="vocab-info">
    <h2 class="fw-bold fs-3 py-4">{{ "Vocabulary information" | trans }}</h2>
    {% set vocabInfo = vocab.info(request.contentLang) %}
    {% if not vocabInfo %}
    <div class="alert alert-danger" role="alert">
        {{ "Error: Failed to retrieve vocabulary information!" | trans }}
    </div>
    {% else %}
    <table class="vocab-info-literals">
      <tbody class="table">
        {% for key, values in vocabInfo %}
        {% set keytrans = key %}
        <tr>
          <td class="main-table-label fw-bold">{{ keytrans | trans }}</td>
          <td class="align-middle">
            {% for val in values %}
            <div class="property-value-wrapper">
              {% if val.get('skosmos:vocab') %}
              <a href="{{ val.uri | link_url(val.get('skosmos:vocab').value,request.lang,'page',request.contentLang) }}">{{ val.get('skosmos:label') }}</a>
              {% elseif val.label %}
              <a href="{{ val.uri }}">
                {% if val.label(request.contentLang) %}{{ val.label(request.contentLang) }}
                {% else %}{{ val.label }}
                {% endif %}
              </a>
              {% elseif val matches '|^(https?://)|' %}<a href="{{ val }}">{{ val }}</a>
              {% elseif val matches '|<\\w*\\b[^>]*>|' %}{{ val|raw|nl2br }}
              {% else %}{{ val|nl2br }}
              {% endif %}
            </div>
            {% endfor %}
          </td>
        </tr>
        {% endfor %}
        <tr>
          <td class="main-table-label fw-bold">URI</td>
          <td class="align-middle">
            <div class="property-value-wrapper">
              <span class="uri-input-box">{{ vocab.defaultConceptScheme }}</span>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
    {% endif %}
    {% if vocab.config.showStatistics %}
    <div class="vocab-statistics">
      <div id="resource-counts"></div>
      <div id="term-counts"></div>
    </div>
    {% endif %}
    {% if vocab.config.dataURLs %}
    {% apply spaceless %}
    <div class="download-links">
      <span class="versal">{{ "Download this vocabulary:" | trans }}</span>
        {% if 'application/rdf+xml' in vocab.config.dataURLs|keys %}
          <a href="rest/v1/{{ request.vocabid }}/data?format=application/rdf%2Bxml">RDF/XML</a>
        {% endif %}
        {% if 'text/turtle' in vocab.config.dataURLs|keys %}
          <a href="rest/v1/{{ request.vocabid }}/data?format=text/turtle">TURTLE</a>
        {% endif %}
        {% if 'application/ld+json' in vocab.config.dataURLs|keys %}
          <a href="rest/v1/{{ request.vocabid }}/data?format=application%2Fld%2Bjson">JSON-LD</a>
        {% endif %}
        {% if 'application/marcxml+xml' in vocab.config.dataURLs|keys %}
            {% if vocab.config.dataURLs['application/marcxml+xml'] is iterable %}
                {% for key, values in vocab.config.dataURLs['application/marcxml+xml'] %}
                    <a href="rest/v1/{{ request.vocabid }}/data?format=application/marcxml%2Bxml&lang={{key}}">MARCXML/{{ key | lang_name(request.lang) }}</a>
                {% endfor %}
            {% else %}
                <a href="rest/v1/{{ request.vocabid }}/data?format=application/marcxml%2Bxml">MARCXML</a>
            {% endif %}
        {% endif %}
    </div>
    {% endapply %}
    {% endif %}
  </div>
  <!-- appendix / concept mapping properties -->
  <div id="concept-mappings">
  </div>
</div>

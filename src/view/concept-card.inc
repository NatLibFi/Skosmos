<div class="col-md-8 ps-3">
  <div class="pt-3" id="main-content">

    <div class="main-content-section p-5">

      <div class="d-flex" id="term-heading">
        <span>{{ "skos:prefLabel" | trans }}</span>
        <h2 class="mb-0" id="pref-label">{{ concept.label }}</h2>
        <button class="btn btn-default copy-clipboard" type="button" data-bs-toggle="tooltip" data-bs-placement="button" title="{{ "Copy to clipboard" | trans }}" for="#pref-label">
          <i class="fa-regular fa-copy"></i>
        </button>
      </div>

      <table class="table" id="main-table">
        <tbody>
          {% for property in concept.properties %}
          <tr class="property prop-{{property.ID}}">
            <td class="main-table-label px-0">{{ property.label }}</td>
            <td class="align-middle px-0">
              <ul>
              {% for propval in property.values %}
                {% if propval.uri %} {# resources with URI #}
                <li><a href="{{ propval.uri | link_url(propval.vocab, request.lang, 'page', request.contentLang) }}">{% if propval.notation %}<span class="versal">{{ propval.notation }} </span>{% endif %} {{ propval.label(request.contentLang) }}</a></li>
                {% else %} {# literals, e.g. altLabels #}
                <li>{{ propval.label }}</li>
                {% endif %}
              {% endfor %}
              </li>
            </td>
          </tr>
          {% endfor %}
          {% set foreignLabels = concept.foreignLabels %}
          {% if foreignLabels %}
          <tr>
            <td class="main-table-label px-0">{{ 'foreign prefLabels'|trans }}</td>
            <td class="term-other-languages align-middle px-0">
                {% for language,labels in foreignLabels %}
                {% for value in labels.prefLabel|default([])|merge(labels.altLabel|default([])) %}
                {% if value.type == "skos:prefLabel" and value.lang in request.vocab.config.languages %}
                <a class="label" href='{{ concept.uri|link_url(request.vocabid,request.lang, 'page', value.lang) }}' hreflang='{{ value.lang }}'>{{ value.label }}</a>
                {% else %}
                <span class="label">{{ value.label }}</span>
                {% endif %}
                {% if loop.first %}<span>{{ language }}</span>{% endif %}
                {% endfor %}
                {% endfor %}
            </td>
          </tr>
          {% endif %}
          <tr>
            <td class="main-table-label px-0">URI</td>
            <td class="align-middle px-0"><a href="#">{{ concept.uri }}</a></td>
          </tr>
          <tr>
            <td class="main-table-label px-0"><i class="fa-solid fa-download"></i> {{ "Download this concept" | trans }}</td>
            <td class="align-middle px-0">
              <a class="me-3" href="rest/v1/{% if concept.vocab %}{{ concept.vocab.id }}{% else %}{{ vocab.id }}{% endif %}/data?uri={{ concept.uri|url_encode }}&amp;format=application/rdf%2Bxml">RDF/XML</a>
              <a class="me-3" href="rest/v1/{% if concept.vocab %}{{ concept.vocab.id }}{% else %}{{ vocab.id }}{% endif %}/data?uri={{ concept.uri|url_encode }}&amp;format=text/turtle">TURTLE</a>
              <a href="rest/v1/{% if concept.vocab %}{{ concept.vocab.id }}{% else %}{{ vocab.id }}{% endif %}/data?uri={{ concept.uri|url_encode }}&amp;format=application/ld%2Bjson">JSON-LD</a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

  </div>
</div>

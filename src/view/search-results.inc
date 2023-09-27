{% block content %}
  {%~ if search_results is not null and search_results|length == 0 ~%}
  <p class="no-results">{{ "The search provided no results." | trans }}</p>
  {%~ endif ~%}

{%~ for concept in search_results ~%} {# loop through the hits #}

<div class="search-result mt-5 pt-1">
  <div class="search-result-term">
  {%- if vocab == '' and concept.vocab.id is defined %}<span class="fw-bold" data-title="{{ concept.vocabTitle }}">{{ concept.shortName }}: </span>{% endif -%}
  {%- if concept.foundBy %} {# hit has been found through an alt/hidden/another language label #}
    {%~ if concept.foundByType != 'lang' and concept.foundByType != 'hidden' ~%}
    <span class="fst-italic">{{ concept.foundBy }}{%~ if explicit_langcodes or request.queryparam('anylang') and concept.contentLang != '' ~%} ({{ concept.contentLang }}){%~ endif ~%}</span>
  &nbsp;<i class="fa-solid fa-arrow-right"></i>&nbsp;
      {%~ if concept.notation ~%}<span>{{ concept.notation }}&nbsp;</span>{%~ endif ~%}
    <a class="fw-bold" href="{%~ if 'isothes:ConceptGroup' in concept.type ~%}{{ concept.uri | link_url(concept.vocab,request.lang,'page',concept.contentLang) }}{%~ else ~%}{{ concept.uri | link_url(concept.vocab,request.lang,'page',concept.contentLang) }}{%~ endif ~%}">{{ concept.label(request.contentLang) }}</a>
      {%~ if explicit_langcodes or request.queryparam('anylang') ~%}<span> ({{ concept.label.lang }})</span>{%~ endif ~%}
    {%~ else ~%}
      {%~ if concept.notation ~%}<span>{{ concept.notation }}&nbsp;</span>{%~ endif ~%}
    <a href="{%~ if "isothes:ConceptGroup" in concept.type ~%}{{ concept.uri | link_url(concept.vocab,request.lang,'page',concept.contentLang) }}{%~ else ~%}{{ concept.uri | link_url(concept.vocab,request.lang,'page',concept.contentLang) }}{%~ endif ~%}">{{ concept.label(request.contentLang) }}</a>
      {%~ if explicit_langcodes or request.queryparam('anylang') ~%}<span> ({{ concept.contentLang }})</span>{%~ endif ~%}
    {%~ endif ~%}
    {%~ else ~%}
      {%~ if concept.notation  %}<span class="notation">{{ concept.notation }}&nbsp;</span>{%  endif ~%}
    <a class="prefLabel conceptlabel" href="{%~ if "isothes:ConceptGroup" in concept.type ~%}{{ concept.uri | link_url(concept.vocab, request.lang, 'page') }}{%~ elseif concept.exvocab is defined%}{{ concept.uri | link_url(concept.exvocab,concept.contentLang) }}{%~ else ~%}{{ concept.uri | link_url(concept.vocab,request.lang,'page',concept.contentLang) }}{%~ endif ~%}">{{ concept.label(request.contentLang) }}</a>{%~ if explicit_langcodes or request.queryparam('anylang') ~%} ({{ concept.contentLang }}){%~ endif ~%}
  {% endif -%}
  </div>
  <ul class="list-group">
  {% set conceptProperties = concept.properties %}
  {%~ for property in conceptProperties ~%} {# loop through ConceptProperty objects #}
    {%~ if property.type in PreferredProperties or property.type in concept.vocab.config.additionalSearchProperties  or property.type in concept.vocab.config.hierarchyProperty ~%}
    <li class="list-group-item px-0 py-1">
      <span class="skosmos-tooltip" data-title="{{ property.description }}">
  {%- if property.type == 'skos:broader' -%}<i class="property-hover fa-solid fa-arrow-turn-up fa-rotate-270"></i>
  {%- elseif property.type == 'skos:narrower' -%}<i class="property-hover fa-solid fa-arrow-turn-up fa-rotate-90"></i>
  {%- elseif property.type == 'skos:altLabel' -%}<i class="property-hover fa-solid fa-arrow-right"></i>
  {%- elseif property.type == 'skos:related' -%}<i class="property-hover fa-solid fa-cloud"></i>
  {%- elseif property.type == 'skosmos:memberOf' -%}<i class="property-hover fa-solid fa-layer-group"></i>
  {%- elseif property.type == 'skos:note' -%}<i class="property-hover fa-solid fa-note-sticky"></i>
  {%- else -%}<i class="property-hover fa-solid fa-globe"></i>
  {%- endif -%}
      </span>
    {%- for propval in property.values -%} {# loop through ConceptPropertyValue objects #}
      {%~ set outerLast = loop.last ~%}
        {%- if propval.uri ~%} {# resources with URI #}
          {%- if propval.label ~%}
            {%- if propval.exvocab and propval.exvocab != propval.vocab ~%}{# if the property is located in a another vocabulary #}{{ propval.label }}
            {% else %}
            {{ propval.label(request.contentLang) }}
            {%- endif -%}
          {%- if propval.lang and (propval.lang != request.lang) or explicit_langcodes %} ({{ propval.lang }}){%~ endif -%}{%- if not loop.last ~%}, {%~ endif ~%}
        {%~ endif -%}
        {%~ else ~%} {# Literals (no URI), eg. alternative labels as properties #}
        <span class="{%~ if propval.type == 'skos:altLabel' %}replaced {% endif ~%}">{{ propval.label }}{%- if not loop.last -%}, {% endif %}</span>
      {%~ endif %}
    {%- endfor %}
    </li>
    {%~ endif ~%}
  {%~ endfor ~%}
  {%~ set foreignLabels = concept.foreignLabels ~%}
  {%~ if foreignLabels  ~%}
  <li class="list-group-item px-0 py-1">
    <span class="skosmos-tooltip" data-title="foreign prefLabel help"><i class="property-hover fa-solid fa-globe"></i></span>
    {%-  for langstring, labels in foreignLabels  ~%}
      {%~ for label in labels.prefLabel|default([])|merge(labels.altLabel|default([])) ~%}
    <span class="{% if label.type == 'skos:prefLabel' %}prefLabel{% elseif label.type == 'skos:altLabel' %}replaced{% endif %} proplabel">{{ label }}</span>{%~ if label.lang %} ({{ label.lang }}){% endif ~%}{%- if loop.last == false %}, {% endif -%}
      {%~ endfor ~%}
      {%- if loop.last == false -%}, {%- endif -%}
    {%~ endfor ~%}
  </li>
{% endif ~%}

{%~ if concept.vocab.config.additionalSearchProperties ~%}
  {%~ set mappingProperties = concept.mappingProperties(concept.vocab.config.additionalSearchProperties) ~%}
  {%~ if mappingProperties ~%}
    {%~ for property in mappingProperties ~%} {# loop through ConceptProperty objects #}
      {%~ if property.type in concept.vocab.config.additionalSearchProperties ~%}
      <li class="list-group-item px-0 py-1">
        <span class="skosmos-tooltip" data-title="{{ property.type }}">
          <i class="property-hover fa-solid fa-globe"></i>
        </span>
          {%- for propval in property.values -%} {# loop through ConceptMappingPropertyValue objects #}
            {%- if propval.exVocab -%}<span class="redirected-vocab-id prefLabel">{{ propval.exVocab.shortName }}: </span>{%- endif -%}{{ propval.label(request.contentLang) }}
            {%- if propval.label(request.contentLang).lang != request.contentLang -%} ({{ propval.label(request.contentLang).lang }}){%- endif -%}{%- if loop.last == false -%}, {% endif %}
          {% endfor %}
      </li>
      {%~ endif ~%}
    {%~ endfor ~%}
  {%~ endif ~%}
{%~ endif -%}
{%- for property in conceptProperties ~%} {# loop through ConceptProperty objects #}
  {%- if property.type == 'rdf:type' ~%}
  <li class="list-group-item px-0 py-1">
    <span class="skosmos-tooltip" data-title="concept_types">
      <i class="property-hover fa-solid fa-arrows-to-circle"></i>
    </span>
      {% for propval in property.values %}{{ propval.label }}{% if loop.last == false %}, {% endif %}{% endfor %}

  </li>
  {%~ endif -%}
{%~ endfor -%}
{%~ if concept.uri ~%}
  <li class="list-group-item px-0 py-1">
      <span class="uri-icon">URI</span>
      <span class="search-result-uri">{{ concept.uri }}</span>
    <i class="fa-regular fa-copy"></i> <!-- no copy to clipboard functionality yet -->
  </li>
{%~ endif ~%}
  </ul>

</div>
{%~ endfor ~%}
{% endblock %}

{% block content %}
{% if search_results is not null and search_results|length == 0 %}
<p class="no-results">"The search provided no results."</p>
{% endif %}

{% for concept in search_results %} {# loop through the hits #}
<div class="search-result my-3 pt-2 pb-3 border-top">
  {% spaceless %}
    {% if vocab == '' and concept.vocab.id is defined %}
    <span class="fw-bold" data-title="{{ concept.vocabTitle }}">{{ concept.shortName }}: </span>
    {% endif %}
    {% if concept.foundBy %} {# hit has been found through an alt/hidden/another language label #}
      {% if concept.foundByType != 'lang' and concept.foundByType != 'hidden' %}
      <span class="fst-italic">{{ concept.foundBy }}{% if explicit_langcodes or request.queryparam('anylang') and concept.contentLang != '' %} ({{ concept.contentLang }}){% endif %}</span>
      &nbsp;<i class="fa-solid fa-arrow-right"></i>&nbsp;
      {% if concept.notation %}<span>{{ concept.notation }}&nbsp;</span>{% endif %}
      <a class="fw-bold fs-5" href="{% if 'isothes:ConceptGroup' in concept.type %}{{ concept.uri | link_url(concept.vocab,request.lang,'page',concept.contentLang) }}{% else %}{{ concept.uri | link_url(concept.vocab,request.lang,'page',concept.contentLang) }}{% endif %}">{{ concept.label(request.contentLang) }}</a>
      {% if explicit_langcodes or request.queryparam('anylang') %}<span> ({{ concept.label.lang }})</span>{% endif %}
      </span>
    {% else %}
      {% if concept.notation %}<span>{{ concept.notation }}&nbsp;</span>{% endif %}
      <a href="{% if "isothes:ConceptGroup" in concept.type %}{{ concept.uri | link_url(concept.vocab,request.lang,'page',concept.contentLang) }}{% else %}{{ concept.uri | link_url(concept.vocab,request.lang,'page',concept.contentLang) }}{% endif %}">{{ concept.label(request.contentLang) }}</a>
      {% if explicit_langcodes or request.queryparam('anylang') %}<span> ({{ concept.contentLang }})</span>{% endif %}
      {% endif %}
    {% else %}
    {% if concept.notation %}<span class="notation">{{ concept.notation }}&nbsp;</span>{% endif %}
    <a class="prefLabel conceptlabel" href="{% if "isothes:ConceptGroup" in concept.type %}{{ concept.uri | link_url(concept.vocab, request.lang, 'page') }}{% elseif concept.exvocab is defined%}{{ concept.uri | link_url(concept.exvocab,concept.contentLang) }}{% else %}{{ concept.uri | link_url(concept.vocab,request.lang,'page',concept.contentLang) }}{% endif %}">{{ concept.label(request.contentLang) }}</a>{% if explicit_langcodes or request.queryparam('anylang') %}<span class="versal"> ({{ concept.contentLang }})</span>{% endif %}
    {% endif %}
  {% endspaceless %}
  {%- if concept.type == 'skosext:DeprecatedConcept' %}<span class="versal deprecated-alert">"deprecated"</span>{% endif -%}
  {% set conceptProperties = concept.properties %}
  {% for property in conceptProperties %} {# loop through ConceptProperty objects #}
    {% if property.type in PreferredProperties or property.type in concept.vocab.config.additionalSearchProperties  or property.type in concept.vocab.config.hierarchyProperty %}
    <div class="property">
      <span class="property-click skosmos-tooltip-wrapper skosmos-tooltip t-top" data-title="{{ property.description }}">
        {% if property.type == 'skos:broader' %}<i class="property-hover fa-solid fa-hourglass"></i>
          {% elseif property.type == 'skos:narrower' %}<i class="property-hover fa-solid fa-hourglass"></i>
          {% elseif property.type == 'skos:altLabel' %}<i class="property-hover fa-solid fa-arrow-right"></i>
          {% elseif property.type == 'skos:related' %}<i class="property-hover fa-solid fa-cloud"></i>
          {% elseif property.type == 'skosmos:memberOf' %}<i class="property-hover fa-solid fa-layer-group"></i>
          {% elseif property.type == 'skos:note' %}<i class="property-hover fa-solid fa-note-sticky"></i>
          {% else %}<i class="property-hover fa-solid fa-globe"></i>
        {% endif %}
      </span>
      <span class="property-values">
      {% set previous = false %}
      {% for propval in property.values %} {# loop through ConceptPropertyValue objects #}
        {% set outerLast = loop.last %}
          {% if propval.SubMembers %} {# if property is a group concept that has sub properties #}
            {% for sub_member in propval.SubMembers %}
              {% if previous != sub_member.label %}
              <span class="versal value">{{ sub_member.label }}{% if sub_member.lang or explicit_langcodes %} ({{ sub_member.lang }}){% endif %}</span>{% if not outerLast %}<span class="versal">,</span>{% endif %}
              {% endif %}
              {% set previous = sub_member.label %}
            {% endfor %}
            {% else %}
              {% if propval.uri %} {# resources with URI #}
              {% spaceless %}
                {% if propval.label %}
                  {% if propval.exvocab and propval.exvocab != propval.vocab %}{# if the property is located in a another vocabulary #}
                  <span class="versal value">{{ propval.label }}</span>
                  {% else %}
                  <span class="versal value">{{ propval.label(request.contentLang) }}</span>
                  {% endif %}
                  {% if propval.lang and (propval.lang != request.lang) or explicit_langcodes %}<span class="versal"> ({{ propval.lang }})</span>{% endif %}{% if not loop.last %}<span class="versal">,</span>{% endif %}
                {% endif %}
              {% endspaceless %}
              {% else %} {# Literals (no URI), eg. alternative labels as properties #}
                 <span class="versal {% if propval.type == 'skos:altLabel' %}replaced {% endif %} value">{{ propval.label }}{% if not loop.last %},{% endif %}</span>
              {% endif %}
          {% endif %}
      {% endfor %}
      </span>
    </div>
    {% endif %}
  {% endfor %}
  {% set foreignLabels = concept.foreignLabels %}
  {% if foreignLabels %}
    <div class="property">
      <span class="property-click skosmos-tooltip-wrapper skosmos-tooltip t-top" data-title="foreign prefLabel help"><i class="property-hover fa-solid fa-globe"></i></span>
      {% for langstring, labels in foreignLabels %}{% spaceless %}
        {% for label in labels.prefLabel|default([])|merge(labels.altLabel|default([])) %}
        <span class="versal{% if label.type == 'skos:prefLabel' %} prefLabel {% elseif label.type == 'skos:altLabel'%} replaced {% endif %} proplabel">{{ label }}</span>{% if label.lang %}<span class="versal"> ({{ label.lang }})</span>{% endif %}{% if loop.last == false %}<span class="versal">, </span>{% endif %}
        {% endfor %}{% if loop.last == false %}<span class="versal">, </span>{% endif %}
      {% endspaceless %}{% endfor %}
    </div>
  {% endif %}
  {% if concept.vocab.config.additionalSearchProperties %}
    {% set mappingProperties = concept.mappingProperties(concept.vocab.config.additionalSearchProperties) %}
    {% if mappingProperties %}
      {% for property in mappingProperties %} {# loop through ConceptProperty objects #}
        {% if property.type in concept.vocab.config.additionalSearchProperties %}
        <div class="property">
          <span class="property-click skosmos-tooltip-wrapper skosmos-tooltip t-top" data-title="{{ property.type }}">
            <i class="property-hover fa-solid fa-globe"></i>
          </span>
          <div class="property-values">
            {% for propval in property.values %} {# loop through ConceptMappingPropertyValue objects #}
            {% if propval.exVocab %}<span class="redirected-vocab-id prefLabel">{{ propval.exVocab.shortName }}: </span>{% endif %}<span class="versal value">{{ propval.label(request.contentLang) }}</span>{% if propval.label(request.contentLang).lang != request.contentLang %}<span class="versal value"> ({{ propval.label(request.contentLang).lang }})</span>{% endif %}{% if loop.last == false %}<span class="versal">, </span>{% endif %}
            {% endfor %}
          </div>
        </div>
        {% endif %}
      {% endfor %}
    {% endif %}
  {% endif %}
  {% for property in conceptProperties %} {# loop through ConceptProperty objects #}
    {% if property.type == 'rdf:type' %}
    {{ property.type }}
      <div class="property">
        <span class="property-click skosmos-tooltip-wrapper skosmos-tooltip t-top" data-title="concept_types">
          <i class="property-hover fa-solid fa-arrows-to-circle"></i>
        </span>
        <div class="property-values">
          {% for propval in property.values %} {# loop through ConceptPropertyValue objects #}
            <span class="versal value">{{ propval.label }}</span>
          {% endfor %}
        </div>
      </div>
    {% endif %}
  {% endfor %}
  {% if concept.uri %}
    <div><span class="versal uri-input-box">URI {{ concept.uri }}</span></div>
  {% endif %}
</div>
{% endfor %}
{% endblock %}

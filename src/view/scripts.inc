<!-- Skosmos variables passed from the backend to the frontend code-->
<script id="skosmos-global-vars">
const SKOSMOS = {
  "content_lang": "{{ request.contentLang }}",
  "explicitLangCodes": {{ explicit_langcodes ? "true" : "false" }},
  "lang": "{{ request.lang }}",
  "vocab": "{{ request.vocabid }}",
  "waypoint_results": {{ parameters ? parameters.searchLimit : "null" }},
  {%- if request.page == "page" and concept %}
  "prefLabels": [{"lang": "{{ concept.label.lang }}","label": "{{ concept.label }}"}{% for lang in concept.foreignLabels %}{% for literal in lang %}{% if literal.type == "skos:prefLabel" %},{"lang": "{{literal.lang}}", "label": "{{literal.label}}"}{% endif %}{% endfor %}{% endfor %}],
  "uri": "{{ concept.uri }}",
  {% endif %}
  {%- if request.vocab ~%}
  "languageOrder": [{% for lang in request.vocab.config.languageOrder(request.contentLang) %}"{{ lang }}"{% if not loop.last %}, {% endif %}{% endfor %}],
  "showNotation": {{ request.vocab.config.showNotation ? "true" : "false" }},
  "sortByNotation": {% if request.vocab.config.sortByNotation %}"{{ request.vocab.config.sortByNotation }}"{% else %}null{% endif ~%},
  "uriSpace": {% if request.vocab.uriSpace %}"{{ request.vocab.uriSpace }}"{% else %}null{% endif ~%},
  "vocShortName": "{{ request.vocab.config.shortname }}",
  {%- endif %}
  {%- if plugin_params ~%}
  "pluginParameters": {{ plugin_params|raw }},
  {%- endif %}
  {%- if request.plugins.callbacks ~%}
  "pluginCallbacks": [{% for function in request.plugins.callbacks %}{% if not loop.first %}, {% endif %}"{{ function }}"{% endfor %}]
  {%- endif ~%}
}
</script>

<!-- Search result data -->
<script type="application/ld+json">
  {%- if concept -%}
    {{ concept.dumpJsonLd|raw }}
  {%- else -%}
    {}
  {%- endif -%}
</script>

<!-- Plugin JS sources -->
{% for files in request.plugins.pluginsJS %}
  {% for file in files %}<script src="{{ file }}"></script>{% endfor %}
{% endfor %}

<!-- Bootstrap -->
<script src="node_modules/bootstrap/dist/js/bootstrap.bundle.js"></script>

<!-- Vue.js -->
<script src="node_modules/vue/dist/vue.global.js"></script>

<!-- Helper methods shared by Vue.js components -->
<script src="resource/js/partial-page-load.js"></script>
<script src="resource/js/get-concept-url.js"></script>

<!-- Vue.js components -->
<script src="resource/js/vocab-counts.js"></script>
<script src="resource/js/term-counts.js"></script>
<script src="resource/js/concept-mappings.js"></script>
<script src="resource/js/tab-alpha.js"></script>
<script src="resource/js/tab-hierarchy.js"></script>

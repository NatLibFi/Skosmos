<!-- Skosmos variables passed from the backend to the frontend code-->
<script>
  let SKOSMOS = (function () {
    var content_lang = "{{ request.contentLang }}"
    var explicitLangCodes = {{ explicit_langcodes ? 'true' : 'false' }}
    var lang = "{{ request.lang }}"
    var vocab = "{{ request.vocabid }}"
    var waypoint_results = {{ parameters ? parameters.searchLimit : "null" }}

    {% if request.page == 'page' and search_results and search_results|length == 1 %}
    var prefLabels = [{"lang": "{{ search_results|first.label.lang }}","label": "{{ search_results|first.label }}"}{% for lang in search_results|first.foreignLabels %}{% for literal in lang %}{% if literal.type == 'skos:prefLabel' %},{"lang": "{{literal.lang}}", "label": "{{literal.label}}"}{% endif %}{% endfor %}{% endfor %}]
    var uri = "{{ search_results|first.uri }}"
    {% endif %}

    {% if request.vocab %}
    var languageOrder = [{% for lang in request.vocab.config.languageOrder(request.contentLang) %}"{{ lang }}"{% if not loop.last %},{% endif %}{% endfor %}]
    var showNotation = {{ request.vocab.config.showNotation ? "true" : "false" }}
    var sortByNotation = {% if request.vocab.config.sortByNotation %}"{{ request.vocab.config.sortByNotation }}"{% else %}null{% endif ~%}
    var uriSpace = {% if request.vocab.uriSpace %}"{{ request.vocab.uriSpace }}"{% else %}null{% endif ~%}
    var vocShortName = "{{ request.vocab.config.shortname }}"
    {% endif %}

    {% if plugin_params %}
    var pluginParameters = {{ plugin_params|raw }}
    {% endif %}

    {% if request.plugins.callbacks %}
    var pluginCallbacks = [{% for function in request.plugins.callbacks %}{% if not loop.first %}, {% endif %}"{{ function }}"{% endfor %}]
    {% endif %}
    
    return {
       Name: "Skosmos global variables"
    }
  })()
</script>

<!-- Search result data -->
<script type="application/ld+json">
  {%- if search_results and search_results|length == 1 -%}
    {{ search_results|first.dumpJsonLd|raw }}
  {%- else -%}
    {}
  {%- endif -%}
</script>

<!-- Plugin JS sources -->
{% for files in request.plugins.pluginsJS %}
  {% for file in files %}<script src="{{ file }}"></script>{% endfor %}
{% endfor %}

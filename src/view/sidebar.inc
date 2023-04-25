<div class="bg-light" id="sidebar">
{% block sidebar %}
  <div class="sidebar-buttons">
    {% set sidebarHeading = request.page != 'search' ? "Sidebar listing: list and traverse vocabulary contents by a criterion" : "Sidebar listing: vocabulary search options" %}
    <h2 class="visually-hidden">sidebarHeading</h2>
    <ul class="nav nav-tabs-no-style">
    {% if request.page != 'search' %}
      {% set css_class = ['nav-link'] %}
      {% if request.vocab.config.showAlphabeticalIndex %}
      <h3 class="visually-hidden">List vocabulary concepts alphabetically</h3>
      {% if search_results|length != 1 or (term and search_results|length == 1) %}{% set css_class = css_class|merge(['bg-dark','text-light','active']) %}{% endif %}
      <li id="alpha" class="nav-item"><a class="{{ css_class|join(' ') }}" href="{{ request.vocabid }}/{{ request.lang }}/index{% if request.contentLang != request.lang %}?clang={{ request.contentLang }}{% endif %}">{% if vocab.config.showChangeList and vocab.config.groupClassURI %}A-Z{% else %}Alpha-nav{% endif %}</a></li>
      {% endif %}
      {% set css_class = ['nav-link'] %}
      {% set disabledHierarchy = not vocab.config.showHierarchy and not search_results|length == 1 %}
      {%  if disabledHierarchy %}
        {% set css_class = css_class|merge(['skosmos-tooltip-wrapper', 'skosmos-tooltip', 't-bottom']) %}
      {%  endif %}
      <h3 class="visually-hidden">List vocabulary concepts hierarchically</h3>
      {% if search_results|length == 1 and not term %}{% set css_class = css_class|merge(['active']) %}{% endif %}
      <li id="hierarchy{%- if disabledHierarchy %}-disabled{% endif -%}" class="nav-item">
        <a class="{{ css_class|join(' ') }}" href="#" id="hier-trigger"
        {% if disabledHierarchy%} data-title="hierarchy-disabled-help"{% endif %}
        >Hier-nav
        </a>
      </li>
      {% set css_class = ['nav-link'] %}
      {% if vocab.config.groupClassURI %}
      <h3 class="visually-hidden">List vocabulary concepts and groupings hierarchically</h3>
      <li id="groups" class="nav-item"><a class="{{ css_class|join(' ') }}" href="{{ request.vocabid }}/{{ request.lang }}/groups{% if request.contentLang != request.lang %}?clang={{ request.contentLang }}{% endif %}">Group-nav</a></li>
      {% endif %}
      {% if vocab.config.showChangeList %}
      {% if active_tab == 'new' %}{% set css_class = css_class|merge(['active']) %}{%  endif %}
      {% if vocab.config.showDeprecatedChanges %}
      <h3 class="visually-hidden">List vocabulary concepts by newest additions including removed</h3>
      <li id="changes" class="nav-item"><a class="{{ css_class|join(' ') }}" href="{{ request.vocabid }}/{{ request.lang }}/new{% if request.contentLang != request.lang %}?clang={{ request.contentLang }}{% endif %}">Changes-and-deprecations-nav</a></li>
      {% else %}
      <h3 class="visually-hidden">List vocabulary concepts by newest additions</h3>
      {% set css_class = ['nav-link'] %}
      {% if active_tab == 'new' %}{% set css_class = css_class|merge(['active']) %}{%  endif %}
      <li id="changes" class="nav-item"><a class="{{ css_class|join(' ') }}" href="{{ request.vocabid }}/{{ request.lang }}/new{% if request.contentLang != request.lang %}?clang={{ request.contentLang }}{% endif %}">Changes-nav</a></li>
      {% endif %}
      {% endif %}
      {% else %}
      <h3 class="visually-hidden">Search options tab</h3>
      <li id="limit" class="nav-item"><p>Search options</p></li>
    {% endif %}
    </ul>
  </div>
  {% if letters %}
  <div class="tab-pane-active" id="tab-alpha" role="tabpanel">
    <h4 class="visually-hidden">Choose alphabetical listing letter</h4>
    <ul class="pagination">
    {% for letter in letters %} {# looping through all letters #}
      <li class="page-item{% if letter == request.letter %} active{% endif %}">
        <a class="page-link" href="{{ request.vocabid }}/{{ request.lang }}/index/{{ letter }}
          {%- if request.contentLang != request.lang %}?clang={{ request.contentLang }}{% endif -%}
        "><span class="visually-hidden">{{ letter | lower }}</span><span aria-hidden=true>{{ letter }}</span></a>
      </li>
    {% endfor %}
    </ul>
  </div>
  {% endif %}

  {% if request.page != 'search' %}
  <h4 class="visually-hidden">Listing vocabulary concepts alphabetically</h4>
  {% endif %}
  <div class="sidebar-grey {% if alpha_results %} sidebar-grey-alpha{% elseif search_results|length == 1 and not term %} concept-hierarchy{% endif %}">
    <div class="container" id="alphabetical-menu">
      {% if alpha_results %}
        {% set previous = '' %}
        <ul id="alpha-list" class="list-group side-navi">
          {% for key,value in alpha_results %}
          {% if all_letters == true %}
            {% set alpha = (value.altLabel ?? value.prefLabel)|upper|first %}
            {% if alpha != previous %}
            <li class="list-group-item text-decoration-none alphabet-header" name="{{ alpha }}">
              <h5 class="visually-hidden">{{ alpha | lower }}</h5>
              <h5 aria-hidden=true>{{ alpha }}</h5>
            </li>
            {% set previous = alpha %}
            {% endif %}
          {% endif %}
          <li class="list-group-item text-decoration-none">
          {% if value.altLabel %}
          {% set prefLabelAltogether = value.prefLabel ~ (value.qualifier ? ' (' ~ value.qualifier ~ ')' : "") %}
          <span aria-hidden=true><span class="replaced">{{ value.altLabel }}</span>	&rarr;</span>
          <a href="{{ value.uri | link_url(vocab,request.lang,'page',request.contentLang) }}"><span class="visually-hidden">{{ value.altLabel}} Prefer using {{ prefLabelAltogether}}</span><span aria-hidden=true>{{prefLabelAltogether}}</span></a>
          {% else %}
          <a href="{{ value.uri | link_url(vocab,request.lang,'page',request.contentLang) }}">{{ value.prefLabel }}{% if value.qualifier %} ({{ value.qualifier }}){% endif %}</a>
          {% endif %}
         </li>
          {% endfor %}
        </ul>
      {% endif %}
    </div>
    {% if request.page == 'search' %}
    <h4 class="visually-hidden">Limit search</h4>
    <form class="search-options">
      {% set conceptSchemes = vocab.conceptSchemes %}
      {% if conceptSchemes|length > 1 %}
      <div class="input-group">
        <label for="scheme-limit" class="versal form-label">By scheme</label>
        <select id="scheme-limit" value="{{ limit-scheme }}" class="form-control multiselect" multiple="multiple">
          {% for uri,scheme in conceptSchemes %}
            <option value="{{ uri }}">{% if scheme.prefLabel %}{{ scheme.prefLabel }}{% elseif scheme.label %}{{ scheme.label }}{% elseif scheme.title %}{{ scheme.title }}{% else %}{{ uri }}{% endif %}</option>
          {% endfor %}
        </select>
      </div>
      {% endif %}
      {% if types|length > 1 %}
      <div class="input-group">
        <label for="type-limit" class="versal form-label">By type</label>
        <select id="type-limit" value="{{ limit-type }}" class="form-control multiselect" multiple="multiple">
          {% for uri,type in types %}
            <option value="{{ uri }}">{% if type.label %}{{ type.label }}{% else %}{{ uri }}{% endif %}</option>
          {% endfor %}
        </select>
      </div>
      {% endif %}
      {% if vocab.config.groupClassURI %}
      <div class="input-group">
        <label for="group-limit" class="versal form-label">By group</label>
        <select id="group-limit" value="{{ limit-group }}" class="form-control multiselect">
          <option></option>
          {% for uri,group in group_index %}
            <option value="{{ group.uri }}">{{ group.prefLabel }}</option>
          {% endfor %}
        </select>
      </div>
      {% endif %}
      <div class="input-group">
        <label for="parent-limit" class="versal form-label">By parent</label><input type="text" id="parent-limit" class="form-control" value="{{ limit_parent }}">
      </div>
      <button type="submit" class="btn btn-default">Limit search</button>
    </form>
    {% endif %}
  </div>
{% endblock %}
</div>

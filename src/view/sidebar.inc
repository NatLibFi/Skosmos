<div class="col-md-4" id="sidebar-col">
  <div id="sidebar">
  {% block sidebar %}
    <div class="sidebar-buttons">
      <h2 class="visually-hidden">{{'Sidebar listing: list and traverse vocabulary contents by a criterion' | trans}}</h2>

      <ul class="nav nav-tabs-no-style nav-justified" id="sidebar-tabs" role="tablist">
        {% for view in vocab.config.sidebarViews %}
          {% if view == 'alphabetical' %} {% set view_trans_text = ['Alpha-nav', 'List vocabulary concepts alphabetically'] %}
          {% elseif view == 'hierarchy' %} {% set view_trans_text = ['Hier-nav', 'List vocabulary concepts hierarchically'] %}
          {% elseif view == 'groups' %} {% set view_trans_text = ['Group-nav', 'List vocabulary concepts and groupings hierarchically'] %}
          {% else %} {% set view_trans_text = vocab.config.showDeprecatedChanges ? ['Changes-and-deprecations-nav', 'List vocabulary concepts by newest additions including removed'] : ['Changes-nav', 'List vocabulary concepts by newest additions'] %}{% endif %}
          <h3 class="visually-hidden">{{ view_trans_text[1] | trans }}</h3>
          {# active_class is used to set initial active tab and disabled_class is used to disable hierarchy tab #}
          {# On vocab page active tab is set to defaultSidebarView, on concept page active tab is set to defaultConceptSidebarView #}
          {% set active_class = (request.page == 'vocab' and view == vocab.config.defaultSidebarView) or (request.page == 'page' and view == vocab.config.defaultConceptSidebarView) %}
          {# Hierarchy tab is disabled on vocab page if showTopConcepts is set to false #}
          {% set disabled_class = request.page == 'vocab' and view == 'hierarchy' and not (vocab.config.showTopConcepts) %}
          <li id="{{ view }}" class="nav-item">
            <a class="nav-link{% if active_class %} active{% elseif disabled_class %} disabled{% endif %}"
              {% if disabled_class %}data-title="{{ 'hierarchy-disabled-help' | trans }}"{% endif %} 
              role="tab" data-bs-toggle="tab" href="#tab-{{ view }}" aria-controls="tab-{{ view }}">
              {{ view_trans_text[0] | trans }}
            </a>
          </li>
        {% endfor %}
      </ul>

    </div>

    <div class="tab-content">
      {% for view in vocab.config.sidebarViews %}
        {# active_class is used to set initial active tab #}
        {# On vocab page active tab is set to defaultSidebarView, on concept page active tab is set to defaultConceptSidebarView #}
        {% set active_class = (request.page == 'vocab' and view == vocab.config.defaultSidebarView) or (request.page == 'page' and view == vocab.config.defaultConceptSidebarView) %}
        <div class="tab-pane{% if active_class %} active{% endif %}" id="tab-{{ view }}" role="tabpanel"></div>
      {% endfor %}
    </div>

  {% endblock %}
  </div>
</div>

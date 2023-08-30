<div class="col-md-4">
  <div class="bg-light" id="sidebar">
  {% block sidebar %}
    <div class="sidebar-buttons">
      <h2 class="visually-hidden">Sidebar listing: list and traverse vocabulary contents by a criterion</h2> {# Should be changed for translation #}

      <ul class="nav nav-tabs-no-style nav-justified" id="sidebar-tabs" role="tablist">
        {% for view in vocab.config.sidebarViews %}
          <h3 class="visually-hidden">{{ view }}</h3> {# Should be changed for translation #}
          {# active_class is used to set initial active tab and disabled_class is used to disable hierarchy tab #}
          {# On vocab page active tab is set to defaultSidebarView, on concept page active tab is set to defaultConceptSidebarView #}
          {% set active_class = (request.page == 'vocab' and view == vocab.config.defaultSidebarView) or (request.page == 'page' and view == vocab.config.defaultConceptSidebarView) %}
          {# Hierarchy tab is disabled on vocab page if showTopConcepts is set to false #}
          {% set disabled_class = request.page == 'vocab' and view == 'hierarchy' and not (vocab.config.showTopConcepts) %}
          <li id="{{ view }}" class="nav-item">
            <a class="nav-link{% if active_class %} active{% elseif disabled_class %} disabled{% endif %}" role="tab" data-bs-toggle="tab" href="#tab-{{ view }}" aria-controls="tab-{{ view }}">
              {{ view }} {# Should be changed for translation #}
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
        <div class="tab-pane{% if active_class %} active {% endif %}" id="tab-{{ view }}" role="tabpanel">
          {{ view }} {# should be removed #}
        </div>
      {% endfor %}
    </div>

  {% endblock %}
  </div>
</div>

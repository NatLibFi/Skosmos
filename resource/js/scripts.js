/* exported getUrlParams, readCookie, createCookie, getUrlParams, debounce, updateContent, updateTopbarLang, updateTitle, updateSidebar, setLangCookie, loadLimitations, loadPage, hideCrumbs, shortenProperties, countAndSetOffset, combineStatistics, loadLimitedResults, naturalCompare, escapeHtml */

/* 
 * Creates a cookie value and stores it for the user. Takes the given
 * value label, the value itself and the number of days until expires.
 * The function is used when storing data about concept views, hidden
 * properties and bookmarks. 
 * @param {String} name
 * @param {String} value 
 * @param {Integer} days 
 */
function createCookie(name,value,days) {
  var expires = '';
  if (days) {
    var date = new Date();
    date.setTime(date.getTime() + (days*24*60*60*1000));
    expires = "; expires=" + date.toGMTString();
  }
  document.cookie = name + "=" + value + expires + "; path=/";
}

function readCookie(name) {
  var nameEQ = name + "=";
  var ca = document.cookie.split(';');
  for(var i=0;i < ca.length;i++) {
    var c = ca[i];
    while (c.charAt(0) === ' ') { c = c.substring(1,c.length); }
    if (c.indexOf(nameEQ) === 0) { return c.substring(nameEQ.length,c.length); }
  }
  return null;
}

function getUrlParams() {
  var params = {};
  window.location.search.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(str,key,value) {
    params[key] = value;
  });
  return params;
}

// Debounce function from underscore.js
function debounce(func, wait, immediate) {
  var timeout;
  return function() {
    var context = this, args = arguments;
    var later = function() {
      timeout = null;
      if (!immediate) func.apply(context, args);
    };
    var callNow = immediate && !timeout;
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
    if (callNow) func.apply(context, args);
  };
}

/*
 * Ajax query queue that keeps track of ongoing queries 
 * so they can be cancelled if a another event is triggered.
 */
$.ajaxQ = (function(){
  var id = 0, Q = {};

  $(document).ajaxSend(function(e, jqx){
    jqx._id = ++id;
    Q[jqx._id] = jqx;
  });
  $(document).ajaxComplete(function(e, jqx){
    delete Q[jqx._id];
  });

  return {
    abortAll: function(){
      var r = [];
      $.each(Q, function(i, jqx){
        r.push(jqx._id);
        jqx.abort();
      });
      return r;
    }
  };

})();

function updateContent(data) {
  $('.content').empty();
  var response = $('.content', data).html();
  $('.content').append(response);
}

function updateJsonLD(data) {
    var $jsonld = $('script[type="application/ld+json"]');
    var $newJsonLD = $(data).filter('script[type="application/ld+json"]');
    if ($jsonld[0]) {
        $jsonld[0].innerHTML = "{}";
        if ($newJsonLD[0]) {
            $jsonld[0].innerHTML = $newJsonLD[0].innerHTML;
        }
    }
    else if ($newJsonLD[0]) {
        // insert after the first JS script as it is in the template
        var elemBefore = $('script[type="text/javascript"]')[0];
        if (elemBefore) {
            $newJsonLD.insertAfter(elemBefore);
        }
    }
}

function updateTopbarLang(data) {
  $('#language').empty();
  var langBut = $('#language', data).html();
  $('#language').append(langBut);
}

function updateTitle(data) {
  var title = $(data).filter('title').text();
  document.title = title;
}

function updateSidebar(data) {
  $('#sidebar').empty();
  var response = $('#sidebar', data).html();
  $('#sidebar').append(response);
}

// sets the language cookie for 365 days
function setLangCookie(lang) {
  createCookie('SKOSMOS_LANGUAGE', lang, 365);
}

function clearResultsAndAddSpinner() {
  var $loading = $("<div class='search-result'><p>" + loading_text + "&hellip;<span class='spinner'/></p></div>"); 
  $('.search-result-listing').empty().append($loading);
}
  
function loadLimitations() {
  var groupLimit = $('#group-limit').val();
  var parentLimit = $('#parent-limit').attr('data-uri');
  var typeLimit = $('#type-limit').val() ? $('#type-limit').val().join('+') : $('#type-limit').val();
  var schemeLimit = $('#scheme-limit').val() ? $('#scheme-limit').val().join('+') : $('#scheme-limit').val();
  if (schemeLimit && schemeLimit[0] === '+') { // filtering the empty selection out of the search string
    schemeLimit = schemeLimit.substring(1);
  }
  if (typeLimit && typeLimit[0] === '+') { // filtering the empty selection out of the search string
    typeLimit = typeLimit.substring(1);
  }

  return $.param({'type' : typeLimit, 'group' : groupLimit, 'parent': parentLimit, 'scheme': schemeLimit});
}

function loadLimitedResults(parameters) {
  clearResultsAndAddSpinner();
  $.ajax({
    data: parameters,
    success : function(data) {
      var response = $('.search-result-listing', data).html();
      if (window.history.pushState) { window.history.pushState({url: this.url}, '', this.url); }
      $('.search-result-listing').append(response);
      $('.spinner').parent().parent().detach();
      updateTitle(data);
    }
  });
}

function loadPage(targetUrl) {
  $.ajax({
    url : targetUrl,
    success : function(data) {
      if (targetUrl.indexOf('index') !== -1 || targetUrl.indexOf('groups') !== -1) {
        updateSidebar(data);
      } else {
        $('.activated-concept').removeClass('activated-concept');
        $('.jstree-clicked').removeClass('jstree-clicked'); 
        updateContent(data);
        $('a[href="' + $('.uri-input-box').text() + '"]').addClass('jstree-clicked');
      }
      updateTitle(data);
      updateTopbarLang(data);
      // take the content language buttons from the response
      $('.header-float .dropdown-menu').empty().append($('.header-float .dropdown-menu', data).html());
      makeCallbacks(data);
    }
  });
}

// if there are multiple breadcrumb paths hide those and generate a button for displaying those
function hideCrumbs() {
  var $crumbs = $('.crumb-path');
  if ($crumbs.length > 4) {
    for (var i = 4; i < $crumbs.length; i++) {
      $($crumbs[i]).addClass('hidden-path');
    }
    if ($('.restore-breadcrumbs').length === 0) {
      $($crumbs[0]).after('<a class="versal restore-breadcrumbs" href="#">[' + expand.replace('#',($crumbs.length)) + ']</a>');
    }
  }
}

// Shortens the properties that don't fit on one row on the search result view.
function shortenProperties() {
  var $properties = $('.property-values');
  for (var i = 0; i < $properties.length; i++) {
    var $property = $($properties[i]);
    if ($property.height() > 24) {
      $property.addClass('shortened-property');
      var count = $property.children('.value').length;
      var uri = $property.parent().siblings('a.prefLabel')[0].href;
      var shortened = '<a href="' + uri +'" class="versal shortened-symbol" style="">... (' + count +')</a>';
      $property.parent().append(shortened);
    }
  }
}

/**
 * Combines the different properties into an object with the language codes as 
 * keys and an another array of property counts as the value.
 * @return object
 */
function combineStatistics(input) {
  var combined = {};
  for (var i = 0; i < input.length; i++) {
    var langdata = input[i];
    combined[langdata.literal] = [langdata.literal];
    for (var j = 0; j < langdata.properties.length; j++) {
      combined[langdata.literal].push(langdata.properties[j].labels);
    }
  }
  return combined;
}

// Calculates and sets how many vertical pixels the sidebar height should be at the current scroll position.
function countAndSetOffset() {
  /* calculates the sidebars content maximum height and sets it as an inline style.
     the .css() can't set important so using .attr() instead. */
  $('.sidebar-grey').attr('style', function() {
    var pixels = $('.nav-tabs').height() + 2; // the 2 pixels are for the borders
    if ($('#sidebar > .pagination').is(':visible')) { pixels += $('.pagination').height(); }
    if ($('.changes-navi').is(':visible')) { pixels += $('.changes-navi').height(); }
    return 'height: calc(100% - ' + pixels + 'px) !important';
  });
  if ($('#sidebar').length && !$('#sidebar').hasClass('fixed')) {
    var yOffset = window.innerHeight - ( $('#sidebar').offset().top - window.pageYOffset);
    $('#sidebar').css('height', yOffset);
  }
}

// Natural sort from: http://stackoverflow.com/a/15479354/3894569
function naturalCompare(a, b) {
  var ax = [], bx = [];

  a.replace(/(\d+)|(\D+)/g, function(_, $1, $2) { ax.push([$1 || Infinity, $2 || ""]); });
  b.replace(/(\d+)|(\D+)/g, function(_, $1, $2) { bx.push([$1 || Infinity, $2 || ""]); });

  while(ax.length && bx.length) {
    var an = ax.shift();
    var bn = bx.shift();
    var nn = (an[0] - bn[0]) || an[1].localeCompare(bn[1], lang);
    if(nn) return nn;
  }

  return ax.length - bx.length;
}

function makeCallbacks(data, pageType) {
  if (!pageType) {
    pageType = 'page';
  }

  var variables = data ? data.substring(data.indexOf('var uri ='), data.indexOf('var uriSpace =')).split('\n') : '';
  var newUri = data ? variables[0].substring(variables[0].indexOf('"')+1, variables[0].indexOf(';')-1) : window.uri;
  var newPrefs = data ? JSON.parse(variables[1].substring(variables[1].indexOf('['), variables[1].lastIndexOf(']')+1)) : window.prefLabels;
  var embeddedJsonLd  = $('script[type="application/ld+json"]')[0] ? JSON.parse($('script[type="application/ld+json"]')[0].innerHTML) : {};

  var params = {'uri': newUri, 'prefLabels': newPrefs, 'page': pageType, "json-ld": embeddedJsonLd};

  if (window.pluginCallbacks) {
    for (var i in window.pluginCallbacks) {
      var fname = window.pluginCallbacks[i];
      var callback = window[fname];
      if (typeof callback === 'function') {
        callback(params);
      }
    }
  }
}

function escapeHtml(string) {
  var entityMap = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': '&quot;',
    "'": '&#39;',
    "/": '&#x2F;'
  };
  return String(string).replace(/[&<>"'\/]/g, function (s) {
    return entityMap[s];
  });
}

function renderPropertyMappingValues(groupedByType) {
  var propertyMappingValues = [];
  var source = document.getElementById("property-mapping-values-template").innerHTML;
  var template = Handlebars.compile(source);
  var context = {
    property: {
      uri: conceptMappingPropertyValue.uri,
      label: conceptMappingPropertyValue.prefLabel,
    }
  };
  propertyMappingValues.push({'body': template(context)});
  return propertyMappingValues;
}

function renderPropertyMappings(concept, contentLang, properties) {
  var source = document.getElementById("property-mappings-template").innerHTML;
  // handlebarjs helper functions
  Handlebars.registerHelper('ifDeprecated', function(conceptType, value, opts) {
    if(conceptType == value) {
      return opts.fn(this);
    }
    return opts.inverse(this);
  });
  Handlebars.registerHelper('toUpperCase', function(str) {
    if (str === undefined) {
      return '';
    }
    return str.toUpperCase();
  });
  Handlebars.registerHelper('ifNotInDescription', function(type, description, opts) {
    if (type === undefined) {
      return opts.inverse(this);
    }
    if (description === undefined) {
      return opts.inverse(this);
    }
    if (description.indexOf(type) > 0 && description.indexOf('_help') > 0) {
      return opts.inverse(this);
    }
    return opts.fn(this);
  });
  Handlebars.registerHelper('ifDifferentLabelLang', function(labelLang, explicitLangCodes, opts) {
    if (labelLang !== undefined && labelLang !== '' && labelLang !== null) {
      if (explicitLangCodes !== undefined && typeof explicitLangCodes === "boolean") {
        return opts.fn(explicitLangCodes);
      }
      if (labelLang !== contentLang) {
        return opts.fn(this);
      }
    }
    return opts.inverse(this);
  });

  var template = Handlebars.compile(source);

  var context = {
    concept: concept,
    properties: properties
  };

  return template(context);
}

/**
 * Load mapping properties, via the JSKOS REST endpoint. Then, render the concept mapping properties template. This
 * template is comprised of another template, for concept mapping property values.
 *
 * @param concept dictionary/object populated with data from the Concept object
 * @param contentLang language to display content
 * @param $htmlElement HTML (a div) parent object (initially hidden)
 * @param conceptData concept page data returned via ajax, passed to makeCallback only
 */
function loadMappingProperties(concept, contentLang, $htmlElement, conceptData) {
  // display with the spinner
  $htmlElement
    .removeClass('hidden')
    .append('<div class="spinner row"></div>');
  $.ajax({
    url: rest_base_url + vocab + '/mappings',
    data: $.param({'uri': concept.uri, lang: contentLang}),
    success: function(data) {

      // The JSKOS REST mapping properties call will have added more resources into the graph. The graph
      // is returned alongside the mapping properties, so now we just need to replace it on the UI.
      $('script[type="application/ld+json"]')[0].innerHTML = data.graph;

      var conceptProperties = [];
      for (var i = 0; i < data.mappings.length; i++) {
        /**
         * @var conceptMappingPropertyValue JSKOS transformed ConceptMappingPropertyValue
         */
        var conceptMappingPropertyValue = data.mappings[i];
        var found = false;
        var conceptProperty = null;
        for (var j = 0; j < conceptProperties.length; j++) {
          conceptProperty = conceptProperties[j];
          if (conceptProperty.type === conceptMappingPropertyValue.type[0]) {
            conceptProperty.values.push(conceptMappingPropertyValue);
            found = true;
            break;
          }
        }

        if (!found) {
          conceptProperty = {
            'type': conceptMappingPropertyValue.type[0],
            'label': conceptMappingPropertyValue.typeLabel,
            'notation': conceptMappingPropertyValue.notation,
            'description': conceptMappingPropertyValue.description,
            'values': []
          };
          conceptProperty.values.push(conceptMappingPropertyValue);
          conceptProperties.push(conceptProperty);
        }
      }

      if (conceptProperties.length > 0) {
        var template = renderPropertyMappings(concept, contentLang, conceptProperties);

        $htmlElement.empty();
        $htmlElement.append(template);
      } else {
        // No concept properties found
        $htmlElement.empty();
        $htmlElement.addClass("hidden");
      }


    },
    error: function(data) {
      console.log("Error retrieving mapping properties for [" + $htmlElement.data('concept-uri') + "]: " + data.responseText);
    },
    complete: function() {
      makeCallbacks(conceptData);
    }
  });
}

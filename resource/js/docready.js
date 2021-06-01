$(function() { // DOCUMENT READY

  var spinner = '<div class="loading-spinner"><span class="spinner-text">'+ loading_text + '</span><span class="spinner"></span></div>';
  var searchString = ''; // stores the search field's value before autocomplete selection changes it
  var selectedVocabs = [];
  var vocabId;
  var vocabSelectionString = getUrlParams().vocabs ? getUrlParams().vocabs.replace(/\+/g,' ') : readCookie('SKOSMOS_SELECTED');
  if ($('.global-content').length && getUrlParams().vocabs === '') {
    vocabSelectionString = null;
  }
  $('#selected-vocabs').val(vocabSelectionString);
  var clang = content_lang !== '' ? content_lang : lang;

  shortenProperties();

  // kills the autocomplete after a form submit so we won't have to wait for the ajax to complete.
  $('.navbar-form').on('submit',
    function() {
      $('#search-field').typeahead('destroy');
      $.ajaxQ.abortAll();
    }
  );

  $('#skiptocontent').on('click', function(e) {
    // fixes an issue with screen reader not regaining true focus after Ctrl + Home
    if (!e.looped) {
      // loop focus in order to restore normal functionality
      e.preventDefault();
      e.stopImmediatePropagation();
      document.getElementById("footer").focus({preventScroll: true});
      setTimeout(function() {
        $(e.target).trigger({type: 'click', looped: true});
      }, 100);
      return false;
    }

    document.getElementById(e.target.hash.slice(1)).focus({preventScroll: true});
  });

  /*
   * Moving the sidenav scrollbar towards the current concept. Aiming the current
   * concept at vertical center of the container. Each concept needs 18px height.
   */
  $(document).ajaxComplete(function(event, xhr, settings) {
    $('.property-click').qtip({
      position: { my: 'bottom center', at: 'top center' },
      style: { classes: 'qtip-tipsy qtip-skosmos' }
    });
    $('#hierarchy-disabled > #hier-trigger').qtip(qtip_skosmos_hierarchy);
    if (settings.url.indexOf('groups') !== -1 || settings.url.indexOf('index') !== -1) {
      $('.sidebar-grey').removeClass(function(index, classes) {
        var elementClasses = classes.split(' ');
        var removeThese = [];

        $.each(elementClasses, function() {
          if(this.match(/jstree*/)) { removeThese.push(this); }
        });
        return removeThese.join(' ');
      });
      var scrollCB = ($('#changes.active').length === 1) ? changeListWaypointCallback : alphaWaypointCallback;
      var element = document.getElementsByClassName('sidebar-grey')[0];
      $('.sidebar-grey').scroll(function () {
        if (element.scrollHeight - element.scrollTop - 300 <= element.clientHeight) {
          scrollCB();
        }
      })
    }

    $('.reified-property-value').each(function() {
      $(this).qtip({
        content: $(this).next('.reified-tooltip'),
        position: { my: 'top left', at: 'top left' },
        style: { classes: 'qtip-skosmos' },
        show: { delay: 100 },
        hide: { fixed: true, delay: 400 }
      });
    });

    var active_tab = $('li.active').attr('id');
    if (active_tab == 'groups') {
      $('#sidebar > h4').remove();
      $('.sidebar-grey').before('<h4 class="sr-only">' + sr_only_translations.groups_listing + '</h4>');
    } else if (active_tab == 'hierarchy') {
      $('#sidebar > h4').remove();
      $('.sidebar-grey').before('<h4 class="sr-only">' + sr_only_translations.hierarchy_listing + '</h4>');
    }

    countAndSetOffset();

    hideCrumbs();
    hidePropertyValues();
  });

  // if the hierarchy tab is active filling the jstree with data
  if ($('#hierarchy').hasClass('active')) {
    $('#sidebar > h4').remove();
    invokeParentTree(getTreeConfiguration());
    $('.sidebar-grey').before('<h4 class="sr-only">' + sr_only_translations.hierarchical_listing + '</h4>');
  }
  if ($('#groups').hasClass('active')) {
    $('#sidebar > h4').remove();
    invokeGroupTree();
    $('.sidebar-grey').before('<h4 class="sr-only">' + sr_only_translations.groups_listing + '</h4>');
  }

  countAndSetOffset();

  function initHierarchyQtip() {
      if (!$('#hierarchy').length) {
          $('#hierarchy-disabled').attr('id', 'hierarchy');
          $('#hier-trigger').attr('title', '');
          $('#hier-trigger').qtip('disable');
      }
  }

  if($('.search-result-listing').length === 0) { // Disabled if on the search results page.
    $(document).on('click','.prefLabel', makeSelection);

  }

  $(document).on('click','.uri-input-box', makeSelection);

  $(document).on('click', 'button.copy-clipboard', copyToClipboard);

  var sidebarResizer = debounce(function() {
    countAndSetOffset();
  }, 40);

  // Event handler for mutilating the sidebar css when the user scrolls the headerbar out of the view.
  if ($('.sidebar-grey').length > 0) {
    $(window).on('scroll', sidebarResizer);

    var sidebarFixed = false;
    // the handler listens to headerbars position so it works correctly after the sidebar is hidden/shown again.
    $('.headerbar').waypoint(function(direction) {
      if (!sidebarFixed && direction === 'down') {
        sidebarFixed = true;
        $('#sidebar').addClass('fixed');
      } else {
        sidebarFixed = false;
        $('#sidebar').removeClass('fixed');
      }
    }, { offset: -60 }); // when the headerbars bottom margin is at the top of the screen
  }

  // event handling the breadcrumb expansion
  $(document).on('click', '.expand-crumbs',
      function(event){
        var clicked = $(event.currentTarget);
        clicked.parent().find('.hidden-breadcrumb').removeClass('hidden-breadcrumb').addClass('bread-crumb');
        clicked.next().remove(); // Removing and the following > symbol.
        clicked.remove(); // and the clickable '...' element
        return false;
      }
  );

  // event handling restoring the hidden breadcrumb paths
  $(document).on('click', '.restore-breadcrumbs',
      function(){
        $(this).remove();
        $('.hidden-path').removeClass('hidden-path');
        return false;
      }
  );

  hideCrumbs();

  // event handling restoring the hidden breadcrumb paths
  $(document).on('click', '.restore-propvals',
      function(){
        $(this).parent().parent().addClass('expand-propvals');
        $(this).remove();
        return false;
      }
  );

  hidePropertyValues();

  // ajaxing the concept count and the preflabel counts on the vocabulary front page
  if ($('#vocab-info').length && $('#statistics').length) {
    // adding the spinners
    $('#counts tr:nth-of-type(1)').after('<tr><td><span class="spinner"></span></td></td></tr>');
    $('#statistics tr:nth-of-type(1)').after('<tr><td><span class="spinner"></span></td></td></tr>');
    $.ajax({
      url : rest_base_url + vocab + '/vocabularyStatistics',
      req_kind: $.ajaxQ.requestKind.GLOBAL,
      data: $.param({'lang' : content_lang}),
      success : function(data) {
        var $spinner = $('#counts tr:nth-of-type(2)');
        var typeStats = '<tr><td class="count-type versal">' + data.concepts.label + '</td><td class="versal">' + data.concepts.count +'</td></tr>';
        for (var i in data.subTypes) {
          var sub = data.subTypes[i];
          var label = sub.label ? sub.label : sub.type;
          typeStats += '<tr><td class="count-type versal">&nbsp;&bull;&nbsp;' + label + '</td><td class="versal">' + sub.count + '</td></tr>';
        }
        if (data.conceptGroups) {
          typeStats += '<tr><td class="count-type versal">' + data.conceptGroups.label + '</td><td class="versal">' + data.conceptGroups.count +'</td></tr>';
        }

        $spinner.after(typeStats);
        $spinner.detach();
      }
    });

    $.ajax({
      url : rest_base_url + vocab + '/labelStatistics',
      req_kind: $.ajaxQ.requestKind.GLOBAL,
      data: $.param({'lang' : lang}),
      success : function(data) {
        $('#statistics tr:nth-of-type(2)').detach(); // removing the spinner
        var stats = '';
        var combined = combineStatistics(data.languages);
        $.each(combined, function(lang, values) { // each array contains one language
          stats += '<tr>';
          for (var i = 0; i < values.length; i++) { // the array values are placed into tds
            stats += '<td class="versal">' + values[i] + '</td>';
          }
          stats += '</tr>';
        });
        $('#statistics tr:nth-of-type(1)').after(stats);
      }
    });
  }

  $(window).on("popstate", function(e) {
    if (window.history.state) { // avoids reloading the page on the safari initial pageload popstate
        if (e.originalEvent.state && e.originalEvent.state.url) {
          loadPage(e.originalEvent.state.url);
        } else {
          loadPage(document.URL);
        }
    }
  });

  // the gear spinner shown when ajax loading a concept takes a long time
  var $delayedSpinner = $("<p class='concept-spinner center-block'>" + loading_text + "&hellip;</p>");

  // adds a delay before showing the spinner configured above
  function delaySpinner() {
    return setTimeout(function() { $('.concept-spinner').show() }, 500);
  }

  function ajaxConceptMapping(data) {
    // ajaxing the concept mapping properties on the concept page
    var $conceptAppendix = $('.concept-appendix');
    if ($conceptAppendix.length) {
      var concept = {
        uri: $conceptAppendix.data('concept-uri'),
        type: $conceptAppendix.data('concept-type')
      };

      // Defined in scripts.js. Will load the mapping properties via Ajax request to JSKOS REST service, and render them.
      loadMappingProperties(concept, lang, clang, $conceptAppendix, data);
    } else {
      makeCallbacks(data);
    }
  }

  // event handler for clicking the hierarchy concepts
  $(document).on('click', '.concept-hierarchy a',
      function(event) {
        $.ajaxQ.abortContentQueries();
        var targetUrl = event.target.href;
        $('#hier-trigger').attr('href', targetUrl);
        var $content = $('.content').empty().append($delayedSpinner.hide());
        var loading = delaySpinner();
        $.ajax({
            url : targetUrl,
            req_kind: $.ajaxQ.requestKind.CONTENT,

            complete: function() { clearTimeout(loading); },
            success : function(data) {
              $content.empty();
              var response = $('.content', data).html();
              if (window.history.pushState) { window.history.pushState({url: targetUrl}, '', targetUrl); }
              $content.append(response);

              updateJsonLD(data);
              updateTitle(data);
              updateTopbarLang(data);
              ajaxConceptMapping(data);
              // take the content language buttons from the response
              $('.header-float .dropdown-menu').empty().append($('.header-float .dropdown-menu', data).html());
            }
        });
        return false;
      }
  );

  // event handler for clicking the alphabetical/group index concepts
  $(document).on('click', '.side-navi a',
      function(event) {
        $.ajaxQ.abortContentQueries();
        $('.activated-concept').removeClass('activated-concept');
        $(this).addClass('activated-concept');
        var $content = $('.content').empty().append($delayedSpinner.hide());
        var loading = delaySpinner();
        $.ajax({
            url : event.currentTarget.href,
            req_kind: $.ajaxQ.requestKind.CONTENT,
            complete: function() { clearTimeout(loading); },
            success : function(data, responseCode, jqxhr) {
              if (window.history.pushState) { window.history.pushState({}, null, event.currentTarget.href); }
              $content.empty().append($('.content', data).html());
              initHierarchyQtip();
              $('#hier-trigger').attr('href', event.currentTarget.href);
              updateJsonLD(data);
              updateTitle(data);
              updateTopbarLang(data);
              ajaxConceptMapping(data);
              // take the content language buttons from the response
              $('.header-float .dropdown-menu').empty().append($('.header-float .dropdown-menu', data).html());
            }
        });
        return false;
      }
  );

  // event handler for clicking the alphabetical index tab
  $(document).on('click', '#alpha',
      function(event) {
        $.ajaxQ.abortSidebarQueries(true);
        $('.active').removeClass('active');
        $('#alpha').addClass('active');
        alpha_complete = false;
        $('.sidebar-grey').empty().prepend(spinner);
        var targetUrl = event.target.href;
        $.ajax({
            url : targetUrl,
            req_kind: $.ajaxQ.requestKind.SIDEBAR,
            success : function(data) {
              updateSidebar(data);
              $('.nav').scrollTop(0);
              if (window.history.pushState) { window.history.pushState({}, null, encodeURI(event.target.href)); }
              updateTitle(data);
            }
        });
        return false;
      }
  );

  // event handler for clicking the changes tab
  $(document).on('click', '#changes',
      function(event) {
        $.ajaxQ.abortSidebarQueries(true);
        $('.active').removeClass('active');
        $('#changes').addClass('active');
        $('.sidebar-grey').empty().prepend(spinner);
        $('.pagination').hide();
        $('#sidebar > h4.sr-only').hide();
        var targetUrl = event.target.href;
        $.ajax({
            url : targetUrl,
            req_kind: $.ajaxQ.requestKind.SIDEBAR,
            success : function(data) {
              updateSidebar(data);
              $('.nav').scrollTop(0);
              if (window.history.pushState) { window.history.pushState({}, null, encodeURI(event.target.href)); }
              updateTitle(data);
            }
        });
        return false;
      }
  );

  // event handler for clicking the sidebar hierarchy tab
  $(document).on('click', '#hier-trigger',
    function () {
      if($(this).parent()[0].id === 'hierarchy-disabled') {
        return false;
      } else if($('.jstree-clicked').hasClass('group')) {
        $('#groups > a').click();
        return false;
      }
      var $content = $('#sidebar');
      if($('#vocab-info').length) { // if on the vocabulary front page
        $('.sidebar-grey').remove();
        $('.active').removeClass('active');
        $('#hier-trigger').parent().addClass('active');
        $('.pagination').hide();
        $('#sidebar > h4.sr-only').hide();
        $content.append('<div class="sidebar-grey concept-hierarchy"></div>');
        invokeParentTree(getTreeConfiguration());
        $('.sidebar-grey').before('<h4 class="sr-only">' + sr_only_translations.hierarchy_listing + '</h4>');
        $('#hier-trigger').attr('href', '#');
        return false;
      }
      var uri = $('.uri-input-box').html();
      var base_href = $('base').attr('href'); // see #315, #633
      var clangIfSet = clang !== lang ? "&clang=" + clang : ""; // see #714
      var redirectUrl = base_href + vocab + '/' + lang + '/page/?uri=' + uri + clangIfSet;
      window.location.replace(encodeURI(redirectUrl));
      return false;
    }
  );

  // event handler for clicking the group index tab
  $(document).on('click', '#groups > a',
      function(event) {
        $.ajaxQ.abortSidebarQueries(true);
        $('.active').removeClass('active');
        var $clicked = $(this);
        $clicked.parent().addClass('active');
        $('.pagination').hide();
        $('#sidebar > h4.sr-only').hide();
        $('.sidebar-grey').remove().prepend(spinner);
        $('#sidebar').append('<div class="sidebar-grey"><div class="group-hierarchy"></div></div>');
        if (window.history.pushState) { window.history.pushState({}, null, encodeURI(event.target.href)); }
        invokeGroupTree();
        $('.sidebar-grey').before('<h4 class="sr-only">' + sr_only_translations.groups_listing + '</h4>');
        return false;
      }
  );

  // event handler for clicking groups
  $(document).on('click','div.group-hierarchy a',
      function(event) {
        $.ajaxQ.abortContentQueries();
        var $content = $('.content').empty().append($delayedSpinner.hide());
        var loading = delaySpinner();
        // ajaxing the sidebar content
        $.ajax({
            url : event.target.href,
            req_kind: $.ajaxQ.requestKind.CONTENT,
            complete: function() { clearTimeout(loading); },
            success : function(data) {
              initHierarchyQtip();
              $('#hier-trigger').attr('href', event.target.href);
              updateTitle(data);
              updateTopbarLang(data);
              $content.empty().append($('.content', data).html());
              $('.nav').scrollTop(0);
              if (window.history.pushState) { window.history.pushState({}, null, event.target.href); }
              updateTitle(data);
              ajaxConceptMapping(data);
              // take the content language buttons from the response
              $('.header-float .dropdown-menu').empty().append($('.header-float .dropdown-menu', data).html());
            }
        });
        return false;
      }
  );

  // event handler for the alphabetical index letters
  $(document).on('click','.pagination > li > a',
      function(event) {
        $.ajaxQ.abortSidebarQueries();
        if ($('.alphabet-header').length === 0) {
          alpha_complete = false;
          var $content = $('.sidebar-grey');
          $content.empty().prepend(spinner);
          var targetUrl = event.currentTarget.href;
          $.ajax({
            url : targetUrl,
            req_kind: $.ajaxQ.requestKind.SIDEBAR,
            success : function(data) {
              updateSidebar(data);
              $('.nav').scrollTop(0);
              if (window.history.pushState) { window.history.pushState({}, null, encodeURI(event.currentTarget.href)); }
              updateTitle(data);
              // take the content language buttons from the response
              $('.header-float .dropdown-menu').empty().append($('.header-float .dropdown-menu', data).html());
            }
          });
        } else {
          var selectedLetter = $(event.currentTarget).find("span:last-child").text().trim();
          if (document.getElementsByName(selectedLetter).length === 0) { return false; }
          var offset = $('li[name=' + selectedLetter + ']').offset().top - $('body').offset().top - 5;
          $('.nav').scrollTop(offset);
        }
        return false;
      }
  );

  // Event handlers for the language selection links for setting the cookie
  $('#language a').each( function(index, el) {
    $(el).on('click', function() {
      var langCode = el.id.substr(el.id.indexOf("-") + 1);
      setLangCookie(langCode);
    });
  });

  var qtip_skosmos = {
    position: { my: 'top center', at: 'bottom center' },
    style: { classes: 'qtip-tipsy qtip-skosmos' },
    show: {
      event: 'mouseenter focusin'
    },
    hide: {
      event: 'mouseleave focusout'
    }
  };

  var qtip_skosmos_hierarchy = {
    position: { my: 'top left', at: 'bottom center' },
    style: { classes: 'qtip-tipsy qtip-skosmos' }
  };

  $('#navi4').qtip(qtip_skosmos);

  $('.property-click').qtip(qtip_skosmos);

  $('.redirected-vocab-id').qtip(qtip_skosmos);

  $('.reified-property-value').each(function() {
    $(this).qtip({
      content: $(this).next('.reified-tooltip'),
      position: { my: 'top left', at: 'top left' },
      style: { classes: 'qtip-skosmos' },
      show: { delay: 100 },
      hide: {
        fixed: true,
        delay: 400
      }
    });
  });

  $('#hierarchy-disabled > #hier-trigger').qtip(qtip_skosmos_hierarchy);

  // Setting the language parameters according to the clang parameter or if that's not possible the cookie.
  var search_lang = (content_lang !== '' && !getUrlParams().anylang && vocab !== '') ? content_lang : readCookie('SKOSMOS_SEARCH_LANG');

  var rest_url = rest_base_url;
  if (rest_url.indexOf('..') === -1 && rest_url.indexOf('http') === -1) { rest_url = encodeURI(location.protocol + '//' + rest_url); }

  // qlang is used in REST queries as a parameter. it is either
  // - a language code, e.g. "en", when searching in a specific language
  // - "" when searching in all languages
  var qlang = search_lang;
  var langPretty;

  if (search_lang === 'anything' || getUrlParams().anylang === 'on') {
    $('#lang-dropdown-toggle').html($('.lang-button-all').html() + ' <span class="caret"></span>');
    qlang = "";
  } else if (!search_lang) {
      langPretty = $('a[hreflang=' + lang + ']').html();
      search_lang = lang;
      if (!langPretty) { langPretty = $('.lang-button-all').html(); }
      $('#lang-dropdown-toggle').html(langPretty + ' <span class="caret"></span>');
      qlang = lang;
  }

  var search_lang_possible = false;
  $.each($('.input-group-btn a'), function(index, value) {
    if(value.hreflang === search_lang) { search_lang_possible = true; }
  });

  if (!search_lang_possible && search_lang !== 'anything') {
    langPretty = $('.lang-button-all').html();
    $('#lang-dropdown-toggle').html(langPretty + ' <span class="caret"></span>');
    qlang = '';
    createCookie('SKOSMOS_SEARCH_LANG', qlang, 365);
  }

  $('.lang-button').on('click', function() {
    qlang = $(this)[0].attributes.hreflang ? $(this)[0].attributes.hreflang.value : 'anything';
    $('#lang-dropdown-toggle').html($(this).html() + ' <span class="caret"></span>');
    $('#lang-input').val(qlang);
    createCookie('SKOSMOS_SEARCH_LANG', qlang, 365);
    if (concepts) { concepts.clear(); }
  });

  $('.lang-button, .lang-button-all').on('click', function() {
    $('#search-field').focus();
  });

  var searchTerm = "";
  if (getUrlParams().q) {
    searchTerm = decodeURI(getUrlParams().q.replace(/\+/g, ' '));
  }

  // disables the button with an empty search form
  $('#search-field').on('keyup', function() {
    var empty = false;
    $('#search-field').each(function() {
      if ($(this).val().length === 0) { empty = true; }
    });

    if (empty) {
      $('#search-all-button').attr('disabled', 'disabled');
    } else {
      $('#search-all-button').attr('disabled', false);
    }
  });

  // typeahead autocomplete selection action
  function onSelection($e, datum) {
    if ($e.currentTarget.id !== 'parent-limit') {
      // restoring the original value
      $typeahead.typeahead('val', searchString);
      var localname = datum.localname;
      var params = {};
      if (!localname || encodeURIComponent(localname) !== localname) {
        localname = '';
        params.uri = datum.uri;
      }
      if ($('input[name=anylang]').is(':checked')) { params.anylang = 'on'; params.clang = datum.lang }
      else if (clang && clang !== lang) { params.clang = clang; }
      var paramstr = $.isEmptyObject(params) ? '' : '?' + $.param(params);
      var base_href = $('base').attr('href'); // see #315
      location.href = base_href + datum.vocab + '/' + lang + '/page/' + localname + paramstr;
    } else {
      $('#parent-limit').attr('data-uri', datum.uri);
      $('#parent-limit').val(datum.label);
      parentLimitReady = true;
      return false;
    }
  }

  Handlebars.registerHelper('noresults', function() {
    return noResultsTranslation;
  });

  var typeLabels = {};

  // iterates the rest types query response into an object for use in the Bloodhound datums.
  function processTypeJSON(response) {
    for(var i in response.types) {
      var type = response.types[i];
      if (type.label) { typeLabels[type.uri] = type.label; }
    }
  }

  // fetch the json from local storage if it has been already cached there.
  var typeJSON = lscache.get('types:' + lang);
  if (typeJSON) {
    processTypeJSON(typeJSON);
  } else { // if not then ajax the rest api and cache the results.
    var typeParam = $.param({'lang' : lang });
    var typeUrl = rest_base_url + 'types';
    $.ajax({
      dataType: 'json',
      url: typeUrl,
      req_kind: $.ajaxQ.requestKind.GLOBAL,
      data: typeParam,
      success: function(response) {
        lscache.set('types:' + lang, response, 1440);
        processTypeJSON(response);
      }
    });
  }

  var wildcard = '';

  var concepts = new Bloodhound({
    remote: {
      url: rest_base_url + 'search?query=',
      replace: function(url, query) {
        var wildcard = (query.indexOf('*') === -1) ? '*' : '';
        return url + encodeURIComponent(query) + wildcard;
      },
      ajax: {
        beforeSend: function(jqXHR, settings) {
          var vocabString = $('.frontpage').length ? vocabSelectionString : vocab;
          var parameters = $.param({'vocab' : vocabString, 'lang' : qlang, 'labellang' : qlang});
          // if the search has been targeted at all languages by clicking the checkbox
          if ($('input[name=anylang]').is(':checked')) {
            parameters = $.param({'vocab' : vocabString, 'lang' : '', 'labellang' : ''});
          }
          settings.url = settings.url + '&' + parameters;
          settings.req_kind = $.ajaxQ.requestKind.GLOBAL;
        }
      },
      // changes the response so it can be easily displayed in the handlebars template.
      filter: function(data) {
        // looping the matches to see if there are hits where the concept has been hit by a property other than hiddenLabel
        var hasNonHiddenMatch = {};
        for (var i = 0; i < data.results.length; i++) {
            var hit = data.results[i];
            if (!hit.hiddenLabel) {
                hasNonHiddenMatch[hit.uri] = true;
            } else if (hit.hiddenLabel) {
                if (hasNonHiddenMatch[hit.uri]) {
                    data.results.splice(i, 1);
                }
                hasNonHiddenMatch[hit.uri] = false;
            }
        }
        var context = data['@context'];
        return ($.map(data.results.filter(
          function() {
            return true;
          }),
          function(item) {
            if (item.vocab !== vocab) {
              var voc = item.vocab;
              var vocabLabel = $('select.multiselect').children('[value="' + voc + '"]').attr('data-label');
              item.vocabLabel = (vocabLabel) ? vocabLabel : voc;
            }
            item.label = item.prefLabel;
            // combining all the matched properties.
            if (item.matchedPrefLabel) { item.label = item.matchedPrefLabel; }
            if (item.altLabel) { item.replaced = item.altLabel; }
            // not showing hiddenLabel matches when there are better matches to show for the same concept
            if (item.hiddenLabel && hasNonHiddenMatch[item.uri]) { return null; }
            // do not show the label language when it's same or in the same subset as the ui language.
            if (item.lang && (item.lang === qlang || item.lang.indexOf(qlang + '-') === 0)) { delete(item.lang); }
            if (item.type) {
              var toBeRemoved = null;
              item.typeLabel = item.type;
              for (var i = 0; i < item.type.length; i++) {
                if (item.type[i] === 'skos:Concept' && item.type.length > 1) {
                  toBeRemoved = item.type.indexOf('skos:Concept');
                }
                var prefix = item.type[i].substr(0, item.type[i].indexOf(':'));
                if (prefix !== 'http' && prefix !== undefined && context[prefix] !== undefined) {
                  var unprefixed = context[prefix] + item.type[i].substr(item.type[i].indexOf(':') + 1, item.type[i].length);
                  if (typeLabels[unprefixed] !== undefined) {
                    item.typeLabel[i] = typeLabels[unprefixed];
                  }
                }
                if (typeLabels[item.type[i]] !== undefined) {
                  item.typeLabel[i] = typeLabels[item.type[i]];
                }
              }
              if (toBeRemoved !== null) { item.type.splice(toBeRemoved, 1); }
            }
            return item;
          }));
      }
    },
    limit: 9999,
    datumTokenizer: Bloodhound.tokenizers.whitespace,
    queryTokenizer: Bloodhound.tokenizers.whitespace
  });

  concepts.initialize();

  var autocompleteTemplate =[
    '{{# if matched }}<p>{{matched}}{{# if lang}} ({{lang}}){{/if}} = </p>{{/if}}',
    '{{# if replaced }}<p class="replaced">{{replaced}}{{# if lang}} ({{lang}}){{/if}} &rarr; </p>{{/if}}',
    '{{# if notation }}<p>{{notation}}</p>{{/if}}',
    '<p class="autocomplete-label">{{label}}{{# if lang}}{{# unless matched }}<p>({{lang}})</p>{{/unless}}{{/if}}</p>',
    '{{# if typeLabel }}<span class="concept-type">{{typeLabel}}</span>{{/if}}',
    '<div class="vocab">{{vocabLabel}}</div>'
  ].join('');

  if ($('.headerbar').length > 0) {
    var dark = ($('#search-field').val().length > 0) ? ' clear-search-dark' : '';
    var clearButton = '<span class="versal clear-search' + dark + '">&#215;</span>';

    var $typeahead = $('#search-field').typeahead({ hint: false, highlight: true, minLength: autocomplete_activation },
      {
        name: 'concept',
        displayKey: 'label',
        templates: {
          empty: Handlebars.compile([
            '<div><p class="autocomplete-no-results">{{#noresults}}{{/noresults}}</p></div>'
          ].join('')),
          suggestion: Handlebars.compile(autocompleteTemplate)
        },
        source: concepts.ttAdapter()
    }).on('typeahead:selected', onSelection).on('focus', function() {
      $('#search-field').typeahead('open');
    }).after(clearButton).on('keypress', function() {
      if ($typeahead.val().length > 0 && $(this).hasClass('clear-search-dark') === false) {
        $('.clear-search').addClass('clear-search-dark');
      }
    });

    // monkey-patching TypeAhead's Dropdown object for: https://github.com/NatLibFi/Skosmos/issues/773
    // Updating typeahead.js to 0.11 requires a few changes that are not really complicated.
    // However, our dropdown style is broken, and it appears hard to be fixed. typeahead.js
    // Also does not appear to be maintained, so this temporary fix will prevent
    // accidental selection of values. TODO: we must fix this in a future release, possibly
    // using another library.
    var typeaheadInstance = $typeahead.data("ttTypeahead");
    typeaheadInstance.dropdown.$menu.off("mouseenter.tt", ".tt-suggestion");
    typeaheadInstance.dropdown.$menu.off("mouseleave.tt", ".tt-suggestion");
  }

  // storing the search input before autocompletion changes it
  $('#search-field').on('input', function() {
    searchString = $(this).val();
  });

  $('.clear-search').on('click', function() {
    searchString = '';
    $typeahead.val('');
    $typeahead.focus();
    $(this).removeClass('clear-search-dark');
  });

  // Some form validation for the feedback form
  $('#send-feedback').on('click', function() {
      $('#message').removeClass('missing-value');
      $('#msgsubject').removeClass('missing-value');
      var emailMessageVal = $("#message").val();
      var emailSubject = $("#msgsubject").val();
      var requiredFields = true;
      if (emailMessageVal === '') {
        $("#message").addClass('missing-value');
        requiredFields = false;
      }
      if (emailSubject === '') {
        $("#msgsubject").addClass('missing-value');
        requiredFields = false;
      }
      return requiredFields;
  });

  // Initializes the waypoints plug-in used for the search listings.
  var $loading = $("<p>" + loading_text + "&hellip;<span class='spinner'></span></p>");
  var $trigger = $('.search-result:nth-last-of-type(6)');
  var options = { offset : '100%', continuous: false, triggerOnce: true };
  var alpha_complete = false;
  var offcount = 1;
  var number_of_hits = $(".search-result").length;
  var $ready = $("<p class='search-count'>" + results_disp.replace('%d', number_of_hits) +"</p>");

  // search-results waypoint
  if (number_of_hits > 0) { // if we are in the search page with some results
    if (number_of_hits === parseInt($('.search-count p').text().substr(0, $('.search-count p').text().indexOf(' ')), 10)) {
      $('.search-result-listing').append($ready);
    }
    else {
      $trigger.waypoint(function() { waypointCallback(); }, options);
    }
  }

  function alphaWaypointCallback() {
    // if the pagination is not visible all concepts are already shown
    if (!alpha_complete && $('.pagination').length === 1) {
      $.ajaxQ.abortSidebarQueries();
      alpha_complete = true;
      $('.alphabetical-search-results').append($loading);
      var parameters = $.param({'offset' : 250, 'clang': content_lang});
      var letter = '/' + ($('.pagination > .active')[0] ? $('.pagination > .active > a > span:last-child')[0].innerHTML : $('.pagination > li > a > span:last-child')[0].innerHTML);
      $.ajax({
        url : vocab + '/' + lang + '/index' + letter,
        req_kind: $.ajaxQ.requestKind.SIDEBAR,
        data : parameters,
        success : function(data) {
          $loading.detach();
          if ($(data).find('.alphabetical-search-results').length === 1) {
            $('.alphabetical-search-results').append($(data).find('.alphabetical-search-results')[0].innerHTML);
          }
        }
      });
    }
  }
  var changeOffset = 200;

  function changeListWaypointCallback() {
    $.ajaxQ.abortSidebarQueries();
    $('.change-list').append($loading);
    var parameters = $.param({'offset' : changeOffset, 'clang': content_lang});
    var lastdate = $('.change-list > h5:last-of-type')[0].innerHTML;
    $.ajax({
      url : vocab + '/' + lang + '/new',
      req_kind: $.ajaxQ.requestKind.SIDEBAR,
      data : parameters,
      success : function(data) {
        $loading.detach();
        if ($(data).find('.change-list').length === 1) {
          $('.change-list').append($(data).find('.change-list')[0].innerHTML);
          var $lastdate = $('.change-list > h5:contains(' + lastdate + ')');
          if ($lastdate.length === 2)
           $lastdate[1].remove();
          $('.change-list > p:last-of-type').remove();
        }
      }
    });
    changeOffset += 200;
  }

  function waypointCallback() {
    var number_of_hits = $(".search-result").length;
    if (number_of_hits < parseInt($('.search-count p').text().substr(0, $('.search-count p').text().indexOf(' ')), 10)) { $('.search-result-listing').append($loading);
      var typeLimit = $('#type-limit').val();
      var schemeLimit = $('#scheme-limit').val();
      var groupLimit = $('#group-limit').val();
      var parentLimit = $('#parent-limit').attr('data-uri');
      var parameters = $.param({'q' : searchTerm, 'vocabs' : vocabSelectionString, 'offset' : offcount * waypoint_results, 'clang' : content_lang, 'type' : typeLimit, 'group' : groupLimit, 'parent': parentLimit, anylang: getUrlParams().anylang, 'scheme' : schemeLimit });
      $.ajax({
        url : window.location.pathname,
        req_kind: $.ajaxQ.requestKind.GLOBAL,
        data : parameters,
        success : function(data) {
          $loading.detach();
          $('.search-result-listing').append($(data).find('.search-result'));
          number_of_hits = $('.uri-input-box').length;
          $ready = $("<p class='search-count'>" + results_disp.replace('%d',$(".search-result").length) +"</p>");
          offcount++;
          shortenProperties();
          if ($(data).find('.search-result').length === 0 || number_of_hits === parseInt($('.search-count p').text().substr(0, $('.search-count p').text().indexOf(' ')), 10)) { $('.search-result-listing');
            $('.search-result-listing').append($ready);
            return false;
          }
          $('.search-result:nth-last-of-type(4)').waypoint(function() { waypointCallback(); }, options );
        }
      });
    }
  }

  // activating the custom autocomplete
  function updateVocabParam() {
    vocabSelectionString = '';
    var $vocabs = $('li.active input');
    $.each($vocabs,
      function(index, ob) {
        if (ob.value === 'multiselect-all') {
          return false;
        }
        vocabSelectionString += ob.value;
        if (index < $vocabs.length - 1) { vocabSelectionString += ' '; }
    });
    // sets the selected vocabularies cookie for the frontpage search.
    createCookie('SKOSMOS_SELECTED', vocabSelectionString, 365);
    $('#selected-vocabs').val(vocabSelectionString);
  }

  // preselecting the vocabularies from the cookie for the multiselect dropdown plugin.
  if (vocabSelectionString !== null) {
    $.each(vocabSelectionString.split(' '), function(index, vocabId) {
      $('option[value="' + vocabId + '"]').prop('selected', 'true');
    });
  } else {
    $.each($('option'), function(i, opt) {
      $(opt).prop('selected', null);
    });
  }

  $('.headerbar .multiselect').multiselect({
    buttonText: function(options) {
      if (options.length === 0 || options.length === ($('.headerbar .multiselect-container li').length - 1)) {
        return all_vocabs;
      } else {
        if (options.length > this.numberDisplayed) {
          return options.length + ' ' + n_selected;
        } else {
          var selected = '';
          options.each(function() {
            var label = ($(this).attr('label') !== undefined) ? $(this).attr('label') : $(this).html();
            selected += label + ', ';
          });
          return selected.substr(0, selected.length - 2);
        }
      }
    },
    numberDisplayed: 2,
    buttonWidth: 'auto',
    includeSelectAllOption: true,
    selectAllText: all_vocabs,
    onChange: function(element, checked) {
      if (element) {
        vocabId = element[0].value;
      } else {
        vocabId = '';
      }
      if (checked && selectedVocabs[vocabId] === undefined) {
        selectedVocabs[vocabId] = vocabId;
      } else if (selectedVocabs[vocabId] !== undefined) {
        delete selectedVocabs[vocabId];
      }
      updateVocabParam();
    },
    maxHeight: 300
  });

  $('.sidebar-grey .multiselect').multiselect({
    buttonText: function(options) {
      if (options.length === 0) {
        return  '';
      } else {
        var selected = '';
        options.each(function() {
          var label = ($(this).attr('label') !== undefined) ? $(this).attr('label') : $(this).html();
          if (label !== '') { selected += label + ', '; }
        });
        return selected.substr(0, selected.length - 2);
      }
    },
    numberDisplayed: 2,
    buttonWidth: 'auto',
    maxHeight: 300
  });

  if ($('#alpha.active').length === 1 || $('#changes.active').length === 1) {
    var scrollCB = ($('#changes.active').length === 1) ? changeListWaypointCallback : alphaWaypointCallback;
    var element = document.getElementsByClassName('sidebar-grey')[0];
    $('.sidebar-grey').scroll(function () {
      if (element.scrollHeight - element.scrollTop - 300 <= element.clientHeight) {
        scrollCB();
      }
    })
  }

  /* adding the replaced by concept href to the alert box when possible.
   */
  var $replaced = $('.replaced-by a');
  if ($replaced.length > 0) {
    var $replacedElem = $('.replaced-by h3');
    var undoUppercasing = $replacedElem.text().substr(0,1) + $replacedElem.text().substr(1).toLowerCase();
    var html = ''
    for (var i = 0; i < $replaced.length; i++) {
        var replacedBy = '<a href="' + $replaced[i] + '">' + $replaced[i].innerHTML + '</a>';
        html += '<p class="alert-replaced">' + undoUppercasing + ': ' + replacedBy + '</p>';
    }
    $('.alert-danger').append(html);
    $(document).on('click', '#groups',
      function() {
        $('.sidebar-grey').clear();
          return false;
      }
    );
  }

  /* makes an AJAX query for the alphabetical index contents when landing on
   * the vocabulary home page.
   */
  if ($('#alpha').hasClass('active') && $('#vocab-info').length === 1 && $('.alphabetical-search-results').length === 0) {
    // taking into account the possibility that the lang parameter has been changed by the WebController.
    var urlLangCorrected = vocab + '/' + lang + '/index?limit=250&offset=0&clang=' + clang;
    $('.sidebar-grey').empty().append('<div class="loading-spinner"><span class="spinner-text">'+ loading_text + '</span><span class="spinner"></span></div>');
    $.ajax({
      url : urlLangCorrected,
      req_kind: $.ajaxQ.requestKind.SIDEBAR,
      success : function(data) {
        $('#sidebar').replaceWith($(data).find('#sidebar'));
      }
    });
  }

  var searchOptions = $('.search-options');
  if (searchOptions.length === 1) {
    var parentLimitReady = true;
    $(document).on('click', '#remove-limits', function() {
      $('#type-limit').val('');
      $('#type-limit').multiselect('refresh');
      $('#scheme-limit').val('');
      $('#scheme-limit').multiselect('refresh');
      $('#parent-limit').attr('data-uri', '');
      $('#parent-limit').val('');
      $('#group-limit').val('');
      $('#group-limit').multiselect('refresh');
      loadLimitedResults(loadLimitations());
    });

    $('#parent-limit').focus(function() {
      parentLimitReady = $('#parent-limit').attr('data-uri') !== '';
    });

    $(document).on('submit', '.search-options', function() {
      if (parentLimitReady) { loadLimitedResults(loadLimitations()); }
      return false;
    });

    $('#parent-limit').typeahead({ hint: false, highlight: true, minLength: autocomplete_activation },{
        name: 'concept',
        displayKey: 'label',
        templates: {
          empty: Handlebars.compile([
            '<div><p class="autocomplete-no-results">{{#noresults}}{{/noresults}}</p></div>'
          ].join('')),
          suggestion: Handlebars.compile(autocompleteTemplate)
        },
        source: concepts.ttAdapter()
    }).on('typeahead:selected', onSelection).on('focus', function() {
      $('#search-field').typeahead('open');
    });
  }

  if ($('#feedback-vocid').length) {
    $('#feedback-fields > .dropdown > .dropdown-menu > li > a').each(function(index, elem) {
      $(elem).on('click', function(event) {
        $('#feedback-vocid-input').val($(this).attr('data-vocid'))
        $('#feedback-vocid').html($(this).html() + '<span class="caret"></span>');
        event.preventDefault();
    })});
  }

  // ajaxing the concept mapping properties on the concept page
  var $conceptAppendix = $('.concept-appendix');
  if ($conceptAppendix.length) {
    var concept = {
      uri: $conceptAppendix.data('concept-uri'),
      type: $conceptAppendix.data('concept-type')
    };

    // Defined in scripts.js. Will load the mapping properties via Ajax request to JSKOS REST service, and render them.
    loadMappingProperties(concept, lang, clang, $conceptAppendix, null);
  } else {
    makeCallbacks();
  }

});

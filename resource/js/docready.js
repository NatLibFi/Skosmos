/* 
 * Copyright (c) 2012 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

$(function() { // DOCUMENT READY 

  var spinner = '<div class="loading-spinner"><span class="spinner-text">'+ loading_text + '</span><span class="spinner" /></div>';
  var searchString = ''; // stores the search field's value before autocomplete selection changes it
  var selectedVocabs = [];
  var vocabId;
  var vocabSelectionString = getUrlParams().vocabs ? getUrlParams().vocabs.replace(/\+/g,' ') : readCookie('SKOSMOS_SELECTED');
  $('#selected-vocabs').val(vocabSelectionString);
  var clang = content_lang !== '' ? content_lang : lang;

  shortenProperties();

  // kills the autocomplete after a form submit so we won't have to wait for the ajax to complete.
  $('.navbar-form').submit(
    function() {
      $('#search-field').typeahead('destroy');
      $.ajaxQ.abortAll();
    }
  );


  /*
   * Moving the sidenav scrollbar towards the current concept. Aiming the current
   * concept at vertical center of the container. Each concept needs 18px height.
   */
  $(document).ajaxComplete(function(event, xhr, settings) {
    $('.property-click').qtip({ 
      position: { my: 'bottom center', at: 'top center' },
      style: { classes: 'qtip-tipsy qtip-skosmos' } 
    });
    if (settings.url.indexOf('groups') !== -1 || settings.url.indexOf('index') !== -1) {
      $('.sidebar-grey').removeClass(function(index, classes) {
        var elementClasses = classes.split(' ');
        var removeThese = [];

        $.each(elementClasses, function() {
          if(this.match(/jstree*/)) { removeThese.push(this); }
        });
        return removeThese.join(' ');
      });
      if (settings.url.indexOf('index/' !== -1)) {
        $(".sidebar-grey").mCustomScrollbar({ 
          alwaysShowScrollbar: 1,
          scrollInertia: 0, 
          mouseWheel:{ preventDefault: true, scrollAmount: 105 },
          snapAmount: 15,
          snapOffset: 1,
          callbacks: { alwaysTriggerOffsets: false, onTotalScroll: alphaWaypointCallback, onTotalScrollOffset: 300 }
        });
      }
    }
    // Sidenav actions only happen when doing other queries than the autocomplete.
    if (settings.url.indexOf('topConcepts') !== -1 || settings.url.indexOf('index') !== -1 || settings.url.indexOf('groups') !== -1 ) {
      var snap = (settings.url.indexOf('hierarchy') !== -1) ? 18 : 15;
      $(".sidebar-grey").mCustomScrollbar({ 
        alwaysShowScrollbar: 1,
        scrollInertia: 0, 
        mouseWheel:{ scrollAmount: 105 },
        snapAmount: snap,
        snapOffset: 0
      });
    } 
    var $autocomplete = $('.tt-dropdown-menu');
    if (settings.url.indexOf('search') !== -1 && $autocomplete.length > 0 && $autocomplete[0].offsetHeight === 302) {
      $(".tt-dropdown-menu").mCustomScrollbar({ alwaysShowScrollbar: 1, scrollInertia: 0 });
    }
    countAndSetOffset();

    hideCrumbs();
  });

  // if the hierarchy tab is active filling the jstree with data 
  if ($('#hierarchy').hasClass('active')) { invokeParentTree(getTreeConfiguration()); }
  if ($('#groups').hasClass('active')) { invokeGroupTree(); }

  var textColor = $('.search-parameter-highlight').css('color');
  countAndSetOffset();

  if($('.search-result-listing').length === 0) { // Disabled if on the search results page.
  /*
   * Event handler for clicking the preflabel and making a selection of it for copy pasting.
   */
    $(document).on('click','.prefLabel',
      function() {
        var text = $('.prefLabel')[0];    
        var range;
        if (document.body.createTextRange) { // ms
          range = document.body.createTextRange();
          range.moveToElementText(text);
          range.select();
        } else if (window.getSelection) { // moz, opera, webkit
          var selection = window.getSelection();            
          range = document.createRange();
          range.selectNodeContents(text);
          selection.removeAllRanges();
          selection.addRange(range);
        }
        return false;
      }
      );
    
  }
    
  $(document).on('click','.uri-input-box',
    function() {
      var $clicked = $(this);
      var text = $clicked[0];    
      var range;
      if (document.body.createTextRange) { // ms
        range = document.body.createTextRange();
        range.moveToElementText(text);
        range.select();
      } else if (window.getSelection) { // moz, opera, webkit
        var selection = window.getSelection();            
        range = document.createRange();
        range.selectNodeContents(text);
        selection.removeAllRanges();
        selection.addRange(range);
      }
      return false;
    }
  );

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

  // Event handler for restoring the DOM back to normal after the focus is lost from the prefLabel.
  $(':not(.search-parameter-highlight)').click(
      function(){
        $('#temp-textarea').remove();
        $('.search-parameter-highlight').css({'background': 'transparent', 'color': textColor});
      }
  );
 
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

  // ajaxing the concept count and the preflabel counts on the vocabulary front page
  if ($('#vocab-info').length) {
    // adding the spinners      
    $('#counts tr:nth-of-type(1)').after('<tr><td><span class="spinner" /></td></td></tr>');
    $('#statistics tr:nth-of-type(1)').after('<tr><td><span class="spinner" /></td></td></tr>');
    $.ajax({
      url : rest_base_url + vocab + '/vocabularyStatistics',
      data: $.param({'lang' : content_lang}),
      success : function(data) {
        var $spinner = $('#counts tr:nth-of-type(2)');
        var typeStats = '<tr><td class="count-type versal">' + data.concepts.class + '</td><td class="versal">' + data.concepts.count +'</td></tr>';
        for (var i in data.subTypes) {
          var sub = data.subTypes[i];
          var label = sub.label ? sub.label : sub.type;
          typeStats += '<tr><td class="count-type versal">' + label + '</td>' + '<td class="versal">' + sub.count + '</td></tr>';
        }
          
        $spinner.after(typeStats);
        $spinner.detach();
      }
    });
    
    $.ajax({
      url : rest_base_url + vocab + '/labelStatistics',
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
    if (e.originalEvent.state !== null) {
      loadPage(e.originalEvent.state.url);
    } else {
      loadPage(document.URL);
    }
  });

  // event handler for clicking the hierarchy concepts
  $(document).on('click', '.jstree-no-icons a',
      function(event) {
        event.preventDefault();
        var $content = $('.content');
        var targetUrl = event.target.href;
        var parameters = $.param({'clang' : clang});
        $('#hier-trigger').attr('href', targetUrl);
        $.ajax({
            url : targetUrl,
            data: parameters,
            success : function(data) {
              $content.empty();
              var response = $('.content', data).html();
              if (window.history.pushState) { window.history.pushState({url: targetUrl + '&' + parameters}, '', targetUrl); }
              $content.append(response);
              updateTitle(data);
              updateTopbarLang(data);
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
        $.ajaxQ.abortAll();
        $('.activated-concept').removeClass('activated-concept');
        $(this).addClass('activated-concept');
        var $content = $('.content');
        var hierButton = '<li id="hierarchy"><a id="hier-trigger" href="#">' + hiertrans + '</a></li>';
        $.ajax({
            url : event.target.href,
            success : function(data) {
              if (window.history.pushState) { window.history.pushState(null, null, event.target.href); }
              $content.empty().append($('.content', data).html());
              if (!$('#hierarchy').length) { $('#alpha').after(hierButton); }
              $('#hier-trigger').attr('href', event.target.href);
              updateTitle(data);
              updateTopbarLang(data);
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
        $.ajaxQ.abortAll();
        $('.active').removeClass('active');
        $(this).parent().addClass('active');
        $('.sidebar-grey').empty().prepend(spinner);
        var targetUrl = event.target.href;
        $.ajax({
            url : targetUrl,
            success : function(data) {
              updateSidebar(data);
              $('#alpha').after($('#hierarchy'));
              $('.nav').scrollTop(0);
              if (window.history.pushState) { window.history.pushState(null, null, encodeURI(event.target.href)); }
              updateTitle(data);
            }
        });
        return false;
      }
  );

  // event handler for clicking the sidebar hierarchy tab
  $(document).on('click', '#hier-trigger', 
    function () {
      var $content = $('#sidebar');
      if($('.uri-input-box').length === 0) { // if on the vocabulary front page
        $('.sidebar-grey').remove();
        $('.active').removeClass('active');
        $('#hier-trigger').parent().addClass('active');
        $('.pagination').hide();
        $content.append('<div class="sidebar-grey"><div class="hierarchy-bar-tree"></div></div>');
        invokeParentTree(getTreeConfiguration(true)); 
        $('#hier-trigger').attr('href', '#');
        return false;
      }
      var uri = $('.uri-input-box').html();
      var redirectUrl = vocab + '/' + lang + '/page/?uri=' + uri;
      window.location.replace(encodeURI(redirectUrl));
      return false;
    }
  );

  // event handler for clicking the group index tab 
  $(document).on('click', '#groups > a',
      function(event) {
        $.ajaxQ.abortAll();
        $('.active').removeClass('active');
        var $clicked = $(this);
        $clicked.parent().addClass('active');
        var $pagination = $('.pagination');
        if ($pagination) { $pagination.hide(); }
        $('.sidebar-grey').remove().prepend(spinner);
        $('#sidebar').append('<div class="sidebar-grey"><div class="group-hierarchy"></div></div>');
        if (window.history.pushState) { window.history.pushState(null, null, encodeURI(event.target.href)); }
        invokeGroupTree();
        return false;
      }
  );
 
  // event handler for clicking groups
  $(document).on('click','.group-index > li > a',
      function(event) {
        $.ajaxQ.abortAll();
        var $content = $('.content');
        $('.sidebar-grey').empty().prepend(spinner);
        var targetUrl = event.target.href;
        // ajaxing the sidebar content
        $.ajax({
            url : targetUrl,
            success : function(data) {
              updateSidebar(data);
              $content.empty();
              var concept = $('.content', data).html();
              $content.append(concept);
              $('.nav').scrollTop(0);
              if (window.history.pushState) { window.history.pushState(null, null, encodeURI(event.target.href)); }
              updateTitle(data);
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
        $.ajaxQ.abortAll();
        if ($('.alphabet-header').length === 0) {
          alpha_complete = false;
          var $content = $('.sidebar-grey');
          $content.empty().prepend(spinner);
          var targetUrl = event.target.href;
          $.ajax({
            url : targetUrl,
            success : function(data) {
              updateSidebar(data);
              $('.nav').scrollTop(0);
              if (window.history.pushState) { window.history.pushState(null, null, encodeURI(event.target.href)); }
              updateTitle(data);
              // take the content language buttons from the response
              $('.header-float .dropdown-menu').empty().append($('.header-float .dropdown-menu', data).html());
            }
          });
        } else {
          var selectedLetter = $(event.target).text().trim();
          if (document.getElementsByName(selectedLetter).length === 0) { return false; }
          var offset = $('li[name=' + selectedLetter + ']').offset().top - $('body').offset().top - 5;
          $('.nav').scrollTop(offset);
        }
        return false;
      }
  );
  
  // Event handlers for the language selection links for setting the cookie
  $('#language a').each( function(index, el) { 
    $(el).click(function() { 
      var langCode = el.id.substr(el.id.indexOf("-") + 1);
      setLangCookie(langCode);
    }); 
  });

  var qtip_skosmos = { 
    position: { my: 'top center', at: 'bottom center' },
    style: { classes: 'qtip-tipsy qtip-skosmos' } 
  };
  
  $('.search-hint').qtip(qtip_skosmos);
  
  $('#navi4').qtip(qtip_skosmos);
    
  $('.property-click').qtip(qtip_skosmos);
  
  $('.redirected-vocab-id').qtip(qtip_skosmos);
  
  // Setting the language parameters according to the clang parameter or if that's not possible the cookie.
  var search_lang = (content_lang !== '' && !getUrlParams().anylang) ? content_lang : readCookie('SKOSMOS_SEARCH_LANG');
  
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
      if (!langPretty) { langPretty = $('a[hreflang="anything"]').html(); }
      $('#lang-dropdown-toggle').html(langPretty + ' <span class="caret"></span>');
      qlang = lang;
  } else {
      langPretty = $('a[hreflang=' + search_lang + ']').html();
      if (!langPretty) { langPretty = $('a[hreflang=""]').html(); }
      $('#lang-dropdown-toggle').html(langPretty + ' <span class="caret"></span>');
  }

  var search_lang_possible = false;
  $.each($('.input-group-btn a'), function(index, value) { 
    if(value.hreflang === search_lang) { search_lang_possible = true; }
  });
  
  if (!search_lang_possible && search_lang !== 'anything') {
    langPretty = $('a[hreflang=""]').html();
    $('#lang-dropdown-toggle').html(langPretty + ' <span class="caret"></span>');
    qlang = '';
    createCookie('SKOSMOS_SEARCH_LANG', qlang, 365);
  }

  $('.lang-button').click(function() {
    qlang = $(this)[0].attributes.hreflang.value;
    if (qlang === '') { qlang = 'anything'; }
    $('#lang-dropdown-toggle').html($(this).html() + ' <span class="caret"></span>');
    $('#lang-input').val(qlang);
    createCookie('SKOSMOS_SEARCH_LANG', qlang, 365);
    if (concepts) { concepts.clear(); }
  });
  
  $('.lang-button-all').on('click', function() {
    createCookie('SKOSMOS_SEARCH_LANG', 'anything', 365);
    $('#lang-input').val('');
    $('#lang-dropdown-toggle').html($('.lang-button-all').html() + ' <span class="caret"></span>');
    if (concepts) { concepts.clear(); }
  });

  $('.lang-button, .lang-button-all').click(function() {
    $('#search-field').focus();
  });
  
  var searchTerm = "";
  if (getUrlParams().q) {
    searchTerm = decodeURI(getUrlParams().q);
  }
  
  // disables the button with an empty search form 
  $('#search-field').keyup(function() {
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

  // typeahead selection action
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
      if ($('input[name=anylang]').is(':checked')) { params.anylang = 'on'; }
      if ($('input[name=anylang]').is(':checked') && clang && clang !== lang) { params.clang = clang; }
      var paramstr = $.isEmptyObject(params) ? '' : '?' + $.param(params);
      if (datum.type && datum.type.indexOf('Collection') !== -1) {
        location.href = datum.vocab + '/' + lang + '/groups/' + localname + paramstr;
      } else {
        location.href = datum.vocab + '/' + lang + '/page/' + localname + paramstr;
      }
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
    $.getJSON(typeUrl, typeParam, function(response) {
      lscache.set('types:' + lang, response, 1440);
      processTypeJSON(response);
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
          wildcard = ($('#search-field').val().indexOf('*') === -1) ? '*' : '';
          var vocabString = $('.frontpage').length ? vocabSelectionString : vocab; 
          var parameters = $.param({'vocab' : vocabString, 'lang' : qlang, 'labellang' : qlang});
          // if the search has been targeted at all languages by clicking the checkbox
          if ($('input[name=anylang]').is(':checked')) {
            parameters = $.param({'vocab' : vocabString, 'lang' : '', 'labellang' : ''});
          }
          settings.url = settings.url + '&' + parameters;
        }
      },
      // changes the response so it can be easily displayed in the handlebars template.
      filter: function(data) {
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
            if (item.hiddenLabel) { item.replaced = item.hiddenLabel; }
            // do not show the label language when it's same or in the same subset as the ui language.
            if (item.lang && (item.lang === qlang || item.lang.indexOf(qlang + '-') === 0)) { delete(item.lang); }
            if (item.type) {
              var toBeRemoved = null;
              for (var i = 0; i < item.type.length; i++) {
                if (item.type[i] === 'skos:Concept' && item.type.length > 1) {
                  toBeRemoved = item.type.indexOf('skos:Concept');
                }
                var prefix = item.type[i].substr(0, item.type[i].indexOf(':'));
                if (prefix !== 'http' && prefix !== undefined && context[prefix] !== undefined) {
                  item.type[i] = context[prefix] + item.type[i].substr(item.type[i].indexOf(':') + 1, item.type[i].length);
                }
                if (typeLabels[item.type[i]] !== undefined) {
                  item.type[i] = typeLabels[item.type[i]];
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
    '<p class="autocomplete-label">{{label}}{{# if lang}}{{# unless matched }}<p>({{lang}})</p>{{/unless}}{{/if}}</p>',
    '{{# if type }}<span class="concept-type">{{type}}</span>{{/if}}',
    '<div class="vocab">{{vocabLabel}}</div>',
  ].join('');

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
  }).on('typeahead:cursorchanged', function() {
    $('.tt-dropdown-menu').mCustomScrollbar("scrollTo", '.tt-cursor');
  }).on('typeahead:selected', onSelection).bind('focus', function() {
    $('#search-field').typeahead('open'); 
  }).after(clearButton).on('keypress', function() {
    if ($typeahead.val().length > 0 && $(this).hasClass('clear-search-dark') === false) {
      $('.clear-search').addClass('clear-search-dark');
    }
  });
    
  // storing the search input before autocompletion changes it 
  $('#search-field').on('input', function() { 
    searchString = $(this).val(); 
  });

  $('.clear-search').on('click', function() { 
    searchString = '';
    $(this).removeClass('clear-search-dark');
  });

  // Some form validation for the feedback form
  $("#send-feedback").click(function() {
      $('#email').removeClass('missing-value');
      $('#message').removeClass('missing-value');
      var emailMessageVal = $("#message").val();
      var emailAddress = $("#email").val();
      var requiredFields = true;  
      if (emailAddress === '' || emailAddress.indexOf('@') === -1) {
        $("#email").addClass('missing-value');
        requiredFields = false;
      }
      if (emailMessageVal === '') {
        $("#message").addClass('missing-value');
        requiredFields = false;
      }
      return requiredFields;
  });

  // Initializes the waypoints plug-in used for the search listings.
  var $loading = $("<p>" + loading_text + "&hellip;<span class='spinner'/></p>"); 
  var $trigger = $('.search-result:nth-last-of-type(6)'); 
  var options = { offset : '100%', continuous: false, triggerOnce: true };
  var alpha_complete = false;
  var offcount = 1;
  var number_of_hits = $(".search-result").length;
  var $ready = $("<p class='search-count'>" + results + " " + number_of_hits + " " + results_disp +"</p>");
  
  // search-results waypoint
  if (number_of_hits > 0) { // if we are in the search page with some results
    if (number_of_hits < waypoint_results * offcount) { 
      $('.search-result-listing').append($ready);
    }
    else {
      $trigger.waypoint(function() { waypointCallback(); }, options);
    }
  }

  function alphaWaypointCallback() {
    // if the pagination is not visible all concepts are already shown
    if (!alpha_complete && $('.pagination').length === 1) {      
      alpha_complete = true;
      $('.alphabetical-search-results').append($loading);
      var parameters = $.param({'offset' : 250, 'clang': content_lang});
      var letter = '/' + $('.pagination > .active > a')[0].innerHTML;
      $.ajax({
        url : vocab + '/' + lang + '/index' + letter,
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

  function waypointCallback() {
    var number_of_hits = $(".search-result").length;
    if (number_of_hits >= waypoint_results * offcount) { $('.search-result-listing').append($loading); }
    var typeLimit = $('#type-limit').val();
    var groupLimit = $('#group-limit').val();
    var parentLimit = $('#parent-limit').attr('data-uri');
    var parameters = $.param({'q' : searchTerm, 'vocabs' : vocabSelectionString, 'offset' : offcount * waypoint_results, 'clang' : content_lang, 'type' : typeLimit, 'group' : groupLimit, 'parent': parentLimit, anylang: getUrlParams().anylang});
    $.ajax({
      url : window.location.pathname,
      data : parameters,
      success : function(data) {
        $loading.detach();
        if ($(data).find('.search-result').length === 0) {
          $('.search-result-listing').append($ready);
          return false;
        }
        $('.search-result-listing').append($(data).find('.search-result'));
        number_of_hits = $('.uri-input-box').length;
        $ready = $("<p class='search-count'>" + results + " " + $(".search-result").length + " " + results_disp +"</p>");
        offcount++;
        shortenProperties();
        $('.search-result:nth-last-of-type(4)').waypoint(function() { waypointCallback(); }, options );
      }
    });
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
  }

  $('.headerbar .multiselect').multiselect({
    buttonText: function(options) {
      if (options.length === 0 || options.length === ($('.headerbar .multiselect-container li').length - 1)) {
        return '<span>' + all_vocabs + ' <b class="caret"></b></span>'; 
      } else {
        if (options.length > this.numberDisplayed) {
          return '<span>' + options.length + ' ' + n_selected + ' <b class="caret"></b></span>';
        } else {
          var selected = '';
          options.each(function() {
            var label = ($(this).attr('label') !== undefined) ? $(this).attr('label') : $(this).html();

            selected += label + ', ';
          });
          return '<span>' + selected.substr(0, selected.length - 2) + ' <b class="caret"></b></span>';
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
      this.vocabSelectionString = updateVocabParam();
    },
    maxHeight: 300 
  });
  
  $('.sidebar-grey .multiselect').multiselect({
    buttonText: function(options) {
      if (options.length === 0) {
        return  '<span>' + ' </span><b class="caret"></b>'; 
      } else {
        var selected = '';
        options.each(function() {
          var label = ($(this).attr('label') !== undefined) ? $(this).attr('label') : $(this).html();
          if (label !== '') { selected += label + ', '; }
        });
        return '<span>' + selected.substr(0, selected.length - 2) + ' </span><b class="caret"></b>';
      }
    },
    numberDisplayed: 2,
    buttonWidth: 'auto',
    onDropdownShown: function(event) { 
      var $activeChild = $(event.currentTarget).find('.active');
      $('.multiselect-container').mCustomScrollbar('scrollTo', $activeChild); 
    },
    maxHeight: 300 
  });

  if ($('#groups.active').length === 1 || ( $('#alpha.active').length === 1 && $('.alphabetic-search-results').length === 1) ) {
    $(".sidebar-grey").mCustomScrollbar({ 
      alwaysShowScrollbar: 1,
      scrollInertia: 0, 
      mouseWheel:{ preventDefault: true, scrollAmount: 105 },
      snapAmount: 15,
      snapOffset: 1,
      callbacks: { alwaysTriggerOffsets: false, onTotalScroll: alphaWaypointCallback, onTotalScrollOffset: 300 }
    });
  }

  /*  activating the custom scrollbars only when not on the hierarchy page
   *  since that goes haywire if it's done before the ajax complete runs
   */
  if ($('#vocab-info').length === -1 && document.URL.indexOf('/page/') === -1 && $('.search-count').length === 0) {
    $(".sidebar-grey").mCustomScrollbar({ 
      alwaysShowScrollbar: 1,
      scrollInertia: 0, 
      mouseWheel:{ scrollAmount: 105 },
      snapAmount: 15,
      snapOffset: 1
    });
  } 
  
  /* adding the replaced by concept href to the alert box when possible.
   */
  var $replaced = $('.replaced-by');
  if ($replaced.length === 1) {
    var $replacedSpan = $('.replaced-by span'); 
    var undoUppercasing = $replacedSpan.text().substr(0,1) + $replacedSpan.text().substr(1).toLowerCase();
    var html = '<h2 class="alert-replaced">' + undoUppercasing + ':<a href="' + $('.replaced-by a')[0] + '">' + $('.replaced-by a').html() + '</h2>';
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
    $('.sidebar-grey').empty().append('<div class="loading-spinner"><span class="spinner-text">'+ loading_text + '</span><span class="spinner" /></div>');
    $.ajax({
      url : urlLangCorrected,
      success : function(data) {
        $('#sidebar').empty();
        $('#sidebar').append($(data).find('#sidebar')[0].innerHTML);
      }
    });
  }

  var searchOptions = $('.search-options');
  if (searchOptions.length === 1) {
    var parentLimitReady = true;
    $(document).on('click', '#remove-limits', function() {
      $('#type-limit').val('');
      $('#type-limit').multiselect('refresh');
      $('#parent-limit').attr('data-uri', '');
      $('#parent-limit').val('');
      $('#group-limit').val('');
      $('#group-limit').multiselect('refresh');
      loadLimitedResults(loadLimitations());
    });

    $('#parent-limit').focus(function() {
      if($('#parent-limit').attr('data-uri') !== '') {
        parentLimitReady = true;
      } else {
        parentLimitReady = false;
      }
    });
    
    $(document).on('submit', '.search-options', function() {
      if (parentLimitReady) { loadLimitedResults(loadLimitations()); }
      return false;
    });

    $('.multiselect-container').mCustomScrollbar({ 
      alwaysShowScrollbar: 1,
      scrollInertia: 0, 
      mouseWheel:{ scrollAmount: 60 },
      snapAmount: 20,
      snapOffset: 1
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
    }).on('typeahead:cursorchanged', function() {
      $('.tt-dropdown-menu').mCustomScrollbar("scrollTo", '.tt-cursor');
    }).on('typeahead:selected', onSelection).bind('focus', function() {
      $('#search-field').typeahead('open'); 
    });
  }

  // setting the focus to the search box on default if we are not on the search results page
  if ($('.search-result-listing').length === 0) { $("#search-field").focus(); }

});

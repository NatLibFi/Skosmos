/* 
 * Copyright (c) 2012 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

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

$(function() { // DOCUMENT READY 

  var spinner = '<div class="loading-spinner"><span class="spinner-text">'+ loading_text + '</span><span class="spinner" /></div>';

  var selectedVocabs = [];
  var vocabSelectionString = readCookie('SKOSMOS_SELECTED') ? readCookie('SKOSMOS_SELECTED') : '';
  $('#selected-vocabs').val(vocabSelectionString);

  // Shortens the properties that don't fit on one row on the search result view.
  function shortenProperties() {
    $properties = $('.property-values');
    for (var i = 0; i < $properties.length; i++) {
      var $property = $($properties[i]);
      if ($property.height() > 24) {
        $property.addClass('shortened-property');
        var count = $property.children('.value').length;
        var uri = $property.parent().siblings('.prefLabel')[0].href;
        var shortened = '<a href="' + uri +'" class="versal shortened-symbol" style="">... (' + count +')</a>';
        $property.parent().append(shortened);
      }
    }
  }

  shortenProperties();

  // kills the autocomplete after a form submit so we won't have to wait for the ajax to complete.
  $('.navbar-form').submit(
    function(event) {
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
      position: { my: 'bottom center', at: 'top center' },
      style: { classes: 'qtip-tipsy qtip-skosmos' } 
    });
    if (settings.url.indexOf('groups') !== -1 || settings.url.indexOf('index') !== -1) {
      $('.sidebar-grey').removeClass(function(index, classes) {
        var elementClasses = classes.split(' ');
        var removeThese = [];

        $.each(elementClasses, function() {
          if(this.match(/jstree*/))
          removeThese.push(this);
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
    if (settings.url.indexOf('topConcepts') !== -1 || settings.url.indexOf('index') !== -1 || settings.url.indexOf('groups') !== -1 /* || settings.url.indexOf('hierarchy') !== -1 */ ) {
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
    if (settings.url.indexOf('search') !== -1 && $autocomplete.length > 0 && $autocomplete[0].offsetHeight === 302)
      $(".tt-dropdown-menu").mCustomScrollbar({ alwaysShowScrollbar: 1, scrollInertia: 0 });
    countAndSetOffset();
  });

  // if on the search results page and there is only one result 
  if ($('.concept-info').length === 1) { 
    invokeParentTree(getTreeConfiguration()); 
  }

  var textColor = $('.search-parameter-highlight').css('color');
  countAndSetOffset();

  if(parts.indexOf('search') == -1) { // Disabled if on the search results page.
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

  // Calculates and sets how many vertical pixels the sidebar height should be at the current scroll position.
  function countAndSetOffset() {
    /* calculates the sidebars content maximum height and sets it as an inline style.
       the .css() can't set important so using .attr() instead. */
    $('.sidebar-grey').attr('style', function() {
      var pixels = $('.nav-tabs').height() + 2; // the 2 pixels are for the borders
      if ($('.pagination').is(':visible'))
        pixels += $('.pagination').height();
      return 'height: calc(100% - ' + pixels + 'px) !important';
    });
    if ($('#sidebar').length && !$('#sidebar').hasClass('fixed')) {
      var yOffset = window.innerHeight - ( $('#sidebar').offset().top - pageYOffset);
      $('#sidebar').css('height', yOffset);
    }
  }

  // Debounce function from underscore.js
  function debounce(a,b,c){var d;return function(){var e=this,f=arguments;clearTimeout(d),d=setTimeout(function(){d=null,c||a.apply(e,f)},b),c&&!d&&a.apply(e,f)}}

  var sidebarResizer = debounce(function() {
    countAndSetOffset();
  }, 40);

  // Event handler for mutilating the sidebar css when the user scrolls the headerbar out of the view.
  if ($('.sidebar-grey').length > 0) {
    $(window).on('scroll', sidebarResizer);

    var sidebarFixed = false;
    // the handler listens to headerbars position so it works correctly after the sidebar is hidden/shown again.
    $('.headerbar').waypoint(function(direction) {
      if (!sidebarFixed && direction == 'down') {
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

  // ajaxing the concept count and the preflabel counts on the vocabulary front page
  if ($('#vocab-info').length) {
    $.ajax({
      url : rest_base_url + vocab + '/count',
      success : function(data) {
        $spinner = $('.vocab-info-literals .spinner');
        $spinner.after(data.concepts);
        $spinner.detach();
      }
    });
    
    $.ajax({
      url : rest_base_url + vocab + '/labelCount',
      success : function(data) {
        var stats = '';
        for (i = 0; i < data.values.length; i++) {
          var row = data.values[i];
          // the preflabel is the first property of a new lang so creating a new tr.
          if (row.prop === 'skos:prefLabel') {
            if (i > 0)
              stats += '</tr>';
            stats += '<tr><td class="versal">' + row.lang + '</td>';
          }
          stats += '<td class="versal">' + row.count + '</td>';
        }
        stats += '</tr>';
        $('#statistics tr:nth-of-type(2)').detach();
        $('#statistics tr:nth-of-type(1)').after(stats);
      }
    });
  }

  function loadPage(targetUrl) {
    if (targetUrl.indexOf('index') !== -1 || targetUrl.indexOf('groups') !== -1) {
      window.location.replace(targetUrl);
    }
    $.ajax({
      url : targetUrl,
      success : function(data) {
        $('#jstree-leaf-proper').attr('id', '');
        $('.activated-concept').removeClass('activated-concept');
        $('.jstree-clicked').removeClass('jstree-clicked'); 
        $('.content').empty();
        var title = $(data).filter('title').text();
        var response = $('.content', data).html();
        document.title = title;
        $('.content').append(response);
        var uri = $('.uri-input-box').text();
        $('a[href="' + uri + '"]').addClass('jstree-clicked');
      }
    });
  }
  
  $(window).on("popstate", function(e) {
    if (e.originalEvent.state !== null) {
      loadPage(e.originalEvent.state.url);
    }
    else
      loadPage(document.URL);
  });

  // event handler for clicking the hierarchy concepts
  $(document).on('click', '.jstree-no-icons a',
      function(event) {
        event.preventDefault();
        var base_path = path_fix.length / 3;
        var clicked = $(this);
        var $content = $('.content');
        var targetUrl = 'http://' + base_url + vocab + '/' + lang + '/page/?uri=' + encodeURIComponent(event.target.href);
        var parameters = $.param({'base_path' : base_path});
        $('#hier-trigger').attr('href', targetUrl);
        $.ajax({
            url : targetUrl,
            data: parameters,
            success : function(data) {
              $('#jstree-leaf-proper').attr('id', '');
              clicked.attr('id', 'jstree-leaf-proper');
              $content.empty();
              var title = $(data).filter('title').text();
              var response = $('.content', data).html();
              document.title = title;
              if (window.history.pushState)
                window.history.pushState({url: targetUrl}, '', targetUrl);
              $content.append(response);
              $.each($('#language > a'), function(index, val) {
                var btn_lang = $(val).attr('id');
                btn_lang = (btn_lang.substr(btn_lang.indexOf('-')+1));
                var url = targetUrl.replace('/' + lang + '/', '/' + btn_lang +'/');
                $(val).attr('href', url);
              });
            }
        });
        return false;
      }
  );
  
  // event handler for clicking the alphabetical/group index concepts
  $(document).on('click', '.side-navi a',
      function(event) {
        $.ajaxQ.abortAll();
        var base_path = path_fix.length / 3;
        var clicked = $(this);
        $('.activated-concept').removeClass('activated-concept');
        clicked.addClass('activated-concept');
        var $content = $('.content');
        var targetUrl = event.target.href;
        var parameters = $.param({'base_path' : base_path});
        var hierButton = '<li id="hierarchy"><a id="hier-trigger" href="#">' + hiertrans + '</a></li>';
        $.ajax({
            url : targetUrl,
            data: parameters,
            success : function(data) {
              $content.empty();
              var title = $(data).filter('title').text();
              var response = $('.content', data).html();
              document.title = title;
              if (window.history.pushState)
                window.history.pushState(null, null, encodeURI(event.target.href));
              $content.append(response);
              if (!$('#hierarchy').length)
                $('#alpha').after(hierButton);
              $('#hier-trigger').attr('href', event.target.href);
              $.each($('#language > a'), function(index, val) {
                var btn_lang = $(val).attr('id');
                btn_lang = (btn_lang.substr(btn_lang.indexOf('-')+1));
                var url = encodeURI(event.target.href).replace('/' + lang + '/', '/' + btn_lang +'/');
                $(val).attr('href', url);
              });
            }
        });
        return false;
      }
  );
  
  // event handler for clicking the alphabetical index tab 
  $(document).on('click', '.nav-tabs a[href$="index"]',
      function(event) {
        $.ajaxQ.abortAll();
        var base_path = path_fix.length / 3;
        $('.active').removeClass('active');
        var clicked = $(this);
        clicked.parent().addClass('active');
        var $content = $('#sidebar');
        var $hier = $('#hierarchy');
        $('.sidebar-grey').empty().prepend(spinner);
        var targetUrl = event.target.href;
        var parameters = $.param({'base_path' : base_path});
        $.ajax({
            url : targetUrl,
            data: parameters,
            success : function(data) {
              $content.empty();
              var title = $(data).filter('title').text();
              var response = $('#sidebar', data).html();
              $content.append(response);
              if ($('#hierarchy').length === 1)
                $('#hierarchy').remove();
              $('#alpha').after($hier);
              $('.nav').scrollTop(0);
              if (window.history.pushState)
                window.history.pushState(null, null, encodeURI(event.target.href));
              document.title = title;
            }
        });
        return false;
      }
  );

  // if we are on the vocab front page initialize the hierarchy view with a top concept.
  $(document).on('click', '#hier-trigger', 
    function (event) {
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
    var redirectUrl = 'http://' + base_url + vocab + '/' + lang + '/page/' + uri.split('/')[uri.split('/').length-1];
    window.location.replace(encodeURI(redirectUrl));
  });
  
  // event handler for clicking the group index tab 
  $(document).on('click', '.nav-tabs a[href$="groups"]',
      function(event) {
        $.ajaxQ.abortAll();
        var base_path = path_fix.length / 3;
        $('.active').removeClass('active');
        var $clicked = $(this);
        $clicked.parent().addClass('active');
        var $pagination = $('.pagination');
        if ($pagination)
          $pagination.hide();
        var $hier = $('#hierarchy');
        var $content = $('#sidebar');
        $('.sidebar-grey').empty().removeClass('sidebar-grey-alpha').prepend(spinner);
        $content.removeClass('sidebar-grey-alpha');
        var targetUrl = event.target.href;
        var parameters = $.param({'base_path' : base_path});
        $.ajax({
            url : targetUrl,
            data: parameters,
            success : function(data) {
              $content.empty();
              var title = $(data).filter('title').text();
              var response = $('#sidebar', data).html();
              $content.append(response);
              if ($('#hierarchy').length === 1)
                $('#hierarchy').remove();
              $('#alpha').after($hier);
              $('.nav').scrollTop(0);
              if (window.history.pushState)
                window.history.pushState(null, null, encodeURI(event.target.href));
              document.title = title;
            }
        });
        return false;
      }
  );
 
  // event handler for clicking groups
  $(document).on('click','.group-index > li > a',
      function(event) {
        $.ajaxQ.abortAll();
        var base_path = path_fix.length / 3;
        var clicked = $(this);
        var $content = $('#sidebar');
        $('.sidebar-grey').empty().prepend(spinner);
        var targetUrl = event.target.href;
        var parameters = $.param({'base_path' : base_path});
        $.ajax({
            url : targetUrl,
            data: parameters,
            success : function(data) {
              $content.empty();
              var title = $(data).filter('title').text();
              var response = $('#sidebar', data).html();
              $content.append(response);
              $('.nav').scrollTop(0);
              if (window.history.pushState)
                window.history.pushState(null, null, encodeURI(event.target.href));
              document.title = title;
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
          alpha_offcount = 1;
          alpha_complete = false;
          var $pagination = $('.pagination');
          var base_path = path_fix.length / 3;
          var $content = $('.sidebar-grey');
          $content.empty().prepend(spinner);
          var targetUrl = event.target.href;
          var parameters = $.param({'base_path' : base_path});
          $.ajax({
            url : targetUrl,
            data: parameters,
            success : function(data) {
              $('#sidebar').empty();
              var title = $(data).filter('title').text();
              var response = $('#sidebar', data).html();
              $('#sidebar').append(response);
              $('.nav').scrollTop(0);
              if (window.history.pushState)
                window.history.pushState(null, null, encodeURI(event.target.href));
              document.title = title;
            }
          });
        } else {
          var selectedLetter = $(event.target).text().trim();
          if (document.getElementsByName(selectedLetter).length === 0)
            return false;
          var offset = $('li[name=' + selectedLetter + ']').offset().top - $('body').offset().top - 5;
          $('.nav').scrollTop(offset);
        }
        return false;
      }
  );

  // sets the language cookie for 365 days
  function setLangCookie(lang) {
    createCookie('SKOSMOS_LANGUAGE', lang, 365);
  }
  
  // Event handlers for the language selection links for setting the cookie
  $('#language a').each( function(index, el) { 
    $(el).click(function() { 
      var langCode = el.id.substr(el.id.indexOf("-") + 1);
      setLangCookie(langCode);
    }); 
  });
  
  $('.search-hint').qtip({ 
    position: { my: 'top center', at: 'bottom center' },
    style: { classes: 'qtip-tipsy qtip-skosmos' } 
  });
  
  $('#navi4').qtip({ 
    position: { my: 'top center', at: 'bottom center' },
    style: { classes: 'qtip-tipsy qtip-skosmos' } 
  });
    
  $('.property-click').qtip({ 
    position: { my: 'bottom center', at: 'top center' },
    style: { classes: 'qtip-tipsy qtip-skosmos' } 
  });
  
  // Setting the language parameters according to the cookie if found.
  var search_lang = readCookie('SKOSMOS_SEARCH_LANG');
  if (search_lang) 
    $('#lang-input')[0].value = search_lang;
  
  // taking the url parameters given by the controller 
  // into parts used for determining if we are on the search listings
  parts = parts.split('/'); // splits pathname, e.g.
  
  var rest_url = rest_base_url; 
  if (rest_url.indexOf('..') == -1 && rest_url.indexOf('http') == -1) { rest_url = encodeURI(location.protocol + '//' + rest_url); }
  
  // qlang is used in REST queries as a parameter. it is either
  // - a language code, e.g. "en", when searching in a specific language
  // - "" when searching in all languages
  var qlang = search_lang;
  
  // setting the focus to the search box on default
  $("#search-field").focus();

  if (search_lang === 'anything' || !search_lang || (typeof getUrlParams().lang !== 'undefined' && getUrlParams().lang === '')) {
    $('#lang-dropdown-toggle').html($('.lang-button-all').html() + ' <span class="caret"></span>');
    $('#lang-input').val('');
    qlang = "";
  } else if (!search_lang){
      var langPretty = $('a[hreflang=' + lang + ']').html();
      search_lang = lang;
      if (!langPretty)
        langPretty = $('a[hreflang="anything"]').html();
      $('#lang-dropdown-toggle').html(langPretty + ' <span class="caret"></span>');
      qlang = lang;
  } else {
      var langPretty = $('a[hreflang=' + search_lang + ']').html();
      if (!langPretty)
        langPretty = $('a[hreflang=""]').html();
      $('#lang-dropdown-toggle').html(langPretty + ' <span class="caret"></span>');
  }

  var search_lang_possible = false;
  $.each($('.input-group-btn a'), function(index, value) { 
    if(value.hreflang === search_lang)
      search_lang_possible = true;
  });
  
  if (!search_lang_possible) {
    var langPretty = $('a[hreflang=""]').html();
    $('#lang-dropdown-toggle').html(langPretty + ' <span class="caret"></span>');
    qlang = '';
    createCookie('SKOSMOS_SEARCH_LANG', qlang, 365);
  }

  $('.lang-button').click(function() {
    qlang = $(this)[0].attributes.hreflang.value;
    if (qlang === '')
      qlang = 'anything';
    $('#lang-dropdown-toggle').html($(this).html() + ' <span class="caret"></span>');
    $('#lang-input').val(qlang);
    createCookie('SKOSMOS_SEARCH_LANG', qlang, 365);
    if (concepts)
      concepts.clear();
  });
  
  $('.lang-button-all').on('click', function() {
    qlang = "";
    createCookie('SKOSMOS_SEARCH_LANG', 'anything', 365);
    $('#lang-input').val('');
    $('#lang-dropdown-toggle').html($('.lang-button-all').html() + ' <span class="caret"></span>');
    if (concepts)
      concepts.clear();
  });

  $('.lang-button, .lang-button-all').click(function() {
    $('#search-field').focus();
  });
  
  var searchTerm = "";
  // calls for another function to highlight search term in the labels.
  if (getUrlParams().q) {
    localSearchHighlight(decodeURI(getUrlParams().q.replace(/\*/g, '')));
    searchTerm = decodeURI(getUrlParams().q);
  }
  
  var NoResultsLabel = [ {
    "label" : noResultsTranslation,
    "vocab" : ""
  } ];
 
  // disables the button with an empty search form 
  $('#search-field').keyup(function() {
    var empty = false;
    $('#search-field').each(function() {
      if ($(this).val().length === 0) {
        empty = true; }
    });

    if (empty) {
      $('#search-all-button').attr('disabled', 'disabled');
    } else {
      $('#search-all-button').attr('disabled', false);
    }
  });

  function onSelection($e, datum) {
    if ($e.currentTarget.id !== 'parent-limit') {
      var localname = datum.localname;
      if (!localname || encodeURIComponent(localname) != localname) {
        localname = "?uri=" + datum.uri;
      }
      // replaced complex logic with path_fix that should always work.
      if (datum.type && datum.type.indexOf('Collection') !== -1) {
        location.href = encodeURI(path_fix + datum.vocab + '/' + lang + '/groups/' + localname);
      } else {
        location.href = encodeURI(path_fix + datum.vocab + '/' + lang + '/page/' + localname);
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
      if (type.label)
        typeLabels[type.uri] = type.label;
    }
  }

  // fetch the json from local storage if it has been already cached there.
  var typeJSON = lscache.get('types:' + lang);
  if (typeJSON) { 
    processTypeJSON(typeJSON); 
  } else { // if not then ajax the rest api and cache the results.
    var typeParam = $.param({'lang' : lang });
    var typeUrl = rest_url + 'types';
    var typeJson = $.getJSON(typeUrl, typeParam, function(response) {
      lscache.set('types:' + lang, response, 1440);
      processTypeJSON(response);
    });
  }

  var wildcard = '';
  
  var concepts = new Bloodhound({
    remote: { 
      url: rest_url + 'search?query=',
      replace: function(url, query) {
        var wildcard = (query.indexOf('*') === -1) ? '*' : '';
        return url + encodeURIComponent(query) + wildcard;
      },
      ajax: {
        beforeSend: function(jqXHR, settings) {
          wildcard = ($('#search-field').val().indexOf('*') === -1) ? '*' : '';
          var vocabString = $('.frontpage').length ? vocabSelectionString : vocab; 
          var parameters = $.param({'vocab' : vocabString, 'lang' : qlang, 'labellang' : lang});
          settings.url = settings.url + '&' + parameters;
        }
      },
      // changes the response so it can be easily displayed in the handlebars template.
      filter: function(data) {
        var context = data['@context'];
        return ($.map(data.results.filter(
          function(item) {
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
            if (item.matchedPrefLabel) {
              item.label = item.matchedPrefLabel;
              delete item.prefLabel;
            }
            if (item.altLabel)
              item.replaced = item.altLabel;
            if (item.hiddenLabel)
              item.replaced = item.hiddenLabel;
            // do not show the label language when it's same or in the same subset as the ui language.
            if (item.lang && (item.lang === lang || item.lang.indexOf(lang + '-') === 0))
              delete(item.lang);
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
              if (toBeRemoved !== null)
                item.type.splice(toBeRemoved, 1);
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
    '{{# if replaced }}<p class="replaced">{{replaced}}</p>{{/if}}',
    '{{# if replaced }}{{# if lang}}<p>({{lang}})</p>{{/if}}<p> &rarr; </p>{{/if}}',
    '<p class="autocomplete-label">{{label}}{{# if lang}}{{# unless replaced }}<p>({{lang}})</p>{{/unless}}{{/if}}</p>',
    '{{# if type }}<span class="concept-type">{{type}}</span>{{/if}}',
    '<div class="vocab">{{vocabLabel}}</div>',
  ].join('');

  $('#search-field').typeahead({ hint: false, highlight: true, minLength: autocomplete_activation },
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
  }).on('typeahead:cursorchanged', function($e) {
    $('.tt-dropdown-menu').mCustomScrollbar("scrollTo", '.tt-cursor');
  }).on('typeahead:selected', onSelection).bind('focus', function() {
    $('#search-field').typeahead('open'); 
  });

  // Some form validation for the feedback form
  $("#send-feedback")
  .click(
    function() {
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
  var number_of_hits = document.getElementsByClassName("search-result").length;
  var $ready = $("<p class='search-count'>" + results + " " + number_of_hits + " " + results_disp +"</p>");
  
  // search-results waypoint
  if (parts[parts.length-1].indexOf('search') !== -1 && number_of_hits !== 0) { // if we are in the search page with some results
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
      var parameters = $.param({'offset' : 250});
      var letter = '/' + $('.pagination > .active > a')[0].innerHTML;
      $.ajax({
        url : 'http://' + base_url + vocab + '/' + lang + '/index' + letter,
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
    var number_of_hits = document.getElementsByClassName("search-result").length;
    if (number_of_hits >= waypoint_results * offcount)
      $('.search-result-listing').append($loading);
    var typeLimit = $('#type-limit').val();
    var groupLimit = $('#group-limit').val();
    var parentLimit = $('#parent-limit').attr('data-uri');
    var parameters = $.param({'q' : searchTerm, 'vocabs' : vocabSelectionString, 'offset' : offcount * waypoint_results, 'lang' : decodeURI(getUrlParams().lang), 'type' : typeLimit, 'group' : groupLimit, 'parent': parentLimit});
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
        $ready = $("<p class='search-count'>" + results + " " + document.getElementsByClassName("search-result").length + " " + results_disp +"</p>");
        offcount++;
        if (getUrlParams().q) {
          localSearchHighlight(decodeURI(getUrlParams().q.replace(/\*/g, "")));
        }
        shortenProperties();
        $('.search-result:nth-last-of-type(4)').waypoint(function() { waypointCallback(); }, options );
      }
    });
  }

  // activating the custom autocomplete 
  function updateVocabParam() {
    vocabSelectionString = '';
    $vocabs = $('li.active input');
    $.each($vocabs, 
      function(index, ob) { 
        if (ob.value === 'multiselect-all') {
          $('input[value=multiselect-all]', $('.multiselect-all')).click();
          return false;
        }
        vocabSelectionString += ob.value; 
        if (index < $vocabs.length - 1)
          vocabSelectionString += ' ';
    });
    // sets the selected vocabularies cookie for the frontpage search.
    createCookie('SKOSMOS_SELECTED', vocabSelectionString, 365);
    $('#selected-vocabs').val(vocabSelectionString);
  }

  // preselecting the vocabularies from the cookie for the multiselect dropdown plugin.
  $.each(vocabSelectionString.split(' '), function(index, vocabId) {
    $('option[value="' + vocabId + '"]').prop('selected', 'true');
  });

  $('.headerbar .multiselect').multiselect({
    buttonText: function(options) {
      if (options.length === 0)
        return  '<span>' + all_vocabs + ' <b class="caret"></b></span>'; 
      else {
        if (options.length > this.numberDisplayed) {
          return '<span>' + options.length + ' ' + n_selected + ' <b class="caret"></b></span>';
        }
        else {
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
      if (element)
        vocabId = element[0].value;
      else
        vocabId = '';
      if (checked && selectedVocabs[vocabId] === undefined)
        selectedVocabs[vocabId] = vocabId;
      else if (selectedVocabs[vocabId] !== undefined) {
        delete selectedVocabs[vocabId];
      } 
      this.vocabSelectionString = updateVocabParam();
    },
    maxHeight: 300 
  });
  
  $('.sidebar-grey .multiselect').multiselect({
    buttonText: function(options) {
      if (options.length === 0)
        return  '<span>' + ' </span><b class="caret"></b>'; 
      else {
        var selected = '';
        options.each(function() {
          var label = ($(this).attr('label') !== undefined) ? $(this).attr('label') : $(this).html();

          selected += label + ', ';
        });
        return '<span>' + selected.substr(0, selected.length - 2) + ' </span><b class="caret"></b>';
      }
    },
    numberDisplayed: 2,
    buttonWidth: 'auto',
    onChange: function(element, checked) {
    },
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
  $replaced = $('.replaced-by');
  if ($replaced.length === 1) {
    var $replacedSpan = $('.replaced-by span'); 
    var undoUppercasing = $replacedSpan.text().substr(0,1) + $replacedSpan.text().substr(1).toLowerCase();
    var html = '<h2 class="alert-replaced">' + undoUppercasing + ':<a href="' + $('.replaced-by a')[0] + '">' + $('.replaced-by a').html() + '</h2>';
    $('.alert-danger').append(html);
  } 

  /* makes an AJAX query for the alphabetical index contents when landing on 
   * the vocabulary home page.
   */
  if ($('#vocab-info').length == 1 && $('.alphabetical-search-results').length === 0) {
    // taking into account the possibility that the lang parameter has been changed by the WebController.
    var urlLangCorrected = '//' + base_url + vocab + '/' + lang + '/index?limit=250&offset=0';
    $('.sidebar-grey').empty().append('<div class="loading-spinner"><span class="spinner-text">'+ loading_text + '</span><span class="spinner" /></div>');
    $.ajax({
      url : urlLangCorrected,
      success : function(data) {
        $('#sidebar').empty();
        $('#sidebar').append($(data).find('#sidebar')[0].innerHTML);
      }
    });
  }

  function loadLimitations() {
    $('#alphabetical-menu').detach();
    var $loading = $("<div class='search-result'><p>" + loading_text + "&hellip;<span class='spinner'/></p></div>"); 
    $('.search-result-listing').empty();
    $('.search-result-listing').append($loading);
    var typeLimit = $('#type-limit').val();
    var groupLimit = $('#group-limit').val();
    var parentLimit = $('#parent-limit').attr('data-uri');
    var parameters = $.param({'type' : typeLimit, 'group' : groupLimit, 'parent': parentLimit});
    $.ajax({
        data: parameters,
        success : function(data) {
         var targetUrl = this.url;
          var title = $(data).filter('title').text();
          var response = $('.search-result-listing', data).html();
          document.title = title;
          if (window.history.pushState)
            window.history.pushState({url: targetUrl}, '', targetUrl);
          $('.search-result-listing').append(response);
          $loading.detach();
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
      loadLimitations();
    });

    $('#parent-limit').focus(function() {
      if($('#parent-limit').attr('data-uri') !== '')
        parentLimitReady = true;
      else
        parentLimitReady = false;
    });
    $(document).on('submit', '.search-options', function() {
      if (parentLimitReady)
        loadLimitations();
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
    }).on('typeahead:cursorchanged', function($e) {
      $('.tt-dropdown-menu').mCustomScrollbar("scrollTo", '.tt-cursor');
    }).on('typeahead:selected', onSelection).bind('focus', function() {
      $('#search-field').typeahead('open'); 
    });
  }

});

/* 
 * Copyright (c) 2012 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

function customAutocomplete() {
    $.ui.autocomplete.prototype._renderItem = function (ul, item) {
        var label = item.label;
        var vocab = '';
        if (label.indexOf('@') != -1) {
          vocab = label.substring(label.indexOf('@'), label.length);
          item.label = label.substring(0, label.indexOf('@')); 
        }
        var output = item.label + '<span class="autocomplete-vocab">' + vocab + '</span>';
        
        if (item.matchedPrefLabel) {
           output = item.matchedPrefLabel + ' (' + vocab.substring(vocab.indexOf('@')+2,vocab.length) + ') → ' + item.label;
        }

        return $("<li>")
            .append($("<a>").html(output))
            .appendTo(ul);
    };
}

$(function() { // DOCUMENT READY 

  var spinner = '<div class="loading-spinner"><span class="spinner-text">'+ loading_text + '</span><span class="spinner" /></div>';

  var selectedVocabs = [];
  var vocabSelectionString = readCookie('SKOSMOS_SELECTED') ? readCookie('SKOSMOS_SELECTED') : '';
  $('#selected-vocabs').val(vocabSelectionString);

  // Shortens the properties that don't fit on one row on the search result view.
  function shortenProperties() {
    $properties = $('.property');
    for (var i = 0; i < $properties.length; i++) {
      var $property = $($properties[i]);
      if ($property.height() > 24) {
        $property.addClass('shortened-property');
        var count = $property.children('.value').length;
        var uri = $property.siblings('.prefLabel')[0].href;
        var shortened = '<a href="' + uri +'" class="versal shortened-symbol" style="">... (' + count +')</a>';
        $property.append(shortened);
      }
    }
  }

  shortenProperties();

  // kills the autocomplete after a form submit so we won't have to wait for the ajax to complete.
  $('.navbar-form').submit(
    function(event) {  
      $('#search-field').autocomplete('option','disabled', 'true');
    }
  );


  /*
   * Moving the sidenav scrollbar towards the current concept. Aiming the current
   * concept at vertical center of the container. Each concept needs 18px height.
   */
  $(document).ajaxComplete(function(event, xhr, settings) {
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
    }
    //$('.sidebar-grey').mCustomScrollbar('update');
    // Sidenav actions only happen when doing other queries than the autocomplete.
    if (settings.url.indexOf('index') !== -1 || settings.url.indexOf('groups') !== -1 || settings.url.indexOf('hierarchy') !== -1) {
      var snap = (settings.url.indexOf('hierarchy') !== -1) ? 18 : 15;
      countAndSetOffset();
      $(".sidebar-grey").mCustomScrollbar({ 
        scrollInertia: 0, 
        mouseWheel:{ scrollAmount: 105 },
        snapAmount: snap,
        snapOffset: 1
      });
      if (settings.url.indexOf('hierarchy') !== -1)
        $(".sidebar-grey").mCustomScrollbar('scrollTo', scrollToConcept());
    }
  });

  function scrollToConcept() {
    var containerHeight = $('.sidebar-grey').height();
    var conceptCount = Math.floor((containerHeight * 0.66) / 18);
    var scrollAmount = 18 * conceptCount;
    return $('#jstree-leaf-proper')[0].offsetTop-scrollAmount;
  }

  // if on the search results page and there is only one result 
  if ($('.concept-info').length === 1) { 
    invokeParentTree(getTreeConfiguration()); 
  }

  // if we are on the vocab front page initialize the hierarchy view with a top concept.
  $(document).on('click', '#hier-trigger', function () {
    var $content = $('.sidebar-grey');
    $content.empty().prepend(spinner);
    if($('.uri-input-box').length === 0) { // if on the vocabulary front page
      $('.active').removeClass('active');
      $('#hier-trigger').parent().addClass('active');
      $content.removeClass('sidebar-grey-alpha');
      $('.pagination').hide();
      $content.append('<div class="hierarchy-bar-tree"></div>');
      invokeParentTree(getTreeConfiguration(true)); 
      $('#hier-trigger').attr('href', '#');
      return false;
    }
    var uri = $('.uri-input-box').html();
    var redirectUrl = 'http://' + base_url + vocab + '/' + lang + '/page/' + uri.split('/')[uri.split('/').length-1];
    window.location.replace(encodeURI(redirectUrl));
  });

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
        var targetUrl = 'http://' + base_url + vocab + '/' + lang + '/page/?uri=' + event.target.href;
        var parameters = $.param({'uri' : event.target.href, 'base_path' : base_path});
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
                window.history.pushState({url: targetUrl}, '', encodeURI(targetUrl));
              $content.append(response);
            }
        });
        return false;
      }
  );
  
  // event handler for clicking the alphabetical/group index concepts
  $(document).on('click', '.side-navi a',
      function(event) {
        var base_path = path_fix.length / 3;
        var clicked = $(this);
        $('.activated-concept').removeClass('activated-concept');
        clicked.addClass('activated-concept');
        var $content = $('.content');
        var targetUrl = event.target.href;
        var parameters = $.param({'base_path' : base_path});
        var hierButton = '<li id="hierarchy"><a id="hier-trigger" href="#">Hierarkia</a></li>';
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
            }
        });
        return false;
      }
  );
  
  // event handler for clicking the alphabetical index tab 
  $(document).on('click', '.nav-tabs a[href$="index"]',
      function(event) {
        var base_path = path_fix.length / 3;
        $('.active').removeClass('active');
        var clicked = $(this);
        clicked.parent().addClass('active');
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
  
  // event handler for clicking the group index tab 
  $(document).on('click', '.nav-tabs a[href$="groups"]',
      function(event) {
        var base_path = path_fix.length / 3;
        $('.active').removeClass('active');
        var $clicked = $(this);
        $clicked.parent().addClass('active');
        var $pagination = $('.pagination');
        if ($pagination)
          $pagination.hide();
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
        if ($('.alphabet-header').length === 0) {
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

  $('.property-mouseover').tooltip().on('click', 
      function() {
        $.each($('.tooltip'), function(index, value) { $(value).siblings('.property-mouseover').tooltip('hide'); });
        if ($(this).siblings('.tooltip').length === 0)
          $(this).tooltip('show');
        else
          $(this).tooltip('hide');
        return false;
      }
  );

  $(document).on('click', '.tooltip',
      function(event) { 
        $(event.target).parent().siblings('.property-mouseover').tooltip('hide'); 
      }
  );

  // Generates property helpers as p elements or removes the helper text if it's clicked again.
  $(document).on('click','.property-click',
      function(event) {
        $.each($('.tooltip'), function(index, value) { $(value).siblings('.property-mouseover').tooltip('hide'); });
        var $property = $(this);
        if ($property.siblings('.tooltip').length === 0)
          $property.tooltip('show');
        else {
          $('.tooltip').remove();
        }
      }
  );
  
  $(document).on('mouseenter','.property-click',
    function(event) {
      var $property = $(this);
      if ($property.siblings('.tooltip').length === 0)
        $property.tooltip('show');
    }  
  );
  
  $(document).on('mouseleave','.property-click',
    function(event) {
      var $property = $(this);
      if ($property.siblings('.tooltip').length !== 0)
        $property.children('.property-mouseover').tooltip('hide');
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
  });
  
  $('.lang-button-all').on('click', function() {
    qlang = "";
    createCookie('SKOSMOS_SEARCH_LANG', 'anything', 365);
    $('#lang-input').val('');
    $('#lang-dropdown-toggle').html($('.lang-button-all').html() + ' <span class="caret"></span>');
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
        empty = true;
      }
    });

    if (empty) {
      $('#search-all-button').attr('disabled', 'disabled');
    } else {
      $('#search-all-button').attr('disabled', false);
    }
  });
  
  // activates jquery autocomplete for the search fields
  var autoC = $("#search-field").autocomplete({
    source : function(request, response) {
      // default to prefix search when no wildcards were used
      var term = request.term.trim(); // surrounding whitespace is not significant
      term = term.indexOf("*") >= 0 ? term : term + "*";
      var vocabString = $('.multiselect').length ? vocabSelectionString : vocab; 
      var parameters = $.param({'query' : term, 'vocab' : vocabString, 'lang' : qlang, 'labellang' : lang});
      $.ajax({
        url : rest_url + 'search',
        data: parameters,
        dataType : "json",
        success : function(data) {
          if (data.results.length === 0) {
            response(NoResultsLabel);
          }
          else {
            response($
              .map(
                data.results
                .filter(function(item) {
                  // either we are performing a local search
                  // or the concept is native to the vocabulary
                  return (vocab !== "" || !item.exvocab);
                }),
                function(item) {
                  var name = (item.altLabel ? item.altLabel +
                    " \u2192 " +
                    item.prefLabel : item.prefLabel);
                  if(item.hiddenLabel) 
                    name =  item.hiddenLabel + " \u2192 " + item.prefLabel;
                  item.label = name;
                  if (item.vocab && item.vocab != vocab) // if performing global search include vocabid
                    item.label += ' @' + item.vocab + ' ';
                  if (item.exvocab && item.exvocab != vocab)
                    item.label += ' @' + item.exvocab + ' ';
                  if (item.lang && item.lang !== lang) // if the label is not in the ui lang
                    item.label += ' @ ' + item.lang;
                  return item;
                }));
          }
        }
      });
    },
    delay : autocomplete_delay, // time (in milliseconds)
    // before autocomplete
    // activates i.e. sends a
    // request to the REST
    // interface
    minLength : autocomplete_activation,
    appendTo: "#header-bar-content",

    select : function(event, ui) { // what happens when
      // user clicks/uses autocomplete
      var localname = ui.item.localname;
      if (ui.item.exvocab) {
        localname = "?uri=" + ui.item.uri;
      }
      if (ui.item.label === NoResultsLabel[0].label) {
        event.preventDefault();
        return false;
      }
      // replaced complex logic with path_fix that should always work.
      if (ui.item.type && ui.item.type.indexOf('Collection') !== -1) {
        location.href = encodeURI(path_fix + ui.item.vocab + '/' + lang + '/groups/' + localname);
      } else {
        location.href = encodeURI(path_fix + ui.item.vocab + '/' + lang + '/page/' + localname);
      }
    },
    focus : function(event, ui) {
      return false; // Prevent the widget from inserting the value.
    }
  }).bind('focus', function() {
    $('#search-field').autocomplete('search'); 
  });

  $("button#send-feedback").button({
    icons : {
      primary : "ui-icon-mail-closed"
    }
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
  var offcount = 1;
  var number_of_hits = document.getElementsByClassName("search-result").length;
  var $ready = $("<p class='search-count'>" + results + " " + number_of_hits + " " + results_disp +"</p>");
  
  if (parts[parts.length-1].indexOf('search') !== -1 && number_of_hits !== 0) { // if we are in the search page with some results
    if (number_of_hits < waypoint_results * offcount) { 
      $('.search-result-listing').append($ready);
    }
    else {
      $trigger.waypoint(function() { waypointCallback(); }, options);
    }
  }

  function waypointCallback() {
    var number_of_hits = document.getElementsByClassName("search-result").length;
    if (number_of_hits >= waypoint_results * offcount)
      $('.search-result-listing').append($loading);
    var parameters = $.param({'q' : searchTerm, 'vocabs' : vocabSelectionString, 'offset' : offcount * waypoint_results, 'lang' : decodeURI(getUrlParams().lang)});
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
  customAutocomplete(); 

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

  $('.multiselect').multiselect({
    buttonText: function(options) {
      if (options.length === 0 || vocabSelectionString === '')
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
      vocabId = element[0].value;
      if (checked && selectedVocabs[vocabId] === undefined)
        selectedVocabs[vocabId] = vocabId;
      else if (selectedVocabs[vocabId] !== undefined) {
        delete selectedVocabs[vocabId];
      } 
      this.vocabSelectionString = updateVocabParam();
    },
    maxHeight: 300 
  });

  /*  activating the custom scrollbars only when not on the hierarchy page
   *  since that goes haywire if it's done before the ajax complete runs
   */
  if (document.URL.indexOf('/page/') === -1) {
    $(".sidebar-grey").mCustomScrollbar({ 
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
  if ($('#vocab-info').length == 1) {
    // taking into account the possibility that the lang parameter has been changed by the WebController.
    var urlLangCorrected = window.location.href.substr(0,window.location.href.length - 3) + lang + '/index';
    $('.sidebar-grey').empty().append('<div class="loading-spinner"><span class="spinner-text">'+ loading_text + '</span><span class="spinner" /></div>');
    $.ajax({
      url : urlLangCorrected,
      success : function(data) {
        $('#sidebar').empty();
        $('#sidebar').append($(data).find('#sidebar'));
      }
    });
  }

});

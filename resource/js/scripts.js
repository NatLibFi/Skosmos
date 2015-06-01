/* 
 * Copyright (c) 2012 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

/* exported getUrlParams, readCookie, createCookie, getUrlParams, debounce, updateContent, updateTopbarLang, updateClangButtons, updateTitle, updateSidebar, setLangCookie, loadLimitations, loadPage, hideCrumbs, shortenProperties */

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

function updateClangButtons(href) {
  var uri = URI(href).removeSearch('clang');
  $.each($('.lang-button'), function(index, val) {
    var btn_params = URI($(val).attr('href')).removeSearch('uri').search(true);
    uri.setSearch(btn_params);
    $(val).attr('href', uri.href());
  });
}

// sets the language cookie for 365 days
function setLangCookie(lang) {
  createCookie('SKOSMOS_LANGUAGE', lang, 365);
}
  
function loadLimitations() {
  $('#alphabetical-menu').detach();
  var $loading = $("<div class='search-result'><p>" + loading_text + "&hellip;<span class='spinner'/></p></div>"); 
  $('.search-result-listing').empty().append($loading);
  var typeLimit = $('#type-limit').val() ? $('#type-limit').val().join('+') : $('#type-limit').val();
  if (typeLimit && typeLimit[0] === '+') { // filtering the empty selection out of the search string
    typeLimit = typeLimit.substring(1);
  }
  var groupLimit = $('#group-limit').val();
  var parentLimit = $('#parent-limit').attr('data-uri');
  var parameters = $.param({'type' : typeLimit, 'group' : groupLimit, 'parent': parentLimit});
  $.ajax({
    data: parameters,
    success : function(data) {
      var targetUrl = this.url;
      var response = $('.search-result-listing', data).html();
      if (window.history.pushState) { window.history.pushState({url: targetUrl}, '', targetUrl); }
      $('.search-result-listing').append(response);
      $loading.detach();
      updateTitle(data);
    }
  });
}

function loadPage(targetUrl) {
  if (targetUrl.indexOf('index') !== -1 || targetUrl.indexOf('groups') !== -1) {
    $.ajax({
      url : targetUrl,
      success : function(data) {
        updateSidebar(data);
        updateTitle(data);
        updateTopbarLang(data);
      }
    });
  } else {
    $.ajax({
      url : targetUrl,
      success : function(data) {
        $('#jstree-leaf-proper').attr('id', '');
        $('.activated-concept').removeClass('activated-concept');
        $('.jstree-clicked').removeClass('jstree-clicked'); 
        updateContent(data);
        $('a[href="' + $('.uri-input-box').text() + '"]').addClass('jstree-clicked');
        updateTitle(data);
        updateTopbarLang(data);
      }
    });
  }
  updateClangButtons(targetUrl);
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


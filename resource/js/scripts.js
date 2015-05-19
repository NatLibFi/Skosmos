/* 
 * Copyright (c) 2012 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

/* exported getUrlParams, readCookie, createCookie, getUrlParams, debounce, updateContent, updateTopbarLang, updateClangButtons, updateTitle, updateSidebar */

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
  $.each($('.lang-button'), function(index, val) {
    var url;
    var btn_href = $(val).attr('href');
    // removing the last page url if this isn't the first.
    btn_href = btn_href.substr(btn_href.indexOf('clang'));
    if (href.indexOf('clang') === -1) {
      url = href + '?' + btn_href;
    } else if (btn_href.indexOf('anylang') === -1) {
      url = (href).replace(/clang=\w{2}/, 'clang=' + btn_href.substr(-2));
    } else {
      url = (href).replace(/clang=\w{2}/, btn_href);
    }
    $(val).attr('href', url);
  });
}

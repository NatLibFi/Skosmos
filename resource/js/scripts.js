/* 
 * Copyright (c) 2012 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

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

/* 
 * Define a function "getElementsByClassName" if it hasn't already been defined (for IE8 and downwards).
 */
if (typeof document.getElementsByClassName!='function') {
  document.getElementsByClassName = function() {
    var elms = document.getElementsByTagName('*');
    var ei = [];
    var i, j, ecl;
    for (i=0;i<elms.length;i++) {
      if (elms[i].getAttribute('class')) {
        ecl = elms[i].getAttribute('class').split(' ');
        for (j=0;j<ecl.length;j++) {
          if (ecl[j].toLowerCase() == arguments[0].toLowerCase()) {
            ei.push(elms[i]);
          }
        }
      } else if (elms[i].className) {
        ecl = elms[i].className.split(' ');
        for (j=0;j<ecl.length;j++) {
          if (ecl[j].toLowerCase() == arguments[0].toLowerCase()) {
            ei.push(elms[i]);
          }
        }
      }
    }
    return ei;
  };
}

function getUrlParams() {
  var params = {};
  window.location.search.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(str,key,value) {
    params[key] = value;
  });
  return params;
}

// Debounce function from underscore.js
function debounce(a,b,c){var d;return function(){var e=this,f=arguments;clearTimeout(d),d=setTimeout(function(){d=null,c||a.apply(e,f)},b),c&&!d&&a.apply(e,f)}}

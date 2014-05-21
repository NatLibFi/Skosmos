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
 * New from Rob Nitti, who credits 
 * http://bytes.com/groups/javascript/145532-replace-french-characters-form-inp
 * The code finds accented vowels and replaces them with their unaccented version. 
 * @param {string} str the input string.
 */
function stripVowelAccent(str) {
  var rExps=[ /[\xC0-\xC2]/g, /[\xE0-\xE2]/g,
  /[\xC8-\xCA]/g, /[\xE8-\xEB]/g,
  /[\xCC-\xCE]/g, /[\xEC-\xEE]/g,
  /[\xD2-\xD4]/g, /[\xF2-\xF4]/g,
  /[\xD9-\xDB]/g, /[\xF9-\xFB]/g ];

  var repChar=['A','a','E','e','I','i','O','o','U','u'];

  for(var i=0; i<rExps.length; ++i) {
    str=str.replace(rExps[i],repChar[i]);
  }
  return str;
}

/* 
 * Modification of
 * http://www.kryogenix.org/code/browser/searchhi/
 * See: 
 *   http://www.tedpavlic.com/post_highlighting_search_results_with_ted_searchhi_javascript.php
 *   http://www.tedpavlic.com/post_inpage_highlighting_example.php
 * for additional modifications of this base code. 
 */
function highlightWord(node,word,doc) {
  var found = false; // if first occasion of the searchword has been found yet
  doc = typeof(doc) != 'undefined' ? doc : document;
  // Iterate into this nodes childNodes
  if (node.hasChildNodes) {
    var hi_cn;
    for (hi_cn=0;hi_cn<node.childNodes.length;hi_cn++) {
      if(found === true) break; // once the first occasion has been found, escape from the loop
      highlightWord(node.childNodes[hi_cn],word,doc);
      found = true; // first occasion has been found
    }
  }
  // And do this node itself
  if (node.nodeType == 3) { // text node

    tempNodeVal = stripVowelAccent(node.nodeValue.toLowerCase());
    tempWordVal = stripVowelAccent(word.toLowerCase());
    if (tempNodeVal.indexOf(tempWordVal) != -1) {
      pn = node.parentNode;
      if (pn.className != "searchword") {
        // word has not already been highlighted!
        nv = node.nodeValue;
        ni = tempNodeVal.indexOf(tempWordVal);
        // Create a load of replacement nodes
        before = doc.createTextNode(nv.substr(0,ni));
        docWordVal = nv.substr(ni,word.length);
        after = doc.createTextNode(nv.substr(ni+word.length));
        hiwordtext = doc.createTextNode(docWordVal);
        hiword = doc.createElement("span");
        hiword.className = "searchword";
        hiword.appendChild(hiwordtext);
        pn.insertBefore(before,node);
        pn.insertBefore(hiword,node);
        pn.insertBefore(after,node);
        pn.removeChild(node);
      }
    }
  }
}

function localSearchHighlight(searchStr,doc) {
  doc = typeof(doc) != 'undefined' ? doc : document;
  if (!doc.createElement) return;
  if (searchStr === '') return;
  // Trim leading and trailing spaces after unescaping
  searchstr = unescape(searchStr).replace(/^\s+|\s+$/g, "");
  if( searchStr === '' ) return;
  phrases = searchStr.replace(/\+/g,' ').split(/\"/);
  // Use this next line if you would like to force the script to always
  // Search for phrases. See below as well!
  for(p=0;p<phrases.length;p++) {
    phrases[p] = unescape(phrases[p]).replace(/^\s+|\s+$/g, "");
    if( phrases[p] === '' ) continue;
    if( p % 2 === 0 ) words = phrases[p].replace(/([+,()]|%(29|28)|\W+(AND|OR)\W+)/g,' ').split(/\s+/);
    else {
      words=Array(1);
      words[0] = phrases[p];
    }
    for (w=0;w<words.length;w++) {
      if( words[w] === '' ) { continue; }
      var len=doc.getElementsByClassName("search-parameter-highlight").length;
      for(var i=0; i<len; i++) {
        highlightWord(doc.getElementsByClassName("search-parameter-highlight")[i],words[w],doc);
      }
    }
  }
}

/* 
 * Define a function "getElementsByClassName" if it hasn't already been defined (for IE8 and downwards).
 */
if (typeof document.getElementsByClassName!='function') {
  document.getElementsByClassName = function() {
    var elms = document.getElementsByTagName('*');
    var ei = [];
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

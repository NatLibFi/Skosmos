/*
 * Copyright (c) 2012 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */
var treeIndex = {}; 
var urlToUri = {};

/* 
 * For legacy browsers that don't natively support Object.size().
 * @param {Object} obj
 */
Object.size = function (obj) {
  var size = 0, key;
  for (key in obj) {
    if (obj.hasOwnProperty(key)) {
      size += 1;
    }
  }
  return size;
};

function getNode(uri) { return treeIndex[uri]; }

function setNode(node) { treeIndex[node.uri] = node; storeUri(node); }

function storeUri(node) { urlToUri[node.a_attr.href] = node.uri; }

/* 
 * Forces node to open when it's clicked.
 * @param {Object} tree
 */
function invokeParentTree(tree) {
  var $treeObject = $('.sidebar-grey');
  $treeObject.on('activate_node.jstree', function(event, node) {
    $treeObject.jstree('open_node', node.node);
  });

  $treeObject.on('loaded.jstree', function(event, node) {
    if ($('.mCustomScrollbar').length === 0) {
      $(".sidebar-grey").mCustomScrollbar({ 
        alwaysShowScrollbar: 1,
        scrollInertia: 0, 
        mouseWheel:{ scrollAmount: 105 },
        snapAmount: 18,
        snapOffset: 1
      });
    }
    if ($('.jstree-leaf-proper').length > 0) {
      $('.sidebar-grey').jstree('select_node', $('.jstree-leaf-proper').toArray());
      $('.sidebar-grey').mCustomScrollbar('scrollTo', getLeafOffset());
    }
  });
}
  
function getLeafOffset() {
  var containerHeight = $('.sidebar-grey').height();
  var conceptCount = Math.floor((containerHeight * 0.66) / 18);
  var scrollAmount = 18 * conceptCount;
  if ($('.jstree-leaf-proper').length) {
    var newOffset = $('.jstree-leaf-proper')[0].offsetTop-scrollAmount;
    if (newOffset > 0) // only scrolls the view if the concept isn't already at the top.
      return newOffset;
  }
}

/*
 * Creates a concept object from the data returned by a rest query.
 * @param
 * @param
 */
function createConceptObject(conceptUri, conceptData) {
  var prefLabel = conceptData.prefLabel; // the json narrower response has a different structure.
  var newNode = { 
    text: prefLabel, 
    a_attr: { "href" : vocab + '/' + lang + '/page/?uri=' + encodeURIComponent(conceptUri) },
    uri: conceptUri,
    parents: conceptData.broader,
    state: { opened: true },
    children: []
  };
  // setting the flag manually if the concept is known to have narrowers, but they aren't included eg. included topconcepts
  if(conceptData.hasChildren === true) {
    newNode.children = true;
    newNode.state.opened = false;
  }
  // if we are at a concept page we want to highlight that node and mark it as to be initially opened.
  if (newNode.uri === $('.uri-input-box').html()) { newNode.li_attr = { class: 'jstree-leaf-proper' }; }
  if (conceptData.notation)
    newNode.text = '<span class="tree-notation">' + conceptData.notation + '</span> ' + newNode.text;
  if (conceptData.narrower) { // filtering out the ones that don't have labels 
    var childArray = [];
    for (var child in conceptData.narrower) {
      var conceptObject = conceptData.narrower[child];
      var hasChildren = conceptObject.hasChildren; 
      var childObject = {
        text: conceptObject.label, 
        a_attr: { "href" : vocab + '/' + lang + '/page/?uri=' + encodeURIComponent(conceptData.narrower[child].uri) },
        uri: conceptData.narrower[child].uri,
        parents: conceptUri,
        state: { opened: true }
      };
      if (conceptData.narrower[child].notation)
        childObject.text = '<span class="tree-notation">' + conceptData.narrower[child].notation + '</span> ' + childObject.text;
      // if the childConcept hasn't got any children the state is not needed.
      if (hasChildren) {
        childObject.children = true;
        childObject.state.opened = false;
      }
      if(!childArray[childObject.uri])
        childArray.push(childObject);
      storeUri(childObject);
    }
    newNode.children = childArray;
  }
  
  return newNode;
}

/*
 * For building a parent hierarchy tree from the leaf concept to the ontology/vocabulary root.
 * @param {String} uri
 * @param {Object} parentData
 */
function buildParentTree(uri, parentData, schemes) {
  if (parentData === undefined || parentData === null) { return; }

  var loopIndex = 0, // for adding the last concept as a root if no better candidates have been found.
    currentNode,
    rootArray = [],
    rootNode;

  if (schemes.length > 1) {
    rootArray = schemes;
  }

  for(var conceptUri in parentData) {
    var branchHelper, 
      exactMatchFound;
    currentNode = createConceptObject(conceptUri, parentData[conceptUri]);
    /* if a node has the property topConceptOf set it as the root node. 
     * Or just setting the last node as a root if nothing else has been found 
     */
    if (parentData[conceptUri].top || ( loopIndex == Object.size(parentData)-1) && !rootNode || !currentNode.parents && !rootNode) { 
      if (!rootNode) {  
        branchHelper = currentNode;
      }
      // if there are multiple concept schemes attach the topConcepts to the concept schemes
      if (schemes.length > 1 && (parentData[conceptUri].top)) {
        for (var i in schemes) {
          if (schemes[i].uri === parentData[conceptUri].top) {
            if(Object.prototype.toString.call(schemes[i].children) !== '[object Array]' ) {
              schemes[i].children = [];
            }
            schemes[i].children.push(currentNode);
            // the hierarchy response contains the parent information before the topConcepts so it's a safe bet to open the first node 
            if (loopIndex === 0) 
              schemes[i].state = currentNode.state;
          }
        }
      }
      else {
        rootNode = currentNode; 
        rootArray.push(rootNode);
      }
    }
    if (exactMatchFound) { // combining branches if we have met a exact match during the previous iteration.
      currentNode.children.push(branchHelper); 
      branchHelper = undefined;
      exactMatchFound = false;
    }
    // here we have iterated far enough to find the merging point of the trees.
    if (branchHelper && parentData[branchHelper.uri].exact === currentNode.uri) {
      exactMatchFound = true;
    } 
    setNode(currentNode);
    loopIndex++;
  }

  // Iterating over the nodes to make sure all concepts have their children set.
  appendChildrenToParents();
  // avoiding the issue with multiple inheritance by deep copying the whole tree object before giving it to jsTree
  return JSON.parse(JSON.stringify(rootArray));
}

function vocabRoot(topConcepts) {
  var topArray = [];
  for (var i = 0; i < topConcepts.length; i++) {
    var conceptData = topConcepts[i];
    var childObject = {
      text: conceptData.label, 
      a_attr : { "href" : vocab + '/' + lang + '/page/?uri=' + encodeURIComponent(conceptData.uri) },
      uri: conceptData.uri,
      state: { opened: false } 
    };
    if (conceptData.hasChildren)
      childObject.children = true;
    setNode(childObject);
    topArray.push(childObject);
  }
  return topArray;
}

/*
 * Iterates through the tree and fixes all the parents by adding references to their child concepts.
 */
function appendChildrenToParents() {
  for (var j in treeIndex) {
    var current = treeIndex[j];
    for (var index in current.parents) {
      var parentNode = getNode(current.parents[index]);
      if (parentNode !== current)
        if (parentNode && $.inArray(current, parentNode.children) === -1) {
          for(var sibling in parentNode.children) {
            if(parentNode.children[sibling].uri === current.uri){ 
              // if the concept has already been found enrich the previous one with the additional information.
              parentNode.children[sibling].children = current.children;
              parentNode.children[sibling].state = current.state;
              parentNode.children[sibling].li_attr = current.li_attr;
            }
          }
        }
    }
  }
}

function createObjectsFromNarrowers(narrowerResponse) {
  var childArray = [];
  for (var child in narrowerResponse.narrower) {
    var conceptObject = narrowerResponse.narrower[child];
    var hasChildren = conceptObject.hasChildren; 
    var childObject = {
      text : conceptObject.prefLabel, 
      a_attr : { "href" : vocab + '/' + lang + '/page/?uri=' + encodeURIComponent(conceptObject.uri) },
      uri: conceptObject.uri,
      parents: narrowerResponse.uri,
      state: { opened: false, disabled: false, selected: false }
    };
    if (conceptObject.notation)
      childObject.text = '<span class="tree-notation">' + conceptObject.notation + '</span> ' + childObject.text;
    if (hasChildren) {
      childObject.children = true;
      childObject.state.opened = false;
    }
    setNode(childObject);
    childArray.push(childObject);
  }
  return childArray;
}

function getParams(node) {
  var nodeId;
  if (node.id === '#')
    nodeId = $('.uri-input-box').html(); // using the real uri of the concept from the view.
  else
    nodeId = node.original.uri;
  var clang = content_lang !== '' ? content_lang : lang;
  return $.param({'uri' : nodeId, 'lang' : clang});
}

function schemeRoot(schemes) {
  var topArray = [];
  for (var i = 0; i < schemes.length; i++) {
    var scheme = schemes[i];
    var label = '';
    if (scheme.prefLabel)
      label = scheme.prefLabel;
    else if (scheme.label)
      label = scheme.label;
    else if (scheme.title)
      label = scheme.title;
    if (label !== '') { // hiding schemes without a label/title
      var schemeObject = {
        text: label, 
        a_attr : { "href" : vocab + '/' + lang + '/page/?uri=' + scheme.uri, 'class': 'scheme'},
        uri: scheme.uri,
        children: true,
        state: { opened: false } 
      };
      //setNode(schemeObject);
      topArray.push(schemeObject);
    }
  }
  return topArray;
}

function topConceptsToSchemes(topConcepts, schemes) {
  var childArray = schemes.length > 1 ? schemes : [];
  for (var i in topConcepts) {
    var conceptObject = topConcepts[i];
    var hasChildren = conceptObject.hasChildren; 
    var childObject = {
      text : conceptObject.label, 
      a_attr : { "href" : vocab + '/' + lang + '/page/?uri=' + encodeURIComponent(conceptObject.uri) },
      uri: conceptObject.uri,
      state: { opened: false, disabled: false, selected: false }
    };
    if (conceptObject.notation)
      childObject.text = '<span class="tree-notation">' + conceptObject.notation + '</span> ' + childObject.text;
    if (hasChildren) {
      childObject.children = true;
      childObject.state.opened = false;
    }
    setNode(childObject);
    if (schemes.length > 1) {
      for (var j in schemes) {
        if (conceptObject.topConceptOf === schemes[j].uri) {
          if(Object.prototype.toString.call(schemes[j].children) !== '[object Array]' ) {
            schemes[j].children = [];
          }
          schemes[j].children.push(childObject);
          schemes[j].state.opened = true;
          schemes[j].a_attr.class = 'jstree-clicked';
        }
      }
    } else {
      childArray.push(childObject);
    }
  }
  return childArray;
}

/* 
 * Gives you the Skosmos default jsTree configuration.
 */
function getTreeConfiguration() {
  $('.sidebar-grey').empty().jstree({ 
    'core' : {
      'animation' : 0,
      'themes' : { 'icons': false },
      'strings' : { 'Loading ...' : jstree_loading },
      'data' : 
        function(node, cb) { 
          var clang = content_lang !== '' ? content_lang : lang;
          var json_url = (rest_base_url + vocab + '/hierarchy');
          var nodeId;
          var params = getParams(node); 
          var schemeObjects;
          $.ajax({
            data: $.param({'lang': clang}),
            url: rest_base_url + vocab + '/',
            success: function (response) {
              schemeObjects = schemeRoot(response.conceptschemes);
              // if there are multiple concept schemes display those at the top level
              if (schemeObjects.length > 1 && node.id === '#' && $('#vocab-info').length) {
                cb(schemeObjects);
              } 
              // if there was only one concept scheme display it's top concepts at the top level 
              else if(node.id === '#' && $('#vocab-info').length) { 
                $.ajax({
                  data: $.param({'lang': clang}),
                  url: rest_base_url + vocab + '/topConcepts', 
                  success: function (response) {
                    cb(vocabRoot(response.topconcepts));
                  }
                });
              }
              else {
                // top concepts of a concept scheme
                if (node.original && node.original.a_attr && node.original.a_attr.class === 'scheme') {
                  json_url = (rest_base_url + vocab + '/topConcepts');
                  params = $.param({'scheme': node.original.uri, 'lang' : clang});
                  // no longer needed at this point
                  schemeObjects = []; 
                } 
                // concept scheme page
                else if (node.id === '#' && $('tbody > tr:nth-of-type(3) p').html() === 'skos:ConceptScheme') {
                  nodeId = $('.uri-input-box').html(); // using the real uri of the concept from the view.
                  json_url = (rest_base_url + vocab + '/topConcepts');
                  params = $.param({'scheme': nodeId, 'lang' : clang});
                } 
                // concept hierarchy
                else if (node.id === '#') {
                  nodeId = $('.uri-input-box').html(); // using the real uri of the concept from the view.
                } 
                // narrowers of a concept
                else  {
                  nodeId = node.uri;
                  json_url = (rest_base_url + vocab + '/children');
                }
                $.ajax({
                  data: params,
                url: json_url, 
                success: function (response) {
                  if (response.broaderTransitive) { // the default hierarchy query that fires when a page loads.
                    cb(buildParentTree(nodeId, response.broaderTransitive, schemeObjects));
                  } else if (response.topconcepts) {
                    cb(topConceptsToSchemes(response.topconcepts, schemeObjects));
                  } else {
                    cb(createObjectsFromNarrowers(response));
                  }
                }
                });
              }
            }
          });
        }
    },
    'plugins' : ['sort'],
    'sort' : function (a,b) { return naturalCompare(this.get_text(a).toLowerCase(), this.get_text(b).toLowerCase()); }
  });
}


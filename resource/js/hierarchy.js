/*
 * Copyright (c) 2012 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */
var treeIndex = {}; 
var urlToUri = {};
var rest = rest_base_url; 

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
    if ($('#jstree-leaf-proper').length > 0) {
      $('.sidebar-grey').jstree('select_node', '#jstree-leaf-proper');
      $('.sidebar-grey').mCustomScrollbar('scrollTo', getLeafOffset());
    }
  });
}
  
function getLeafOffset() {
  var containerHeight = $('.sidebar-grey').height();
  var conceptCount = Math.floor((containerHeight * 0.66) / 18);
  var scrollAmount = 18 * conceptCount;
  if ($('#jstree-leaf-proper').length)
    return $('#jstree-leaf-proper')[0].offsetTop-scrollAmount;
}


/*
 * Creates a concept object from the data returned by a rest query.
 * @param
 * @param
 */
function createConceptObject(conceptUri, conceptData) {
  var prefLabel = conceptData.prefLabel; // the json narrower response has a different structure.
  newNode = { 
    text: prefLabel, 
    a_attr: { "href" : conceptUri },
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
  // if we are at a top concepts page we want to highlight that node and mark it as to be initially opened.
  if (newNode.uri === $('.uri-input-box').html()) { newNode.li_attr = { id: 'jstree-leaf-proper' }; }
  if (conceptData.narrower) { // filtering out the ones that don't have labels 
    var childArray = [];
    for (var child in conceptData.narrower) {
      var conceptObject = conceptData.narrower[child];
      var hasChildren = conceptObject.hasChildren; 
      var childObject = {
        text: conceptObject.label, 
        a_attr: { "href" : conceptData.narrower[child].uri },
        uri: conceptData.narrower[child].uri,
        parents: conceptUri,
        state: { opened: true }
      };
      if (child === $('.uri-input-box').html()) { childObject.data.attr.id = 'jstree-leaf-proper'; }
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
function buildParentTree(uri, parentData) {
  if (parentData === undefined || parentData === null) { return; }

  var loopIndex = 0, // for adding the last concept as a root if no better candidates have been found.
    currentNode,
    rootArray = [],
    rootNode;

    for(var conceptUri in parentData) {
    var previousNode = currentNode,
      branchHelper, 
      exactMatchFound;
    currentNode = createConceptObject(conceptUri, parentData[conceptUri]);
    /* if a node has the property topConceptOf set it as the root node. 
     * Or just setting the last node as a root if nothing else has been found 
     */
    if (parentData[conceptUri].top || ( loopIndex == Object.size(parentData)-1) && !rootNode || !currentNode.parents && !rootNode) { 
      if (!rootNode) {  
        branchHelper = currentNode;
      }
      rootNode = currentNode; 
      rootArray.push(rootNode);
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
  return rootArray;
}

function vocabRoot(topConcepts) {
  var topArray = [];
  for (var i = 0; i < topConcepts.length; i++) {
    var conceptData = topConcepts[i];
    var childObject = {
      text: conceptData.label, 
      a_attr : { "href" : conceptData.uri },
      uri: conceptData.uri,
      children: [],
      state: { opened: false } 
    };
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
            if(parentNode.children[sibling].uri == current.uri){ 
              // if the concept has already been added remove the previous one since this one is more accurate.
              parentNode.children.splice(sibling, 1);
            }
          }
          parentNode.children.push(current);
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
      a_attr : { "href" : conceptObject.uri },
      uri: conceptObject.uri,
      parents: narrowerResponse.uri,
      state: { opened: false, disabled: false, selected: false }
    };
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
  return $.param({'uri' : nodeId, 'lang' : lang});
}

/* 
 * Gives you the Skosmos default jsTree configuration.
 */
function getTreeConfiguration(root) {
  $('.sidebar-grey').empty().jstree({ 
    'core' : {
      'animation' : 0,
      'themes' : { 'icons': false },
      'data' : 
        function(node, cb) { 
          var json_url = (rest_base_url + vocab + '/hierarchy');
          if (node.id === '#') {
            nodeId = $('.uri-input-box').html(); // using the real uri of the concept from the view.
          } else  {
            nodeId = node.uri;
            json_url = (rest_base_url + vocab + '/children');
          }
          var params = getParams(node); 
          var jsondata = $.ajax({
            data: params,
            url: json_url, 
            success: function (response) {
              if (response.broaderTransitive) { // the default hierarchy query that fires when a page loads.
                cb(buildParentTree(nodeId, response.broaderTransitive));
              } else if(response.topconcepts) {
                cb(vocabRoot(response.topconcepts));
              } else {
                cb(createObjectsFromNarrowers(response));
              }
            }
          });
      }
    },
    'plugins' : ['sort']
  });
  /*
  var childResponse = false;
  var nodeId = '';
  var jsonData = {
    json_data: {
      ajax: {
        type: 'GET',
        data: function (node) {
          if (node == -1 && root)
            return $.param({'lang' : lang});
          else if(node != -1) { 
            nodeId = urlToUri[node[0].children[1].href];
            if (!nodeId) {
              nodeId = node[0].children[1].href;
            }
            nodeId = decodeURI(nodeId);
            return $.param({'uri' : nodeId, 'lang' : lang});
          } else {
            nodeId = $('.uri-input-box').html(); // using the real uri of the concept from the view.
            return $.param({'uri' : nodeId, 'lang' : lang});
          }
        },
        url: function (node) { 
          if (node == -1 && root) {
            return (rest_base_url + vocab + '/topConcepts');
          } else if(node != -1) { 
            return (rest_base_url + vocab + '/children');
          } else {
            nodeId = $('.uri-input-box').html(); // using the real uri of the concept from the view.
            return (rest_base_url + vocab + '/hierarchy');
          }
        },
        success: function (response) {
          if (response.broaderTransitive) { // the default hierarchy query that fires when a page loads.
            return buildParentTree(nodeId, response.broaderTransitive); 
          } else if(response.topconcepts) {
            return vocabRoot(response.topconcepts);
          } else {
            return createObjectsFromNarrowers(response);
          }
          return (nodeId.indexOf('http') == -1 ) ? ret : ret.children; // or is for the vocabulary top concept hierarchy.
        },
      },
    },
    core: { animation: 0, initially_open: ['#jstree-leaf-proper'], strings: { loading : jstree_loading, new_node : 'New node' } },
    ui: { initially_select: ['#jstree-leaf-proper'] },
    sort: function(a, b) { return this.get_text(a).toLowerCase() > this.get_text(b).toLowerCase() ? 1 : -1; },
    themes: {}
  };
  jsonData.plugins = ['themes', 'json_data', 'ui', 'sort'];
  jsonData.themes = {
    theme: 'default',
    url: path_fix + 'lib/jsTree/default/style.css',
    icons: false,
    dots: true
  };
  
  return jsonData;
  */
}


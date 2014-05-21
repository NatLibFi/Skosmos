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

function storeUri(node) { urlToUri[node.data.attr.href] = node.uri; }

/* 
 * Initializes jsTree with the json formatted data for a concept and it's parents.
 * Also binds a window.location.href redirect to clicking a node.
 * @param {Object} tree
 */
function invokeParentTree(tree) {
  var treeObject = $('.sidebar-grey');
  treeObject.jstree(tree).on('loaded.jstree', function() {
    treeObject.bind('select_node.jstree', function (event, data) {
      /* 
       * preventing redirect recursion with the initially_select option and 
       * at the same time stopping the user from refreshing the current page by clicking the concepts name in the hierarchy.
       */
      if (data.rslt.obj[0].children[1].id != 'jstree-leaf-proper') { 
        data.inst.open_node(data.rslt.obj);
      }
    });
  });
}

/*
 * Creates a concept object from the data returned by a rest query.
 * @param
 * @param
 */
function createConceptObject(conceptUri, conceptData) {
  var prefLabel = conceptData.prefLabel; // the json narrower response has a different structure.
  newNode = { 
    data: { "title" : prefLabel, "attr" : { "href" : conceptUri } },
    uri: conceptUri,
    parents: conceptData.broader,
    attr: {},
    children: []
  };
  // if we are at a top concepts page we want to highlight that node and mark it as to be initially opened.
  if (newNode.uri === $('.uri-input-box').html()) { newNode.data.attr.id = 'jstree-leaf-proper'; }
  
  if (conceptData.narrower /* && !conceptData.narrower[0] */) { // filtering out the ones that don't have labels 
    var childArray = [];
    for (var child in conceptData.narrower) {
      var conceptObject = conceptData.narrower[child];
      var hasChildren = conceptObject.hasChildren; 
      var childObject = {
        data: { "title" : conceptObject.label, "attr" : { "href" : conceptData.narrower[child].uri } },
        uri: conceptData.narrower[child].uri,
        parents: conceptUri,
        children: [],
        state: "closed"
      };
      if (child === $('.uri-input-box').html()) { childObject.data.attr.id = 'jstree-leaf-proper'; }
      // if the childConcept hasn't got any children the state is not needed.
      if (hasChildren === false) {
        delete childObject.state;
      }
      if(!childArray[childObject.uri])
        childArray.push(childObject);
      storeUri(childObject);
    }
    newNode.children.push(childArray);
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
    rootNode;

    for(var conceptUri in parentData) {
    var previousNode = currentNode,
      branchHelper, 
      exactMatchFound;
    currentNode = createConceptObject(conceptUri, parentData[conceptUri]);
    /* if a node has the property topConceptOf set it as the root node. 
     * Or just setting the last node as a root if nothing else has been found 
     */
    if (parentData[conceptUri].top || ( loopIndex == Object.size(parentData)-1) && !rootNode) { 
      if (!rootNode) {  
        branchHelper = currentNode;
      }
      rootNode = currentNode; 
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
  return rootNode;
}

function vocabRoot(topConcepts) {
  var topArray = [];
  for (var i = 0; i < topConcepts.length; i++) {
    var conceptData = topConcepts[i];
    var childObject = {
      data: { "title" : conceptData.label, "attr" : { "href" : conceptData.uri } },
      uri: conceptData.uri,
      children: [],
      state: "closed"
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
          for(var sibling in parentNode.children[0]) {
            if(parentNode.children[0][sibling].uri == current.uri){ 
              // if the concept has already been added remove the previous one since this one is more accurate.
              parentNode.children[0].splice(sibling, 1);
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
      data: { "title" : conceptObject.prefLabel, "attr" : { "href" : conceptObject.uri } },
      uri: conceptObject.uri,
      parents: narrowerResponse.uri,
      children: [],
      state: "closed"
    };
    if (hasChildren === false) {
      delete childObject.state;
    }
    setNode(childObject);
    childArray.push(childObject);
  }
  return childArray;
}

/* 
 * Gives you the Skosmos default jsTree configuration.
 */
function getTreeConfiguration(root) {
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
          return (nodeId.indexOf('http') == -1 /* || top_concepts !== '' */) ? ret : ret.children; // or is for the vocabulary top concept hierarchy.
        },
      },
    },
    core: { animation: 0, initially_open: ['#jstree-leaf-proper'], strings: { loading : jstree_loading, new_node : 'New node' } },
    ui: { initially_select: ['#jstree-leaf-proper'] },
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
}


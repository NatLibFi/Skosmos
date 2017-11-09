var treeIndex = {}; 
var urlToUri = {};
var hierTreeConf ={ 
  alwaysShowScrollbar: 1,
  scrollInertia: 0, 
  mouseWheel:{ scrollAmount: 105 },
  snapAmount: 18,
  snapOffset: 1
};

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

  $treeObject.on('loaded.jstree', function() {
    if ($('.mCustomScrollbar').length === 0) {
      $(".sidebar-grey").mCustomScrollbar(hierTreeConf);
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

function getLabel(object) {
  var labelProp = 'prefLabel';
  if (!object.prefLabel) {
    labelProp = 'label';
  }
  if (window.showNotation && object.notation) {
    return '<span class="tree-notation">' + object.notation + '</span> ' + object[labelProp];
  }
  return escapeHtml(object[labelProp]);
}

function createObjectsFromChildren(conceptData, conceptUri) {
  var childArray = [];
  for (var i = 0; i < conceptData.narrower.length; i++) {
    var childObject = {
      text: getLabel(conceptData.narrower[i]), 
      a_attr: getConceptHref(conceptData.narrower[i]),
      uri: conceptData.narrower[i].uri,
      parents: conceptUri,
      state: { opened: true }
    };
    // if the childConcept hasn't got any children the state is not needed.
    if (conceptData.narrower[i].hasChildren) {
      childObject.children = true;
      childObject.state.opened = false;
    }
    if(!childArray[childObject.uri])
      childArray.push(childObject);
    storeUri(childObject);
  }
  return childArray;
}

/*
 * Creates a concept object from the data returned by a rest query.
 * @param
 * @param
 */
function createConceptObject(conceptUri, conceptData) {
  var newNode = { 
    text: getLabel(conceptData), 
    a_attr: getConceptHref(conceptData),
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
  if (newNode.uri === window.uri) { newNode.li_attr = { class: 'jstree-leaf-proper' }; }
  if (conceptData.narrower) { // filtering out the ones that don't have labels 
    newNode.children = createObjectsFromChildren(conceptData, conceptUri);
  }
  
  return newNode;
}

/*
 * For building a parent hierarchy tree from the leaf concept to the ontology/vocabulary root.
 * @param {Object} schemes 
 * @param {Object} currentNode 
 * @param {Object} parentData 
 */
function attachTopConceptsToSchemes(schemes, currentNode, parentData) {
  var foundFirstLevel = false;

  for (var i = 0; i < schemes.length; i++) {
	  foundFirstLevel = true;
	  
	  // search if top concept uri is equal to scheme uri on first level
	  // search in all the 'tops' key
	  for (var j = 0; j < parentData[currentNode.uri].tops.length; j++) {
		  var currentTop = parentData[currentNode.uri].tops[j];
		  // we found a scheme at the first level that is equal to one of the 'tops' value
		  if(schemes[i].uri===currentTop){			  
			  if(Object.prototype.toString.call(schemes[i].children) !== '[object Array]' ) {
				  schemes[i].children = [];
			  }
			  // append to a first-level node that corresponds to a concept scheme
			  schemes[i].children.push(currentNode);

			  // the hierarchy response contains the parent info before the topConcepts so it's a safe to open the first one without broaders 
			  if (!schemes[i].opened && !currentNode.broader) {
				  schemes[i].state = currentNode.state;
				  schemes.opened = true;
			  }
			  break;
		  }
	  }

	  // search if top concept uri is equal to scheme children uri, if there are children (second level)
	  // this may be the case if schemes are organised by subjects : subjects will be first-level nodes, concepts schemes will be second level
	  for (var h = 0; h <schemes[i].children.length; h++) {
		  // one of the second-level node, actually corresponding to a concept scheme inside a 'domain' node
		  var currentScheme = schemes[i].children[h];	
		  
		  // search in all the 'tops' key
		  for (var j = 0; j < parentData[currentNode.uri].tops.length; j++) {
			  var currentTop = parentData[currentNode.uri].tops[j];
			  
			  // we found a scheme at the second level that is equal to one of the 'tops' value
			  if(currentScheme.uri===currentTop) {
				  if(Object.prototype.toString.call(currentScheme.children) !== '[object Array]' ) {
					  currentScheme.children = [];
				  }
				  currentScheme.children.push(currentNode);

				  // the hierarchy response contains the parent info before the topConcepts so it's a safe to open the first one without broaders 
				  if (!currentScheme.opened && !currentNode.broader) {
					  // open the first-level node and second-level node
					  schemes[i].state = currentNode.state;
					  currentScheme.state=schemes[i].state;
					  currentScheme.opened=true;
					  currentScheme.children.opened=true;
				  }
				  break;
			  }
		  }
	  }
       
  }

  return schemes;
}


/*
 * For building a parent hierarchy tree from the leaf concept to the ontology/vocabulary root.
 * @param {String} uri
 * @param {Object} parentData
 * @param {Object} schemes 
 */
function buildParentTree(uri, parentData, schemes) {
	if (parentData === undefined || parentData === null) { return; }

	var loopIndex = 0, // for adding the last concept as a root if no better candidates have been found.
	currentNode,
	rootArray = (schemes.length > 1) ? schemes : [];
	//	console.log(JSON.stringify(parentData, null, 4));
	for(var conceptUri in parentData) {
		if (parentData.hasOwnProperty(conceptUri)) {
			var branchHelper, 
			exactMatchFound;
			currentNode = createConceptObject(conceptUri, parentData[conceptUri]);
			/* if a node has the property topConceptOf set it as the root node. 
			 * Or just setting the last node as a root if nothing else has been found 
			 */
			if (parentData[conceptUri].top || ( loopIndex === Object.size(parentData)-1) && rootArray.length === 0 || !currentNode.parents && rootArray.length === 0) { 
				if (rootArray.length === 0) {  
					branchHelper = currentNode;
				}
				// if there are multiple concept schemes attach the topConcepts to the concept schemes
				if (schemes.length > 1 && (parentData[conceptUri].top)) {
					schemes = attachTopConceptsToSchemes(schemes, currentNode, parentData);

				}
				else {
					rootArray.push(currentNode);
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
	}

	// Iterating over the nodes to make sure all concepts have their children set.
	appendChildrenToParents();
	// avoiding the issue with multiple inheritance by deep copying the whole tree object before giving it to jsTree
	
	// console.log(JSON.stringify(rootArray, null, 4));
	return JSON.parse(JSON.stringify(rootArray));
}

function getConceptHref(conceptData) {
	
  if (conceptData.uri.indexOf(window.uriSpace) !== -1) {
    var page = conceptData.uri.substr(window.uriSpace.length);
    if (/[^a-zA-Z0-9\.]/.test(page) || page.indexOf("/") > -1 ) {
      // contains special characters or contains an additionnal '/' - fall back to full URI
      page = '?uri=' + encodeURIComponent(conceptData.uri);
    }
  } else {
    // not within URI space - fall back to full URI
    page = '?uri=' + encodeURIComponent(conceptData.uri);
  }
  return { "href" : vocab + '/' + lang + '/page/' + page };
}

function vocabRoot(topConcepts) {
  var topArray = [];
  for (var i = 0; i < topConcepts.length; i++) {
    var conceptData = topConcepts[i];
    var childObject = {
      text: conceptData.label, 
      a_attr : getConceptHref(conceptData),
      uri: conceptData.uri,
      state: { opened: false } 
    };
    if (conceptData.hasChildren)
      childObject.children = true;
    if (window.showNotation && conceptData.notation)
      childObject.text = '<span class="tree-notation">' + conceptData.notation + '</span> ' + childObject.text;
    setNode(childObject);
    topArray.push(childObject);
  }
  return topArray;
}

/*
 * Iterates through the tree and fixes all the parents by adding references to their child concepts.
 */
function appendChildrenToParents() {
  for (var uri in treeIndex) {
    if (treeIndex.hasOwnProperty(uri)) {
      var current = treeIndex[uri];
      if (current.parents) {
        for (var i = 0; i < current.parents.length; i++) {
          var parentNode = getNode(current.parents[i]);
          if (parentNode && parentNode !== current && $.inArray(current, parentNode.children) === -1) {
            for(var j = 0; j < parentNode.children.length; j++) {
              if(parentNode.children[j].uri === current.uri){ 
                // if the concept has already been found enrich the previous one with the additional information.
                parentNode.children[j].children = current.children;
                parentNode.children[j].state = current.state;
                parentNode.children[j].li_attr = current.li_attr;
              }
            }
          }
        }
      }
    }
  }
}

function createObjectsFromNarrowers(narrowerResponse) {
  var childArray = [];
     
        for (var i = 0; i < narrowerResponse.narrower.length; i++) {
        var conceptObject = narrowerResponse.narrower[i];
        var childObject = {
          text : getLabel(conceptObject), 
          a_attr : getConceptHref(conceptObject),
          uri: conceptObject.uri,
          parents: narrowerResponse.uri,
          state: { opened: false, disabled: false, selected: false }
        };
        childObject.children = conceptObject.hasChildren ? true : false;
        setNode(childObject);
        childArray.push(childObject);
      }

  
  return childArray;
}

function getParams(node) {
  var nodeId = (node.id === '#') ? window.uri : node.original.uri;
  var clang = content_lang !== '' ? content_lang : lang;
  return $.param({'uri' : nodeId, 'lang' : clang});
}

function pickLabel(scheme) {
  var label = '';
  if (scheme.prefLabel)
    label = scheme.prefLabel;
  else if (scheme.label)
    label = scheme.label;
  else if (scheme.title)
    label = scheme.title;
  return label;
}

function schemeRoot(schemes) {
  var topArray = [];
  
  
  // Step 1 : gather domain list
  var domains=[];
  for (var i = 0; i < schemes.length; i++) {
    if(schemes[i].subject != null) {
        var schemeDomain = schemes[i].subject.uri;

        // test if domain was already found  
        var found = false;
        for (var k = 0; k < domains.length; k++) {
          if(domains[k].uri===schemeDomain){
            found = true;
            break;
          }
        }

        // if not found, store it in domain list
        if(!found) {
          domains.push(schemes[i].subject);
        }
    }
  }

  // Step 2 : create tree nodes for each domain
 
  for (var i = 0; i < domains.length; i++) {
    var theDomain = domains[i];
    var theDomainLabel = pickLabel(theDomain);
    
    // avoid creating entries with empty labels
    if(theDomainLabel != '') {        
        // Step 2.1 : create domain node without children
        var domainObject = {
        		text: theDomainLabel, 
        		// note that the class 'domain' will make sure the node will be sorted _before_ others (see the 'sort' functio nat the end)
        		a_attr : { "href" : vocab + '/' + lang + '/page/?uri=' + theDomain.uri, 'class': 'domain'},
        		uri: theDomain.uri,
        		children: [],
        		state: { opened: false } 
        };

        // Step 2.2 : find the concept schemes in this domain and add them as children
        for (var k = 0; k < schemes.length; k++) {
        	var theScheme = schemes[k];
        	var theSchemeLabel = pickLabel(theScheme);
        	
        	// avoid creating entries with empty labels
        	if(theSchemeLabel != '') { 
	        	if((theScheme.subject) != null && (theScheme.subject.uri===theDomain.uri)) {
	        		domainObject.children.push(
	        				{
	        					text: theSchemeLabel,
	        					a_attr:{ "href" : vocab + '/' + lang + '/page/?uri=' + theScheme.uri, 'class': 'scheme'},
	        					uri: theScheme.uri,
	        					children: true,
	        					state: { opened: false } 
	        				}
	        		);
	        	}
        	}
        }
        topArray.push(domainObject);   	
    }

  }

  // Step 3 : add the schemes without any subjects after the subjects node
  for (var k = 0; k < schemes.length; k++) {
	  var theScheme = schemes[k]; 
	  
	  if(theScheme.subject == null) {		  
		  // avoid creating entries with empty labels
		  var theSchemeLabel = pickLabel(theScheme);
		  if(theSchemeLabel != '') { 		  
			  topArray.push(
					  {
						  text:theSchemeLabel,
						  a_attr:{ "href" : vocab + '/' + lang + '/page/?uri=' + theScheme.uri, 'class': 'scheme'},
						  uri: theScheme.uri,
						  children: true,
						  state: { opened: false } 
					  }
			  );
		  }
	  }
  }
  
  // console.log(JSON.stringify(topArray, null, 4));

  return topArray;
}

function addConceptsToScheme(topConcept, childObject, schemes) {
  for (var j in schemes) {
    if (schemes.hasOwnProperty(j) && topConcept.topConceptOf === schemes[j].uri) {
      if(Object.prototype.toString.call(schemes[j].children) !== '[object Array]' ) {
        schemes[j].children = [];
      }
      schemes[j].children.push(childObject);
      schemes[j].state.opened = true;
      schemes[j].a_attr.class = 'jstree-clicked';
    }
  }
  return schemes;
}


function topConceptsToSchemes(topConcepts, schemes) {
  var childArray = schemes.length > 1 ? schemes : [];
  for (var i in topConcepts) {
    var topConcept = topConcepts[i];
    var hasChildren = topConcept.hasChildren; 
    var childObject = {
      text : getLabel(topConcept), 
      a_attr : { "href" : vocab + '/' + lang + '/page/?uri=' + encodeURIComponent(topConcept.uri) },
      uri: topConcept.uri,
      state: { opened: false, disabled: false, selected: false }
    };
    if (hasChildren) {
      childObject.children = true;
      childObject.state.opened = false;
    }
    setNode(childObject);
    if (schemes.length > 1) {
      schemes = addConceptsToScheme(topConcept, childObject, schemes);
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
                return cb(schemeObjects);
              } 
              // if there was only one concept scheme display it's top concepts at the top level 
              else if(node.id === '#' && $('#vocab-info').length) { 
                $.ajax({
                  data: $.param({'lang': clang}),
                  url: rest_base_url + vocab + '/topConcepts', 
                  success: function (response) {
                    return cb(vocabRoot(response.topconcepts));
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
                else if (node.id === '#' && $('.property-value-wrapper:first() p').html() === 'skos:ConceptScheme') {
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
                    return cb(buildParentTree(nodeId, response.broaderTransitive, schemeObjects));
                  } else if (response.topconcepts) {
                    return cb(topConceptsToSchemes(response.topconcepts, schemeObjects));
                  } else {
                    return cb(createObjectsFromNarrowers(response));
                  }
                }
                });
              }
            }
          });
        }
    },
    'plugins' : ['sort'],
    'sort' : function (a,b) {
    	var nodeA = this.get_node(a);
        var nodeB = this.get_node(b);
        // make sure the tree nodes with class 'domain' are sorted before the others
    	if(nodeA.a_attr && nodeA.a_attr['class']) {
    		if(nodeB.a_attr && nodeB.a_attr['class']) {
    			if(nodeA.a_attr['class'] == 'domain' && nodeB.a_attr['class'] == 'domain') {
    				return naturalCompare(this.get_text(a).toLowerCase(), this.get_text(b).toLowerCase());
    			}
    			else if(nodeA.a_attr['class'] == 'domain' && nodeB.a_attr['class'] != 'domain') {
    				return -1;
    			}
    			else if(nodeA.a_attr['class'] != 'domain' && nodeB.a_attr['class'] == 'domain') {
    				return 1;
    			}
    		} else {
    			return naturalCompare(this.get_text(a).toLowerCase(), this.get_text(b).toLowerCase());
    		}
    	}  else {
    		return naturalCompare(this.get_text(a).toLowerCase(), this.get_text(b).toLowerCase());
    	}
    	// return naturalCompare(this.get_text(a).toLowerCase(), this.get_text(b).toLowerCase());
     }
  });
}


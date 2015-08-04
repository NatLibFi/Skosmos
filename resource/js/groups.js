/* 
 * Copyright (c) 2012 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

function buildGroupTree(response) {
  var data = [];
  for (var uri in response) {
    var group = createGroupNode(uri, response[uri]); 
    if (group.text)
      data.push(group);
  }
  
  $('.group-hierarchy').jstree({ 
    'plugins' : ['sort'],
    'core' : { 
      'data' : data, 
      'animation' : 0,
      'themes' : { 'icons': false },
    } });
}

function createGroupNode(uri, groupObject) {
  var node = {'id' : uri, 'parent' : '#', children : [], a_attr : { "href" : uri }};
  node.text = groupObject.label;
  if (groupObject.members) {
    for (var memberUri in groupObject.members) {
      var child = { 'id' : memberUri, 'text' : groupObject.members[memberUri], 'parent' : uri };
      if (child.text)
        node.children.push(child);
    }
  }
  return node;
}

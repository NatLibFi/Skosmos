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
    'animation' : 0,
    'themes' : { 'icons': false },
    'plugins' : ['sort'],
    'core' : { 'data' : data } });
}

function createGroupNode(uri, groupObject) {
  var node = {'id' : uri, 'parent' : '#'};
  node.text = groupObject.label;
  if (groupObject.members) {
    node.children = [];
    for (var memberUri in node.members) {
      var child = createGroupNode(memberUri, node.members[memberUri]);
      child.parent = uri; 
      if (child.text)
        node.children.push(child);
    }
  }
  return node;
}

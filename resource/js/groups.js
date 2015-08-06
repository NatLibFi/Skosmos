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
  return data;
}
  
function invokeGroupTree() {
  var $treeObject = $('.group-hierarchy');
  $treeObject.on('activate_node.jstree', function(event, node) {
    $treeObject.jstree('open_node', node.node);
  });

  $('.group-hierarchy').jstree({ 
    'plugins' : ['sort'],
    'core' : { 
      'data' : 
        function(node, cb) { 
          var nodeId;
          var json_url = (rest_base_url + vocab + '/groups');
          if (node.id !== '#') {
            nodeId = node.id;
            json_url = (rest_base_url + vocab + '/groupMembers');
          }
          var jsondata = $.ajax({
            data: $.param({'uri' : nodeId, 'lang' : content_lang}),
            url: json_url, 
            success: function (response) {
              if (response.groupHierarchy) { // the default hierarchy query that fires when a page loads.
                cb(buildGroupTree(response.groupHierarchy));
              } else {
                var children = [];
                for (var memberUri in response.members) {
                  var child = {'id' : memberUri, 'text' : response.members[memberUri].label,'parent' : nodeId, children : false, a_attr : { "href" : memberUri}};
                  if (response.members[memberUri].hasMembers)
                    child.children = true;
                  children.push(child);
                }
                cb(children);
              }
            }
          });
      },
      'animation' : 0,
      'themes' : { 'icons': false },
    } 
  });
}
var first = true;

function createGroupNode(uri, groupObject) {
  var node = {'id' : uri, 'parent' : '#', children : [], a_attr : { "href" : uri }};
  node.text = groupObject.label;
  if (groupObject.members)
    node.children = true;
  return node;
}


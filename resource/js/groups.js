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
    if (uri === $('.uri-input-box').html()) {
      group.state = { 'opened' : true };
      group.a_attr.class = "jstree-clicked group";
    }
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
    'sort' : function (a,b) { return this.get_text(a).toLowerCase() > this.get_text(b).toLowerCase() ? 1 : -1; },
    'core' : { 
      'data' : 
        function(node, cb) { 
          var nodeId;
          var json_url = (rest_base_url + vocab + '/groups');
          if (node.id !== '#') {
            nodeId = node.id;
            json_url = (rest_base_url + vocab + '/groupMembers');
          }
          $.ajax({
            data: $.param({'uri' : nodeId, 'lang' : content_lang}),
            url: json_url, 
            success: function (response) {
              if (response.groupHierarchy) { // the default hierarchy query that fires when a page loads.
                cb(buildGroupTree(response.groupHierarchy));
              } else {
                var children = [];
                for (var i in response.members) {
                  var member = response.members[i];
                  var child = {'id' : member.uri, 'text' : member.label,'parent' : nodeId, children : false, a_attr : { "href" : member.uri}};
                  if (member.hasMembers) {
                    child.children = true;
                  }
                  if ($.inArray('skos:Collection', member.type) !== -1) {
                    child.a_attr.class = 'group';
                  }
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

function createGroupNode(uri, groupObject) {
  var node = {'id' : uri, 'parent' : '#', children : [], a_attr : { "href" : uri, "class" : "group" }};
  node.text = groupObject.label;
  if (groupObject.members) {
    node.children = true;
  }
  return node;
}


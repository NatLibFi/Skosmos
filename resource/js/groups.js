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
                  children.push({'id' : memberUri, 'text' : response.members[memberUri].label,'parent' : nodeId, children : true, a_attr : { "href" : memberUri, 'state' : { 'opened' : false }}});
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
  var node = {'id' : uri, 'parent' : '#', children : [], a_attr : { "href" : uri }};
  node.text = groupObject.label;
  node.children = true;
  node.state = { 'opened' : false };
  //if (groupObject.members) {
    //for (var memberUri in groupObject.members) {
      //var child = { 'id' : memberUri, 'text' : groupObject.members[memberUri], 'parent' : uri };
      //if (child.text)
        //node.children.push(child);
    //}
  //}
  return node;
}


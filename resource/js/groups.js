/* 
 * Copyright (c) 2012 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */
var grouplist = [];

function buildGroupTree(response) {
  var data = [];
  for (var i in response) {
    var group = createGroupNode(response[i].uri, response[i]); 
    grouplist[response[i].uri] = group;
    if (group.text && !response[i].super)
      data.push(group);
    if (response[i].uri === $('.uri-input-box').html()) {
      group.state = { 'opened' : true };
      group.a_attr.class = "jstree-clicked group";
    }
  }
  // appending the groups to their superGroups
  for (var i in response) {
    var groupobj = grouplist[response[i].uri];
    for (var j in response[i].super) {
      var superuri = response[i].super[j];
      if (grouplist[superuri]) {
        if (grouplist[superuri].children === true)
          grouplist[superuri].children = [];
        grouplist[superuri].children.push(groupobj);
        if (groupobj.state)
          grouplist[superuri].state = groupobj.state;

      }
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
              if (response.groups) { // the default hierarchy query that fires when a page loads.
                cb(buildGroupTree(response.groups));
              } else {
                var children = [];
                for (var i in response.members) {
                  var member = response.members[i];
                  var child = {'id' : member.uri, 'text' : member.label,'parent' : nodeId, children : false, a_attr : { "href" : vocab + '/' + lang + '/page/?uri=' + encodeURIComponent(member.uri)}};
                  if (member.hasMembers || member.isSuper) {
                    child.children = true;
                  }
                  if ($.inArray('skos:Collection', member.type) !== -1) {
                    child.a_attr.class = 'group';
                    child.a_attr.href = vocab + '/' + lang + '/groups/?uri=' + encodeURIComponent(member.uri);
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
  var node = {'id' : uri, children : [], a_attr : { "href" : vocab + '/' + lang + '/groups/?uri=' + encodeURIComponent(uri), "class" : "group" }};
  node.text = groupObject.label;
  if (groupObject.hasMembers || groupObject.isSuper) {
    node.children = true;
  }
  return node;
}


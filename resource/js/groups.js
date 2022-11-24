function buildGroupTree(response) {
  var data = [];
  var groups = {};
  for (var i in response) {
    var group = createGroupNode(response[i].uri, response[i]); 
    groups[response[i].uri] = group;
    if (response[i].uri === $('.uri-input-box').html()) {
      group.state = { 'opened' : true };
      group.a_attr.class = "jstree-clicked group";
    }
  }
  // adding children to the groups
  for (var i in response) {
    var groupobj = groups[response[i].uri];
    for (var j in response[i].childGroups) {
      var childuri = response[i].childGroups[j];
      if (groups[childuri]) {
        var childobj = groups[childuri];
        if (groupobj.children === true)
          groupobj.children = [];
        groupobj.children.push(childobj);
        if (childobj.state)
          groupobj.state = childobj.state;
        childobj.is_child = true;
      }
    }
  }
  for (var uri in groups) {
    var groupObj = groups[uri];
    if (!groupObj.is_child) {
      fixStates(groupObj);
      data.push(groupObj);
    }
  }
  return JSON.parse(JSON.stringify(data));
}

function fixStates(groupobj) {
  for (var i in groupobj.children) {
    fixStates(groupobj.children[i]);
    if (groupobj.children[i].state) {
      groupobj.state = groupobj.children[i].state;
    }
  }
}
  
function invokeGroupTree() {
  var $treeObject = $('.group-hierarchy');
  $treeObject.on('activate_node.jstree', function(event, node) {
    $treeObject.jstree('open_node', node.node);
  });

  $treeObject.jstree({
    'plugins' : ['sort'],
    'sort' : hierarchySort,
    'core' : { 
      'data' : 
        function(node, cb) { 
          var json_url = (node.id !== '#') ? (rest_base_url + vocab + '/groupMembers') : (rest_base_url + vocab + '/groups');
          var params = (node.id !== '#') ? $.param({'uri' : node.a_attr['data-uri'], 'lang' : content_lang}): $.param({'lang' : content_lang});
          $.ajax({
            data: params,
            url: json_url, 
            req_kind: $.ajaxQ.requestKind.SIDEBAR_PRIVILEGED,
            success: function (response) {
              if (response.groups) { // the default hierarchy query that fires when a page loads.
                return cb(buildGroupTree(response.groups));
              } else {
                var children = [];
                for (var i in response.members) {
                  var member = response.members[i];
                  var child = {
                    text: getLabel(member),
                    label: pickLabel(member),
                    parent: node.a_attr['data-uri'],
                    notation: member.notation,
                    children: false,
                    a_attr: {
                      'data-uri': member.uri,
                      "href": getHrefForUri(member.uri, true)
                    }
                  };
                  if (member.hasMembers || member.isSuper) {
                    child.children = true;
                  }
                  children.push(JSON.parse(JSON.stringify(child)));
                }
                return cb(JSON.parse(JSON.stringify(children)));
              }
            }
          });
      },
      'animation' : 0,
      'themes' : { 'icons': false },
      'strings' : { 'Loading ...' : jstree_loading }
    } 
  });
}

function createGroupNode(uri, groupObject) {
  var node = {
    text: getLabel(groupObject),
    label: pickLabel(groupObject),
    notation: groupObject.notation,
    children: [],
    a_attr: {
      'data-uri': uri,
      "href": getHrefForUri(uri, true),
      "class": "group"
    }
  };
  if (groupObject.hasMembers || groupObject.isSuper)
    node.children = true;
  return node;
}


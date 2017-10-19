


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


function getAllVersions(graph,lang) {
  var tab=[];

  for (var i = 0; i < graph.length ; i++) {
    console.log('graph :'+i);
    var groupPage = graph[i].uri.indexOf(uriSpace) !== -1 ? graph[i].uri.substr(uriSpace.length) : '?uri=' + encodeURIComponent(graph[i].uri);
      for (var k = 0; k < graph[i].hasVersion.length; k++) {
        console.log('version: '+k);
        console.log('version value: '+graph[i].hasVersion[k].version);
           var objectInfos={
          text : graph[i].uri, 
          a_attr : { "href" : graph[i].hasVersion[k].skosmosVocab + '/' + lang + '/page/' + groupPage },
          version: graph[i].hasVersion[k].version,
          date : graph[i].hasVersion[k].createdOn,
          iscurrent : graph[i].hasVersion[k].iscurrent
        };
        tab.push(objectInfos);
      }
  }
  console.log(JSON.stringify(tab));
  return tab;
}
// adds a delay before showing the spinner configured above
  function delaySpinner(loading) {
    loading = setTimeout(function() { $('.history-spinner').show() }, 500);
  }

function getConceptVersions(uri,lang) {
        var results=[];
        var html='';
        var loading;
        var json_url = (rest_base_url + vocab + '/history');
        $.ajax({
          data: $.param({'uri': uri, 'lang' : lang}),
          url: json_url,
          beforeSend: delaySpinner(loading),
          complete: clearTimeout(loading),
          success: function (response) {
              $('.history-spinner').hide();
                results= getAllVersions(response.graph,lang);

                console.log(JSON.stringify(results));
                $('.page-header').append('<h3>Versions</h3>');
                for(var i=0; i<results.length;i++){
                  if(results[i].iscurrent===true){
                     html='<li class="timeline-item">'
                          +'<div class="timeline-badge primary"><a href="'+results[i].a_attr.href+'"><i class="glyphicon glyphicon-check"></i></a></div>'
                          +'<div class="timeline-panel">'
                           + '<div class="timeline-heading">'
                              +'<h4 class="timeline-title">'+results[i].version+'</h4>'
                              +'<p style="text-align:center;"><small class="text-muted"  style="text-align:center;"><i class="glyphicon glyphicon-time"></i>'+results[i].date+'</small></p>'
                            +'</div>'
                          +'</div>'
                        +'</li>';
                  }else{
                    html='<li class="timeline-item">'
                          +'<div class="timeline-badge info"><a href="'+results[i].a_attr.href+'"><i class="glyphicon glyphicon-check"></i></a></div>'
                          +'<div class="timeline-panel">'
                           + '<div class="timeline-heading">'
                              +'<h4 class="timeline-title">'+results[i].version+'</h4>'
                              +'<p style="text-align:center;"><small class="text-muted"  style="text-align:center;"><i class="glyphicon glyphicon-time"></i>'+results[i].date+'</small></p>'
                            +'</div>'
                          +'</div>'
                        +'</li>';
                  }
                  $('.concept-main #history').append(html);
                } 
            }
        });
      }

//'<tr><td><a href="'+results[i].a_attr.href+'">'+results[i].text+'</a></td><td>'+results[i].version+'</td><td>'+results[i].date+'</td></tr>'

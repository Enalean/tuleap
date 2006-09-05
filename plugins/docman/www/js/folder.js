function folder_expand(caller, node) {
  caller.src = "20_joinbottom_minus.gif";
  caller.onclick = jsCollapseFolder;
  //alert('ok');

  match = node.id.split('_');
  nodeId = match[1];

  expandUrl = '/plugins/docman/index.php?group_id=101&view=rawDisplay&action=expandFolder&id='+nodeId;

  new Ajax.Updater('subdir_'+nodeId, 
		   expandUrl, 
  {asynchronous:true});  
}

function jsExpandFolder(e) {
  if (!e) var e = window.event;
  if (e.target) targ = e.target;
  else if (e.srcElement) targ = e.srcElement;
  
  var parentN = targ.parentNode;
  match = parentN.id.split('_');
  nodeId = match[1];  

  folder_expand(targ, $('subdir_'+nodeId));
}

function jsCollapseFolder(e) {
  if (!e) var e = window.event;
  if (e.target) targ = e.target;
  else if (e.srcElement) targ = e.srcElement;
  
  var parentN = targ.parentNode;
  match = parentN.id.split('_');
  nodeId = match[1];  

  folder_collapse(targ, $('subdir_'+nodeId));
}

function debug() {
  alert('debug');
}

function folder_collapse(caller, node) {  
  var div = document.createElement("div");
  div.className="subdir";
  div.id=node.id;

  match = node.id.split('_');
  nodeId = match[1];
  
  collapseUrl = '/plugins/docman/?group_id=101&action=collapseFolder&id='+nodeId;
  
  new Ajax.Request(collapseUrl, {method: 'get'});
  
  var parentN = node.parentNode;
  parentN.replaceChild(div, node);

  caller.src = "20_joinbottom_plus.gif";
  caller.onclick = jsExpandFolder;
}


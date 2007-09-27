function folder_expand(caller, node) {
  caller.src = "20_joinbottom_minus.gif";
  caller.onclick = jsCollapseFolder;
  //alert('ok');

  match = node.id.split('_');
  nodeId = match[1];

  expandUrl = '/plugins/docman/index.php?group_id=101&view=rawDisplay&act=expandFolder&id='+nodeId;

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

function folder_collapse(caller, node) {  
  var div = document.createElement("div");
  div.className="subdir";
  div.id=node.id;

  match = node.id.split('_');
  nodeId = match[1];
  
  collapseUrl = '/plugins/docman/?group_id=101&act=collapseFolder&id='+nodeId;
  
  new Ajax.Request(collapseUrl, {method: 'get'});
  
  var parentN = node.parentNode;
  parentN.replaceChild(div, node);

  caller.src = "20_joinbottom_plus.gif";
  caller.onclick = jsExpandFolder;
}

/**
 * This part of the file is for UL/LI tree display
 *
 */

function HTTPRequest() {
  this.request = window.location.search;
  this.params = new Array();

  paramArray = this.request.slice(1).split('&');
  for(i=0;i<paramArray.length;i++) {
    name  = paramArray[i].slice(0, paramArray[i].indexOf('='));
    value = paramArray[i].slice(paramArray[i].indexOf('=')+1,paramArray[i].length);
    this.params[name] = value;
  }
}

HTTPRequest.prototype.get = function(param) {
  return this.params[param];
}

function LI_folder_expand(node) {
  // retreive nodeid
  match = node.id.split('_');
  nodeId = match[1];
  
  // retreive group_id
  request = new HTTPRequest();
  groupId = request.get('group_id');

  expandUrl = '/plugins/docman/index.php?group_id='+groupId;
  expandUrl += '&view=ulsubfolder&act=expandFolder';
  expandUrl += '&id='+nodeId;

  // Due to Ajax.Update, we have to create an empty div to be filled
  var div = document.createElement("div");
  div.id='fakediv_ul_'+nodeId;
  node.appendChild(div);

  // Toggle behaviour of "onclick"
  node.onclick=LI_jsCollapseFolder;

  // Update div
  new Ajax.Updater('fakediv_ul_'+nodeId, 
		   expandUrl, 
  {asynchronous:true});  
  }

function LI_jsExpandFolder(e) {
  if (!e) var e = window.event;
  if (e.target) targ = e.target;
  else if (e.srcElement) targ = e.srcElement;

  LI_folder_expand(targ);
}

function  LI_collapse_folder(caller, node) {
  match = node.id.split('_');
  nodeId = match[1];

  request = new HTTPRequest();
  groupId = request.get('group_id');

  collapseUrl = '/plugins/docman/index.php?group_id='+groupId;
  collapseUrl += '&act=collapseFolder';
  collapseUrl += '&id='+nodeId;

  new Ajax.Request(collapseUrl, {method: 'get'});
  
  var parentN = node.parentNode;
  parentN.removeChild(node);
 
  //node.className="hidden";
  caller.onclick = LI_jsExpandFolder;
}

function LI_jsCollapseFolder(e) {
  if (!e) var e = window.event;
  if (e.target) targ = e.target;
  else if (e.srcElement) targ = e.srcElement;
  
  ularray = targ.getElementsByTagName('ul');
  if(ularray.length != 1) {
    divarray = targ.getElementsByTagName('div');
    if(divarray.length != 1) {
      LI_collapse_folder(targ, divarray[0]);
    }
    else {
      alert('Error');
    }
  }
  else {
    LI_collapse_folder(targ, ularray[0]);
  }
}

/*function highlight(e) {  
  if (!e) var e = window.event;

  if (e.target) targ = e.target;
  else if (e.srcElement) targ = e.srcElement;

  targ.className="selected";
}

function unhighlight(e) {  
  if (!e) var e = window.event;
 
  if (e.target) targ = e.target;
  else if (e.srcElement) targ = e.srcElement;

  targ.className="";
  }*/


function initEvents() {
  var myrules = {
    'li.expanded' : function(element){
      element.onclick =  LI_jsCollapseFolder
    },
    'li.collapsed' : function(element){
      element.onclick =  LI_jsExpandFolder
    }
  };
  
  Behaviour.register(myrules);
  //node = $('li_2');
  //node.onmouseover = highlight;
  //  node.onmouseout = unhighlight;

  //node = $('li_23');
  //node.onclick = LI_jsCollapseFolder;

  //  node = $('lidir');
  //node.

  //  node = $('li_4');
  //node.onmouseover = highlight;

  //node = $('li_2');
  //node.onmouseover = highlight;
}



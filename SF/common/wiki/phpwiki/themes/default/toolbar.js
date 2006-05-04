// Toolbar JavaScript support functions. Taken from mediawiki 
// $Id: toolbar.js,v 1.12 2005/09/28 18:51:03 rurban Exp $

// Un-trap us from framesets
if( window.top != window ) window.top.location = window.location;
var pullwin;

// This function generates the actual toolbar buttons with localized text
// We use it to avoid creating the toolbar where javascript is not enabled
// Not all buttons use this helper, some need special javascript treatment.
function addButton(imageFile, speedTip, func, args) {
  var i;
  speedTip=escapeQuotes(speedTip);
  document.write("<a href=\"javascript:"+func+"(");
  for (i=0; i<args.length; i++){
    if (i>0) document.write(",");
    document.write("'"+escapeQuotes(args[i])+"'");
  }
  //width=\"23\" height=\"22\"
  document.write(");\"><img src=\""+imageFile+"\" width=\"18\" height=\"18\" border=\"0\" alt=\""+speedTip+"\" title=\""+speedTip+"\">");
  document.write("</a>");
  return;
}
function addTagButton(imageFile, speedTip, tagOpen, tagClose, sampleText) {
  addButton(imageFile, speedTip, "insertTags", [tagOpen, tagClose, sampleText]);
  return;
}

// This function generates a popup list to select from. 
// In an external window so far, but we really want that as acdropdown pulldown, hence the name.
// plugins, pagenames, categories, templates. 
// not with document.write because we cannot use self.opener then.
//function addPulldown(imageFile, speedTip, pages) {
//  addButton(imageFile, speedTip, "showPulldown", pages);
//  return;
//}
// pages is either an array of strings or an array of array(name,value)
function showPulldown(title, pages, okbutton, closebutton) {
  height = new String(Math.min(270, 70 + (pages.length * 12))); // 270 or smaller
  pullwin = window.open('','','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=yes,copyhistory=no,height='+height+',width=180');
  pullwin.window.document.write('<html><head><title>'+escapeQuotes(title)+'</title><style type=\"text/css\"><'+'!'+'-- body {font-family:Tahoma,Arial,Helvetica,sans-serif;font-size:10pt;background-color:#dddddd;} input { font-weight:bold;margin-left:2px;margin-right:2px;} option {font-size:9pt} #buttons { background-color:#dddddd;padding-right:10px;width:180px;} --'+'></style></head>');
  pullwin.window.document.write('\n<body bgcolor=\"#dddddd\"><form><div id=\"buttons\"><input type=\"button\" value=\"'+okbutton+'\" onclick=\"if(self.opener)self.opener.do_pulldown(document.forms[0].select.value); return false;\"><input type=\"button\" value=\"'+closebutton+'\" onclick=\"self.close(); return false;\"></div>\n<select style=\"margin-top:10px;\" name=\"select\" size=\"'+((pages.length>20)?'20':new String(pages.length))+'\" ondblclick=\"if(self.opener)self.opener.do_pulldown(document.forms[0].select.value); return false;\">');
  for (i=0; i<pages.length; i++){
    if (typeof pages[i] == 'string')
      pullwin.window.document.write('<option value="'+pages[i]+'">'+escapeQuotes(pages[i])+'</option>\n');
    else  // array=object
      pullwin.window.document.write('<option value="'+pages[i][1]+'">'+escapeQuotes(pages[i][0])+'</option>\n');
  }
  pullwin.window.document.write('</select></form></body></html>');
  pullwin.window.document.close();
  return false;
}
function do_pulldown(value) {
  insertTags(value, '', '\n');
  return;
}
function addInfobox(infoText) {
  // if no support for changing selection, add a small copy & paste field
  var clientPC = navigator.userAgent.toLowerCase(); // Get client info
  var is_nav = ((clientPC.indexOf('gecko')!=-1) && (clientPC.indexOf('spoofer')==-1)
                && (clientPC.indexOf('khtml') == -1));
  if(!document.selection && !is_nav) {
    infoText=escapeQuotesHTML(infoText);
    document.write("<form name='infoform' id='infoform'>"+
		   "<input size=80 id='infobox' name='infobox' value=\""+
		   infoText+"\" readonly=\"readonly\"></form>");
  }
}
function escapeQuotes(text) {
  var re=new RegExp("'","g");
  text=text.replace(re,"\\'");
  re=new RegExp('"',"g");
  text=text.replace(re,'&quot;');
  re=new RegExp("\\n","g");
  text=text.replace(re,"\\n");
  return text;
}
function escapeQuotesHTML(text) {
  var re=new RegExp('"',"g");
  text=text.replace(re,"&quot;");
  return text;
}
// apply tagOpen/tagClose to selection in textarea,
// use sampleText instead of selection if there is none
// copied and adapted from phpBB
function insertTags(tagOpen, tagClose, sampleText) {
  //f=document.getElementById('editpage');
  var txtarea = document.getElementById('edit[content]');
  // var txtarea = document.editpage.edit[content];
  
  // IE
  var re=new RegExp('%0A',"g");
  tagOpen = tagOpen.replace(re,'\n');
  var re=new RegExp('%22',"g");
  tagOpen = tagOpen.replace(re,'"');
  var re=new RegExp('%27',"g");
  tagOpen = tagOpen.replace(re,'\'');
  var re=new RegExp('%09',"g");
  tagOpen = tagOpen.replace(re,'    ');
  var re=new RegExp('%7C',"g");
  tagOpen = tagOpen.replace(re,'|');
  var re=new RegExp('%5B',"g");
  tagOpen = tagOpen.replace(re,'[');
  var re=new RegExp('%5D',"g");
  tagOpen = tagOpen.replace(re,']');
  var re=new RegExp('%5C',"g");
  tagOpen = tagOpen.replace(re,'\\');

  if(document.selection) {
    var theSelection = document.selection.createRange().text;
    if(!theSelection) { theSelection=sampleText;}
    txtarea.focus();
    if(theSelection.charAt(theSelection.length - 1) == " "){// exclude ending space char, if any
      theSelection = theSelection.substring(0, theSelection.length - 1);
      document.selection.createRange().text = tagOpen + theSelection + tagClose + " ";
    } else {
      document.selection.createRange().text = tagOpen + theSelection + tagClose;
    }
    // Mozilla -- disabled because it induces a scrolling bug which makes it virtually unusable
  } else if(txtarea.selectionStart || txtarea.selectionStart == '0') {
    var startPos = txtarea.selectionStart;
    var endPos = txtarea.selectionEnd;
    var scrollTop=txtarea.scrollTop;
    var myText = (txtarea.value).substring(startPos, endPos);
    if(!myText) { myText=sampleText;}
    if(myText.charAt(myText.length - 1) == " "){ // exclude ending space char, if any
      subst = tagOpen + myText.substring(0, (myText.length - 1)) + tagClose + " "; 
    } else {
      subst = tagOpen + myText + tagClose; 
    }
    txtarea.value = txtarea.value.substring(0, startPos) + subst + txtarea.value.substring(endPos, txtarea.value.length);
    txtarea.focus();
    var cPos=startPos+(tagOpen.length+myText.length+tagClose.length);
    txtarea.selectionStart=cPos;
    txtarea.selectionEnd=cPos;
    txtarea.scrollTop=scrollTop;
    // All others
  } else {
    // Append at the end: Some people find that annoying
    txtarea.value += tagOpen + sampleText + tagClose;
    //txtarea.focus();
    //var re=new RegExp("\\n","g");
    //tagOpen=tagOpen.replace(re,"");
    //tagClose=tagClose.replace(re,"");
    //document.infoform.infobox.value=tagOpen+sampleText+tagClose;
    txtarea.focus();
  }
  // reposition cursor if possible
  if (txtarea.createTextRange) txtarea.caretPos = document.selection.createRange().duplicate();
}

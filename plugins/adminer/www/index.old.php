<?php
/** Adminer - Compact database management
* @link http://www.adminer.org/
* @author Jakub Vrana, http://php.vrana.cz/
* @copyright 2007 Jakub Vrana
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
*/error_reporting(6135);$ie=(!ereg('^(unsafe_raw)?$',ini_get("filter.default"))||ini_get("filter.default_flags"));if($ie){foreach(array('_GET','_POST','_COOKIE','_SERVER')as$b){$Ke=filter_input_array(constant("INPUT$b"),FILTER_UNSAFE_RAW);if($Ke){$$b=$Ke;}}}if(isset($_GET["file"])){header("Expires: ".gmdate("D, d M Y H:i:s",time()+365*24*60*60)." GMT");if($_GET["file"]=="favicon.ico"){header("Content-Type: image/x-icon");echo
base64_decode("AAABAAEAEBAQAAEABAAoAQAAFgAAACgAAAAQAAAAIAAAAAEABAAAAAAAwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA////AAAA/wBhTgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAERERAAAAAAETMzEQAAAAATERExAAAAABMRETEAAAAAExERMQAAAAATERExAAAAABMRETEAAAAAETMzEREREQATERExEhEhABEzMxEhEREAAREREhERIRAAAAARIRESEAAAAAESEiEQAAAAABEREQAAAAAAAAAAD///8BwP//AYB//wGAf/8BgH//AYB//wGAf/8BgH//AYAB/wGAAf8BgAH/AcAA/wH+AP8B/wD/Af+B/wH///8B");}elseif($_GET["file"]=="default.css"){header("Content-Type: text/css; charset=utf-8");echo'body{color:#000;background:#fff;font:90%/1.25 Verdana,Arial,Helvetica,sans-serif;margin:0;}a{color:blue;}a:visited{color:navy;}a:hover{color:red;}h1{font-size:150%;margin:0;padding:.8em 1em;border-bottom:1px solid #999;font-weight:normal;color:#777;background:#eee;}h2{font-size:150%;margin:0 0 20px -18px;padding:.8em 1em;border-bottom:1px solid #000;color:#000;font-weight:normal;background:#ddf;}h3{font-weight:normal;font-size:130%;margin:1em 0 0;}form{margin:0;}table{margin:1em 20px 0 0;border:0;border-top:1px solid #999;border-left:1px solid #999;font-size:90%;}td,th{border:0;border-right:1px solid #999;border-bottom:1px solid #999;padding:.2em .3em;}th{background:#eee;text-align:left;}thead th{text-align:center;}thead td,thead th{background:#ddf;}fieldset{display:inline;vertical-align:top;padding:.5em .8em;margin:.8em .5em 0 0;border:1px solid #999;}p{margin:.8em 20px 0 0;}img{vertical-align:middle;border:0;}td img{max-width:200px;max-height:200px;}code{background:#eee;}tr:hover td,tr:hover th{background:#ddf;}pre{margin:1em 0 0;}.version{color:#777;font-size:67%;}.js .hidden{display:none;}.nowrap td,.nowrap th,td.nowrap{white-space:pre;}.wrap td{white-space:normal;}.error{color:red;background:#fee;}.error b{background:#fff;font-weight:normal;}.message{color:green;background:#efe;}.error,.message{padding:.5em .8em;margin:1em 20px 0 0;}.char{color:#007F00;}.date{color:#7F007F;}.enum{color:#007F7F;}.binary{color:red;}.odd td{background:#F5F5F5;}.time{color:silver;font-size:70%;}.function{text-align:right;}.number{text-align:right;}.datetime{text-align:right;}.type{width:15ex;width:auto\\9;}#menu{position:absolute;margin:10px 0 0;padding:0 0 30px 0;top:2em;left:0;width:19em;overflow:auto;overflow-y:hidden;white-space:nowrap;}#menu p{padding:.8em 1em;margin:0;border-bottom:1px solid #ccc;}#content{margin:2em 0 0 21em;padding:10px 20px 20px 0;}#lang{position:absolute;top:0;left:0;line-height:1.8em;padding:.3em 1em;}#breadcrumb{white-space:nowrap;position:absolute;top:0;left:21em;background:#eee;height:2em;line-height:1.8em;padding:0 1em;margin:0 0 0 -18px;}#h1{color:#777;text-decoration:none;font-style:italic;}#version{font-size:67%;color:red;}#schema{margin-left:60px;position:relative;}#schema .table{border:1px solid silver;padding:0 2px;cursor:move;position:absolute;}#schema .references{position:absolute;}@media print{#lang,#menu{display:none;}#content{margin-left:1em;}#breadcrumb{left:1em;}}';}elseif($_GET["file"]=="functions.js"){header("Content-Type: text/javascript; charset=utf-8");?>
document.body.className='js';function toggle(id){var el=document.getElementById(id);el.className=(el.className=='hidden'?'':'hidden');return true;}
function cookie(assign,days,params){var date=new Date();date.setDate(date.getDate()+days);document.cookie=assign+'; expires='+date+(params||'');}
function verifyVersion(protocol){cookie('adminer_version=0',1);var script=document.createElement('script');script.src=protocol+'://www.adminer.org/version.php';document.body.appendChild(script);}
function formCheck(el,name){var elems=el.form.elements;for(var i=0;i<elems.length;i++){if(name.test(elems[i].name)){elems[i].checked=el.checked;}}}
function formUncheck(id){document.getElementById(id).checked=false;}
function formChecked(el,name){var checked=0;var elems=el.form.elements;for(var i=0;i<elems.length;i++){if(name.test(elems[i].name)&&elems[i].checked){checked++;}}
return checked;}
function tableClick(event){var el=event.target||event.srcElement;while(!/^tr$/i.test(el.tagName)){if(/^(table|a|input|textarea)$/i.test(el.tagName)){return;}
el=el.parentNode;}
el=el.firstChild.firstChild;el.click&&el.click();el.onclick&&el.onclick();}
function setHtml(id,html){var el=document.getElementById(id);if(el){if(html==undefined){el.parentNode.innerHTML='&nbsp;';}else{el.innerHTML=html;}}}
function selectAddRow(field){var row=field.parentNode.cloneNode(true);var selects=row.getElementsByTagName('select');for(var i=0;i<selects.length;i++){selects[i].name=selects[i].name.replace(/[a-z]\[[0-9]+/,'$&1');selects[i].selectedIndex=0;}
var inputs=row.getElementsByTagName('input');if(inputs.length){inputs[0].name=inputs[0].name.replace(/[a-z]\[[0-9]+/,'$&1');inputs[0].value='';inputs[0].className='';}
field.parentNode.parentNode.appendChild(row);field.onchange=function(){};}
function textareaKeydown(target,event,tab,button){if(tab&&event.keyCode==9&&!event.shiftKey&&!event.altKey&&!event.ctrlKey&&!event.metaKey){if(target.setSelectionRange){var start=target.selectionStart;target.value=target.value.substr(0,start)+'\t'+target.value.substr(target.selectionEnd);target.setSelectionRange(start+1,start+1);return false;}else if(target.createTextRange){document.selection.createRange().text='\t';return false;}}
if(event.ctrlKey&&(event.keyCode==13||event.keyCode==10)&&!event.altKey&&!event.metaKey){if(button){button.click();}else{target.form.submit();}}
return true;}
function selectDblClick(td,event,text){var pos=event.rangeOffset;var value=(td.firstChild.firstChild?td.firstChild.firstChild.data:(td.firstChild.alt?td.firstChild.alt:td.firstChild.data));var input=document.createElement(text?'textarea':'input');input.name=td.id;input.value=(value=='\u00A0'||td.getElementsByTagName('i').length?'':value);input.style.width=Math.max(td.clientWidth-14,20)+'px';if(text){var rows=1;value.replace(/\n/g,function(){rows++;});input.rows=rows;input.onkeydown=function(event){return textareaKeydown(input,event||window.event);};}
if(document.selection){var range=document.selection.createRange();range.moveToPoint(event.x,event.y);var range2=range.duplicate();range2.moveToElementText(td);range2.setEndPoint('EndToEnd',range);pos=range2.text.length;}
td.innerHTML='';td.appendChild(input);input.focus();input.selectionStart=pos;input.selectionEnd=pos;if(document.selection){var range=document.selection.createRange();range.moveStart('character',pos);range.select();}
td.ondblclick=function(){};}
function bodyLoad(version,protocol){var jushRoot=protocol + '://www.adminer.org/static/';var script=document.createElement('script');script.src=jushRoot+'jush.js';script.onload=function(){if(window.jush){jush.create_links=' target="_blank"';jush.urls.sql[0]='http://dev.mysql.com/doc/refman/'+version+'/en/$key';jush.urls.sql_sqlset=jush.urls.sql[0];jush.urls.sqlset[0]=jush.urls.sql[0];jush.urls.sqlstatus[0]=jush.urls.sql[0];jush.urls.pgsql[0]='http://www.postgresql.org/docs/'+version+'/static/$key';jush.urls.pgsql_pgsqlset=jush.urls.pgsql[0];jush.urls.pgsqlset[0]='http://www.postgresql.org/docs/'+version+'/static/runtime-config-$key.html#GUC-$1';jush.style(jushRoot+'jush.css');if(window.jushLinks){jush.custom_links=jushLinks;}
jush.highlight_tag('pre',0);jush.highlight_tag('code');}};script.onreadystatechange=function(){if(/^(loaded|complete)$/.test(script.readyState)){script.onload();}};document.body.appendChild(script);}
function selectValue(select){return select.value||select.options[select.selectedIndex].text;}
function formField(form,name){for(var i=0;i<form.length;i++){if(form[i].name==name){return form[i];}}}
function typePassword(el,disable){try{el.type=(disable?'text':'password');}catch(e){}}
var added='.',rowCount;function reEscape(s){return s.replace(/[\[\]\\^$*+?.(){|}]/,'\\$&');}
function idfEscape(s){return s.replace(/`/,'``');}
function editingNameChange(field){var name=field.name.substr(0,field.name.length-7);var type=formField(field.form,name+'[type]');var opts=type.options;var table=reEscape(field.value);var column='';var match;if((match=/(.+)_(.+)/.exec(table))||(match=/(.*[a-z])([A-Z].*)/.exec(table))){table=match[1];column=match[2];}
var plural='(?:e?s)?';var tabCol=table+plural+'_?'+column;var re=new RegExp('(^'+idfEscape(table+plural)+'`'+idfEscape(column)+'$'
+'|^'+idfEscape(tabCol)+'`'
+'|^'+idfEscape(column+plural)+'`'+idfEscape(table)+'$'
+')|`'+idfEscape(tabCol)+'$','i');var candidate;for(var i=opts.length;i--;){if(!/`/.test(opts[i].value)){if(i==opts.length-2&&candidate&&!match[1]&&name=='fields[1]'){return false;}
break;}
if(match=re.exec(opts[i].value)){if(candidate){return false;}
candidate=i;}}
if(candidate){type.selectedIndex=candidate;type.onchange();}}
function editingAddRow(button,allowed,focus){if(allowed&&rowCount>=allowed){return false;}
var match=/([0-9]+)(\.[0-9]+)?/.exec(button.name);var x=match[0]+(match[2]?added.substr(match[2].length):added)+'1';var row=button.parentNode.parentNode;var row2=row.cloneNode(true);var tags=row.getElementsByTagName('select');var tags2=row2.getElementsByTagName('select');for(var i=0;i<tags.length;i++){tags2[i].name=tags[i].name.replace(/([0-9.]+)/,x);tags2[i].selectedIndex=tags[i].selectedIndex;}
tags=row.getElementsByTagName('input');tags2=row2.getElementsByTagName('input');var input=tags2[0];for(var i=0;i<tags.length;i++){if(tags[i].name=='auto_increment_col'){tags2[i].value=x;tags2[i].checked=false;}
tags2[i].name=tags[i].name.replace(/([0-9.]+)/,x);if(/\[(orig|field|comment|default)/.test(tags[i].name)){tags2[i].value='';}
if(/\[(has_default)/.test(tags[i].name)){tags2[i].checked=false;}}
tags[0].onchange=function(){editingNameChange(tags[0]);};row.parentNode.insertBefore(row2,row.nextSibling);if(focus){input.onchange=function(){editingNameChange(input);};input.focus();}
added+='0';rowCount++;return true;}
function editingRemoveRow(button){var field=formField(button.form,button.name.replace(/drop_col(.+)/,'fields$1[field]'));field.parentNode.removeChild(field);button.parentNode.parentNode.style.display='none';return true;}
var lastType='';function editingTypeChange(type){var name=type.name.substr(0,type.name.length-6);var text=selectValue(type);for(var i=0;i<type.form.elements.length;i++){var el=type.form.elements[i];if(el.name==name+'[length]'&&!((/(char|binary)$/.test(lastType)&&/(char|binary)$/.test(text))||(/(enum|set)$/.test(lastType)&&/(enum|set)$/.test(text)))){el.value='';}
if(lastType=='timestamp'&&el.name==name+'[has_default]'&&/timestamp/i.test(formField(type.form,name+'[default]').value)){el.checked=false;}
if(el.name==name+'[collation]'){el.className=(/(char|text|enum|set)$/.test(text)?'':'hidden');}
if(el.name==name+'[unsigned]'){el.className=(/(int|float|double|decimal)$/.test(text)?'':'hidden');}
if(el.name==name+'[on_delete]'){el.className=(/`/.test(text)?'':'hidden');}}}
function editingLengthFocus(field){var td=field.parentNode;if(/(enum|set)$/.test(selectValue(td.previousSibling.firstChild))){var edit=document.getElementById('enum-edit');var val=field.value;edit.value=(/^'.+','.+'$/.test(val)?val.substr(1,val.length-2).replace(/','/g,"\n").replace(/''/g,"'"):val);td.appendChild(edit);field.style.display='none';edit.style.display='inline';edit.focus();}}
function editingLengthBlur(edit){var field=edit.parentNode.firstChild;var val=edit.value;field.value=(/\n/.test(val)?"'"+val.replace(/\n+$/,'').replace(/'/g,"''").replace(/\n/g,"','")+"'":val);field.style.display='inline';edit.style.display='none';}
function columnShow(checked,column){var trs=document.getElementById('edit-fields').getElementsByTagName('tr');for(var i=0;i<trs.length;i++){trs[i].getElementsByTagName('td')[column].className=(checked?'':'hidden');}}
function partitionByChange(el){var partitionTable=/RANGE|LIST/.test(selectValue(el));el.form['partitions'].className=(partitionTable||!el.selectedIndex?'hidden':'');document.getElementById('partition-table').className=(partitionTable?'':'hidden');}
function partitionNameChange(el){var row=el.parentNode.parentNode.cloneNode(true);row.firstChild.firstChild.value='';el.parentNode.parentNode.parentNode.appendChild(row);el.onchange=function(){};}
function foreignAddRow(field){var row=field.parentNode.parentNode.cloneNode(true);var selects=row.getElementsByTagName('select');for(var i=0;i<selects.length;i++){selects[i].name=selects[i].name.replace(/\]/,'1$&');selects[i].selectedIndex=0;}
field.parentNode.parentNode.parentNode.appendChild(row);field.onchange=function(){};}
function indexesAddRow(field){var row=field.parentNode.parentNode.cloneNode(true);var spans=row.getElementsByTagName('span');for(var i=0;i<spans.length-1;i++){row.removeChild(spans[i]);}
var selects=row.getElementsByTagName('select');for(var i=0;i<selects.length;i++){selects[i].name=selects[i].name.replace(/indexes\[[0-9]+/,'$&1');selects[i].selectedIndex=0;}
var input=row.getElementsByTagName('input')[0];input.name=input.name.replace(/indexes\[[0-9]+/,'$&1');input.value='';field.parentNode.parentNode.parentNode.appendChild(row);field.onchange=function(){};}
function indexesAddColumn(field){var column=field.parentNode.cloneNode(true);var select=column.getElementsByTagName('select')[0];select.name=select.name.replace(/\]\[[0-9]+/,'$&1');select.selectedIndex=0;var input=column.getElementsByTagName('input')[0];input.name=input.name.replace(/\]\[[0-9]+/,'$&1');input.value='';field.parentNode.parentNode.appendChild(column);field.onchange=function(){};}
var that,x,y,em,tablePos;function schemaMousedown(el,event){that=el;x=event.clientX-el.offsetLeft;y=event.clientY-el.offsetTop;}
function schemaMousemove(ev){if(that!==undefined){ev=ev||event;var left=(ev.clientX-x)/em;var top=(ev.clientY-y)/em;var divs=that.getElementsByTagName('div');var lineSet={};for(var i=0;i<divs.length;i++){if(divs[i].className=='references'){var div2=document.getElementById((divs[i].id.substr(0,4)=='refs'?'refd':'refs')+divs[i].id.substr(4));var ref=(tablePos[divs[i].title]?tablePos[divs[i].title]:[div2.parentNode.offsetTop/em,0]);var left1=-1;var isTop=true;var id=divs[i].id.replace(/^ref.(.+)-.+/,'$1');if(divs[i].parentNode!=div2.parentNode){left1=Math.min(0,ref[1]-left)-1;divs[i].style.left=left1+'em';divs[i].getElementsByTagName('div')[0].style.width=-left1+'em';var left2=Math.min(0,left-ref[1])-1;div2.style.left=left2+'em';div2.getElementsByTagName('div')[0].style.width=-left2+'em';isTop=(div2.offsetTop+ref[0]*em>divs[i].offsetTop+top*em);}
if(!lineSet[id]){var line=document.getElementById(divs[i].id.replace(/^....(.+)-[0-9]+$/,'refl$1'));var shift=ev.clientY-y-that.offsetTop;line.style.left=(left+left1)+'em';if(isTop){line.style.top=(line.offsetTop+shift)/em+'em';}
if(divs[i].parentNode!=div2.parentNode){line=line.getElementsByTagName('div')[0];line.style.height=(line.offsetHeight+(isTop?-1:1)*shift)/em+'em';}
lineSet[id]=true;}}}
that.style.left=left+'em';that.style.top=top+'em';}}
function schemaMouseup(ev){if(that!==undefined){ev=ev||event;tablePos[that.firstChild.firstChild.firstChild.data]=[(ev.clientY-y)/em,(ev.clientX-x)/em];that=undefined;var s='';for(var key in tablePos){s+='_'+key+':'+Math.round(tablePos[key][0]*10000)/10000+'x'+Math.round(tablePos[key][1]*10000)/10000;}
cookie('adminer_schema='+encodeURIComponent(s.substr(1)),30,'; path="'+location.pathname+location.search+'"');}}<?php
}else{header("Content-Type: image/gif");switch($_GET["file"]){case"plus.gif":echo
base64_decode("R0lGODdhEgASAKEAAO7u7gAAAJmZmQAAACwAAAAAEgASAAACIYSPqcvtD00I8cwqKb5v+q8pIAhxlRmhZYi17iPE8kzLBQA7");break;case"cross.gif":echo
base64_decode("R0lGODdhEgASAKEAAO7u7gAAAJmZmQAAACwAAAAAEgASAAACI4SPqcvtDyMKYdZGb355wy6BX3dhlOEx57FK7gtHwkzXNl0AADs=");break;case"up.gif":echo
base64_decode("R0lGODdhEgASAKEAAO7u7gAAAJmZmQAAACwAAAAAEgASAAACIISPqcvtD00IUU4K730T9J5hFTiKEXmaYcW2rgDH8hwXADs=");break;case"down.gif":echo
base64_decode("R0lGODdhEgASAKEAAO7u7gAAAJmZmQAAACwAAAAAEgASAAACIISPqcvtD00I8cwqKb5bV/5cosdMJtmcHca2lQDH8hwXADs=");break;case"arrow.gif":echo
base64_decode("R0lGODlhCAAKAIAAAICAgP///yH5BAEAAAEALAAAAAAIAAoAAAIPBIJplrGLnpQRqtOy3rsAADs=");break;}}exit;}function
connection(){global$g;return$g;}function
idf_unescape($N){$wb=substr($N,-1);return
str_replace($wb.$wb,$wb,substr($N,1,-1));}function
escape_string($b){return
substr(q($b),1,-1);}function
remove_slashes($xb){if(get_magic_quotes_gpc()){while(list($c,$b)=each($xb)){foreach($b
as$Na=>$w){unset($xb[$c][$Na]);if(is_array($w)){$xb[$c][stripslashes($Na)]=$w;$xb[]=&$xb[$c][stripslashes($Na)];}else{$xb[$c][stripslashes($Na)]=($ie?$w:stripslashes($w));}}}}}function
bracket_escape($N,$Ze=false){static$Te=array(':'=>':1',']'=>':2','['=>':3');return
strtr($N,($Ze?array_flip($Te):$Te));}function
h($G){return
htmlspecialchars($G,ENT_QUOTES);}function
nbsp($G){return(trim($G)!=""?h($G):"&nbsp;");}function
nl_br($G){return
str_replace("\n","<br>",$G);}function
checkbox($f,$p,$Ea,$Oe="",$Pe=""){static$U=0;$U++;$e="<input type='checkbox'".($f?" name='$f' value='".h($p)."'":"").($Ea?" checked":"").($Pe?" onclick=\"$Pe\"":"")." id='checkbox-$U'>";return($Oe!=""?"<label for='checkbox-$U'>$e".h($Oe)."</label>":$e);}function
optionlist($Fc,$vf=null,$Be=false){$e="";foreach($Fc
as$Na=>$w){if(is_array($w)){$e.='<optgroup label="'.h($Na).'">';}foreach((is_array($w)?$w:array($Na=>$w))as$c=>$b){$e.='<option'.($Be||is_string($c)?' value="'.h($c).'"':'').(($Be||is_string($c)?(string)$c:$b)===$vf?' selected':'').'>'.h($b);}if(is_array($w)){$e.='</optgroup>';}}return$e;}function
html_select($f,$Fc,$p="",$Lb=true){if($Lb){return"<select name='".h($f)."'".(is_string($Lb)?" onchange=\"$Lb\"":"").">".optionlist($Fc,$p)."</select>";}$e="";foreach($Fc
as$c=>$b){$e.="<label><input type='radio' name='".h($f)."' value='".h($c)."'".($c==$p?" checked":"").">".h($b)."</label>";}return$e;}function
ini_bool($wf){$b=ini_get($wf);return(eregi('^(on|true|yes)$',$b)||(int)$b);}function
q($G){global$g;return$g->quote($G);}function
get_vals($j,$H=0){global$g;$e=array();$i=$g->query($j);if(is_object($i)){while($a=$i->fetch_row()){$e[]=$a[$H];}}return$e;}function
get_key_vals($j,$I=null){global$g;if(!is_object($I)){$I=$g;}$e=array();$i=$I->query($j);while($a=$i->fetch_row()){$e[$a[0]]=$a[1];}return$e;}function
get_rows($j,$I=null,$n="<p class='error'>"){global$g;if(!is_object($I)){$I=$g;}$e=array();$i=$I->query($j);if(is_object($i)){while($a=$i->fetch_assoc()){$e[]=$a;}}elseif(!$i&&$n&&(headers_sent()||ob_get_level())){echo$n.error()."\n";}return$e;}function
unique_array($a,$K){foreach($K
as$v){if(ereg("PRIMARY|UNIQUE",$v["type"])){$e=array();foreach($v["columns"]as$c){if(!isset($a[$c])){continue
2;}$e[$c]=$a[$c];}return$e;}}$e=array();foreach($a
as$c=>$b){if(!preg_match('~^(COUNT\\((\\*|(DISTINCT )?`(?:[^`]|``)+`)\\)|(AVG|GROUP_CONCAT|MAX|MIN|SUM)\\(`(?:[^`]|``)+`\\))$~',$c)){$e[$c]=$b;}}return$e;}function
where($t){global$_;$e=array();foreach((array)$t["where"]as$c=>$b){$e[]=idf_escape(bracket_escape($c,1)).(ereg('\\.',$b)||$_=="mssql"?" LIKE ".exact_value(addcslashes($b,"%_")):" = ".exact_value($b));}foreach((array)$t["null"]as$c){$e[]=idf_escape($c)." IS NULL";}return
implode(" AND ",$e);}function
where_check($b){parse_str($b,$Ae);remove_slashes(array(&$Ae));return
where($Ae);}function
where_link($l,$H,$p,$Df="="){return"&where%5B$l%5D%5Bcol%5D=".urlencode($H)."&where%5B$l%5D%5Bop%5D=".urlencode($Df)."&where%5B$l%5D%5Bval%5D=".urlencode($p);}function
cookie($f,$p){global$Vb;$zc=array($f,(ereg("\n",$p)?"":$p),time()+2592000,preg_replace('~\\?.*~','',$_SERVER["REQUEST_URI"]),"",$Vb);if(version_compare(PHP_VERSION,'5.2.0')>=0){$zc[]=true;}return
call_user_func_array('setcookie',$zc);}function
restart_session(){if(!ini_bool("session.use_cookies")){session_start();}}function&get_session($c){return$_SESSION[$c][DRIVER][SERVER][$_GET["username"]];}function
set_session($c,$b){$_SESSION[$c][DRIVER][SERVER][$_GET["username"]]=$b;}function
auth_url($Ib,$F,$Q){global$ja;preg_match('~([^?]*)\\??(.*)~',remove_from_uri(implode("|",array_keys($ja))."|username|".session_name()),$k);return"$k[1]?".(SID&&!$_COOKIE?SID."&":"").($Ib!="server"||$F!=""?urlencode($Ib)."=".urlencode($F)."&":"")."username=".urlencode($Q).($k[2]?"&$k[2]":"");}function
redirect($la,$za=null){if(isset($za)){restart_session();$_SESSION["messages"][]=$za;}if(isset($la)){header("Location: ".($la!=""?$la:"."));exit;}}function
query_redirect($j,$la,$za,$Nc=true,$hf=true,$se=false){global$g,$n,$r;if($hf){$se=!$g->query($j);}$Id="";if($j){$Id=$r->messageQuery($j);}if($se){$n=error().$Id;return
false;}if($Nc){redirect($la,$za.$Id);}return
true;}function
queries($j=null){global$g;static$eb=array();if(!isset($j)){return
implode(";\n",$eb);}$eb[]=$j;return$g->query($j);}function
apply_queries($j,$D,$df='table'){foreach($D
as$h){if(!queries("$j ".$df($h))){return
false;}}return
true;}function
queries_redirect($la,$za,$Nc){return
query_redirect(queries(),$la,$za,$Nc,false,!$Nc);}function
remove_from_uri($ab=""){return
substr(preg_replace("~(?<=[?&])($ab".(SID?"":"|".session_name()).")=[^&]*&~",'',"$_SERVER[REQUEST_URI]&"),0,-1);}function
pagination($aa,$cf){return" ".($aa==$cf?$aa+1:'<a href="'.h(remove_from_uri("page").($aa?"&page=$aa":"")).'">'.($aa+1)."</a>");}function
get_file($c,$_e=false){$sa=$_FILES[$c];if(!$sa||$sa["error"]){return$sa["error"];}return
file_get_contents($_e&&ereg('\\.gz$',$sa["name"])?"compress.zlib://$sa[tmp_name]":($_e&&ereg('\\.bz2$',$sa["name"])?"compress.bzip2://$sa[tmp_name]":$sa["tmp_name"]));}function
upload_error($n){$ye=($n==UPLOAD_ERR_INI_SIZE?ini_get("upload_max_filesize"):null);return($n?lang(0).($ye?" ".lang(1,$ye):""):lang(2));}function
odd($e=' class="odd"'){static$l=0;if(!$e){$l=-1;}return($l++%
2?$e:'');}function
is_utf8($b){return(preg_match('~~u',$b)&&!preg_match('~[\\0-\\x8\\xB\\xC\\xE-\\x1F]~',$b));}function
shorten_utf8($G,$da=80,$if=""){if(!preg_match("(^([\t\r\n -\x{FFFF}]{0,$da})($)?)u",$G,$k)){preg_match("(^([\t\r\n -~]{0,$da})($)?)",$G,$k);}return
h($k[1]).$if.(isset($k[2])?"":"<i>...</i>");}function
friendly_url($b){return
preg_replace('~[^a-z0-9_]~i','-',$b);}function
hidden_fields($xb,$jf=array()){while(list($c,$b)=each($xb)){if(is_array($b)){foreach($b
as$Na=>$w){$xb[$c."[$Na]"]=$w;}}elseif(!in_array($c,$jf)){echo'<input type="hidden" name="'.h($c).'" value="'.h($b).'">';}}}function
hidden_fields_get(){echo(SID&&!$_COOKIE?'<input type="hidden" name="'.session_name().'" value="'.h(session_id()).'">':''),(SERVER!==null?'<input type="hidden" name="'.DRIVER.'" value="'.h(SERVER).'">':""),'<input type="hidden" name="username" value="'.h($_GET["username"]).'">';}function
column_foreign_keys($h){$e=array();foreach(foreign_keys($h)as$A){foreach($A["source"]as$b){$e[$b][]=$A;}}return$e;}function
enum_input($y,$Oa,$d,$p){preg_match_all("~'((?:[^']|'')*)'~",$d["length"],$ka);foreach($ka[1]as$l=>$b){$b=stripcslashes(str_replace("''","'",$b));$Ea=(is_int($p)?$p==$l+1:(is_array($p)?in_array($l+1,$p):$p===$b));echo" <label><input type='$y'$Oa value='".($l+1)."'".($Ea?' checked':'').'>'.h($b).'</label>';}}function
input($d,$p,$P){global$T,$r,$_;$f=h(bracket_escape($d["field"]));echo"<td class='function'>";$W=(isset($_GET["select"])?array("orig"=>lang(3)):array())+$r->editFunctions($d);$Oa=" name='fields[$f]'";if($d["type"]=="enum"){echo
nbsp($W[""])."<td>".($W["orig"]?"<label><input type='radio'$Oa value='-1' checked><i>$W[orig]</i></label> ":""),$r->editInput($_GET["edit"],$d,$Oa,$p);enum_input("radio",$Oa,$d,$p);}else{$fb=0;foreach($W
as$c=>$b){if($c===""||!$b){break;}$fb++;}$Lb=($fb?" onchange=\"var f = this.form['function[".addcslashes($f,"\r\n'\\")."]']; if ($fb > f.selectedIndex) f.selectedIndex = $fb;\"":"");$Oa.=$Lb;echo(count($W)>1?html_select("function[$f]",$W,!isset($P)||in_array($P,$W)||isset($W[$P])?$P:""):nbsp(reset($W))).'<td>';$fe=$r->editInput($_GET["edit"],$d,$Oa,$p);if($fe!=""){echo$fe;}elseif($d["type"]=="set"){preg_match_all("~'((?:[^']|'')*)'~",$d["length"],$ka);foreach($ka[1]as$l=>$b){$b=stripcslashes(str_replace("''","'",$b));$Ea=(is_int($p)?($p>>$l)&1:in_array($b,explode(",",$p),true));echo" <label><input type='checkbox' name='fields[$f][$l]' value='".(1<<$l)."'".($Ea?' checked':'')."$Lb>".h($b).'</label>';}}elseif(ereg('blob|bytea|raw|file',$d["type"])&&ini_bool("file_uploads")){echo"<input type='file' name='fields-$f'$Lb>";}elseif(ereg('text|lob',$d["type"])){echo"<textarea ".($_!="sqlite"||ereg("\n",$p)?"cols='50' rows='12'":"cols='30' rows='1' style='height: 1.2em;'")."$Oa onkeydown='return textareaKeydown(this, event);'>".h($p).'</textarea>';}else{$od=(!ereg('int',$d["type"])&&preg_match('~^([0-9]+)(,([0-9]+))?$~',$d["length"],$k)?((ereg("binary",$d["type"])?2:1)*$k[1]+($k[3]?1:0)+($k[2]&&!$d["unsigned"]?1:0)):($T[$d["type"]]?$T[$d["type"]]+($d["unsigned"]?0:1):0));echo"<input value='".h($p)."'".($od?" maxlength='$od'":"").(ereg('char|binary',$d["type"])&&$od>20?" size='40'":"")."$Oa>";}}}function
process_input($d){global$r;$N=bracket_escape($d["field"]);$P=$_POST["function"][$N];$p=$_POST["fields"][$N];if($d["type"]=="enum"){if($p==-1){return
false;}if($p==""){return"NULL";}return
intval($p);}if($d["auto_increment"]&&$p==""){return
null;}if($P=="orig"){return
false;}if($P=="NULL"){return"NULL";}if($d["type"]=="set"){return
array_sum((array)$p);}if(ereg('blob|bytea|raw|file',$d["type"])&&ini_bool("file_uploads")){$sa=get_file("fields-$N");if(!is_string($sa)){return
false;}return
q($sa);}return$r->processInput($d,$p,$P);}function
search_tables(){global$r,$g;$_GET["where"][0]["op"]="LIKE %%";$_GET["where"][0]["val"]=$_POST["query"];$qa=false;foreach(table_status()as$h=>$J){$f=$r->tableName($J);if(isset($J["Engine"])&&$f!=""&&(!$_POST["tables"]||in_array($h,$_POST["tables"]))){$i=$g->query("SELECT".limit("1 FROM ".table($h)," WHERE ".implode(" AND ",$r->selectSearchProcess(fields($h),array())),1));if($i->fetch_row()){if(!$qa){echo"<ul>\n";$qa=true;}echo"<li><a href='".h(ME."select=".urlencode($h)."&where[0][op]=".urlencode($_GET["where"][0]["op"])."&where[0][val]=".urlencode($_GET["where"][0]["val"]))."'>".h($f)."</a>\n";}}}echo($qa?"</ul>":"<p class='message'>".lang(4))."\n";}function
dump_csv($a){foreach($a
as$c=>$b){if(preg_match("~[\"\n,;]~",$b)||$b===""){$a[$c]='"'.str_replace('"','""',$b).'"';}}echo
implode(($_POST["format"]=="csv"?",":";"),$a)."\n";}function
apply_sql_function($P,$H){return($P?($P=="unixepoch"?"DATETIME($H, '$P')":($P=="count distinct"?"COUNT(DISTINCT ":strtoupper("$P("))."$H)"):$H);}function
password_file(){$Xc=ini_get("upload_tmp_dir");if(!$Xc){if(function_exists('sys_get_temp_dir')){$Xc=sys_get_temp_dir();}else{$Z=@tempnam("","");if(!$Z){return
false;}$Xc=dirname($Z);unlink($Z);}}$Z="$Xc/adminer.key";$e=@file_get_contents($Z);if($e){return$e;}$Ma=@fopen($Z,"w");if($Ma){$e=md5(uniqid(mt_rand(),true));fwrite($Ma,$e);fclose($Ma);}return$e;}function
is_mail($pf){$Se='[-a-z0-9!#$%&\'*+/=?^_`{|}~]';$oc='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';$ha="$Se+(\\.$Se+)*@($oc?\\.)+$oc";return
preg_match("(^$ha(,\\s*$ha)*\$)i",$pf);}function
is_url($G){$oc='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';return(preg_match("~^(https?)://($oc?\\.)+$oc(:[0-9]+)?(/.*)?(\\?.*)?(#.*)?\$~i",$G,$k)?strtolower($k[1]):"");}function
print_fieldset($U,$qf,$rf=false){echo"<fieldset><legend><a href='#fieldset-$U' onclick=\"return !toggle('fieldset-$U');\">$qf</a></legend><div id='fieldset-$U'".($rf?"":" class='hidden'").">\n";}function
bold($G,$of){return($of?"<b>$G</b>":$G);}if(!isset($_SERVER["REQUEST_URI"])){$_SERVER["REQUEST_URI"]=$_SERVER["ORIG_PATH_INFO"].($_SERVER["QUERY_STRING"]!=""?"?$_SERVER[QUERY_STRING]":"");}$Vb=$_SERVER["HTTPS"]&&strcasecmp($_SERVER["HTTPS"],"off");@ini_set("session.use_trans_sid",false);if(!defined("SID")){session_name("adminer_sid");$zc=array(0,preg_replace('~\\?.*~','',$_SERVER["REQUEST_URI"]),"",$Vb);if(version_compare(PHP_VERSION,'5.2.0')>=0){$zc[]=true;}call_user_func_array('session_set_cookie_params',$zc);session_start();}remove_slashes(array(&$_GET,&$_POST,&$_COOKIE));if(function_exists("set_magic_quotes_runtime")){set_magic_quotes_runtime(false);}@set_time_limit(0);$Yb=array('en'=>'English','cs'=>'Čeština','sk'=>'Slovenčina','nl'=>'Nederlands','es'=>'Español','de'=>'Deutsch','fr'=>'Français','it'=>'Italiano','et'=>'Eesti','hu'=>'Magyar','ca'=>'Català','ru'=>'Русский язык','zh'=>'简体中文','zh-tw'=>'繁體中文','ja'=>'日本語','ta'=>'த‌மிழ்',);function
lang($N,$Zc=null){global$Wa,$na;$Pb=$na[$N];if(is_array($Pb)&&$Pb){$yc=($Zc==1||(!$Zc&&$Wa=='fr')?0:((!$Zc||$Zc>=5)&&ereg('cs|sk|ru',$Wa)?2:1));$Pb=$Pb[$yc];}$Yd=func_get_args();array_shift($Yd);return
vsprintf((isset($Pb)?$Pb:$N),$Yd);}function
switch_lang(){global$Wa,$Yb;echo"<form action=''>\n<div id='lang'>";hidden_fields($_GET,array('lang'));echo
lang(5).": ".html_select("lang",$Yb,$Wa,"this.form.submit();")," <input type='submit' value='".lang(6)."' class='hidden'>\n","</div>\n</form>\n";}if(isset($_GET["lang"])){$_COOKIE["adminer_lang"]=$_GET["lang"];$_SESSION["lang"]=$_GET["lang"];}$Wa="en";if(isset($Yb[$_COOKIE["adminer_lang"]])){cookie("adminer_lang",$_COOKIE["adminer_lang"]);$Wa=$_COOKIE["adminer_lang"];}elseif(isset($Yb[$_SESSION["lang"]])){$Wa=$_SESSION["lang"];}else{$Vc=array();preg_match_all('~([-a-z]+)(;q=([0-9.]+))?~',str_replace("_","-",strtolower($_SERVER["HTTP_ACCEPT_LANGUAGE"])),$ka,PREG_SET_ORDER);foreach($ka
as$k){$Vc[$k[1]]=(isset($k[3])?$k[3]:1);}arsort($Vc);foreach($Vc
as$c=>$ra){if(isset($Yb[$c])){$Wa=$c;break;}$c=preg_replace('~-.*~','',$c);if(!isset($Vc[$c])&&isset($Yb[$c])){$Wa=$c;break;}}}switch($Wa){case"ca":$na=array('Impossible adjuntar el fitxer.','La mida màxima permesa del fitxer és de %sB.','El fitxer no existeix.','original','Cap taula.','Idioma','Utilitza','Please use one of the extensions %s.','El fitxer ja existeix.','Tipus de l\'usuari','Nombres','Data i temps','Cadenes','Binari','Xarxa','Geometria','Llistes','Sistema','Servidor','Nom d\'usuari','Contrasenya','Inicia la sessió','Sessió permanent','Escull dades','Mostra l\'estructura','Modifica la vista','Modifica la taula','Nou element','Plana','darrera','Edita',array('%d byte','%d bytes'),'Escull','Funcions','Agregació','Cerca','a qualsevol lloc','Ordena','descendent','Límit','Longitud del text','Acció','Ordre SQL','buit','obre','desa','Exporta','Desconnecta','base de dades','esquema','Crea una nova taula','escull','Cap extensió','No hi ha cap de les extensions PHP soporatades (%s) disponible.','Token CSRF invàlid. Torna a enviar el formulari.','Desconnexió correcta.','Cal que estigui permès l\'us de sessions.','La sessió ha expirat, torna a iniciar-ne una.','Credencials invàlids.','Les dades POST són massa grans. Redueix les dades o incrementa la directiva de configuració %s.','Base de dades','Base de dades invàlida.','S\'han suprimit les bases de dades.','Selecciona base de dades','Crea una nova base de dades','Privilegis','Llista de processos','Variables','Estat','Versió %s: %s amb l\'extensió de PHP %s','Connectat com: %s','Compaginació','Taules','Suprimeix','Estàs segur?','Esquema','Invalid schema.','No hi ha cap fila.','Claus foranes','compaginació','ON DELETE','Nom de la columna','Nom del paràmetre','Tipus','Llargada','Opcions','Increment automàtic','Valors per defecte','Comentari','Afegeix el següent','Mou a dalt','Mou a baix','Esborra','Vista','Taula','Columna','Índexs','Modifica els índexs','Font','Destí','ON UPDATE','Modifica','Afegeix una clau forana','Activadors','Afegeix un activador','Esquema de la base de dades','Exporta','Sortida','Format','Rutines','Events','Data','edita','Crea un usuari','Error en la consulta','%.3f s',array('%d fila','%d files'),array('Consulta executada correctament, %d fila modificada.','Consulta executada correctament, %d files modificades.'),'Cap comanda per executar.','Adjunta un fitxer','L\'ddjunció de fitxers està desactivada.','Executa','Atura en trobar un error','En el servidor','Fitxer %s del servidor web','Executa el fitxer','Història','Esborra','S\'ha suprmit l\'element.','S\'ha actualitzat l\'element.','S\'ha insertat l\'element%s.','Insereix','Desa','Desa i segueix editant','Desa i insereix el següent','Suprimeix','S\'ha creat la taula.','S\'ha creat la taula.','S\'ha creat la taula.','Crea una taula','S\'ha assolit el nombre màxim de camps. Incrementa %s i %s.','Nom de la taula','motor','Fes particions segons','Particions','Nom de la partició','Valors','S\'han modificat els índexs.','Tipus d\'índex','Columna (longitud)','S\'ha suprimit la base de dades.','S\'ha canviat el nom de la base de dades.','S\'ha creat la base de dades.','S\'ha modificat la base de dades.','Modifica la base de dades','Crea una base de dades','S\'ha suprimit l\'esquema.','S\'ha creat l\'esquema.','S\'ha modificat l\'esquema.','Modifica l\'esquema','Crea un esquema','Crida',array('S\'ha cridat la rutina, %d fila modificada.','S\'ha cridat la rutina, %d files modificades.'),'S\'ha suprimit la clau forana.','S\'ha modificat la clau forana.','S\'ha creat la clau forana.','Les columnes origen i destí han de ser del mateix tipus, la columna destí ha d\'estar indexada i les dades referenciades han d\'existir.','Clau forana','Taula de destí','Canvi','Afegeix una columna','S\'ha suprimit la vista.','S\'ha modificat la vista.','S\'ha creat la vista.','Crea una vista','Nom','S\'ha suprimit l\'event.','S\'ha modificat l\'event.','S\'ha creat l\'event.','Modifica l\'event','Crea un event','Comença','Acaba','Cada','Conservar en completar','S\'ha suprimit la rutina.','S\'ha modificat la rutina.','S\'ha creat la rutina.','Modifica la funció','Modifica el procediment','Crea una funció','Crea un procediment','Tipus retornat','S\'ha suprimit la seqüència.','S\'ha creat la seqüència.','S\'ha modificat la seqüència.','Modifica la seqüència','Crea una seqüència','S\'ha suprimit el tipus.','S\'ha creat el tipus.','Modifica el tipus','Crea un tipus','S\'ha suprimit l\'activador.','S\'ha modificat l\'activador.','S\'ha creat l\'activador.','Modifica l\'activador','Crea un activador','Temps','Event','S\'ha suprimit l\'usuari.','S\'ha modificat l\'usuari.','S\'ha creat l\'usuari.','Hashed','Rutina','Grant','Revoke',array('S\'ha aturat %d procés.','S\'han aturat %d processos.'),'Atura',array('S\'ha modificat %d element.','S\'han modificat %d elements.'),'Fes un doble clic a un valor per modificar-lo.',array('S\'ha importat %d fila.','S\'han importat %d files.'),'Impossible seleccionar la taula','Relacions','Incrementa la Longitud del text per modificar aquest valor.','Utilitza l\'enllaç d\'edició per modificar aquest valor.','tots els resultats','Clona','Importa CSV','Importa',',','S\'han escapçat les taules.','S\'han desplaçat les taules.','S\'han suprimit les taules.','Taules i vistes','Cerca dades en les taules','Motor','Longitud de les dades','L\'ongitud de l\'índex','Espai lliure','Files','%d en total','Analitza','Optimitza','Verifica','Repara','Escapça','Desplaça a una altra base de dades','Desplaça','Seqüències','Horari','A un moment donat','ara');break;case"cs":$na=array('Nepodařilo se nahrát soubor.','Maximální povolená velikost souboru je %sB.','Soubor neexistuje.','původní','Žádné tabulky.','Jazyk','Vybrat','Prosím použijte jednu z koncovek %s.','Soubor existuje.','Uživatelské typy','Čísla','Datum a čas','Řetězce','Binární','Síť','Geometrie','Seznamy','Systém','Server','Uživatel','Heslo','Přihlásit se','Trvalé přihlášení','Vypsat data','Zobrazit strukturu','Pozměnit pohled','Pozměnit tabulku','Nová položka','Stránka','poslední','Upravit',array('%d bajt','%d bajty','%d bajtů'),'Vypsat','Funkce','Agregace','Vyhledat','kdekoliv','Seřadit','sestupně','Limit','Délka textů','Akce','SQL příkaz','prázdné','otevřít','uložit','Export','Odhlásit','databáze','schéma','Vytvořit novou tabulku','vypsat','Žádná extenze','Není dostupná žádná z podporovaných PHP extenzí (%s).','Neplatný token CSRF. Odešlete formulář znovu.','Odhlášení proběhlo v pořádku.','Session proměnné musí být povolené.','Session vypršela, přihlašte se prosím znovu.','Neplatné přihlašovací údaje.','Příliš velká POST data. Zmenšete data nebo zvyšte hodnotu konfigurační direktivy %s.','Databáze','Nesprávná databáze.','Databáze byly odstraněny.','Vybrat databázi','Vytvořit novou databázi','Oprávnění','Seznam procesů','Proměnné','Stav','Verze %s: %s přes PHP extenzi %s','Přihlášen jako: %s','Porovnávání','Tabulky','Odstranit','Opravdu?','Schéma','Nesprávné schéma.','Žádné řádky.','Cizí klíče','porovnávání','Při smazání','Název sloupce','Název parametru','Typ','Délka','Volby','Auto Increment','Výchozí hodnoty','Komentář','Přidat další','Přesunout nahoru','Přesunout dolů','Odebrat','Pohled','Tabulka','Sloupec','Indexy','Pozměnit indexy','Zdroj','Cíl','Při změně','Změnit','Přidat cizí klíč','Triggery','Přidat trigger','Schéma databáze','Export','Výstup','Formát','Procedury a funkce','Události','Data','upravit','Vytvořit uživatele','Chyba v dotazu','%.3f s',array('%d řádek','%d řádky','%d řádků'),array('Příkaz proběhl v pořádku, byl změněn %d záznam.','Příkaz proběhl v pořádku, byly změněny %d záznamy.','Příkaz proběhl v pořádku, bylo změněno %d záznamů.'),'Žádné příkazy k vykonání.','Nahrání souboru','Nahrávání souborů není povoleno.','Provést','Zastavit při chybě','Ze serveru','Soubor %s na webovém serveru','Spustit soubor','Historie','Vyčistit','Položka byla smazána.','Položka byla aktualizována.','Položka%s byla vložena.','Vložit','Uložit','Uložit a pokračovat v editaci','Uložit a vložit další','Smazat','Tabulka byla odstraněna.','Tabulka byla změněna.','Tabulka byla vytvořena.','Vytvořit tabulku','Byl překročen maximální povolený počet polí. Zvyšte prosím %s a %s.','Název tabulky','úložiště','Rozdělit podle','Oddíly','Název oddílu','Hodnoty','Indexy byly změněny.','Typ indexu','Sloupec (délka)','Databáze byla odstraněna.','Databáze byla přejmenována.','Databáze byla vytvořena.','Databáze byla změněna.','Pozměnit databázi','Vytvořit databázi','Schéma bylo odstraněno.','Schéma bylo vytvořeno.','Schéma bylo změněno.','Pozměnit schéma','Vytvořit schéma','Zavolat',array('Procedura byla zavolána, byl změněn %d záznam.','Procedura byla zavolána, byly změněny %d záznamy.','Procedura byla zavolána, bylo změněno %d záznamů.'),'Cizí klíč byl odstraněn.','Cizí klíč byl změněn.','Cizí klíč byl vytvořen.','Zdrojové a cílové sloupce musí mít stejný datový typ, nad cílovými sloupci musí být definován index a odkazovaná data musí existovat.','Cizí klíč','Cílová tabulka','Změnit','Přidat sloupec','Pohled byl odstraněn.','Pohled byl změněn.','Pohled byl vytvořen.','Vytvořit pohled','Název','Událost byla odstraněna.','Událost byla změněna.','Událost byla vytvořena.','Pozměnit událost','Vytvořit událost','Začátek','Konec','Každých','Po dokončení zachovat','Procedura byla odstraněna.','Procedura byla změněna.','Procedura byla vytvořena.','Změnit funkci','Změnit proceduru','Vytvořit funkci','Vytvořit proceduru','Návratový typ','Sekvence byla odstraněna.','Sekvence byla vytvořena.','Sekvence byla změněna.','Pozměnit sekvenci','Vytvořit sekvenci','Typ byl odstraněn.','Typ byl vytvořen.','Pozměnit typ','Vytvořit typ','Trigger byl odstraněn.','Trigger byl změněn.','Trigger byl vytvořen.','Změnit trigger','Vytvořit trigger','Čas','Událost','Uživatel byl odstraněn.','Uživatel byl změněn.','Uživatel byl vytvořen.','Zahašované','Procedura','Povolit','Zakázat',array('Byl ukončen %d proces.','Byly ukončeny %d procesy.','Bylo ukončeno %d procesů.'),'Ukončit',array('Byl ovlivněn %d záznam.','Byly ovlivněny %d záznamy.','Bylo ovlivněno %d záznamů.'),'Dvojklikněte na políčko, které chcete změnit.',array('Byl importován %d záznam.','Byly importovány %d záznamy.','Bylo importováno %d záznamů.'),'Nepodařilo se vypsat tabulku','Vztahy','Ke změně této hodnoty zvyšte Délku textů.','Ke změně této hodnoty použijte odkaz upravit.','celý výsledek','Klonovat','Import CSV','Import',' ','Tabulky byly vyprázdněny.','Tabulky byly přesunuty.','Tabulky byly odstraněny.','Tabulky a pohledy','Vyhledat data v tabulkách','Úložiště','Velikost dat','Velikost indexů','Volné místo','Řádků','%d celkem','Analyzovat','Optimalizovat','Zkontrolovat','Opravit','Vyprázdnit','Přesunout do jiné databáze','Přesunout','Sekvence','Plán','V daný čas','teď');break;case"de":$na=array('Hochladen von Datei fehlgeschlagen.','Maximal erlaubte Dateigrösse ist %sB.','Datei existiert nicht.','Original','Keine Tabellen.','Sprache','Benutzung','Please use one of the extensions %s.','Datei existiert schon.','Benutzer-definierte Typen','Zahlen','Datum oder Zeit','Zeichenketten','Binär','Netzwerk','Geometrie','Listen','Datenbank System','Server','Benutzer','Passwort','Login','Passwort speichern','Daten auswählen','Struktur anzeigen','View ändern','Tabelle ändern','Neuer Datensatz','Seite','letzte','Ändern',array('%d Byte','%d Bytes'),'Daten zeigen von','Funktionen','Agregationen','Suchen','beliebig','Ordnen','absteigend','Begrenzung','Textlänge','Aktion','SQL-Query','leer','anzeigen','Datei','Export','Abmelden','Datenbank','Schema','Neue Tabelle','zeigen','Keine Erweiterungen installiert','Keine der unterstützten PHP-Erweiterungen (%s) ist vorhanden.','CSRF Token ungültig. Bitte die Formulardaten erneut abschicken.','Abmeldung erfolgreich.','Sitzungen müssen aktiviert sein.','Sitzungsdauer abgelaufen, bitte erneut anmelden.','Ungültige Anmelde-Informationen.','POST data zu gross. Reduzieren Sie die Grösse oder vergrössern Sie den Wert %s in der Konfiguration.','Datenbank','Datenbank ungültig.','Datenbanken entfernt.','Datenbank auswählen','Neue Datenbank','Rechte','Prozessliste','Variablen','Status','Version %s: %s, mit PHP-Erweiterung %s','Angemeldet als: %s','Collation','Tabellen','Entfernen','Sind Sie sicher ?','Schema','Invalid schema.','Keine Daten.','Fremdschlüssel','Kollation','ON DELETE','Spaltenname','Name des Parameters','Typ','Länge','Optionen','Auto-Inkrement','Vorgabewerte festlegen','Kommentar','Hinzufügen','Nach oben','Nach unten','Entfernen','View','Tabelle','Spalte','Indizes','Indizes ändern','Ursprung','Ziel','ON UPDATE','Ändern','Fremdschlüssel hinzufügen','Trigger','Trigger hinzufügen','Datenbankschema','Exportieren','Ergebnis','Format','Prozeduren','Ereignisse','Daten','ändern','Neuer Benutzer','Fehler in der SQL-Abfrage','%.3f s',array('%d Datensatz','%d Datensätze'),array('Abfrage ausgeführt, %d Datensatz betroffen.','Abfrage ausgeführt, %d Datensätze betroffen.'),'Kein Kommando vorhanden.','Datei importieren','Importieren von Dateien abgeschaltet.','Ausführen','Bei Fehler anhalten','Auf Server','Webserver Datei %s','Datei ausführen','History','Entleeren','Datensatz gelöscht.','Datensatz geändert.','Datensatz%s hinzugefügt.','Hinzufügen','Speichern','Speichern und weiter bearbeiten','Speichern und nächsten hinzufügen','Entfernen','Tabelle entfernt.','Tabelle geändert.','Tabelle erstellt.','Neue Tabelle erstellen','Die maximal erlaubte Anzahl der Felder ist überschritten. Bitte %s und %s erhöhen.','Name der Tabelle','Motor','Partitionieren um','Partitionen','Name der Partition','Werte','Indizes geändert.','Index-Typ','Spalte (Länge)','Datenbank entfernt.','Datenbank umbenannt.','Datenbank erstellt.','Datenbank geändert.','Datenbank ändern','Neue Datenbank','Schema wurde gelöscht.','Neues Schema erstellt.','Schema geändert.','Schema ändern','Neues Schema','Aufrufen',array('Kommando SQL ausgeführt, %d Datensatz betroffen.','Kommando SQL ausgeführt, %d Datensätze betroffen.'),'Fremdschlüssel entfernt.','Fremdschlüssel geändert.','Fremdschlüssel erstellt.','Spalten des Ursprungs und des Zieles müssen vom gleichen Datentyp sein, es muss unter den Zielspalten ein Index existieren und die referenzierten Daten müssen existieren.','Fremdschlüssel','Zieltabelle','Ändern','Spalte hinzufügen','View entfernt.','View geändert.','View erstellt.','Neue View erstellen','Name','Ereignis entfernt.','Ereignis geändert.','Ereignis erstellt.','Ereignis ändern','Ereignis erstellen','Start','Ende','Jede','Nach der Ausführung erhalten','Prozedur entfernt.','Prozedur geändert.','Prozedur erstellt.','Funktion ändern','Prozedur ändern','Neue Funktion','Neue Prozedur','Typ des Rückgabewertes','Sequenz gelöscht.','Neue Sequenz erstellt.','Sequenz geändert.','Sequenz ändern','Neue Sequenz','Typ gelöscht.','Typ erstellt.','Typ ändern','Typ erstellen','Trigger entfernt.','Trigger geändert.','Trigger erstellt.','Trigger ändern','Trigger hinzufügen','Zeitpunkt','Ereignis','Benutzer entfernt.','Benutzer geändert.','Benutzer erstellt.','Hashed','Rutine','Erlauben','Verbieten',array('%d Prozess gestoppt.','%d Prozesse gestoppt.'),'Anhalten',array('%d Artikel betroffen.','%d Artikel betroffen.'),'Doppelklick zum Bearbeiten des Wertes.',array('%d Datensatz importiert.','%d Datensätze wurden importiert.'),'Auswahl der Tabelle fehlgeschlagen','Relationen','Vergrössern Sie die Textlänge um den Wert ändern zu können.','Benutzen Sie den Link zum editieren dieses Wertes.','Gesamtergebnis','Klonen','Importiere CSV','Importieren',' ','Tabellen sind entleert worden (truncate).','Tabellen verschoben.','Tabellen wurden entfernt (drop).','Tabellen und Views','Suche in Tabellen','Motor','Datengrösse','Indexgrösse','Freier Bereich','Datensätze','%d insgesamt','Analysieren','Optimieren','Prüfen','Reparieren','Entleeren (truncate)','In andere Datenbank verschieben','Verschieben','Sequenz','Zeitplan','Zur angegebenen Zeit','Anhänge');break;case"en":$na=array('Unable to upload a file.','Maximum allowed file size is %sB.','File does not exist.','original','No tables.','Language','Use','Please use one of the extensions %s.','File exists.','User types','Numbers','Date and time','Strings','Binary','Network','Geometry','Lists','System','Server','Username','Password','Login','Permanent login','Select data','Show structure','Alter view','Alter table','New item','Page','last','Edit',array('%d byte','%d bytes'),'Select','Functions','Aggregation','Search','anywhere','Sort','descending','Limit','Text length','Action','SQL command','empty','open','save','Dump','Logout','database','schema','Create new table','select','No extension','None of the supported PHP extensions (%s) are available.','Invalid CSRF token. Send the form again.','Logout successful.','Session support must be enabled.','Session expired, please login again.','Invalid credentials.','Too big POST data. Reduce the data or increase the %s configuration directive.','Database','Invalid database.','Databases have been dropped.','Select database','Create new database','Privileges','Process list','Variables','Status','%s version: %s through PHP extension %s','Logged as: %s','Collation','Tables','Drop','Are you sure?','Schema','Invalid schema.','No rows.','Foreign keys','collation','ON DELETE','Column name','Parameter name','Type','Length','Options','Auto Increment','Default values','Comment','Add next','Move up','Move down','Remove','View','Table','Column','Indexes','Alter indexes','Source','Target','ON UPDATE','Alter','Add foreign key','Triggers','Add trigger','Database schema','Export','Output','Format','Routines','Events','Data','edit','Create user','Error in query','%.3f s',array('%d row','%d rows'),array('Query executed OK, %d row affected.','Query executed OK, %d rows affected.'),'No commands to execute.','File upload','File uploads are disabled.','Execute','Stop on error','From server','Webserver file %s','Run file','History','Clear','Item has been deleted.','Item has been updated.','Item%s has been inserted.','Insert','Save','Save and continue edit','Save and insert next','Delete','Table has been dropped.','Table has been altered.','Table has been created.','Create table','Maximum number of allowed fields exceeded. Please increase %s and %s.','Table name','engine','Partition by','Partitions','Partition name','Values','Indexes have been altered.','Index Type','Column (length)','Database has been dropped.','Database has been renamed.','Database has been created.','Database has been altered.','Alter database','Create database','Schema has been dropped.','Schema has been created.','Schema has been altered.','Alter schema','Create schema','Call',array('Routine has been called, %d row affected.','Routine has been called, %d rows affected.'),'Foreign key has been dropped.','Foreign key has been altered.','Foreign key has been created.','Source and target columns must have the same data type, there must be an index on the target columns and referenced data must exist.','Foreign key','Target table','Change','Add column','View has been dropped.','View has been altered.','View has been created.','Create view','Name','Event has been dropped.','Event has been altered.','Event has been created.','Alter event','Create event','Start','End','Every','On completion preserve','Routine has been dropped.','Routine has been altered.','Routine has been created.','Alter function','Alter procedure','Create function','Create procedure','Return type','Sequence has been dropped.','Sequence has been created.','Sequence has been altered.','Alter sequence','Create sequence','Type has been dropped.','Type has been created.','Alter type','Create type','Trigger has been dropped.','Trigger has been altered.','Trigger has been created.','Alter trigger','Create trigger','Time','Event','User has been dropped.','User has been altered.','User has been created.','Hashed','Routine','Grant','Revoke',array('%d process has been killed.','%d processes have been killed.'),'Kill',array('%d item has been affected.','%d items have been affected.'),'Double click on a value to modify it.',array('%d row has been imported.','%d rows have been imported.'),'Unable to select the table','Relations','Increase Text length to modify this value.','Use edit link to modify this value.','whole result','Clone','CSV Import','Import',',','Tables have been truncated.','Tables have been moved.','Tables have been dropped.','Tables and views','Search data in tables','Engine','Data Length','Index Length','Data Free','Rows','%d in total','Analyze','Optimize','Check','Repair','Truncate','Move to other database','Move','Sequences','Schedule','At given time',array('%d e-mail has been sent.','%d e-mails have been sent.'));break;case"es":$na=array('No es posible importar archivo.','Tamaño máximo de archivo es %sB.','Archivo no existe.','original','No existen tablas.','Idioma','Usar','Please use one of the extensions %s.','Archivo ya existe.','Tipos definido por el usuario','Números','Fecha y hora','Cadena','Binario','Red','Geometría','Listas','Motor de base de datos','Servidor','Usuario','Contraseña','Login','Guardar contraseña','Seleccionar datos','Mostrar estructura','Modificar vista','Modifique estructura','Nuevo Registro','Página','último','Modificar',array('%d byte','%d bytes'),'Mostrar','Funciones','Agregaciones','Condición','donde sea','Ordenar','descendiente','Limit','Longitud de texto','Acción','Comando SQL','ningúno','mostrar','archivo','Export','Logout','base de datos','esquema','Nueva tabla','registros','No hay extension','Ninguna de las extensiones PHP soportadas (%s) está disponible.','Token CSRF inválido. Vuelva a enviar los datos del formulario.','Salida exitosa.','Deben estar habilitadas las sesiones.','Sesión expirada, por favor ingrese su clave de nuevo.','Identificacion inválida.','POST data demasiado grande. Reduzca el tamaño o aumente la directiva de configuración %s.','Base de datos','Base de datos inválida.','Bases de datos eliminadas.','Seleccionar Base de datos','Ingrese nueva base de datos','Privilegios','Lista de procesos','Variables','Estado','Versión %s: %s a través de extensión PHP %s','Logeado como: %s','Colación','Tablas','Eliminar','Está seguro?','Esquema','Invalid schema.','No existen registros.','Claves foráneas','colación','ON DELETE','Nombre de columna','Nombre de Parámetro','Tipo','Longitud','Opciones','Incremento automático','Valores predeterminados','Comentario','Agregar','Mover arriba','Mover abajo','Eliminar','Vistas','Tabla','Columna','Indices','Modificar indices','Origen','Destino','ON UPDATE','Modificar','Agregar clave foránea','Triggers','Agregar trigger','Esquema de base de datos','Exportar','Salida','Formato','Procedimientos','Eventos','Datos','modificar','Crear Usuario','Error en consulta','%.3f s',array('%d registro','%d registros'),array('Consulta ejecutada, %d registro afectado.','Consulta ejecutada, %d registros afectados.'),'No hay comando para ejecutar.','Importar archivo','Importación de archivos deshablilitado.','Ejecutar','Parar en caso de error','Desde servidor','Archivo de servidor web %s','Ejecutar Archivo','History','Vaciar','Registro eliminado.','Registro modificado.','Registro%s insertado.','Agregar','Guardar','Guardar y continuar editando','Guardar e insertar otro','Eliminar','Tabla eliminada.','Tabla modificada.','Tabla creada.','Cree tabla','Cantida máxima de campos permitidos excedidos. Por favor aumente %s y %s.','Nombre de la tabla','motor','Particionar por','Particiones','Nombre de Partición','Valores','Indices modificados.','Tipo de índice','Columna (longitud)','Base de datos eliminada.','Base de datos renombrada.','Base de datos creada.','Base de datos modificada.','Modificar Base de datos','Crear Base de datos','Esquema eliminado.','Esquema creado.','Esquema modificado.','Modificar esquema','Crear esquema','Llamar',array('Consulta ejecutada, %d registro afectado.','Consulta ejecutada, %d registros afectados.'),'Clave externa eliminada.','Clave externa modificada.','Clave externa creada.','Las columnas de origen y destino deben ser del mismo tipo, debe existir un índice entre las columnas del destino y el registro referenciado debe existir.','Clave externa','Tabla de destino','Modificar','Agregar columna','Vista eliminada.','Vista modificada.','Vista creada.','Cear vista','Nombre','Evento eliminado.','Evento modificado.','Evento creado.','Modificar Evento','Crear Evento','Inicio','Fin','Cada','Al completar preservar','Procedimiento eliminado.','Procedimiento modificado.','Procedimiento creado.','Modificar Función','Modificar procedimiento','Crear función','Crear procedimiento','Tipo de valor de regreso','Secuencia eliminada.','Secuencia creada.','Secuencia modificada.','Modificar secuencia','Crear secuencias','Tipo eliminado.','Tipo creado.','Modificar tipo','Crear tipo','Trigger eliminado.','Trigger modificado.','Trigger creado.','Modificar Trigger','Agregar Trigger','Tiempo','Evento','Usuario eliminado.','Usuario modificado.','Usuario creado.','Hash','Rutina','Conceder','Impedir',array('%d proceso detenido.','%d procesos detenidos.'),'Detener',array('%d ítem afectado.','%d itemes afectados.'),'Doble-clic sobre el valor para editarlo.',array('%d registro importado.','%d registros importados.'),'No es posible seleccionar la tabla','Relaciones','Aumente el tamaño del campo de texto para modificar este valor.','Utilice el enlace de modificar para realizar los cambios.','resultado completo','Clonar','Importar CSV','Importar',' ','Tablas vaciadas (truncate).','Se movieron las tablas.','Tablas eliminadas.','Tablas y vistas','Buscar datos en tablas','Motor','Longitud de datos','Longitud de índice','Espacio libre','Registros','%d en total','Analizar','Optimizar','Comprobar','Reparar','Vaciar','Mover a otra base de datos','Mover','Secuencias','Agenda','A hora determinada','Adjuntos');break;case"et":$na=array('Faili üleslaadimine pole võimalik.','Maksimaalne failisuurus %sB.','Faili ei leitud.','originaal','Tabeleid ei leitud.','Keel','Kasuta','Please use one of the extensions %s.','Fail juba eksisteerib.','Kasutajatüübid','Numbrilised','Kuupäev ja kellaaeg','Tekstid','Binaar','Võrk (network)','Geomeetria','Listid','Andmebaasimootor','Server','Kasutajanimi','Parool','Logi sisse','Jäta mind meelde','Vaata andmeid','Näita struktuuri','Muuda vaadet (VIEW)','Muuda tabeli struktuuri','Lisa kirje','Lehekülg','viimane','Muuda',array('%d bait','%d baiti'),'Kuva','Funktsioonid','Liitmine','Otsi','vahet pole','Sorteeri','kahanevalt','Piira','Teksti pikkus','Tegevus','SQL-Päring','tühi','näita brauseris','salvesta failina','Ekspordi','Logi välja','andmebaas','struktuur','Loo uus tabel','kuva','Ei leitud laiendust','Serveris pole ühtegi toetatud PHP laiendustest (%s).','Sobimatu CSRF, palun postitage vorm uuesti.','Väljalogimine õnnestus.','Sessioonid peavad olema lubatud.','Sessioon on aegunud, palun logige uuesti sisse.','Ebakorrektsed andmed.','POST-andmete maht on liialt suur. Palun vähendage andmeid või suurendage %s php-seadet.','Andmebaas','Tundmatu andmebaas.','Andmebaasid on edukalt kustutatud.','Vali andmebaas','Loo uus andmebaas','Õigused','Protsesside nimekiri','Muutujad','Staatus','%s versioon: %s, kasutatud PHP moodul: %s','Sisse logitud: %s','Tähetabel','Tabelid','Kustuta','Kas oled kindel?','Struktuur','Invalid schema.','Sissekanded puuduvad.','Võõrvõtmed (foreign key)','tähetabel','ON DELETE','Veeru nimi','Parameetri nimi','Tüüp','Pikkus','Valikud','Automaatselt suurenev','Vaikimisi väärtused','Kommentaar','Lisa järgmine','Liiguta ülespoole','Liiguta allapoole','Eemalda','Vaata','Tabel','Veerg','Indeksid','Muuda indekseid','Allikas','Sihtkoht','ON UPDATE','Muuda','Lisa võõrvõti','Päästikud (trigger)','Lisa päästik (TRIGGER)','Andmebaasi skeem','Ekspordi','Väljund','Formaat','Protseduurid','Sündmused (EVENTS)','Andmed','muuda','Loo uus kasutaja','Päringus esines viga','%.3f s',array('%d rida','%d rida'),array('Päring õnnestus, mõjutatatud ridu: %d.','Päring õnnestus, mõjutatatud ridu: %d.'),'Käsk puudub.','Faili üleslaadimine','Failide üleslaadimine on keelatud.','Käivita','Peatuda vea esinemisel','Serverist','Fail serveris: %s','Käivita fail','Ajalugu','Puhasta','Kustutamine õnnestus.','Uuendamine õnnestus.','Kirje%s on edukalt lisatud.','Sisesta','Salvesta','Salvesta ja jätka muutmist','Salvesta ja lisa järgmine','Kustuta','Tabel on edukalt kustutatud.','Tabeli andmed on edukalt muudetud.','Tabel on edukalt loodud.','Loo uus tabel','Maksimaalne väljade arv ületatud. Palun suurendage %s ja %s.','Tabeli nimi','andmebaasimootor','Partitsiooni','Partitsioonid','Partitsiooni nimi','Väärtused','Indeksite andmed on edukalt uuendatud.','Indeksi tüüp','Veerg (pikkus)','Andmebaas on edukalt kustutatud.','Andmebaas on edukalt ümber nimetatud.','Andmebaas on edukalt loodud.','Andmebaasi struktuuri uuendamine õnnestus.','Muuda andmebaasi','Loo uus andmebaas','Struktuur on edukalt kustutatud.','Struktuur on edukalt loodud.','Struktuur on edukalt muudetud.','Muuda struktuuri','Loo struktuur','Käivita',array('Protseduur täideti edukalt, mõjutatud ridu: %d.','Protseduur täideti edukalt, mõjutatud ridu: %d.'),'Võõrvõti on edukalt kustutatud.','Võõrvõtme andmed on edukalt muudetud.','Võõrvõri on edukalt loodud.','Lähte- ja sihtveerud peavad eksisteerima ja omama sama andmetüüpi, sihtveergudel peab olema määratud indeks ning viidatud andmed peavad eksisteerima.','Võõrvõti','Siht-tabel','Muuda','Lisa veerg','Vaade (VIEW) on edukalt kustutatud.','Vaade (VIEW) on edukalt muudetud.','Vaade (VIEW) on edukalt loodud.','Loo uus vaade (VIEW)','Nimi','Sündmus on edukalt kustutatud.','Sündmuse andmed on edukalt uuendatud.','Sündmus on edukalt loodud.','Muuda sündmuse andmeid','Loo uus sündmus (EVENT)','Alusta','Lõpeta','Iga','Lõpetamisel jäta sündmus alles','Protseduur on edukalt kustutatud.','Protseduuri andmed on edukalt muudetud.','Protseduur on edukalt loodud.','Muuda funktsiooni','Muuda protseduuri','Loo uus funktsioon','Loo uus protseduur','Tagastustüüp','Jada on edukalt kustutatud.','Jada on edukalt loodud.','Jada on edukalt muudetud.','Muuda jada','Loo jada','Tüüp on edukalt kustutatud.','Tüüp on edukalt loodud.','Muuda tüüpi','Loo tüüp','Päästik on edukalt kustutatud.','Päästiku andmed on edukalt uuendatud.','Uus päästik on edukalt loodud.','Muuda päästiku andmeid','Loo uus päästik (TRIGGER)','Aeg','Sündmus','Kasutaja on edukalt kustutatud.','Kasutaja andmed on edukalt muudetud.','Kasutaja on edukalt lisatud.','Häshitud (Hashed)','Protseduur','Anna','Eemalda',array('Protsess on edukalt peatatud (%d).','Valitud protsessid (%d) on edukalt peatatud.'),'Peata',array('Mõjutatud kirjeid: %d.','Mõjutatud kirjeid: %d.'),'Väärtuse muutmiseks topelt-kliki sellel.',array('Imporditi %d rida.','Imporditi %d rida.'),'Tabeli valimine ebaõnnestus','Seosed','Väärtuse muutmiseks suurenda Tekstiveeru pikkust.','Väärtuse muutmiseks kasuta muutmislinki.','Täielikud tulemused','Kloon','Impordi CSV','Impordi',',','Validud tabelid on edukalt tühjendatud.','Valitud tabelid on edukalt liigutatud.','Valitud tabelid on edukalt kustutatud.','Tabelid ja vaated','Otsi kogu andmebaasist','Implementatsioon','Andmete pikkus','Indeksi pikkus','Vaba ruumi','Ridu','Kokku: %d','Analüüsi','Optimeeri','Kontrolli','Paranda','Tühjenda','Liiguta teise andmebaasi','Liiguta','Jadad (sequences)','Ajakava','Antud ajahetkel','Manused');break;case"fr":$na=array('Impossible d\'importer le fichier.','La taille maximale des fichiers est de %sB.','Le fichier est introuvable.','original','Aucune table.','Langue','Utiliser','Please use one of the extensions %s.','Le fichier existe.','Types utilisateur','Nombres','Date et heure','Chaînes','Binaires','Réseau','Géométrie','Listes','Système','Serveur','Utilisateur','Mot de passe','Authentification','Authentification permanente','Afficher les données','Afficher la structure','Modifier une vue','Modifier la table','Nouvel élément','Page','dernière','Modifier',array('%d octet','%d octets'),'Select','Fonctions','Agrégation','Rechercher','n\'importe où','Trier','décroissant','Limite','Longueur du texte','Action','Requête SQL','vide','ouvrir','sauvegarder','Exporter','Déconnexion','base de données','schéma','Créer une nouvelle table','select','Extension introuvable','Aucune des extensions PHP supportées (%s) n\'est disponible.','Token CSRF invalide. Veuillez réenvoyer le formulaire.','Au revoir!','Veuillez activer les sessions.','Session expirée, veuillez vous authentifier à nouveau.','Authentification échouée.','Données POST trop grandes. Réduisez la taille des données ou augmentez la valeur de %s dans la configuration de PHP.','Base de données','Base de données invalide.','Les bases de données ont été supprimées.','Sélectionner la base de données','Créer une base de données','Privilèges','Liste des processus','Variables','Statut','Version de %s : %s via l\'extension PHP %s','Authentifié en tant que %s','Interclassement','Tables','Supprimer','Êtes-vous certain ?','Schéma','Invalid schema.','Aucun résultat.','Clés étrangères','interclassement','ON DELETE','Nom de la colonne','Nom du paramètre','Type','Longueur','Options','Auto increment','Valeurs par défaut','Commentaire','Ajouter le prochain','Déplacer vers le haut','Déplacer vers le bas','Effacer','Vue','Table','Colonne','Index','Modifier les index','Source','Cible','ON UPDATE','Modifier','Ajouter une clé étrangère','Triggers','Ajouter un trigger','Schéma de la base de données','Exporter','Sortie','Format','Routines','Évènements','Données','modifier','Créer un utilisateur','Erreur dans la requête','%.3f s',array('%d ligne','%d lignes'),array('Requête exécutée avec succès, %d ligne modifiée.','Requête exécutée avec succès, %d lignes modifiées.'),'Aucune commande à exécuter.','Importer un fichier','L\'importation de fichier est désactivée.','Exécuter','Arrêter en cas d\'erreur','Depuis le serveur','Fichier %s du serveur Web','Exécuter le fichier','Historique','Effacer','L\'élément a été supprimé.','L\'élément a été modifié.','L\'élément%s a été inséré.','Insérer','Sauvegarder','Sauvegarder et continuer l\'édition','Sauvegarder et insérer le prochain','Effacer','La table a été effacée.','La table a été modifiée.','La table a été créée.','Créer une table','Le nombre maximum de champs est dépassé. Veuillez augmenter %s et %s.','Nom de la table','moteur','Partitionner par','Partitions','Nom de la partition','Valeurs','Index modifiés.','Type d\'index','Colonne (longueur)','La base de données a été supprimée.','La base de données a été renommée.','La base de données a été créée.','La base de données a été modifiée.','Modifier la base de données','Créer une base de données','Le schéma a été supprimé.','Le schéma a été créé.','Le schéma a été modifié.','Modifier le schéma','Créer un schéma','Appeler',array('La routine a été exécutée, %d ligne modifiée.','La routine a été exécutée, %d lignes modifiées.'),'La clé étrangère a été effacée.','La clé étrangère a été modifiée.','La clé étrangère a été créée.','Les colonnes de source et de destination doivent être du même type, il doit y avoir un index sur les colonnes de destination et les données référencées doivent exister.','Clé étrangère','Table visée','Modifier','Ajouter une colonne','La vue a été effacée.','La vue a été modifiée.','La vue a été créée.','Créer une vue','Nom','L\'évènement a été supprimé.','L\'évènement a été modifié.','L\'évènement a été créé.','Modifier un évènement','Créer un évènement','Démarrer','Terminer','Chaque','Conserver quand complété','La routine a été supprimée.','La routine a été modifiée.','La routine a été créée.','Modifier la fonction','Modifier la procédure','Créer une fonction','Créer une procédure','Type de retour','La séquence a été supprimée.','La séquence a été créée.','La séquence a été modifiée.','Modifier la séquence','Créer une séquence','Le type a été supprimé.','Le type a été créé.','Modifier le type','Créer un type','Le trigger a été supprimé.','Le trigger a été modifié.','Le trigger a été créé.','Modifier un trigger','Ajouter un trigger','Temps','Évènement','L\'utilisateur a été effacé.','L\'utilisateur a été modifié.','L\'utilisateur a été créé.','Haché','Routine','Grant','Revoke',array('%d processus a été arrêté.','%d processus ont été arrêtés.'),'Arrêter',array('%d élément a été modifié.','%d éléments ont été modifiés.'),'Double-cliquez sur une valeur pour la modifier.',array('%d ligne a été importée.','%d lignes ont été importées.'),'Impossible de sélectionner la table','Relations','Augmentez la Longueur de texte affiché pour modifier cette valeur.','Utilisez le lien "modifier" pour modifier cette valeur.','résultat entier','Cloner','Importer CSV','Importer',',','Les tables ont été tronquées.','Les tables ont été déplacées.','Les tables ont été effacées.','Tables et vues','Rechercher dans les tables','Moteur','Longueur des données','Longueur de l\'index','Espace inutilisé','Lignes','%d au total','Analyser','Optimiser','Vérifier','Réparer','Tronquer','Déplacer vers une autre base de données','Déplacer','Séquences','Horaire','À un moment précis','maintenant');break;case"hu":$na=array('Nem tudom feltölteni a fájlt.','A maximális fájlméret %s B.','A fájl nem létezik.','eredeti','Nincs tábla.','Nyelv','Használ','Please use one of the extensions %s.','A fájl létezik.','Felhasználói típus','Szám','Dátum és idő','Szöveg','Bináris','Hálózat','Geometria','Lista','Adatbázis','Szerver','Felhasználó','Jelszó','Belépés','Emlékezz rám','Tartalom','Struktúra','Nézet módosítása','Tábla módosítása','Új tétel','oldal','utoljára','Szerkeszt',array('%d bájt','%d bájt','%d bájt'),'Kiválaszt','Funkciók','Aggregálás','Keresés','bárhol','Sorba rendezés','csökkenő','korlát','Szöveg hossz','Művelet','SQL parancs','üres','megnyit','ment','Exportálás','Kilépés','adatbázis','schéma','Új tábla','kiválaszt','Nincs kiterjesztés','Nincs egy elérhető támogatott PHP kiterjesztés (%s) sem.','Érvénytelen CSRF azonosító. Küldd újra az űrlapot.','Sikeres kilépés.','A munkameneteknek (session) engedélyezve kell lennie.','Munkamenet lejárt, jelentkezz be újra.','Érvénytelen adatok.','Túl sok a POST adat! Csökkentsd az adat méretét, vagy növeld a %s beállítást.','Adatbázis','Érvénytelen adatbázis.','Adatbázis eldobva.','Adatbázis kiválasztása','Új adatbázis','Privilégiumok','Folyamatok','Változók','Állapot','%s verzió: %s, PHP: %s','Belépve: %s','Egybevetés','Táblák','Eldob','Biztos benne?','Schéma','Invalid schema.','Nincs megjeleníthető eredmény.','Idegen kulcs','egybevetés','törléskor','Oszlop neve','Paraméter neve','Típus','Hossz','Opciók','Automatikus növelés','Alapértelmezett értékek','Megjegyzés','Következő hozzáadása','Felfelé','Lefelé','Eltávolítás','Nézet','Tábla','Oszlop','Indexek','Index módosítása','Forrás','Cél','frissítéskor','Módosítás','Idegen kulcs hozzadása','Trigger','Trigger hozzáadása','Adatbázis séma','Export','Kimenet','Formátum','Rutinok','Esemény','Adat','szerkeszt','Felhasználó hozzáadása','Hiba a lekérdezésben','%.3f másodperc',array('%d sor','%d sor','%d sor'),array('Lekérdezés sikeresen végrehajtva, %d sor érintett.','Lekérdezés sikeresen végrehajtva, %d sor érintett.','Lekérdezés sikeresen végrehajtva, %d sor érintett.'),'Nincs végrehajtható parancs.','Fájl feltöltése','A fájl feltöltés le van tiltva.','Végrehajt','Hiba esetén megáll','Szerverről','Webszerver fájl %s','Fájl futtatása','Történet','Törlés','A tétel törölve.','A tétel frissítve.','%s tétel beszúrva.','Beszúr','Mentés','Mentés és szerkesztés folytatása','Mentés és újat beszúr','Törlés','A tábla eldobva.','A tábla módosult.','A tábla létrejött.','Tábla létrehozása','A maximális mezőszámot elérted. Növeld meg ezeket: %s, %s.','Tábla név','motor','Rozdělit podle','Particiók','Partició neve','Értékek','Az indexek megváltoztak.','Index típusa','Oszop (méret)','Az adatbázis eldobva.','Az adadtbázis átnevezve.','Az adatbázis létrejött.','Az adatbázis módosult.','Adatbázis módosítása','Adatbázis létrehozása','Séma eldobva.','Séma létrejött.','Séma módosult.','Séma módosítása','Séma létrehozása','Meghív',array('Rutin meghívva, %d sor érintett.','Rutin meghívva, %d sor érintett.','Rutin meghívva, %d sor érintett.'),'Idegen kulcs eldobva.','Idegen kulcs módosult.','Idegen kulcs létrejött.','A forrás és cél oszlopoknak azonos típusúak legyenek, a cél oszlopok indexeltek legyenek, és a hivatkozott adatnak léteznie kell.','Idegen kulcs','Cél tábla','Változtat','Oszlop hozzáadása','A nézet eldobva.','A nézet módosult.','A nézet létrejött.','Nézet létrehozása','Név','Az esemény eldobva.','Az esemény módosult.','Az esemény létrejött.','Esemény módosítása','Esemény létrehozása','Kezd','Vége','Minden','Befejezéskor megőrzi','A rutin eldobva.','A rutin módosult.','A rutin létrejött.','Funkció módosítása','Eljárás módosítása','Funkció létrehozása','Eljárás létrehozása','Visszatérési érték','Sorozat eldobva.','Sorozat létrejött.','Sorozat módosult.','Sorozat módosítása','Sorozat létrehozása','Típus eldobva.','Típus létrehozva.','Típus módosítása','Típus létrehozása','A trigger eldobva.','A trigger módosult.','A trigger létrejött.','Trigger módosítása','Trigger létrehozása','Idő','Esemény','A felhasználó eldobva.','A felhasználó módosult.','A felhasználó létrejött.','Hashed','Rutin','Engedélyezés','Visszavonás',array('%d folyamat leállítva.','%d folyamat leállítva.','%d folyamat leállítva.'),'Leállít',array('%d tétel érintett.','%d tétel érintett.','%d tétel érintett.'),'Kattints kétszer az értékre a szerkesztéshez.',array('%d sor importálva.','%d sor importálva.','%d sor importálva.'),'Nem tudom kiválasztani a táblát','Reláció','Növeld a Szöveg hosszát, hogy módosítani tudd ezt az értéket.','Használd a szerkesztés hivatkozást ezen érték módosításához.','összes eredményt mutatása','Klónoz','CSV importálása','Importálás',' ','A tábla felszabadítva.','Táblák áthelyezve.','Táblák eldobva.','Táblák és nézetek','Keresés a táblákban','Motor','Méret','Index hossz','Adat szabad','Oszlop','összesen %d','Elemzés','Optimalizál','Ellenőrzés','Javít','Felszabadít','Áthelyezés másik adatbázisba','Áthelyez','Sorozatok','Ütemzés','Megadott időben','most');break;case"it":$na=array('Caricamento del file non riuscito.','La dimensione massima del file è %sB.','Il file non esiste.','originale','No tabelle.','Lingua','Usa','Please use one of the extensions %s.','Il file esiste già.','Tipi definiti dall\'utente','Numeri','Data e ora','Stringhe','Binari','Rete','Geometria','Liste','Sistema','Server','Utente','Password','Autenticazione','Login permanente','Visualizza dati','Visualizza struttura','Modifica vista','Modifica tabella','Nuovo elemento','Pagina','ultima','Modifica',array('%d byte','%d bytes'),'Seleziona','Funzioni','Aggregazione','Cerca','ovunque','Ordina','discendente','Limite','Lunghezza testo','Azione','Comando SQL','vuoto','apri','salva','Dump','Esci','database','schema','Crea nuova tabella','seleziona','Estensioni non presenti','Nessuna delle estensioni PHP supportate (%s) disponibile.','Token CSRF non valido. Reinvia la richiesta.','Uscita effettuata con successo.','Le sessioni devono essere abilitate.','Sessione scaduta, autenticarsi di nuovo.','Credenziali non valide.','Troppi dati via POST. Ridurre i dati o aumentare la direttiva di configurazione %s.','Database','Database non valido.','Database eliminati.','Seleziona database','Crea nuovo database','Privilegi','Elenco processi','Variabili','Stato','Versione %s: %s via estensione PHP %s','Autenticato come: %s','Collazione','Tabelle','Elimina','Sicuro?','Schema','Invalid schema.','Nessuna riga.','Chiavi esterne','collazione','ON DELETE','Nome colonna','Nome parametro','Tipo','Lunghezza','Opzioni','Auto incremento','Valori predefiniti','Commento','Aggiungi altro','Sposta su','Sposta giu','Rimuovi','Vedi','Tabella','Colonna','Indici','Modifica indici','Sorgente','Obiettivo','ON UPDATE','Modifica','Aggiungi foreign key','Trigger','Aggiungi trigger','Schema database','Esporta','Risultato','Formato','Routine','Eventi','Dati','modifica','Crea utente','Errore nella query','%.3f s',array('%d riga','%d righe'),array('Esecuzione della query OK, %d riga interessata.','Esecuzione della query OK, %d righe interessate.'),'Nessun commando da eseguire.','Caricamento file','Caricamento file disabilitato.','Esegui','Stop su errore','Dal server','Webserver file %s','Esegui file','Storico','Pulisci','Elemento eliminato.','Elemento aggiornato.','Elemento%s inserito.','Inserisci','Salva','Salva e continua','Salva e inserisci un altro','Elimina','Tabella eliminata.','Tabella modificata.','Tabella creata.','Crea tabella','Troppi campi. Per favore aumentare %s e %s.','Nome tabella','motore','Partiziona per','Partizioni','Nome partizione','Valori','Indici modificati.','Tipo indice','Colonna (lunghezza)','Database eliminato.','Database rinominato.','Database creato.','Database modificato.','Modifica database','Crea database','Schema eliminato.','Schema creato.','Schema modificato.','Modifica schema','Crea schema','Chiama',array('Routine chiamata, %d riga interessata.','Routine chiamata, %d righe interessate.'),'Foreign key eliminata.','Foreign key modificata.','Foreign key creata.','Le colonne sorgente e destinazione devono essere dello stesso tipo e ci deve essere un indice sulla colonna di destinazione e sui dati referenziati.','Foreign key','Tabella obiettivo','Cambia','Aggiungi colonna','Vista eliminata.','Vista modificata.','Vista creata.','Crea vista','Nome','Evento eliminato.','Evento modificato.','Evento creato.','Modifica evento','Crea evento','Inizio','Fine','Ogni','Al termine preservare','Routine eliminata.','Routine modificata.','Routine creata.','Modifica funzione','Modifica procedura','Crea funzione','Crea procedura','Return type','Sequenza eliminata.','Sequenza creata.','Sequenza modificata.','Modifica sequenza','Crea sequenza','Tipo definito dall\'utente eliminato.','Tipo definito dall\'utente creato.','Modifica tipo definito dall\'utente','Crea tipo definito dall\'utente','Trigger eliminato.','Trigger modificato.','Trigger creato.','Modifica trigger','Crea trigger','Orario','Evento','Utente eliminato.','Utente modificato.','Utente creato.','Hashed','Routine','Permetti','Revoca',array('%d processo interrotto.','%d processi interrotti.'),'Interrompi',array('Il risultato consiste in %d elemento.','Il risultato consiste in %d elementi.'),'Fai doppio click su un valore per modificarlo.',array('%d riga importata.','%d righe importate.'),'Selezione della tabella non riuscita','Relazioni','Aumenta la Lunghezza del testo per modificare questo valore.','Usa il link modifica per modificare questo valore.','intero risultato','Clona','Importa da CSV','Importa','.','Le tabelle sono state svuotate.','Le tabelle sono state spostate.','Le tabelle sono state eliminate.','Tabelle e viste','Cerca nelle tabelle','Motore','Lunghezza dato','Lunghezza indice','Dati liberi','Righe','%d in totale','Analizza','Ottimizza','Controlla','Ripara','Svuota','Sposta in altro database','Sposta','Sequenza','Pianifica','A tempo prestabilito','Allegati');break;case"ja":$na=array('ファイルをアップロードできません','最大ファイルサイズ %sB','ファイルは存在しません','元','テーブルがありません。','言語','使用','Please use one of the extensions %s.','ファイルが既に存在します','ユーザー定義型','数字','日時','文字列','バイナリ','ネットワーク型','ジオメトリ型','リスト','データベース種類','サーバ','ユーザ名','パスワード','ログイン','永続的にログイン','データ','構造','ビューを変更','テーブルの変更','項目の作成','ページ','最終','編集','%d バイト','選択','関数','集合','検索','任意','ソート','降順','制約','文字列の長さ','動作','SQLコマンド','空','開く','保存','ダンプ','ログアウト','データベース','スキーマ','テーブルを作成','選択','拡張機能がありません','PHPの拡張機能（%s）がセットアップされていません','不正なCSRFトークン。再送信してください','ログアウト','セッションを有効にしてください','セッションの期限切れ。ログインし直してください','不正なログイン','POSTデータが大きすぎます。データサイズを小さくするか %s 設定を大きくしてください','データベース','不正なデータベース','データベースを削除しました','データベースを選択してください','新規にデータベースを作成','権限','プロセス一覧','変数','状態','%sバージョン：%s、 PHP拡張機能 %s','ログ：%s','照合順序','テーブル','削除','実行しますか？','スキーマ','Invalid schema.','行がありません','外部キー','照合順序','ON DELETE','列名','参数名','型','長さ','設定','連番','規定値','コメント','追加','上','下','移除','ビュー','テーブル','列','索引','索引の変更','ソース','ターゲット','ON UPDATE','変更','外部キーを追加','トリガー','トリガーの追加','構造','エクスポート','出力','形式','ルーチン','イベント','データ','編集','ユーザを作成','クエリーのエラー','%.3f 秒','%d 行','クエリーを実行しました。%d 行を変更しました','実行するコマンドがありません','ファイルをアップロード','ファイルのアップロードが無効です','実行','エラーの場合は停止','サーバーから実行','Webサーバファイル %s','ファイルを実行','履歴','消去','項目を削除しました','項目を更新しました','%s項目を挿入しました','挿入','保存','保存して継続','保存／追加','削除','テーブルを削除しました','テーブルを変更しました','テーブルを作成しました','テーブルを作成','定義可能な最大フィールド数を越えました。%s と %s を増やしてください。','テーブル名','エンジン','パーティション','パーティション','パーティション名','値','索引を変更しました','索引の型','列（長さ）','データベースを削除しました','データベースの名前を変えました','データベースを作成しました','データベースを変更しました','データベースを変更','データベースを作成','スキーマを削除しました','スキーマを追加しました','スキーマを変更しました','スキーマ変更','スキーマ追加','呼出し','ルーチンを呼びました。%d 行を変更しました','外部キーを削除しました','外部キーを変更しました','外部キーを作成しました','ソースとターゲットの列は同じデータ型でなければなりません。ターゲット列に索引があり、データが存在しなければなりません。','外キー','テーブル','変更','列を追加','ビューを削除しました','ビューを変更しました','ビューを作成しました','ビューを作成','名称','削除しました','変更しました','作成しました','変更','作成','開始','終了','毎回','完成後に保存','ルーチンを作成','ルーチンを変更','ルーチンを作成','関数の変更','プロシージャの変更','関数の作成','プロシージャの作成','戻り値の型','シーケンスを削除しました','シーケンスを追加しました','シーケンスを変更しました','シーケンス変更','シーケンス作成','ユーザー定義型を削除しました','ユーザー定義型を追加しました','ユーザー定義型変更','ユーザー定義型作成','トリガーを削除しました','トリガーを変更しました','トリガーを追加しました','トリガーの変更','トリガーの作成','時間','イベント','ユーザを削除','ユーザを変更','ユーザを作成','Hashed','ルーチン','権限の付与','権限の取消し','%d プロセスを強制終了しました','強制終了','%d を更新しました','ダブルクリックして編集','%d 行をインポートしました','テーブルを選択できません','関係','編集枠を広げる','リンクを編集する','全結果','クローン','CSV インポート','インポート',',','テーブルをtruncateしました','テーブルを移動しました','テーブルを削除しました','テーブルとビュー','データを検索する','エンジン','データ長','索引長','空き','行数','合計 %d','分析','最適化','チェック','修復','Truncate','別のデータベースへ移動?','移動','シーケンス','スケジュール','指定時刻','現在の日時');break;case"nl":$na=array('Onmogelijk bestand te uploaden.','Maximum toegelaten bestandsgrootte is %sB.','Bestand niet gevonden.','origineel','Geen tabellen.','Taal','Gebruik','Please use one of the extensions %s.','Bestand bestaat reeds.','Gebruikersgedefiniëerde types','Getallen','Datum en tijd','Tekst','Binaire gegevens','Netwerk','Geometrie','Lijsten','Databasesysteem','Server','Gebruikersnaam','Wachtwoord','Inloggen','Blijf aangemeld','Gegevens selecteren','Toon structuur','View aanpassen','Tabel aanpassen','Nieuw item','Pagina','laatste','Bewerk',array('%d byte','%d bytes'),'Kies','Functies','Totalen','Zoeken','overal','Sorteren','Aflopend','Beperk','Tekst lengte','Acties','SQL opdracht','leeg','openen','opslaan','Exporteer','Uitloggen','database','schema','Nieuwe tabel','kies','Geen extensie','Geen geldige PHP extensies beschikbaar (%s).','Ongeldig CSRF token. Verstuur het formulier opnieuw.','Uitloggen geslaagd.','Sessies moeten geactiveerd zijn.','Uw sessie is verlopen. Gelieve opnieuw in te loggen.','Ongeldige logingegevens.','POST-data is te groot. Verklein de hoeveelheid data of verhoog de %s configuratie.','Database','Ongeldige database.','Databases verwijderd.','Database selecteren','Nieuwe database','Rechten','Proceslijst','Variabelen','Status','%s versie: %s met PHP extensie %s','Aangemeld als: %s','Collatie','Tabellen','Verwijderen','Weet u het zeker?','Schema','Invalid schema.','Geen rijen.','Foreign keys','collation','ON DELETE','Kolomnaam','Parameternaam','Type','Lengte','Opties','Auto nummering','Standaard waarden','Commentaar','Volgende toevoegen','Omhoog','Omlaag','Verwijderen','View','Tabel','Kolom','Indexen','Indexen aanpassen','Bron','Doel','ON UPDATE','Aanpassen','Foreign key aanmaken','Triggers','Trigger aanmaken','Database schema','Exporteren','Uitvoer','Formaat','Procedures','Events','Data','bewerk','Gebruiker aanmaken','Fout in query','%.3f s',array('%d rij','%d rijen'),array('Query uitgevoerd, %d rij geraakt.','Query uitgevoerd, %d rijen geraakt.'),'Geen opdrachten uit te voeren.','Bestand uploaden','Bestanden uploaden is uitgeschakeld.','Uitvoeren','Stoppen bij fout','Van server','Webserver bestand %s','Bestand uitvoeren','Geschiedenis','Wissen','Item verwijderd.','Item aangepast.','Item%s toegevoegd.','Toevoegen','Opslaan','Opslaan en verder bewerken','Opslaan, daarna toevoegen','Verwijderen','Tabel verwijderd.','Tabel aangepast.','Tabel aangemaakt.','Tabel aanmaken','Maximum aantal velden bereikt. Verhoog %s en %s.','Tabelnaam','engine','Partitioneren op','Partities','Partitie naam','Waarden','Index aangepast.','Index type','Kolom (lengte)','Database verwijderd.','Database hernoemd.','Database aangemaakt.','Database aangepast.','Database aanpassen','Database aanmaken','Schema verwijderd.','Schema aangemaakt.','Schema gewijzigd.','Schema wijzigen','Schema maken','Uitvoeren',array('Procedure uitgevoerd, %d rij geraakt.','Procedure uitgevoerd, %d rijen geraakt.'),'Foreign key verwijderd.','Foreign key aangepast.','Foreign key aangemaakt.','Bron- en doelkolommen moeten van hetzelfde data type zijn, er moet een index bestaan op de gekozen kolommen en er moet gerelateerde data bestaan.','Foreign key','Doeltabel','Veranderen','Kolom toevoegen','View verwijderd.','View aangepast.','View aangemaakt.','View aanmaken','Naam','Event werd verwijderd.','Event werd aangepast.','Event werd aangemaakt.','Event aanpassen','Event aanmaken','Start','Stop','Iedere','Bewaren na voltooiing','Procedure verwijderd.','Procedure aangepast.','Procedure aangemaakt.','Functie aanpassen','Procedure aanpassen','Functie aanmaken','Procedure aanmaken','Return type','Sequence verwijderd.','Sequence aangemaakt.','Sequence gewijzigd.','Sequence wijzigen','Sequence maken','Type verwijderd.','Type aangemaakt.','Type wijzigen','Type maken','Trigger verwijderd.','Trigger aangepast.','Trigger aangemaakt.','Trigger aanpassen','Trigger aanmaken','Time','Event','Gebruiker verwijderd.','Gebruiker aangepast.','Gebruiker aangemaakt.','Gehashed','Routine','Toekennen','Intrekken',array('%d proces gestopt.','%d processen gestopt.'),'Stoppen',array('%d item aangepast.','%d items aangepast.'),'Dubbelklik op een waarde om deze te bewerken.',array('%d rij werd geïmporteerd.','%d rijen werden geïmporteerd.'),'Onmogelijk tabel te selecteren','Relaties','Verhoog de lengte om deze waarde te bewerken.','Gebruik de link "bewerk" om deze waarde te wijzigen.','volledig resultaat','Dupliceer','CSV Import','Importeren','.','Tabellen werden geleegd.','Tabellen werden verplaatst.','Tabellen werden verwijderd.','Tabellen en views','Zoeken in database','Engine','Data lengte','Index lengte','Data Vrij','Rijen','%d in totaal','Analyseer','Optimaliseer','Controleer','Herstel','Legen','Verplaats naar andere database','Verplaats','Sequences','Schedule','Op aangegeven tijd','Bijlagen');break;case"ru":$na=array('Не удалось загрузить файл на сервер.','Максимальный разрешенный размер файла - %sB.','Такого файла не существует.','исходный','В базе данных нет таблиц.','Язык','Выбрать','Please use one of the extensions %s.','Файл уже существует.','Типы пользователей','Число','Дата и время','Строки','Двоичный тип','Сеть','Геометрия','Списки','Движок','Сервер','Имя пользователя','Пароль','Войти','Оставаться в системе','Выбрать','Показать структуру','Изменить представление','Изменить таблицу','Новая запись','Страница','последняя','Редактировать',array('%d байт','%d байта','%d байтов'),'Выбрать','Функции','Агрегация','Поиск','в любом месте','Сортировать','по убыванию','Лимит','Длина текста','Действие','SQL запрос','пусто','открыть','сохранить','Дамп','Выйти','база данных','схема','Создать новую таблицу','выбрать','Нет расширений','Не доступно ни одного расширения из поддерживаемых (%s).','Недействительный CSRF токен. Отправите форму ещё раз.','Вы успешно покинули систему.','Сессии должны быть включены.','Срок действия сесси истек, нужно снова войти в систему.','Неправильное имя пользователя или пароль.','Слишком большой объем POST-данных. Пошлите меньший объем данных или увеличьте параметр конфигурационной директивы %s.','База данных','Плохая база данных.','Базы данных удалены.','Выбрать базу данных','Создать новую базу данных','Полномочия','Список процессов','Переменные','Состояние','Версия %s: %s с PHP-расширением %s','Вы вошли как: %s','Режим сопоставления','Таблицы','Удалить','Вы уверены?','Схема','Invalid schema.','Нет записей.','Внешние ключи','режим сопоставления','При стирании','Название поля','Название параметра','Тип','Длина','Действие','Автоматическое приращение','Значения по умолчанию','Комментарий','Добавить еще','Переместить вверх','Переместить вниз','Удалить','Представление','Таблица','Колонка','Индексы','Изменить индексы','Источник','Цель','При обновлении','Изменить','Добавить внешний ключ','Триггеры','Добавить триггер','Схема базы данных','Экспорт','Выходные данные','Формат','Хранимые процедуры и функции','События','Данные','редактировать','Создать пользователя','Ошибка в запросe','%.3f s',array('%d строка','%d строки','%d строк'),array('Запрос завершен, изменена %d запись.','Запрос завершен, изменены %d записи.','Запрос завершен, изменено %d записей.'),'Нет команд для выполнения.','Загрузить файл на сервер','Загрузка файлов на сервер запрещена.','Выполнить','Остановить при ошибке','С сервера','Файл %s на вебсервере','Запустить файл','История','Очистить','Запись удалена.','Запись обновлена.','Запись%s была вставлена.','Вставить','Сохранить','Сохранить и продолжить редактирование','Сохранить и вставить еще','Стереть','Таблица была удалена.','Таблица была изменена.','Таблица была создана.','Создать таблицу','Достигнуто максимальное значение количества доступных полей. Увеличьте %s и %s.','Название таблицы','тип','Разделить по','Разделы','Название раздела','Параметры','Индексы изменены.','Тип индекса','Колонка (длина)','База данных была удалена.','База данных была переименована.','База данных была создана.','База данных была изменена.','Изменить базу данных','Создать базу данных','Схема удалена.','Создана новая схема.','Схема изменена.','Изменить схему','Новая схема','Вызвать',array('Была вызвана процедура, %d запись была изменена.','Была вызвана процедура, %d записи было изменено.','Была вызвана процедура, %d записей было изменено.'),'Внешний ключ был удален.','Внешний ключ был изменен.','Внешний ключ был создан.','Колонки должны иметь одинаковые типы данных, в результирующей колонке должен быть индекс, данные для импорта должны существовать.','Внешний ключ','Результирующая таблица','Изменить','Добавить колонку','Представление было удалено.','Представление было изменено.','Представление было создано.','Создать представление','Название','Событие было удалено.','Событие было изменено.','Событие было создано.','Изменить событие','Создать событие','Начало','Конец','Каждые','После завершения сохранить','Процедура была удалена.','Процедура была изменена.','Процедура была создана.','Изменить функцию','Изменить процедуру','Создать функцию','Создать процедуру','Возвращаемый тип','«Последовательность» удалена.','Создана новая «последовательность».','«Последовательность» изменена.','Изменить «последовательность»','Создать «последовательность»','Тип удален.','Создан новый тип.','Изменить тип','Создать тип','Триггер был удален.','Триггер был изменен.','Триггер был создан.','Изменить триггер','Создать триггер','Время','Событие','Пользователь был удален.','Пользователь был изменен.','Пользователь был создан.','Хешировано','Процедура','Позволить','Запретить',array('Был завершен %d процесс.','Было завершено %d процесса.','Было завершёно %d процессов.'),'Завершить',array('Была изменена %d запись.','Были изменены %d записи.','Было изменено %d записей.'),'Кликни два раза по значению, чтобы его изменить.',array('Импортирована %d строка.','Импортировано %d строки.','Импортировано %d строк.'),'Не удалось получить данные из таблицы','Реляции','Увеличь Длину текста, чтобы изменить это значение.','Изменить это значение можно с помощью ссылки «изменить».','весь результат','Клонировать','Импорт CSV','Импорт',' ','Таблицы были очищены.','Таблицы были перемещены.','Таблицы были удалены.','Таблицы и представления','Поиск в таблицах','Тип','Объём данных','Объём индексов','Свободное место','Строк','Всего %d','Анализировать','Оптимизировать','Проверить','Исправить','Очистить','Переместить в другою базу данных','Переместить','«Последовательности»','Расписание','В данное время','Прикрепленные файлы');break;case"sk":$na=array('Súbor sa nepodarilo nahrať.','Maximálna povolená veľkosť súboru je %sB.','Súbor neexistuje.','originál','Žiadne tabuľky.','Jazyk','Vybrať','Please use one of the extensions %s.','Súbor existuje.','Užívateľské typy','Čísla','Dátum a čas','Reťazce','Binárne','Sieť','Geometria','Zoznamy','Systém','Server','Používateľ','Heslo','Prihlásiť sa','Trvalé prihlásenie','Vypísať dáta','Zobraziť štruktúru','Zmeniť pohľad','Zmeniť tabuľku','Nová položka','Stránka','posledný','Upraviť',array('%d bajt','%d bajty','%d bajtov'),'Vypísať','Funkcie','Agregácia','Vyhľadať','kdekoľvek','Zotriediť','zostupne','Limit','Dĺžka textov','Akcia','SQL príkaz','prázdne','otvoriť','uložiť','Export','Odhlásiť','databáza','schéma','Vytvoriť novú tabuľku','vypísať','Žiadne rozšírenie','Nie je dostupné žiadne z podporovaných rozšírení (%s).','Neplatný token CSRF. Odošlite formulár znova.','Odhlásenie prebehlo v poriadku.','Session premenné musia byť povolené.','Session vypršala, prihláste sa prosím znova.','Neplatné prihlasovacie údaje.','Príliš veľké POST dáta. Zmenšite dáta alebo zvýšte hodnotu konfiguračej direktívy %s.','Databáza','Nesprávna databáza.','Databázy boli odstránené.','Vybrať databázu','Vytvoriť novú databázu','Oprávnenia','Zoznam procesov','Premenné','Stav','Verzia %s: %s cez PHP rozšírenie %s','Prihlásený ako: %s','Porovnávanie','Tabuľky','Odstrániť','Naozaj?','Schéma','Invalid schema.','Žiadne riadky.','Cudzie kľúče','porovnávanie','ON DELETE','Názov stĺpca','Názov parametra','Typ','Dĺžka','Voľby','Auto Increment','Východzie hodnoty','Komentár','Pridať ďalší','Presunúť hore','Presunúť dolu','Odobrať','Pohľad','Tabuľka','Stĺpec','Indexy','Zmeniť indexy','Zdroj','Cieľ','ON UPDATE','Zmeniť','Pridať cudzí kľúč','Triggery','Pridať trigger','Schéma databázy','Export','Výstup','Formát','Procedúry','Udalosti','Dáta','upraviť','Vytvoriť používateľa','Chyba v dotaze','%.3f s',array('%d riadok','%d riadky','%d riadkov'),array('Príkaz prebehol v poriadku, bol zmenený %d záznam.','Príkaz prebehol v poriadku boli zmenené %d záznamy.','Príkaz prebehol v poriadku bolo zmenených %d záznamov.'),'Žiadne príkazy na vykonanie.','Nahranie súboru','Nahrávánie súborov nie je povolené.','Vykonať','Zastaviť pri chybe','Zo serveru','Súbor %s na webovom serveri','Spustiť súbor','História','Vyčistiť','Položka bola vymazaná.','Položka bola aktualizovaná.','Položka%s bola vložená.','Vložiť','Uložiť','Uložiť a pokračovať v úpravách','Uložiť a vložiť ďalší','Zmazať','Tabuľka bola odstránená.','Tabuľka bola zmenená.','Tabuľka bola vytvorená.','Vytvoriť tabuľku','Bol prekročený maximálny počet povolených polí. Zvýšte prosím %s a %s.','Názov tabuľky','úložisko','Rozdeliť podľa','Oddiely','Názov oddielu','Hodnoty','Indexy boli zmenené.','Typ indexu','Stĺpec (dĺžka)','Databáza bola odstránená.','Databáza bola premenovaná.','Databáza bola vytvorená.','Databáza bola zmenená.','Zmeniť databázu','Vytvoriť databázu','Schéma bola odstránená.','Schéma bola vytvorená.','Schéma bola zmenená.','Pozmeniť schému','Vytvoriť schému','Zavolať',array('Procedúra bola zavolaná, bol zmenený %d záznam.','Procedúra bola zavolaná, boli zmenené %d záznamy.','Procedúra bola zavolaná, bolo zmenených %d záznamov.'),'Cudzí kľúč bol odstránený.','Cudzí kľúč bol zmenený.','Cudzí kľúč bol vytvorený.','Zdrojové a cieľové stĺpce musia mať rovnaký datový typ, nad cieľovými stĺpcami musí byť definovaný index a odkazované dáta musia existovať.','Cudzí kľúč','Cieľová tabuľka','Zmeniť','Pridať stĺpec','Pohľad bol odstránený.','Pohľad bol zmenený.','Pohľad bol vytvorený.','Vytvoriť pohľad','Názov','Udalosť bola odstránená.','Udalosť bola zmenená.','Udalosť bola vytvorená.','Upraviť udalosť','Vytvoriť udalosť','Začiatok','Koniec','Každých','Po dokončení zachovat','Procedúra bola odstránená.','Procedúra bola zmenená.','Procedúra bola vytvorená.','Zmeniť funkciu','Zmeniť procedúru','Vytvoriť funkciu','Vytvoriť procedúru','Návratový typ','Sekvencia bola odstránená.','Sekvencia bola vytvorená.','Sekvencia bola zmenená.','Pozmeniť sekvenciu','Vytvoriť sekvenciu','Typ bol odstránený.','Typ bol vytvorený.','Pozmeniť typ','Vytvoriť typ','Trigger bol odstránený.','Trigger bol zmenený.','Trigger bol vytvorený.','Zmeniť trigger','Vytvoriť trigger','Čas','Udalosť','Používateľ bol odstránený.','Používateľ bol zmenený.','Používateľ bol vytvorený.','Zahašované','Procedúra','Povoliť','Zakázať',array('Bol ukončený %d proces.','Boli ukončené %d procesy.','Bolo ukončených %d procesov.'),'Ukončiť','%d položiek bolo ovplyvnených.','Dvojkliknite na políčko, ktoré chcete zmeniť.',array('Bol importovaný %d záznam.','Boli importované %d záznamy.','Bolo importovaných %d záznamov.'),'Tabuľku sa nepodarilo vypísať','Vzťahy','Pre zmenu tejto hodnoty zvýšte Dĺžku textov.','Pre zmenu tejto hodnoty použite odkaz upraviť.','celý výsledok','Klonovať','Import CSV','Import',' ','Tabuľka bola vyprázdnená.','Tabuľka bola presunutá.','Tabuľka bola odstránená.','Tabuľky a pohľady','Vyhľadať dáta v tabuľkách','Typ','Veľkosť dát','Veľkosť indexu','Voľné miesto','Riadky','%d celkom','Analyzovať','Optimalizovať','Skontrolovať','Opraviť','Vyprázdniť','Presunúť do inej databázy','Presunúť','Sekvencia','Plán','V stanovený čas','Prílohy');break;case"ta":$na=array('கோப்பை மேலேற்ற‌ம் (upload) செய்ய‌ இயல‌வில்லை.','கோப்பின் அதிக‌ப‌ட்ச‌ அள‌வு %sB.','கோப்பு இல்லை.','அச‌ல்','அட்ட‌வ‌ணை இல்லை.','மொழி','உப‌யோகி','Please use one of the extensions %s.','கோப்பு உள்ள‌து.','ப‌ய‌னாள‌ர் வ‌கைக‌ள்','எண்க‌ள்','தேதி ம‌ற்றும் நேர‌ம்','ச‌ர‌ம் (String)','பைன‌ரி','நெட்வொர்க்','வ‌டிவ‌விய‌ல் (Geometry)','ப‌ட்டிய‌ல்','சிஸ்ட‌ம் (System)','வ‌ழ‌ங்கி (Server)','ப‌ய‌னாள‌ர் (User)','க‌ட‌வுச்சொல்','நுழை','நிர‌ந்த‌ர‌மாக‌ நுழைய‌வும்','த‌க‌வ‌லை தேர்வு செய்','க‌ட்ட‌மைப்பை காண்பிக்க‌வும்','தோற்ற‌த்தை மாற்று','அட்ட‌வ‌ணையை மாற்று','புதிய‌ உருப்ப‌டி','ப‌க்க‌ம்','க‌டைசி','தொகு',array('%d பைட்','%d பைட்டுக‌ள்'),'தேர்வு செய்','Functions','திர‌ள்வு (Aggregation)','தேடு','எங்காயினும்','த‌ர‌ம் பிரி','இற‌ங்குமுக‌மான‌','வ‌ர‌ம்பு','உரை நீள‌ம்','செய‌ல்','SQL க‌ட்ட‌ளை','வெறுமை (empty)','திற‌','சேமி','Dump','வெளியேறு','த‌க‌வ‌ல்த‌ள‌ம்','அமைப்புமுறை','புதிய‌ அட்ட‌வ‌ணையை உருவாக்கு','தேர்வு செய்','விரிவு (extensஇஒன்) இல்லை ','PHP ஆத‌ர‌வு விரிவுக‌ள் (%s) இல்லை.','CSRF டோக்க‌ன் செல்லாது. ப‌டிவ‌த்தை மீண்டும் அனுப்ப‌வும்.','வெற்றிக‌ர‌மாய் வெளியேறியாயிற்று.','செஷ‌ன் ஆத‌ர‌வு இய‌க்க‌ப்ப‌ட‌ வேண்டும்.','செஷ‌ன் காலாவ‌தியாகி விட்ட‌து. மீண்டும் நுழைய‌வும்.','ச‌ரியான‌ விப‌ர‌ங்க‌ள் இல்லை.','மிக‌ அதிக‌மான‌ POST  த‌க‌வ‌ல். த‌க‌வ‌லை குறைக்க‌வும் அல்ல‌து %s வ‌டிவ‌மைப்பை (configuration directive) மாற்ற‌வும்.','த‌க‌வ‌ல்த‌ள‌ம்','த‌க‌வ‌ல்த‌ள‌ம் ச‌ரியானதல்ல‌.','த‌க‌வ‌ல் த‌ள‌ங்க‌ள் நீக்க‌ப்ப‌ட்டன‌.','த‌க‌வ‌ல்த‌ள‌த்தை தேர்வு செய்','புதிய‌ த‌க‌வ‌ல்த‌ள‌த்தை உருவாக்கு','ச‌லுகைக‌ள் / சிற‌ப்புரிமைக‌ள்','வேலைக‌ளின் ப‌ட்டி','மாறிலிக‌ள் (Variables)','நிக‌ழ்நிலை (Status)','%s ப‌திப்பு: %s through PHP extension %s','ப‌ய‌னாளர்: %s','கொலேச‌ன்','அட்ட‌வ‌ணை','நீக்கு','நிச்ச‌ய‌மாக‌ ?','அமைப்புமுறை','Invalid schema.','வ‌ரிசை இல்லை.','வேற்று விசைக‌ள்','கொலேச‌ன்','ON DELETE','நெடுவ‌ரிசையின் பெய‌ர்','அள‌புரு (Parameter) பெய‌ர்','வ‌கை','நீளம்','வேண்டிய‌வ‌ற்றை ','ஏறுமான‌ம்','உள்ளிருக்கும் (Default) ம‌திப்புக‌ள் ','குறிப்பு','அடுத்த‌தை சேர்க்க‌வும்','மேலே ந‌க‌ர்த்து','கீழே நக‌ர்த்து','நீக்கு','தோற்றம்','அட்ட‌வ‌ணை','நெடுவ‌ரிசை','அக‌வ‌ரிசைக‌ள் (Index) ','அக‌வ‌ரிசையை (Index) மாற்று','மூல‌ம்','இல‌க்கு','ON UPDATE','மாற்று','வேற்று விசை சேர்க்க‌வும்','தூண்டுத‌ல்க‌ள்','தூண்டு விசையை சேர்','த‌க‌வ‌ல்த‌ள‌ அமைப்பு முறைக‌ள்','ஏற்றும‌தி','வெளியீடு','ஃபார்ம‌ட் (Format)','ரொட்டீன் ','நிக‌ழ்ச்சிக‌ள்','த‌க‌வ‌ல்','தொகு','ப‌ய‌னாள‌ரை உருவாக்கு','வின‌வ‌லில் த‌வ‌றுள்ள‌து','%.3f s',array('%d வ‌ரிசை','%d வ‌ரிசைக‌ள்'),array('வின‌வ‌ல் செய‌ல்ப‌டுத்த‌ப்ப‌ட்ட‌து, %d வ‌ரிசை மாற்ற‌ப்ப‌ட்ட‌து.','வின‌வ‌ல் செய‌ல்ப‌டுத்த‌ப்ப‌ட்ட‌து, %d வ‌ரிசைக‌ள் மாற்றப்ப‌ட்ட‌ன‌.'),'செய‌ல் ப‌டுத்த‌ எந்த‌ க‌ட்ட‌ளைக‌ளும் இல்லை.','கோப்பை மேலேற்று (upload) ','கோப்புக‌ள் மேலேற்றம் (upload)முட‌க்க‌ப்ப‌ட்டுள்ள‌ன‌.','செய‌ல்ப‌டுத்து','பிழை ஏற்ப‌டின் நிற்க‌','செர்வ‌ரில் இருந்து','வெப் ச‌ர்வ‌ர் கோப்பு %s','கோப்பினை இய‌க்க‌வும்','வ‌ர‌லாறு','துடை (Clear)','உருப்படி நீக்க‌ப்ப‌ட்ட‌து.','உருப்ப‌டி புதுப்பிக்க‌ப்ப‌ட்ட‌து.','உருப்ப‌டி (Item) சேர்க்க‌ப்ப‌ட்ட‌து.','புகுத்து','சேமி','சேமித்த‌ பிற‌கு தொகுப்ப‌தை தொட‌ர‌வும்','சேமித்த‌ப் பின் அடுத்த‌தை புகுத்து','நீக்கு','அட்ட‌வ‌ணை நீக்க‌ப்ப‌ட்ட‌து.','அட்ட‌வணை மாற்ற‌ப்ப‌ட்ட‌து.','அட்ட‌வ‌ணை உருவாக்க‌ப்ப‌ட்ட‌து.','அட்ட‌வ‌ணையை உருவாக்கு','அனும‌திக்க‌ப்ப‌ட்ட‌ அதிக‌ப‌ட்ச‌ கோப்புக‌ளின் எண்ணிக்கை மீற‌ப்ப‌ட்ட‌து. த‌ய‌வு செய்து %s ம‌ற்றும் %s யை அதிக‌ரிக்க‌வும்.','அட்ட‌வ‌ணைப் பெய‌ர்','எஞ்சின்','பிரித்த‌து','பிரிவுக‌ள்','பிரிவின் பெய‌ர்','ம‌திப்புக‌ள்','அக‌வ‌ரிசைக‌ள் (Indexes) மாற்ற‌ப்பட்ட‌து.','அக‌வ‌ரிசை வ‌கை (Index Type)','நெடுவ‌ரிசை (நீள‌ம்)','த‌க‌வ‌ல்த‌ள‌ம் நீக்க‌ப்ப‌ட்ட‌து.','த‌க‌வ‌ல்த‌ள‌ம் பெய‌ர் மாற்ற‌ப்ப‌ட்ட‌து.','த‌க‌வ‌ல்த‌ள‌ம் உருவாக்க‌ப்ப‌ட்ட‌து.','த‌க‌வ‌ல்த‌ள‌ம் மாற்ற‌ப்ப‌ட்ட‌து.','த‌க‌வ‌ல்த‌ள‌த்தை மாற்று','த‌க‌வ‌ல்த‌ள‌த்தை உருவாக்கு','அமைப்புமுறை நீக்க‌ப்ப‌ட்ட‌து.','அமைப்புமுறை உருவாக்க‌ப்ப‌ட்ட‌து.','அமைப்புமுறை மாற்ற‌ப்ப‌ட்ட‌து.','அமைப்புமுறையை மாற்று','அமைப்புமுறையை உருவாக்கு','அழை',array('ரொட்டீன்க‌ள் அழைக்க‌ப்பட்டுள்ள‌ன‌, %d வ‌ரிசை மாற்ற‌ம் அடைந்த‌து.','ரொட்டீன்க‌ள் அழைக்க‌ப்ப‌ட்டுள்ள‌ன‌, %d வ‌ரிசைக‌ள் மாற்றம் அடைந்துள்ள‌ன‌.'),'வேற்று விசை நீக்க‌ப்ப‌ட்ட‌து.','வேற்று விசை மாற்ற‌ப்ப‌ட்ட‌து.','வேற்று விசை உருவாக்க‌ப்ப‌ட்ட‌து.','இல‌க்கு நெடுவ‌ரிசையில் அக‌வ‌ரிசை (Index) ம‌ற்றும் குறிக்க‌ப்ப‌ட்ட‌ த‌க‌வல் (Referenced DATA) க‌ண்டிப்பாக‌ இருத்த‌ல் வேண்டும். மூல‌ நெடுவ‌ரிசை ம‌ற்றும் இலக்கு நெடுவ‌ரிசையின் த‌க‌வ‌ல் வ‌டிவ‌ம் (DATA TYPE) ஒன்றாக‌ இருக்க‌ வேண்டும்.','வேற்று விசை','அட்ட‌வ‌ணை இல‌க்கு','மாற்று','நெடு வ‌ரிசையை சேர்க்க‌வும்','தோற்ற‌ம் நீக்க‌ப்ப‌ட்ட‌து.','தோற்றம் மாற்றப்ப‌ட்ட‌து.','தோற்ற‌ம் உருவாக்க‌ப்ப‌ட்ட‌து.','தோற்றத்தை உருவாக்கு','பெய‌ர்','நிக‌ழ்ச்சி (Event) நீக்க‌ப்ப‌ட்ட‌து.','நிக‌ழ்ச்சி (Event) மாற்றப்ப‌ட்ட‌து.','நிக‌ழ்ச்சி (Event) உருவாக்க‌‌ப்ப‌ட்ட‌து.','நிக‌ழ்ச்சியை (Event) மாற்று','நிக‌ழ்ச்சியை (Event) உருவாக்கு','தொட‌ங்கு','முடி (வு)','ஒவ்வொரு','முடிந்த‌தின் பின் பாதுகாக்க‌வும்','ரொட்டீன் நீக்க‌ப்ப‌ட்ட‌து.','ரொட்டீன் மாற்ற‌ப்ப‌ட்டது.','ரொட்டீன் உருவாக்க‌ப்ப‌ட்ட‌து.','Function மாற்று','செய‌ல்முறையை மாற்று','Function உருவாக்கு','செய்முறையை உருவாக்கு','திரும்பு வ‌கை','வ‌ரிசைமுறை நீக்க‌ப்ப‌ட்ட‌து.','வ‌ரிசைமுறை உருவாக்க‌ப்ப‌ட்ட‌து.','வ‌ரிசைமுறை மாற்ற‌ப்ப‌ட்ட‌து.','வ‌ரிசைமுறையை மாற்று','வ‌ரிசைமுறையை உருவாக்கு','வ‌கை (type) நீக்க‌ப்ப‌ட்ட‌து.','வ‌கை (type) உருவாக்க‌ப்ப‌ட்ட‌து.','வ‌கையினை (type) மாற்று','வ‌கையை உருவாக்கு','தூண்டு விசை நீக்க‌ப்ப‌ட்ட‌து.','தூண்டு விசை மாற்ற‌ப்ப‌ட்ட‌து.','தூண்டு விசை உருவாக்க‌ப்ப‌ட்ட‌து.','தூண்டு விசையை மாற்று','தூண்டு விசையை உருவாக்கு','நேர‌ம்','நிக‌ழ்ச்சி','ப‌யனீட்டாள‌ர் நீக்க‌ப்ப‌ட்டார்.','ப‌யனீட்டாள‌ர் மாற்றப்ப‌ட்டார்.','ப‌ய‌னீட்டாள‌ர் உருவாக்க‌ப்ப‌ட்ட‌து.','Hashed','ரொட்டீன்','அனும‌திய‌ளி','இர‌த்துச்செய்',array('%d வேலை வ‌லுவில் நிறுத்த‌ப‌ட்ட‌து.','%d வேலைக‌ள் வ‌லுவில் நிறுத்த‌ப‌ட்ட‌ன‌.'),'வ‌லுவில் நிறுத்து',array('%d உருப்ப‌டி மாற்ற‌ம‌டைந்தது.','%d உருப்ப‌டிக‌ள் மாற்ற‌ம‌டைந்த‌ன‌.'),'ம‌திப்பினை மாற்ற அத‌ன் மீது இருமுறை சொடுக்க‌வும் (Double click).',array('%d வ‌ரிசை இற‌க்கும‌தி (Import) செய்ய‌ப்ப‌ட்ட‌து.','%d வ‌ரிசைக‌ள் இற‌க்கும‌தி (Import) செய்ய‌ப்ப‌ட்டன‌.'),'அட்ட‌வ‌ணையை தேர்வு செய்ய‌ முடிய‌வில்லை','உற‌வுக‌ள் (Relations)','இந்த‌ ம‌திப்பினை மாற்ற, டெக்ஸ்ட் நீள‌த்தினை அதிக‌ரிக்க‌வும்.','இந்த‌ ம‌திப்பினை மாற்ற‌, தொகுப்பு இணைப்பினை உப‌யோகிக்க‌வும்.','முழுமையான‌ முடிவு','ந‌க‌லி (Clone)','இம்போர்ட் CSV','இற‌க்கும‌தி (Import)',',','அட்ட‌வ‌ணை குறைக்க‌ப்ப‌ட்ட‌து (truncated).','அட்ட‌வ‌ணை ந‌க‌ர்த்த‌ப்ப‌ட்ட‌து.','அட்ட‌வ‌ணை நீக்க‌ப்ப‌ட்ட‌து.','அட்ட‌வ‌ணைக‌ளும் பார்வைக‌ளும்','த‌க‌வ‌லை அட்ட‌வ‌ணையில் தேடு','எஞ்சின் (Engine)','த‌க‌வ‌ல் நீள‌ம்','Index நீள‌ம்','Data Free','வ‌ரிசைக‌ள்','மொத்தம் %d ','நுணுகி ஆராய‌வும்','உக‌ப்பாக்கு (Optimize)','ப‌ரிசோதி','ப‌ழுது பார்','குறை (Truncate)','ம‌ற்ற‌ த‌க‌வ‌ல் தள‌த்திற்க்கு ந‌க‌ர்த்து','ந‌க‌ர்த்து','வ‌ரிசைமுறை','கால‌ அட்ட‌வ‌ணை','குறித்த‌ நேர‌த்தில்','இப்பொழுது');break;case"zh-tw":$na=array('無法上傳檔案。','允許的檔案上限大小為%sB','檔案不存在','原始','沒有資料表。','語言','使用','Please use one of the extensions %s.','檔案已存在。','使用者類型','數字','日期時間','字符串','二進制','網路','幾何','列表','資料庫系統','伺服器','帳號','密碼','登入','永久登入','選擇資料','秀出結構','更改檢視表','更改資料表','新建項','頁','最後一頁','編輯','%d byte(s)','選擇','函數','集合','搜尋','任意位置','排序','降冪','限定','Text 長度','動作','SQL命令','空值','打開','儲存','導入/導出','登出','資料庫','架構','建立新資料表','選擇','沒有 擴充模組','沒有任何支援的PHP擴充模組（%s）。','無效的 CSRF token。請重新發送表單。','登出成功。','Session 必須被啟用。','Session 已過期，請重新登入。','無效的憑證。','POST 資料太大。減少資料或者增加 %s 的設定值。','資料庫','無效的資料庫。','資料庫已刪除。','選擇資料庫','建立新資料庫','權限','進程列表','變數','狀態','%s版本：%s 透過PHP擴充模組 %s','登錄為：%s','校對','資料表','丟棄','你確定嗎？','架構','Invalid schema.','沒有行。','外鍵','校對','ON DELETE','列名','參數名稱','類型','長度','選項','自動增加','預設值','註解','新增下一個','上移','下移','移除','檢視表','資料表','列','索引','更改索引','來源','目標','ON UPDATE','更改','新增外鍵','觸發器','建立觸發器','資料庫架構','匯出','輸出','格式','程序','事件','資料','編輯','建立使用者','查詢出錯','%.3f秒','%d行','執行查詢OK，%d行受影響','沒有命令可執行。','檔案上傳','檔案上傳被禁用。','執行','出錯時停止','從伺服器','網頁伺服器檔案 %s','執行檔案','歷史','清除','該項目已被刪除','已更新項目。','已插入項目%s。','插入','儲存','保存並繼續編輯','儲存並插入下一個','刪除','已經刪除資料表。','資料表已更改。','資料表已更改。','建立資料表表','超過最多允許的字段數量。請增加%s和%s 。','資料表名稱','引擎','分區類型','分區','分區名','值','已更改索引。','索引類型','列（長度）','資料庫已刪除。','已重新命名資料庫。','已建立資料庫。','已更改資料庫。','更改資料庫','建立資料庫','已刪除架構。','已建立架構。','已更改架構。','更改架構','建立架構','呼叫','程序已被執行，%d行被影響','已刪除外鍵。','已更改外鍵。','已建立外鍵。','源列和目標列必須具有相同的數據類型，在目標列上必須有一個索引並且引用的數據必須存在。','外鍵','目標資料表','更改','新增資料列','已丟棄檢視表。','已更改檢視表。','已建立檢視表。','建立檢視表','名稱','已丟棄事件。','已更改事件。','已建立事件。','更改事件','建立事件','開始','結束','每','在完成後保存','已丟棄程序。','已更改子程序。','已建立子程序。','更改函數','更改過程','建立函數','建立預存程序','返回類型','已刪除 sequence。','已建立 sequence。','已更改 sequence。','更改 sequence','建立 sequence','已刪除類型。','已建立類型。','更改類型','建立類型','已丟棄觸發器。','已更改觸發器。','已建立觸發器。','更改觸發器','建立觸發器','時間','事件','已丟棄使用者。','已更改使用者。','已建立使用者。','Hashed','程序','授權','廢除','%d 個 Process(es) 被終止','終止','%d個項目受到影響。','雙擊以進行修改。','%d行已導入。','無法選擇該資料表','關聯','增加字串長度來修改。','使用編輯連結來修改。','所有結果','複製','匯入 CSV','匯入',',','已清空資料表。','已轉移資料表。','已丟棄表。','資料表和檢視表','在資料庫搜尋','引擎','資料長度','索引長度','資料空閒','行數','總共 %d 個','分析','優化','檢查','修復','清空','轉移到其它資料庫','轉移','Sequences','調度','在指定時間','附件');break;case"zh":$na=array('不能上传文件。','最多允许的文件大小为 %sB','文件不存在。','原始','没有表。','语言','使用','Please use one of the extensions %s.','文件已存在。','用户类型','数字','日期时间','字符串','二进制','网络','几何图形','列表','系统','服务器','用户名','密码','登录','保持登录','选择数据','显示结构','更改视图','更改表','新建项','页面','最后','编辑','%d 字节','选择','函数','集合','搜索','任意位置','排序','降序','限定','文本长度','动作','SQL命令','空','打开','保存','导出','注销','数据库','模式','创建新表','选择','没有扩展','没有支持的 PHP 扩展可用（%s）。','无效 CSRF 令牌。重新发送表单。','注销成功。','会话必须被启用。','会话已过期，请重新登录。','无效凭据。','太大的 POST 数据。减少数据或者增加 %s 配置命令。','数据库','无效数据库。','已丢弃数据库。','选择数据库','创建新数据库','权限','进程列表','变量','状态','%s 版本：%s 通过 PHP 扩展 %s','登录为：%s','校对','表','丢弃','你确定吗？','模式','Invalid schema.','没有行。','外键','校对','ON DELETE','列名','参数名','类型','长度','选项','自动增量','默认值','注释','添加下一个','上移','下移','移除','视图','表','列','索引','更改索引','源','目标','ON UPDATE','更改','添加外键','触发器','创建触发器','数据库概要','导出','输出','格式','子程序','事件','数据','编辑','创建用户','查询出错','%.3f 秒','%d 行','执行查询OK，%d 行受影响。','没有命令执行。','文件上传','文件上传被禁用。','执行','出错时停止','来自服务器','Web服务器文件 %s','运行文件','历史','清除','已删除项目。','已更新项目。','已插入项目%s。','插入','保存','保存并继续编辑','保存并插入下一个','删除','已丢弃表。','已更改表。','已创建表。','创建表','超过最多允许的字段数量。请增加 %s 和 %s 。','表名','引擎','分区类型','分区','分区名','值','已更改索引。','索引类型','列（长度）','已丢弃数据库。','已重命名数据库。','已创建数据库。','已更改数据库。','更改数据库','创建数据库','已丢弃模式。','已创建模式。','已更改模式。','更改模式','创建模式','调用','子程序被调用，%d 行被影响。','已删除外键。','已更改外键。','已创建外键。','源列和目标列必须具有相同的数据类型，在目标列上必须有一个索引并且引用的数据必须存在。','外键','目标表','更改','增加列','已丢弃视图。','已更改视图。','已创建视图。','创建视图','名称','已丢弃事件。','已更改事件。','已创建事件。','更改事件','创建事件','开始','结束','每','完成后保存','已丢弃子程序。','已更改子程序。','已创建子程序。','更改函数','更改过程','创建函数','创建过程','返回类型','已丢弃序列。','已创建序列。','已更改序列。','更改序列','创建序列','已丢弃类型。','已创建类型。','更改类型','创建类型','已丢弃触发器。','已更改触发器。','已创建触发器。','更改触发器','创建触发器','时间','事件','已丢弃用户。','已更改用户。','已创建用户。','Hashed','子程序','授权','废除','%d 个进程被终止','终止','%d 个项目受到影响。','在值上双击类修改它。','%d 行已导入。','不能选择该表','关联信息','增加文本长度以修改该值。','使用编辑链接来修改该值。','所有结果','克隆','CSV 导入','导入',',','已清空表。','已转移表。','已丢弃表。','表和视图','在表中搜索数据','引擎','数据长度','索引长度','数据空闲','行数','共计 %d','分析','优化','检查','修复','清空','转移到其它数据库','转移','序列','调度','在指定时间','附件');break;}if(extension_loaded('pdo')){class
Min_PDO
extends
PDO{var$_result,$server_info,$affected_rows,$error;function
__construct(){}function
dsn($mf,$Q,$S,$lf='auth_error'){set_exception_handler($lf);parent::__construct($mf,$Q,$S);restore_exception_handler();$this->setAttribute(13,array('Min_PDOStatement'));$this->server_info=$this->getAttribute(4);}function
query($j,$bb=false){$i=parent::query($j);if(!$i){$kf=$this->errorInfo();$this->error=$kf[2];return
false;}$this->store_result($i);return$i;}function
multi_query($j){return$this->_result=$this->query($j);}function
store_result($i=null){if(!$i){$i=$this->_result;}if($i->columnCount()){$i->num_rows=$i->rowCount();return$i;}$this->affected_rows=$i->rowCount();return
true;}function
next_result(){return$this->_result->nextRowset();}function
result($j,$d=0){$i=$this->query($j);if(!$i){return
false;}$a=$i->fetch();return$a[$d];}}class
Min_PDOStatement
extends
PDOStatement{var$_offset=0,$num_rows;function
fetch_assoc(){return$this->fetch(2);}function
fetch_row(){return$this->fetch(3);}function
fetch_field(){$a=(object)$this->getColumnMeta($this->_offset++);$a->orgtable=$a->table;$a->orgname=$a->name;$a->charsetnr=(in_array("blob",$a->flags)?63:0);return$a;}}}$Ja=array();$ja=array();$Ja[]="SQLite";$Ja[]="SQLite3";$Ja[]="PDO_SQLite";if(extension_loaded("sqlite3")||extension_loaded("pdo_sqlite")){$ja["sqlite"]="SQLite 3";}if(extension_loaded("sqlite")||extension_loaded("pdo_sqlite")){$ja["sqlite2"]="SQLite 2";}if(isset($_GET["sqlite"])||isset($_GET["sqlite2"])){define("DRIVER",(isset($_GET["sqlite"])?"sqlite":"sqlite2"));if(extension_loaded(isset($_GET["sqlite2"])?"sqlite":"sqlite3")){if(isset($_GET["sqlite2"])){class
Min_SQLite{var$extension="SQLite",$server_info,$affected_rows,$error,$_link;function
Min_SQLite($Z){$this->server_info=sqlite_libversion();$this->_link=new
SQLiteDatabase($Z);}function
query($j,$bb=false){$nf=($bb?"unbufferedQuery":"query");$i=@$this->_link->$nf($j,SQLITE_BOTH,$n);if(!$i){$this->error=$n;return
false;}elseif($i===true){$this->affected_rows=$this->changes();return
true;}return
new
Min_Result($i);}function
quote($G){return"'".sqlite_escape_string($G)."'";}function
store_result(){return$this->_result;}function
result($j,$d=0){$i=$this->query($j);if(!is_object($i)){return
false;}$a=$i->_result->fetch();return$a[$d];}}class
Min_Result{var$_result,$_offset=0,$num_rows;function
Min_Result($i){$this->_result=$i;if(method_exists($i,'numRows')){$this->num_rows=$i->numRows();}}function
fetch_assoc(){$a=$this->_result->fetch(SQLITE_ASSOC);if(!$a){return
false;}$e=array();foreach($a
as$c=>$b){$e[($c[0]=='"'?idf_unescape($c):$c)]=$b;}return$e;}function
fetch_row(){return$this->_result->fetch(SQLITE_NUM);}function
fetch_field(){$f=$this->_result->fieldName($this->_offset++);$ha='(\\[.*]|"(?:[^"]|"")*"|(.+))';if(preg_match("~^($ha\\.)?$ha\$~",$f,$k)){$h=($k[3]!=""?$k[3]:idf_unescape($k[2]));$f=($k[5]!=""?$k[5]:idf_unescape($k[4]));}return(object)array("name"=>$f,"orgname"=>$f,"orgtable"=>$h,);}}}else{class
Min_SQLite{var$extension="SQLite3",$server_info,$affected_rows,$error,$_link;function
Min_SQLite($Z){$this->_link=new
SQLite3($Z);$fd=$this->_link->version();$this->server_info=$fd["versionString"];}function
query($j){$i=@$this->_link->query($j);if(!$i){$this->error=$this->_link->lastErrorMsg();return
false;}elseif($i->numColumns()){return
new
Min_Result($i);}$this->affected_rows=$this->_link->changes();return
true;}function
quote($G){return"'".$this->_link->escapeString($G)."'";}function
store_result(){return$this->_result;}function
result($j,$d=0){$i=$this->query($j);if(!is_object($i)){return
false;}$a=$i->_result->fetchArray();return$a[$d];}}class
Min_Result{var$_result,$_offset=0,$num_rows;function
Min_Result($i){$this->_result=$i;}function
fetch_assoc(){return$this->_result->fetchArray(SQLITE3_ASSOC);}function
fetch_row(){return$this->_result->fetchArray(SQLITE3_NUM);}function
fetch_field(){$H=$this->_offset++;$y=$this->_result->columnType($H);return(object)array("name"=>$this->_result->columnName($H),"type"=>$y,"charsetnr"=>($y==SQLITE3_BLOB?63:0),);}function
__desctruct(){return$this->_result->finalize();}}}}elseif(extension_loaded("pdo_sqlite")){class
Min_SQLite
extends
Min_PDO{var$extension="PDO_SQLite";function
Min_SQLite($Z){$this->dsn(DRIVER.":$Z","","");}}}class
Min_DB
extends
Min_SQLite{function
Min_DB(){$this->Min_SQLite(":memory:");}function
select_db($Z){if(is_readable($Z)&&$this->query("ATTACH ".$this->quote(ereg("(^[/\\]|:)",$Z)?$Z:dirname($_SERVER["SCRIPT_FILENAME"])."/$Z")." AS a")){$this->Min_SQLite($Z);return
true;}return
false;}function
multi_query($j){return$this->_result=$this->query($j);}function
next_result(){return
false;}}function
idf_escape($N){return'"'.str_replace('"','""',$N).'"';}function
table($N){return
idf_escape($N);}function
connect(){return
new
Min_DB;}function
get_databases(){return
array();}function
limit($j,$t,$M,$O=0,$Ta=" "){return" $j$t".(isset($M)?$Ta."LIMIT $M".($O?" OFFSET $O":""):"");}function
limit1($j,$t){global$g;return($g->result("SELECT sqlite_compileoption_used('ENABLE_UPDATE_DELETE_LIMIT')")?limit($j,$t,1):" $j$t");}function
db_collation($s,$X){global$g;return$g->result("PRAGMA encoding");}function
engines(){return
array();}function
logged_user(){return
get_current_user();}function
tables_list(){return
get_key_vals("SELECT name, type FROM sqlite_master WHERE type IN ('table', 'view') ORDER BY (name = 'sqlite_sequence'), name",1);}function
count_tables($z){return
array();}function
table_status($f=""){$e=array();foreach(get_rows("SELECT name AS Name, type AS Engine FROM sqlite_master WHERE type IN ('table', 'view')".($f!=""?" AND name = ".q($f):""))as$a){$a["Auto_increment"]="";$e[$a["Name"]]=$a;}foreach(get_rows("SELECT * FROM sqlite_sequence",null,"")as$a){$e[$a["name"]]["Auto_increment"]=$a["seq"];}return($f!=""?$e[$f]:$e);}function
is_view($J){return$J["Engine"]=="view";}function
fk_support($J){global$g;return!$g->result("SELECT sqlite_compileoption_used('OMIT_FOREIGN_KEY')");}function
fields($h,$nc=false){$e=array();foreach(get_rows("PRAGMA table_info(".table($h).")")as$a){$y=strtolower($a["type"]);$va=$a["dflt_value"];$e[$a["name"]]=array("field"=>$a["name"],"type"=>(eregi("int",$y)?"integer":(eregi("char|clob|text",$y)?"text":(eregi("blob",$y)?"blob":(eregi("real|floa|doub",$y)?"real":"numeric")))),"full_type"=>$y,"default"=>(ereg("'(.*)'",$va,$k)?str_replace("''","'",$k[1]):($va=="NULL"?null:$va)),"null"=>!$a["notnull"],"auto_increment"=>eregi('^integer$',$y)&&$a["pk"],"privileges"=>array("select"=>1,"insert"=>1,"update"=>1),"primary"=>$a["pk"],);}return$e;}function
indexes($h,$I=null){$e=array();$Ia=array();foreach(fields($h)as$d){if($d["primary"]){$Ia[]=$d["field"];}}if($Ia){$e[""]=array("type"=>"PRIMARY","columns"=>$Ia,"lengths"=>array());}foreach(get_rows("PRAGMA index_list(".table($h).")")as$a){$e[$a["name"]]["type"]=($a["unique"]?"UNIQUE":"INDEX");$e[$a["name"]]["lengths"]=array();foreach(get_rows("PRAGMA index_info(".idf_escape($a["name"]).")")as$cd){$e[$a["name"]]["columns"][]=$cd["name"];}}return$e;}function
foreign_keys($h){$e=array();foreach(get_rows("PRAGMA foreign_key_list(".table($h).")")as$a){$A=&$e[$a["id"]];if(!$A){$A=$a;}$A["source"][]=$a["from"];$A["target"][]=$a["to"];}return$e;}function
view($f){global$g;return
array("select"=>preg_replace('~^(?:[^`"[]+|`[^`]*`|"[^"]*")* AS\\s+~iU','',$g->result("SELECT sql FROM sqlite_master WHERE name = ".q($f))));}function
collations(){return(isset($_GET["create"])?get_vals("PRAGMA collation_list",1):array());}function
information_schema($s){return
false;}function
error(){global$g;return
h($g->error);}function
exact_value($b){return
q($b);}function
check_sqlite_name($f){global$g;$ke="db|sdb|sqlite";if(!preg_match("~^[^\\0]*\\.($ke)\$~",$f)){$g->error=lang(7,str_replace("|",", ",$ke));return
false;}return
true;}function
create_database($s,$R){global$g;if(file_exists($s)){$g->error=lang(8);return
false;}if(!check_sqlite_name($s)){return
false;}$x=new
Min_SQLite($s);$x->query('PRAGMA encoding = "UTF-8"');$x->query('CREATE TABLE adminer (i)');$x->query('DROP TABLE adminer');return
true;}function
drop_databases($z){global$g;$g->Min_SQLite(":memory:");foreach($z
as$s){if(!@unlink($s)){$g->error=lang(8);return
false;}}return
true;}function
rename_database($f,$R){global$g;if(!check_sqlite_name($f)){return
false;}$g->Min_SQLite(":memory:");$g->error=lang(8);return@rename(DB,$f);}function
auto_increment(){return" PRIMARY KEY".(DRIVER=="sqlite"?" AUTOINCREMENT":"");}function
alter_table($h,$f,$o,$hb,$Ba,$yb,$R,$Pa,$tb){$u=array();foreach($o
as$d){if($d[1]){$u[]=($h!=""&&$d[0]==""?"ADD ":"  ").implode($d[1]);}}$u=array_merge($u,$hb);if($h!=""){foreach($u
as$b){if(!queries("ALTER TABLE ".table($h)." $b")){return
false;}}if($h!=$f&&!queries("ALTER TABLE ".table($h)." RENAME TO ".table($f))){return
false;}}elseif(!queries("CREATE TABLE ".table($f)." (\n".implode(",\n",$u)."\n)")){return
false;}if($Pa){queries("UPDATE sqlite_sequence SET seq = $Pa WHERE name = ".q($f));}return
true;}function
alter_indexes($h,$u){foreach($u
as$b){if(!queries(($b[2]?"DROP INDEX":"CREATE".($b[0]!="INDEX"?" UNIQUE":"")." INDEX ".idf_escape(uniqid($h."_"))." ON ".table($h))." $b[1]")){return
false;}}return
true;}function
truncate_tables($D){return
apply_queries("DELETE FROM",$D);}function
drop_views($Y){return
apply_queries("DROP VIEW",$Y);}function
drop_tables($D){return
apply_queries("DROP TABLE",$D);}function
move_tables($D,$Y,$ta){return
false;}function
trigger($f){global$g;preg_match('~^CREATE\\s+TRIGGER\\s*(?:[^`"\\s]+|`[^`]*`|"[^"]*")+\\s*([a-z]+)\\s+([a-z]+)\\s+ON\\s*(?:[^`"\\s]+|`[^`]*`|"[^"]*")+\\s*(?:FOR\\s*EACH\\s*ROW\\s)?(.*)~is',$g->result("SELECT sql FROM sqlite_master WHERE name = ".q($f)),$k);return
array("Timing"=>strtoupper($k[1]),"Event"=>strtoupper($k[2]),"Trigger"=>$f,"Statement"=>$k[3]);}function
triggers($h){$e=array();foreach(get_rows("SELECT * FROM sqlite_master WHERE type = 'trigger' AND tbl_name = ".q($h))as$a){preg_match('~^CREATE\\s+TRIGGER\\s*(?:[^`"\\s]+|`[^`]*`|"[^"]*")+\\s*([a-z]+)\\s*([a-z]+)~i',$a["sql"],$k);$e[$a["name"]]=array($k[1],$k[2]);}return$e;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER","INSTEAD OF"),"Type"=>array("FOR EACH ROW"),);}function
routine($f,$y){}function
routines(){}function
begin(){return
queries("BEGIN");}function
insert_into($h,$q){return
queries("INSERT INTO ".table($h).($q?" (".implode(", ",array_keys($q)).")\nVALUES (".implode(", ",$q).")":"DEFAULT VALUES"));}function
insert_update($h,$q,$Ia){return
queries("REPLACE INTO ".table($h)." (".implode(", ",array_keys($q)).") VALUES (".implode(", ",$q).")");}function
last_id(){global$g;return$g->result("SELECT LAST_INSERT_ROWID()");}function
explain($g,$j){return$g->query("EXPLAIN $j");}function
types(){return
array();}function
schemas(){return
array();}function
get_schema(){return"";}function
set_schema($Dd){return
true;}function
create_sql($h,$Pa){global$g;return$g->result("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($h));}function
truncate_sql($h){return"DELETE FROM ".table($h);}function
use_sql($ba){}function
trigger_sql($h,$V){return
implode(get_vals("SELECT sql || ';;\n' FROM sqlite_master WHERE type = 'trigger' AND name = ".q($h)));}function
show_variables(){global$g;$e=array();foreach(array("auto_vacuum","cache_size","count_changes","default_cache_size","empty_result_callbacks","encoding","foreign_keys","full_column_names","fullfsync","journal_mode","journal_size_limit","legacy_file_format","locking_mode","page_size","max_page_count","read_uncommitted","recursive_triggers","reverse_unordered_selects","secure_delete","short_column_names","synchronous","temp_store","temp_store_directory","schema_version","integrity_check","quick_check")as$c){$e[$c]=$g->result("PRAGMA $c");}return$e;}function
show_status(){$e=array();foreach(get_vals("PRAGMA compile_options")as$bf){list($c,$b)=explode("=",$bf,2);$e[$c]=$b;}return$e;}function
support($vb){return
ereg('^(view|trigger|variables|status|dump)$',$vb);}$_="sqlite";$T=array("integer"=>0,"real"=>0,"numeric"=>0,"text"=>0,"blob"=>0);$Sa=array_keys($T);$sb=array();$bc=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL");$W=array("hex","length","lower","round","unixepoch","upper");$ob=array("avg","count","count distinct","group_concat","max","min","sum");$dc=array(array(),array("integer|real|numeric"=>"+/-","text"=>"||",));}$Ja[]="PgSQL";$Ja[]="PDO_PgSQL";if(extension_loaded("pgsql")||extension_loaded("pdo_pgsql")){$ja["pgsql"]="PostgreSQL";}if(isset($_GET["pgsql"])){define("DRIVER","pgsql");if(extension_loaded("pgsql")){class
Min_DB{var$extension="PgSQL",$_link,$_result,$_string,$_database=true,$server_info,$affected_rows,$error;function
_error($af,$n){if(ini_bool("html_errors")){$n=html_entity_decode(strip_tags($n));}$n=ereg_replace('^[^:]*: ','',$n);$this->error=$n;}function
connect($F,$Q,$S){set_error_handler(array($this,'_error'));$this->_string="host='".str_replace(":","' port='",addcslashes($F,"'\\"))."' user='".addcslashes($Q,"'\\")."' password='".addcslashes($S,"'\\")."'";$this->_link=@pg_connect($this->_string.(DB!=""?" dbname='".addcslashes(DB,"'\\")."'":" dbname='template1'"),PGSQL_CONNECT_FORCE_NEW);if(!$this->_link&&DB!=""){$this->_database=false;$this->_link=@pg_connect("$this->_string dbname='template1'",PGSQL_CONNECT_FORCE_NEW);}restore_error_handler();if($this->_link){$fd=pg_version($this->_link);$this->server_info=$fd["server"];pg_set_client_encoding($this->_link,"UTF8");}return(bool)$this->_link;}function
quote($G){return"'".pg_escape_string($this->_link,$G)."'";}function
select_db($ba){if($ba==DB){return$this->_database;}$e=@pg_connect("$this->_string dbname='".addcslashes($ba,"'\\")."'",PGSQL_CONNECT_FORCE_NEW);if($e){$this->_link=$e;}return$e;}function
close(){$this->_link=@pg_connect("$this->_string dbname='template1'");}function
query($j,$bb=false){$i=@pg_query($this->_link,$j);if(!$i){$this->error=pg_last_error($this->_link);return
false;}elseif(!pg_num_fields($i)){$this->affected_rows=pg_affected_rows($i);return
true;}return
new
Min_Result($i);}function
multi_query($j){return$this->_result=$this->query($j);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($j,$d=0){$i=$this->query($j);if(!$i){return
false;}return
pg_fetch_result($i->_result,0,$d);}}class
Min_Result{var$_result,$_offset=0,$num_rows;function
Min_Result($i){$this->_result=$i;$this->num_rows=pg_num_rows($i);}function
fetch_assoc(){return
pg_fetch_assoc($this->_result);}function
fetch_row(){return
pg_fetch_row($this->_result);}function
fetch_field(){$H=$this->_offset++;$e=new
stdClass;if(function_exists('pg_field_table')){$e->orgtable=pg_field_table($this->_result,$H);}$e->name=pg_field_name($this->_result,$H);$e->orgname=$e->name;$e->type=pg_field_type($this->_result,$H);$e->charsetnr=($e->type=="bytea"?63:0);return$e;}function
__destruct(){pg_free_result($this->_result);}}}elseif(extension_loaded("pdo_pgsql")){class
Min_DB
extends
Min_PDO{var$extension="PDO_PgSQL";function
connect($F,$Q,$S){$G="pgsql:host='".str_replace(":","' port='",addcslashes($F,"'\\"))."' options='-c client_encoding=utf8'";$this->dsn($G.(DB!=""?" dbname='".addcslashes(DB,"'\\")."'":""),$Q,$S);return
true;}function
select_db($ba){return(DB==$ba);}function
close(){}}}function
idf_escape($N){return'"'.str_replace('"','""',$N).'"';}function
table($N){return
idf_escape($N);}function
connect(){global$r;$g=new
Min_DB;$Aa=$r->credentials();if($g->connect($Aa[0],$Aa[1],$Aa[2])){return$g;}return$g->error;}function
get_databases(){return
get_vals("SELECT datname FROM pg_database");}function
limit($j,$t,$M,$O=0,$Ta=" "){return" $j$t".(isset($M)?$Ta."LIMIT $M".($O?" OFFSET $O":""):"");}function
limit1($j,$t){return" $j$t";}function
db_collation($s,$X){global$g;return$g->result("SHOW LC_COLLATE");}function
engines(){return
array();}function
logged_user(){global$g;return$g->result("SELECT user");}function
tables_list(){return
get_key_vals("SELECT table_name, table_type FROM information_schema.tables WHERE table_schema = current_schema() ORDER BY table_name");}function
count_tables($z){return
array();}function
table_status($f=""){$e=array();foreach(get_rows("SELECT relname AS \"Name\", CASE relkind WHEN 'r' THEN '' ELSE 'view' END AS \"Engine\", pg_relation_size(oid) AS \"Data_length\", pg_total_relation_size(oid) - pg_relation_size(oid) AS \"Index_length\", obj_description(oid, 'pg_class') AS \"Comment\"
FROM pg_class
WHERE relkind IN ('r','v')
AND relnamespace = (SELECT oid FROM pg_namespace WHERE nspname = current_schema())".($f!=""?" AND relname = ".q($f):""))as$a){$e[$a["Name"]]=$a;}return($f!=""?$e[$f]:$e);}function
is_view($J){return$J["Engine"]=="view";}function
fk_support($J){return
true;}function
fields($h,$nc=false){$e=array();foreach(get_rows("SELECT a.attname AS field, format_type(a.atttypid, a.atttypmod) AS full_type, d.adsrc AS default, a.attnotnull, col_description(c.oid, a.attnum) AS comment
FROM pg_class c
JOIN pg_namespace n ON c.relnamespace = n.oid
JOIN pg_attribute a ON c.oid = a.attrelid
LEFT JOIN pg_attrdef d ON c.oid = d.adrelid AND a.attnum = d.adnum
WHERE c.relname = ".q($h)."
AND n.nspname = current_schema()
AND NOT a.attisdropped
".($nc?"":"AND a.attnum > 0")."
ORDER BY a.attnum < 0, a.attnum")as$a){ereg('(.*)(\\((.*)\\))?',$a["full_type"],$k);list(,$a["type"],,$a["length"])=$k;$a["full_type"]=$a["type"].($a["length"]?"($a[length])":"");$a["null"]=($a["attnotnull"]=="f");$a["auto_increment"]=eregi("^nextval\\(",$a["default"]);$a["privileges"]=array("insert"=>1,"select"=>1,"update"=>1);$e[$a["field"]]=$a;}return$e;}function
indexes($h,$I=null){global$g;if(!is_object($I)){$I=$g;}$e=array();$ue=$I->result("SELECT oid FROM pg_class WHERE relname = ".q($h));$B=get_key_vals("SELECT attnum, attname FROM pg_attribute WHERE attrelid = $ue AND attnum > 0",$I);foreach(get_rows("SELECT relname, indisunique, indisprimary, indkey FROM pg_index i, pg_class ci WHERE i.indrelid = $ue AND ci.oid = i.indexrelid",$I)as$a){$e[$a["relname"]]["type"]=($a["indisprimary"]=="t"?"PRIMARY":($a["indisunique"]=="t"?"UNIQUE":"INDEX"));$e[$a["relname"]]["columns"]=array();foreach(explode(" ",$a["indkey"])as$ef){$e[$a["relname"]]["columns"][]=$B[$ef];}$e[$a["relname"]]["lengths"]=array();}return$e;}function
foreign_keys($h){$e=array();foreach(get_rows("SELECT tc.constraint_name, kcu.column_name, rc.update_rule AS on_update, rc.delete_rule AS on_delete, ccu.table_name AS table, ccu.column_name AS ref
FROM information_schema.table_constraints tc
LEFT JOIN information_schema.key_column_usage kcu USING (constraint_catalog, constraint_schema, constraint_name)
LEFT JOIN information_schema.referential_constraints rc USING (constraint_catalog, constraint_schema, constraint_name)
LEFT JOIN information_schema.constraint_column_usage ccu ON rc.unique_constraint_catalog = ccu.constraint_catalog AND rc.unique_constraint_schema = ccu.constraint_schema AND rc.unique_constraint_name = ccu.constraint_name
WHERE tc.constraint_type = 'FOREIGN KEY' AND tc.table_name = ".q($h))as$a){$A=&$e[$a["constraint_name"]];if(!$A){$A=$a;}$A["source"][]=$a["column_name"];$A["target"][]=$a["ref"];}return$e;}function
view($f){global$g;return
array("select"=>$g->result("SELECT pg_get_viewdef(".q($f).")"));}function
collations(){return
array();}function
information_schema($s){return($s=="information_schema");}function
error(){global$g;$e=h($g->error);if(preg_match('~^(.*\\n)?([^\\n]*)\\n( *)\\^(\\n.*)?$~s',$e,$k)){$e=$k[1].preg_replace('~((?:[^&]|&[^;]*;){'.strlen($k[3]).'})(.*)~','\\1<b>\\2</b>',$k[2]).$k[4];}return
nl_br($e);}function
exact_value($b){return
q($b);}function
create_database($s,$R){return
queries("CREATE DATABASE ".idf_escape($s).($R?" ENCODING ".idf_escape($R):""));}function
drop_databases($z){global$g;$g->close();return
apply_queries("DROP DATABASE",$z,'idf_escape');}function
rename_database($f,$R){return
queries("ALTER DATABASE ".idf_escape(DB)." RENAME TO ".idf_escape($f));}function
auto_increment(){return"";}function
alter_table($h,$f,$o,$hb,$Ba,$yb,$R,$Pa,$tb){$u=array();$eb=array();foreach($o
as$d){$H=idf_escape($d[0]);$b=$d[1];if(!$b){$u[]="DROP $H";}else{$ud=$b[5];unset($b[5]);if(isset($b[6])&&$d[0]==""){$b[1]=($b[1]=="bigint"?" big":" ")."serial";}if($d[0]==""){$u[]=($h!=""?"ADD ":"  ").implode($b);}else{if($H!=$b[0]){$eb[]="ALTER TABLE ".table($h)." RENAME $H TO $b[0]";}$u[]="ALTER $H TYPE$b[1]";if(!$b[6]){$u[]="ALTER $H ".($b[3]?"SET$b[3]":"DROP DEFAULT");$u[]="ALTER $H ".($b[2]==" NULL"?"DROP NOT":"SET").$b[2];}}if($d[0]!=""||$ud!=""){$eb[]="COMMENT ON COLUMN ".table($h).".$b[0] IS ".($ud!=""?substr($ud,9):"''");}}}$u=array_merge($u,$hb);if($h==""){array_unshift($eb,"CREATE TABLE ".table($f)." (\n".implode(",\n",$u)."\n)");}elseif($u){array_unshift($eb,"ALTER TABLE ".table($h)."\n".implode(",\n",$u));}if($h!=""&&$h!=$f){$eb[]="ALTER TABLE ".table($h)." RENAME TO ".table($f);}if($h!=""||$Ba!=""){$eb[]="COMMENT ON TABLE ".table($f)." IS ".q($Ba);}if($Pa!=""){}foreach($eb
as$j){if(!queries($j)){return
false;}}return
true;}function
alter_indexes($h,$u){$ga=array();$Da=array();foreach($u
as$b){if($b[0]!="INDEX"){$ga[]=($b[2]?"\nDROP CONSTRAINT ":"\nADD $b[0] ".($b[0]=="PRIMARY"?"KEY ":"")).$b[1];}elseif($b[2]){$Da[]=$b[1];}elseif(!queries("CREATE INDEX ".idf_escape(uniqid($h."_"))." ON ".table($h)." $b[1]")){return
false;}}return((!$ga||queries("ALTER TABLE ".table($h).implode(",",$ga)))&&(!$Da||queries("DROP INDEX ".implode(", ",$Da))));}function
truncate_tables($D){return
queries("TRUNCATE ".implode(", ",array_map('table',$D)));return
true;}function
drop_views($Y){return
queries("DROP VIEW ".implode(", ",array_map('table',$Y)));}function
drop_tables($D){return
queries("DROP TABLE ".implode(", ",array_map('table',$D)));}function
move_tables($D,$Y,$ta){foreach($D
as$h){if(!queries("ALTER TABLE ".table($h)." SET SCHEMA ".idf_escape($ta))){return
false;}}foreach($Y
as$h){if(!queries("ALTER VIEW ".table($h)." SET SCHEMA ".idf_escape($ta))){return
false;}}return
true;}function
trigger($f){$E=get_rows('SELECT trigger_name AS "Trigger", condition_timing AS "Timing", event_manipulation AS "Event", \'FOR EACH \' || action_orientation AS "Type", action_statement AS "Statement" FROM information_schema.triggers WHERE event_object_table = '.q($_GET["trigger"]).' AND trigger_name = '.q($f));return
reset($E);}function
triggers($h){$e=array();foreach(get_rows("SELECT * FROM information_schema.triggers WHERE event_object_table = ".q($h))as$a){$e[$a["trigger_name"]]=array($a["condition_timing"],$a["event_manipulation"]);}return$e;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER"),"Type"=>array("FOR EACH ROW","FOR EACH STATEMENT"),);}function
begin(){return
queries("BEGIN");}function
insert_into($h,$q){return
queries("INSERT INTO ".table($h).($q?" (".implode(", ",array_keys($q)).")\nVALUES (".implode(", ",$q).")":"DEFAULT VALUES"));}function
insert_update($h,$q,$Ia){global$g;$oa=array();$t=array();foreach($q
as$c=>$b){$oa[]="$c = $b";if(isset($Ia[idf_unescape($c)])){$t[]="$c = $b";}}return($t&&queries("UPDATE ".table($h)." SET ".implode(", ",$oa)." WHERE ".implode(" AND ",$t))&&$g->affected_rows)||queries("INSERT INTO ".table($h)." (".implode(", ",array_keys($q)).") VALUES (".implode(", ",$q).")");}function
last_id(){return
0;}function
explain($g,$j){return$g->query("EXPLAIN $j");}function
types(){return
get_vals("SELECT typname
FROM pg_type
WHERE typnamespace = (SELECT oid FROM pg_namespace WHERE nspname = current_schema())
AND typtype IN ('b','d','e')
AND typelem = 0");}function
schemas(){return
get_vals("SELECT nspname FROM pg_namespace");}function
get_schema(){global$g;return$g->result("SELECT current_schema()");}function
set_schema($Ka){global$g,$T,$Sa;$e=$g->query("SET search_path TO ".idf_escape($Ka));foreach(types()as$y){if(!isset($T[$y])){$T[$y]=0;$Sa[lang(9)][]=$y;}}return$e;}function
use_sql($ba){return"\connect ".idf_escape($ba);}function
show_variables(){return
get_key_vals("SHOW ALL");}function
show_status(){}function
support($vb){return
ereg('^(comment|view|scheme|sequence|trigger|type|variables|drop_col)$',$vb);}$_="pgsql";$T=array();$Sa=array();foreach(array(lang(10)=>array("smallint"=>5,"integer"=>10,"bigint"=>19,"boolean"=>1,"numeric"=>0,"real"=>7,"double precision"=>16,"money"=>20),lang(11)=>array("date"=>13,"time"=>17,"timestamp"=>20,"interval"=>0),lang(12)=>array("character"=>0,"character varying"=>0,"text"=>0,"tsquery"=>0,"tsvector"=>0,"uuid"=>0,"xml"=>0),lang(13)=>array("bit"=>0,"bit varying"=>0,"bytea"=>0),lang(14)=>array("cidr"=>43,"inet"=>43,"macaddr"=>17,"txid_snapshot"=>0),lang(15)=>array("box"=>0,"circle"=>0,"line"=>0,"lseg"=>0,"path"=>0,"point"=>0,"polygon"=>0),)as$c=>$b){$T+=$b;$Sa[$c]=array_keys($b);}$sb=array();$bc=array("=","<",">","<=",">=","!=","~","!~","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL");$W=array("char_length","lower","round","to_hex","to_timestamp","upper");$ob=array("avg","count","count distinct","max","min","sum");$dc=array(array("char"=>"md5","date|time"=>"now",),array("int|numeric|real|money"=>"+/-","date|time"=>"+ interval/- interval","char|text"=>"||",));}$Ja[]="OCI8";$Ja[]="PDO_OCI";if(extension_loaded("oci8")||extension_loaded("pdo_oci")){$ja["oracle"]="Oracle";}if(isset($_GET["oracle"])){define("DRIVER","oracle");if(extension_loaded("oci8")){class
Min_DB{var$extension="oci8",$_link,$_result,$server_info,$affected_rows,$error;function
_error($af,$n){if(ini_bool("html_errors")){$n=html_entity_decode(strip_tags($n));}$n=ereg_replace('^[^:]*: ','',$n);$this->error=$n;}function
connect($F,$Q,$S){$this->_link=@oci_new_connect($Q,$S,$F);if($this->_link){$this->server_info=oci_server_version($this->_link);return
true;}$n=oci_error();$this->error=$n["message"];return
false;}function
quote($G){return"'".str_replace("'","''",$G)."'";}function
select_db($ba){return
true;}function
query($j,$bb=false){$i=oci_parse($this->_link,$j);if(!$i){$n=oci_error($this->_link);$this->error=$n["message"];return
false;}set_error_handler(array($this,'_error'));$e=@oci_execute($i);restore_error_handler();if($e){if(oci_num_fields($i)){return
new
Min_Result($i);}$this->affected_rows=oci_num_rows($i);}return$e;}function
multi_query($j){return$this->_result=$this->query($j);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($j,$d=1){$i=$this->query($j);if(!is_object($i)||!oci_fetch($i->_result)){return
false;}return
oci_result($i->_result,$d);}}class
Min_Result{var$_result,$_offset=1,$num_rows;function
Min_Result($i){$this->_result=$i;}function
_convert($a){foreach((array)$a
as$c=>$b){if(is_a($b,'OCI-Lob')){$a[$c]=$b->load();}}return$a;}function
fetch_assoc(){return$this->_convert(oci_fetch_assoc($this->_result));}function
fetch_row(){return$this->_convert(oci_fetch_row($this->_result));}function
fetch_field(){$H=$this->_offset++;$e=new
stdClass;$e->name=oci_field_name($this->_result,$H);$e->orgname=$e->name;$e->type=oci_field_type($this->_result,$H);$e->charsetnr=(ereg("raw|blob|bfile",$e->type)?63:0);return$e;}function
__destruct(){oci_free_statement($this->_result);}}}elseif(extension_loaded("pdo_oci")){class
Min_DB
extends
Min_PDO{var$extension="PDO_OCI";function
connect($F,$Q,$S){}function
select_db($ba){}}}function
idf_escape($N){return'"'.str_replace('"','""',$N).'"';}function
table($N){return
idf_escape($N);}function
connect(){global$r;$g=new
Min_DB;$Aa=$r->credentials();if($g->connect($Aa[0],$Aa[1],$Aa[2])){return$g;}return$g->error;}function
get_databases(){return
get_vals("SELECT tablespace_name FROM user_tablespaces");}function
limit($j,$t,$M,$O=0,$Ta=" "){return" $j$t".(isset($M)?($t?" AND":$Ta."WHERE").($O?" rownum > $O AND":"")." rownum <= ".($M+$O):"");}function
limit1($j,$t){return" $j$t";}function
db_collation($s,$X){global$g;return$g->result("SELECT value FROM nls_database_parameters WHERE parameter = 'NLS_CHARACTERSET'");}function
engines(){return
array();}function
logged_user(){global$g;return$g->result("SELECT USER FROM DUAL");}function
tables_list(){return
get_key_vals("SELECT table_name, 'table' FROM all_tables WHERE tablespace_name = ".q(DB)."
UNION SELECT view_name, 'view' FROM user_views");}function
count_tables($z){return
array();}function
table_status($f=""){$e=array();$ve=q($f);foreach(get_rows('SELECT table_name "Name", \'table\' "Engine" FROM all_tables WHERE tablespace_name = '.q(DB).($f!=""?" AND table_name = $ve":"")."
UNION SELECT view_name, 'view' FROM user_views".($f!=""?" WHERE view_name = $ve":""))as$a){if($f!=""){return$a;}$e[$a["Name"]]=$a;}return$e;}function
is_view($J){return$J["Engine"]=="view";}function
fk_support($J){return
true;}function
fields($h,$nc=false){$e=array();foreach(get_rows("SELECT * FROM all_tab_columns WHERE table_name = ".q($h)." ORDER BY column_id")as$a){$y=$a["DATA_TYPE"];$da="$a[DATA_PRECISION],$a[DATA_SCALE]";if($da==","){$da=$a["DATA_LENGTH"];}$e[$a["COLUMN_NAME"]]=array("field"=>$a["COLUMN_NAME"],"full_type"=>$y.($da?"($da)":""),"type"=>strtolower($y),"length"=>$da,"default"=>$a["DATA_DEFAULT"],"null"=>($a["NULLABLE"]=="Y"),"privileges"=>array("insert"=>1,"select"=>1,"update"=>1),);}return$e;}function
indexes($h,$I=null){return
array();}function
view($f){$E=get_rows('SELECT text "select" FROM user_views WHERE view_name = '.q($f));return
reset($E);}function
collations(){return
array();}function
information_schema($s){return
false;}function
error(){global$g;return
h($g->error);}function
exact_value($b){return
q($b);}function
explain($g,$j){$g->query("EXPLAIN PLAN FOR $j");return$g->query("SELECT * FROM plan_table");}function
alter_table($h,$f,$o,$hb,$Ba,$yb,$R,$Pa,$tb){$u=$Da=array();foreach($o
as$d){$b=$d[1];if($b&&$d[0]!=""&&idf_escape($d[0])!=$b[0]){queries("ALTER TABLE ".table($h)." RENAME COLUMN ".idf_escape($d[0])." TO $b[0]");}if($b){$u[]=($h!=""?($d[0]!=""?"MODIFY (":"ADD ("):"  ").implode($b).($h!=""?")":"");}else{$Da[]=idf_escape($d[0]);}}if($h==""){return
queries("CREATE TABLE ".table($f)." (\n".implode(",\n",$u)."\n)");}return(!$u||queries("ALTER TABLE ".table($h)."\n".implode("\n",$u)))&&(!$Da||queries("ALTER TABLE ".table($h)." DROP (".implode(", ",$Da).")"))&&($h==$f||queries("ALTER TABLE ".table($h)." RENAME TO ".table($f)));}function
foreign_keys($h){return
array();}function
truncate_tables($D){return
apply_queries("TRUNCATE TABLE",$D);}function
drop_views($Y){return
apply_queries("DROP VIEW",$Y);}function
drop_tables($D){return
apply_queries("DROP TABLE",$D);}function
begin(){return
true;}function
insert_into($h,$q){return
queries("INSERT INTO ".table($h)." (".implode(", ",array_keys($q)).")\nVALUES (".implode(", ",$q).")");}function
last_id(){return
0;}function
schemas(){return
array();}function
get_schema(){return"";}function
set_schema($Dd){return
true;}function
show_variables(){return
get_key_vals('SELECT name, display_value FROM v$parameter');}function
show_status(){$E=get_rows('SELECT * FROM v$instance');return
reset($E);}function
support($vb){return
ereg("view|drop_col|variables|status",$vb);}$_="oracle";$T=array();$Sa=array();foreach(array(lang(10)=>array("number"=>38,"binary_float"=>12,"binary_double"=>21),lang(11)=>array("date"=>10,"timestamp"=>29,"interval year"=>12,"interval day"=>28),lang(12)=>array("char"=>2000,"varchar2"=>4000,"nchar"=>2000,"nvarchar2"=>4000,"clob"=>4294967295,"nclob"=>4294967295),lang(13)=>array("raw"=>2000,"long raw"=>2147483648,"blob"=>4294967295,"bfile"=>4294967296),)as$c=>$b){$T+=$b;$Sa[$c]=array_keys($b);}$sb=array();$bc=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT REGEXP","NOT IN","IS NOT NULL");$W=array("length","lower","round","upper");$ob=array("avg","count","count distinct","max","min","sum");$dc=array(array("date"=>"current_date","timestamp"=>"current_timestamp",),array("number|float|double"=>"+/-","date|timestamp"=>"+ interval/- interval","char|clob"=>"||",));}$Ja[]="SQLSRV";$Ja[]="MSSQL";if(extension_loaded("sqlsrv")||extension_loaded("mssql")){$ja["mssql"]="MS SQL";}if(isset($_GET["mssql"])){define("DRIVER","mssql");if(extension_loaded("sqlsrv")){class
Min_DB{var$extension="sqlsrv",$_link,$_result,$server_info,$affected_rows,$error;function
_get_error(){$this->error="";foreach(sqlsrv_errors()as$n){$this->error.="$n[message]\n";}$this->error=rtrim($this->error);}function
connect($F,$Q,$S){$this->_link=@sqlsrv_connect($F,array("UID"=>$Q,"PWD"=>$S));if($this->_link){$gf=sqlsrv_server_info($this->_link);$this->server_info=$gf['SQLServerVersion'];}else{$this->_get_error();}return(bool)$this->_link;}function
quote($G){return"'".str_replace("'","''",$G)."'";}function
select_db($ba){return$this->query("USE $ba");}function
query($j,$bb=false){$i=sqlsrv_query($this->_link,$j);if(!$i){$this->_get_error();return
false;}return$this->store_result($i);}function
multi_query($j){$this->_result=sqlsrv_query($this->_link,$j);if(!$this->_result){$this->_get_error();return
false;}return
true;}function
store_result($i=null){if(!$i){$i=$this->_result;}if(sqlsrv_field_metadata($i)){return
new
Min_Result($i);}$this->affected_rows=sqlsrv_rows_affected($i);return
true;}function
next_result(){return
sqlsrv_next_result($this->_result);}function
result($j,$d=0){$i=$this->query($j);if(!is_object($i)){return
false;}$a=$i->fetch_row();return$a[$d];}}class
Min_Result{var$_result,$_offset=0,$_fields,$num_rows;function
Min_Result($i){$this->_result=$i;}function
_convert($a){foreach((array)$a
as$c=>$b){if(is_a($b,'DateTime')){$a[$c]=$b->format("Y-m-d H:i:s");}}return$a;}function
fetch_assoc(){return$this->_convert(sqlsrv_fetch_array($this->_result,SQLSRV_FETCH_ASSOC,SQLSRV_SCROLL_NEXT));}function
fetch_row(){return$this->_convert(sqlsrv_fetch_array($this->_result,SQLSRV_FETCH_NUMERIC,SQLSRV_SCROLL_NEXT));}function
fetch_field(){if(!$this->_fields){$this->_fields=sqlsrv_field_metadata($this->_result);}$d=$this->_fields[$this->_offset++];$e=new
stdClass;$e->name=$d["Name"];$e->orgname=$d["Name"];$e->type=($d["Type"]==1?254:0);return$e;}function
seek($O){for($l=0;$l<$O;$l++){sqlsrv_fetch($this->_result);}}function
__destruct(){sqlsrv_free_stmt($this->_result);}}}elseif(extension_loaded("mssql")){class
Min_DB{var$extension="MSSQL",$_link,$_result,$server_info,$affected_rows,$error;function
connect($F,$Q,$S){$this->_link=@mssql_connect($F,$Q,$S);if($this->_link){$i=$this->query("SELECT SERVERPROPERTY('ProductLevel'), SERVERPROPERTY('Edition')");$a=$i->fetch_row();$this->server_info=$this->result("sp_server_info 2",2)." [$a[0]] $a[1]";}else{$this->error=mssql_get_last_message();}return(bool)$this->_link;}function
quote($G){return"'".str_replace("'","''",$G)."'";}function
select_db($ba){return
mssql_select_db($ba);}function
query($j,$bb=false){$i=mssql_query($j,$this->_link);if(!$i){$this->error=mssql_get_last_message();return
false;}if($i===true){$this->affected_rows=mssql_rows_affected($this->_link);return
true;}return
new
Min_Result($i);}function
multi_query($j){return$this->_result=$this->query($j);}function
store_result(){return$this->_result;}function
next_result(){return
mssql_next_result($this->_result);}function
result($j,$d=0){$i=$this->query($j);if(!is_object($i)){return
false;}return
mssql_result($i->_result,0,$d);}}class
Min_Result{var$_result,$_offset=0,$_fields,$num_rows;function
Min_Result($i){$this->_result=$i;$this->num_rows=mssql_num_rows($i);}function
fetch_assoc(){return
mssql_fetch_assoc($this->_result);}function
fetch_row(){return
mssql_fetch_row($this->_result);}function
num_rows(){return
mssql_num_rows($this->_result);}function
fetch_field(){$e=mssql_fetch_field($this->_result);$e->orgtable=$e->table;$e->orgname=$e->name;return$e;}function
seek($O){mssql_data_seek($this->_result,$O);}function
__destruct(){mssql_free_result($this->_result);}}}function
idf_escape($N){return"[".str_replace("]","]]",$N)."]";}function
table($N){return($_GET["ns"]!=""?idf_escape($_GET["ns"]).".":"").idf_escape($N);}function
connect(){global$r;$g=new
Min_DB;$Aa=$r->credentials();if($g->connect($Aa[0],$Aa[1],$Aa[2])){return$g;}return$g->error;}function
get_databases(){return
get_vals("EXEC sp_databases");}function
limit($j,$t,$M,$O=0,$Ta=" "){return(isset($M)?" TOP (".($M+$O).")":"")." $j$t";}function
limit1($j,$t){return
limit($j,$t,1);}function
db_collation($s,$X){global$g;return$g->result("SELECT collation_name FROM sys.databases WHERE name =  ".q($s));}function
engines(){return
array();}function
logged_user(){global$g;return$g->result("SELECT SUSER_NAME()");}function
tables_list(){return
get_key_vals("SELECT name, type_desc FROM sys.all_objects WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') ORDER BY name");}function
count_tables($z){global$g;$e=array();foreach($z
as$s){$g->select_db($s);$e[$s]=$g->result("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES");}return$e;}function
table_status($f=""){$e=array();foreach(get_rows("SELECT name AS Name, type_desc AS Engine FROM sys.all_objects WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V')".($f!=""?" AND name = ".q($f):""))as$a){if($f!=""){return$a;}$e[$a["Name"]]=$a;}return$e;}function
is_view($J){return$J["Engine"]=="VIEW";}function
fk_support($J){return
true;}function
fields($h,$nc=false){$e=array();foreach(get_rows("SELECT c.*, t.name type, d.definition [default]
FROM sys.all_columns c
JOIN sys.all_objects o ON c.object_id = o.object_id
JOIN sys.types t ON c.user_type_id = t.user_type_id
LEFT JOIN sys.default_constraints d ON c.default_object_id = d.parent_column_id
WHERE o.schema_id = SCHEMA_ID(".q(get_schema()).") AND o.type IN ('S', 'U', 'V') AND o.name = ".q($h))as$a){$y=$a["type"];$da=(ereg("char|binary",$y)?$a["max_length"]:($y=="decimal"?"$a[precision],$a[scale]":""));$e[$a["name"]]=array("field"=>$a["name"],"full_type"=>$y.($da?"($da)":""),"type"=>$y,"length"=>$da,"default"=>$a["default"],"null"=>$a["is_nullable"],"auto_increment"=>$a["is_identity"],"collation"=>$a["collation_name"],"privileges"=>array("insert"=>1,"select"=>1,"update"=>1),"primary"=>$a["is_identity"],);}return$e;}function
indexes($h,$I=null){global$g;if(!is_object($I)){$I=$g;}$e=array();foreach(get_rows("SELECT i.name, key_ordinal, is_unique, is_primary_key, c.name AS column_name
FROM sys.indexes i
INNER JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
INNER JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
WHERE OBJECT_NAME(i.object_id) = ".q($h),$I)as$a){$e[$a["name"]]["type"]=($a["is_primary_key"]?"PRIMARY":($a["is_unique"]?"UNIQUE":"INDEX"));$e[$a["name"]]["lengths"]=array();$e[$a["name"]]["columns"][$a["key_ordinal"]]=$a["column_name"];}return$e;}function
view($f){global$g;return
array("select"=>preg_replace('~^(?:[^[]|\\[[^]]*])*\\s+AS\\s+~isU','',$g->result("SELECT VIEW_DEFINITION FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = SCHEMA_NAME() AND TABLE_NAME = ".q($f))));}function
collations(){$e=array();foreach(get_vals("SELECT name FROM fn_helpcollations()")as$R){$e[ereg_replace("_.*","",$R)][]=$R;}return$e;}function
information_schema($s){return
false;}function
error(){global$g;return
nl_br(h(preg_replace('~^(\\[[^]]*])+~m','',$g->error)));}function
exact_value($b){return
q($b);}function
create_database($s,$R){return
queries("CREATE DATABASE ".idf_escape($s).(eregi('^[a-z0-9_]+$',$R)?" COLLATE $R":""));}function
drop_databases($z){return
queries("DROP DATABASE ".implode(", ",array_map('idf_escape',$z)));}function
rename_database($f,$R){if(eregi('^[a-z0-9_]+$',$R)){queries("ALTER DATABASE ".idf_escape(DB)." COLLATE $R");}queries("ALTER DATABASE ".idf_escape(DB)." MODIFY NAME = ".idf_escape($f));return
true;}function
auto_increment(){return" IDENTITY".($_POST["Auto_increment"]!=""?"(".preg_replace('~\\D+~','',$_POST["Auto_increment"]).",1)":"");}function
alter_table($h,$f,$o,$hb,$Ba,$yb,$R,$Pa,$tb){$u=array();foreach($o
as$d){$H=idf_escape($d[0]);$b=$d[1];if(!$b){$u["DROP"][]=" COLUMN $d[0]";}else{$b[1]=preg_replace("~( COLLATE )'(\\w+)'~","\\1\\2",$b[1]);if($d[0]==""){$u["ADD"][]="\n  ".implode("",$b);}else{unset($b[6]);if($H!=$b[0]){queries("EXEC sp_rename ".q(table($h).".$H").", ".q(idf_unescape($b[0])).", 'COLUMN'");}$u["ALTER COLUMN ".implode("",$b)][]="";}}}if($h==""){return
queries("CREATE TABLE ".table($f)." (".implode(",",(array)$u["ADD"])."\n)");}if($h!=$f){queries("EXEC sp_rename ".q(table($h)).", ".q($f));}foreach($u
as$c=>$b){if(!queries("ALTER TABLE ".idf_escape($f)." $c".implode(",",$b))){return
false;}}return
true;}function
alter_indexes($h,$u){$v=array();$Da=array();foreach($u
as$b){if($b[2]){if($b[0]=="PRIMARY"){$Da[]=$b[1];}else{$v[]="$b[1] ON ".table($h);}}elseif(!queries(($b[0]!="PRIMARY"?"CREATE".($b[0]!="INDEX"?" UNIQUE":"")." INDEX ".idf_escape(uniqid($h."_"))." ON ".table($h):"ALTER TABLE ".table($h)." ADD PRIMARY KEY")." $b[1]")){return
false;}}return(!$v||queries("DROP INDEX ".implode(", ",$v)))&&(!$Da||queries("ALTER TABLE ".table($h)." DROP ".implode(", ",$Da)));}function
begin(){return
queries("BEGIN TRANSACTION");}function
insert_into($h,$q){return
queries("INSERT INTO ".table($h).($q?" (".implode(", ",array_keys($q)).")\nVALUES (".implode(", ",$q).")":"DEFAULT VALUES"));}function
insert_update($h,$q,$Ia){$oa=array();$t=array();foreach($q
as$c=>$b){$oa[]="$c = $b";if(isset($Ia[idf_unescape($c)])){$t[]="$c = $b";}}return
queries("MERGE ".table($h)." USING (VALUES(".implode(", ",$q).")) AS source (c".implode(", c",range(1,count($q))).") ON ".implode(" AND ",$t)." WHEN MATCHED THEN UPDATE SET ".implode(", ",$oa)." WHEN NOT MATCHED THEN INSERT (".implode(", ",array_keys($q)).") VALUES (".implode(", ",$q).");");}function
last_id(){global$g;return$g->result("SELECT SCOPE_IDENTITY()");}function
explain($g,$j){$g->query("SET SHOWPLAN_ALL ON");$e=$g->query($j);$g->query("SET SHOWPLAN_ALL OFF");return$e;}function
foreign_keys($h){$e=array();foreach(get_rows("EXEC sp_fkeys @fktable_name = ".q($h))as$a){$A=&$e[$a["FK_NAME"]];$A["table"]=$a["PKTABLE_NAME"];$A["source"][]=$a["FKCOLUMN_NAME"];$A["target"][]=$a["PKCOLUMN_NAME"];}return$e;}function
truncate_tables($D){return
apply_queries("TRUNCATE TABLE",$D);}function
drop_views($Y){return
queries("DROP VIEW ".implode(", ",array_map('table',$Y)));}function
drop_tables($D){return
queries("DROP TABLE ".implode(", ",array_map('table',$D)));}function
move_tables($D,$Y,$ta){return
apply_queries("ALTER SCHEMA ".idf_escape($ta)." TRANSFER",array_merge($D,$Y));}function
trigger($f){$E=get_rows("SELECT s.name [Trigger],
CASE WHEN OBJECTPROPERTY(s.id, 'ExecIsInsertTrigger') = 1 THEN 'INSERT' WHEN OBJECTPROPERTY(s.id, 'ExecIsUpdateTrigger') = 1 THEN 'UPDATE' WHEN OBJECTPROPERTY(s.id, 'ExecIsDeleteTrigger') = 1 THEN 'DELETE' END [Event],
CASE WHEN OBJECTPROPERTY(s.id, 'ExecIsInsteadOfTrigger') = 1 THEN 'INSTEAD OF' ELSE 'AFTER' END [Timing],
c.text
FROM sysobjects s
JOIN syscomments c ON s.id = c.id
WHERE s.xtype = 'TR' AND s.name = ".q($f));$e=reset($E);if($e){$e["Statement"]=preg_replace('~^.+\\s+AS\\s+~isU','',$e["text"]);}return$e;}function
triggers($h){$e=array();foreach(get_rows("SELECT sys1.name,
CASE WHEN OBJECTPROPERTY(sys1.id, 'ExecIsInsertTrigger') = 1 THEN 'INSERT' WHEN OBJECTPROPERTY(sys1.id, 'ExecIsUpdateTrigger') = 1 THEN 'UPDATE' WHEN OBJECTPROPERTY(sys1.id, 'ExecIsDeleteTrigger') = 1 THEN 'DELETE' END [Event],
CASE WHEN OBJECTPROPERTY(sys1.id, 'ExecIsInsteadOfTrigger') = 1 THEN 'INSTEAD OF' ELSE 'AFTER' END [Timing]
FROM sysobjects sys1
JOIN sysobjects sys2 ON sys1.parent_obj = sys2.id
WHERE sys1.xtype = 'TR' AND sys2.name = ".q($h))as$a){$e[$a["name"]]=array($a["Timing"],$a["Event"]);}return$e;}function
trigger_options(){return
array("Timing"=>array("AFTER","INSTEAD OF"),"Type"=>array("AS"),);}function
schemas(){return
get_vals("SELECT name FROM sys.schemas");}function
get_schema(){global$g;if($_GET["ns"]!=""){return$_GET["ns"];}return$g->result("SELECT SCHEMA_NAME()");}function
set_schema($Ka){return
true;}function
use_sql($ba){return"USE ".idf_escape($ba);}function
show_variables(){return
array();}function
show_status(){return
array();}function
support($vb){return
ereg('^(scheme|trigger|view|drop_col)$',$vb);}$_="mssql";$T=array();$Sa=array();foreach(array(lang(10)=>array("tinyint"=>3,"smallint"=>5,"int"=>10,"bigint"=>20,"bit"=>1,"decimal"=>0,"real"=>12,"float"=>53,"smallmoney"=>10,"money"=>20),lang(11)=>array("date"=>10,"smalldatetime"=>19,"datetime"=>19,"datetime2"=>19,"time"=>8,"datetimeoffset"=>10),lang(12)=>array("char"=>8000,"varchar"=>8000,"text"=>2147483647,"nchar"=>4000,"nvarchar"=>4000,"ntext"=>1073741823),lang(13)=>array("binary"=>8000,"varbinary"=>8000,"image"=>2147483647),)as$c=>$b){$T+=$b;$Sa[$c]=array_keys($b);}$sb=array();$bc=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL");$W=array("len","lower","round","upper");$ob=array("avg","count","count distinct","max","min","sum");$dc=array(array("date|time"=>"getdate",),array("int|decimal|real|float|money|datetime"=>"+/-","char|text"=>"+",));}$Ja[]="MySQLi";$Ja[]="MySQL";$Ja[]="PDO_MySQL";if(extension_loaded("mysqli")||extension_loaded("mysql")||extension_loaded("pdo_mysql")){$ja=array("server"=>"MySQL")+$ja;}if(!defined("DRIVER")){define("DRIVER","server");if(extension_loaded("mysqli")){class
Min_DB
extends
MySQLi{var$extension="MySQLi";function
Min_DB(){parent::init();}function
connect($F,$Q,$S){mysqli_report(MYSQLI_REPORT_OFF);list($tf,$Qc)=explode(":",$F,2);$e=@$this->real_connect(($F!=""?$tf:ini_get("mysqli.default_host")),("$F$Q"!=""?$Q:ini_get("mysqli.default_user")),("$F$Q$S"!=""?$S:ini_get("mysqli.default_pw")),null,(is_numeric($Qc)?$Qc:ini_get("mysqli.default_port")),(!is_numeric($Qc)?$Qc:null));if($e){if(method_exists($this,'set_charset')){$this->set_charset("utf8");}else{$this->query("SET NAMES utf8");}}return$e;}function
result($j,$d=0){$i=$this->query($j);if(!$i){return
false;}$a=$i->fetch_array();return$a[$d];}function
quote($G){return"'".$this->escape_string($G)."'";}}}elseif(extension_loaded("mysql")){class
Min_DB{var$extension="MySQL",$server_info,$affected_rows,$error,$_link,$_result;function
connect($F,$Q,$S){$this->_link=@mysql_connect(($F!=""?$F:ini_get("mysql.default_host")),("$F$Q"!=""?$Q:ini_get("mysql.default_user")),("$F$Q$S"!=""?$S:ini_get("mysql.default_password")),true,131072);if($this->_link){$this->server_info=mysql_get_server_info($this->_link);if(function_exists('mysql_set_charset')){mysql_set_charset("utf8",$this->_link);}else{$this->query("SET NAMES utf8");}}else{$this->error=mysql_error();}return(bool)$this->_link;}function
quote($G){return"'".mysql_real_escape_string($G,$this->_link)."'";}function
select_db($ba){return
mysql_select_db($ba,$this->_link);}function
query($j,$bb=false){$i=@($bb?mysql_unbuffered_query($j,$this->_link):mysql_query($j,$this->_link));if(!$i){$this->error=mysql_error($this->_link);return
false;}if($i===true){$this->affected_rows=mysql_affected_rows($this->_link);$this->info=mysql_info($this->_link);return
true;}return
new
Min_Result($i);}function
multi_query($j){return$this->_result=$this->query($j);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($j,$d=0){$i=$this->query($j);if(!$i){return
false;}return
mysql_result($i->_result,0,$d);}}class
Min_Result{var$num_rows,$_result;function
Min_Result($i){$this->_result=$i;$this->num_rows=mysql_num_rows($i);}function
fetch_assoc(){return
mysql_fetch_assoc($this->_result);}function
fetch_row(){return
mysql_fetch_row($this->_result);}function
fetch_field(){$e=mysql_fetch_field($this->_result);$e->orgtable=$e->table;$e->orgname=$e->name;$e->charsetnr=($e->blob?63:0);return$e;}function
__destruct(){mysql_free_result($this->_result);}}}elseif(extension_loaded("pdo_mysql")){class
Min_DB
extends
Min_PDO{var$extension="PDO_MySQL";function
connect($F,$Q,$S){$this->dsn("mysql:host=".str_replace(":",";unix_socket=",preg_replace('~:([0-9])~',';port=\\1',$F)),$Q,$S);$this->query("SET NAMES utf8");return
true;}function
select_db($ba){return$this->query("USE ".idf_escape($ba));}function
query($j,$bb=false){$this->setAttribute(1000,!$bb);return
parent::query($j,$bb);}}}function
idf_escape($N){return"`".str_replace("`","``",$N)."`";}function
table($N){return
idf_escape($N);}function
connect(){global$r;$g=new
Min_DB;$Aa=$r->credentials();if($g->connect($Aa[0],$Aa[1],$Aa[2])){$g->query("SET SQL_QUOTE_SHOW_CREATE=1");return$g;}return$g->error;}function
get_databases($Ff=true){$e=&get_session("dbs");if(!isset($e)){if($Ff){restart_session();ob_flush();flush();}$e=get_vals("SHOW DATABASES");}return$e;}function
limit($j,$t,$M,$O=0,$Ta=" "){return" $j$t".(isset($M)?$Ta."LIMIT $M".($O?" OFFSET $O":""):"");}function
limit1($j,$t){return
limit($j,$t,1);}function
db_collation($s,$X){global$g;$e=null;$ga=$g->result("SHOW CREATE DATABASE ".idf_escape($s),1);if(preg_match('~ COLLATE ([^ ]+)~',$ga,$k)){$e=$k[1];}elseif(preg_match('~ CHARACTER SET ([^ ]+)~',$ga,$k)){$e=$X[$k[1]][0];}return$e;}function
engines(){$e=array();foreach(get_rows("SHOW ENGINES")as$a){if(ereg("YES|DEFAULT",$a["Support"])){$e[]=$a["Engine"];}}return$e;}function
logged_user(){global$g;return$g->result("SELECT USER()");}function
tables_list(){global$g;return
get_key_vals("SHOW".($g->server_info>=5?" FULL":"")." TABLES");}function
count_tables($z){$e=array();foreach($z
as$s){$e[$s]=count(get_vals("SHOW TABLES IN ".idf_escape($s)));}return$e;}function
table_status($f=""){$e=array();foreach(get_rows("SHOW TABLE STATUS".($f!=""?" LIKE ".q(addcslashes($f,"%_")):""))as$a){if($a["Engine"]=="InnoDB"){$a["Comment"]=preg_replace('~(?:(.+); )?InnoDB free: .*~','\\1',$a["Comment"]);}if(!isset($a["Rows"])){$a["Comment"]="";}if($f!=""){return$a;}$e[$a["Name"]]=$a;}return$e;}function
is_view($J){return!isset($J["Rows"]);}function
fk_support($J){return($J["Engine"]=="InnoDB");}function
fields($h,$nc=false){$e=array();foreach(get_rows("SHOW FULL COLUMNS FROM ".table($h))as$a){preg_match('~^([^( ]+)(?:\\((.+)\\))?( unsigned)?( zerofill)?$~',$a["Type"],$k);$e[$a["Field"]]=array("field"=>$a["Field"],"full_type"=>$a["Type"],"type"=>$k[1],"length"=>$k[2],"unsigned"=>ltrim($k[3].$k[4]),"default"=>($a["Default"]!=""||ereg("char",$k[1])?$a["Default"]:null),"null"=>($a["Null"]=="YES"),"auto_increment"=>($a["Extra"]=="auto_increment"),"on_update"=>(eregi('^on update (.+)',$a["Extra"],$k)?$k[1]:""),"collation"=>$a["Collation"],"privileges"=>array_flip(explode(",",$a["Privileges"])),"comment"=>$a["Comment"],"primary"=>($a["Key"]=="PRI"),);}return$e;}function
indexes($h,$I=null){global$g;if(!is_object($I)){$I=$g;}$e=array();foreach(get_rows("SHOW INDEX FROM ".table($h),$I)as$a){$e[$a["Key_name"]]["type"]=($a["Key_name"]=="PRIMARY"?"PRIMARY":($a["Index_type"]=="FULLTEXT"?"FULLTEXT":($a["Non_unique"]?"INDEX":"UNIQUE")));$e[$a["Key_name"]]["columns"][]=$a["Column_name"];$e[$a["Key_name"]]["lengths"][]=$a["Sub_part"];}return$e;}function
foreign_keys($h){global$g,$db;static$ha='`(?:[^`]|``)+`';$e=array();$ze=$g->result("SHOW CREATE TABLE ".table($h),1);if($ze){preg_match_all("~CONSTRAINT ($ha) FOREIGN KEY \\(((?:$ha,? ?)+)\\) REFERENCES ($ha)(?:\\.($ha))? \\(((?:$ha,? ?)+)\\)(?: ON DELETE (".implode("|",$db)."))?(?: ON UPDATE (".implode("|",$db)."))?~",$ze,$ka,PREG_SET_ORDER);foreach($ka
as$k){preg_match_all("~$ha~",$k[2],$Ga);preg_match_all("~$ha~",$k[5],$ta);$e[idf_unescape($k[1])]=array("db"=>idf_unescape($k[4]!=""?$k[3]:$k[4]),"table"=>idf_unescape($k[4]!=""?$k[4]:$k[3]),"source"=>array_map('idf_unescape',$Ga[0]),"target"=>array_map('idf_unescape',$ta[0]),"on_delete"=>$k[6],"on_update"=>$k[7],);}}return$e;}function
view($f){global$g;return
array("select"=>preg_replace('~^(?:[^`]|`[^`]*`)*\\s+AS\\s+~isU','',$g->result("SHOW CREATE VIEW ".table($f),1)));}function
collations(){$e=array();foreach(get_rows("SHOW COLLATION")as$a){$e[$a["Charset"]][]=$a["Collation"];}ksort($e);foreach($e
as$c=>$b){sort($e[$c]);}return$e;}function
information_schema($s){global$g;return($g->server_info>=5&&$s=="information_schema");}function
error(){global$g;return
h(preg_replace('~^You have an error.*syntax to use~U',"Syntax error",$g->error));}function
exact_value($b){return
q($b)." COLLATE utf8_bin";}function
create_database($s,$R){set_session("dbs",null);return
queries("CREATE DATABASE ".idf_escape($s).($R?" COLLATE ".q($R):""));}function
drop_databases($z){set_session("dbs",null);return
apply_queries("DROP DATABASE",$z,'idf_escape');}function
rename_database($f,$R){if(create_database($f,$R)){$Tb=array();foreach(tables_list()as$h=>$y){$Tb[]=table($h)." TO ".idf_escape($f).".".table($h);}if(!$Tb||queries("RENAME TABLE ".implode(", ",$Tb))){queries("DROP DATABASE ".idf_escape(DB));return
true;}}return
false;}function
auto_increment(){$Ad=" PRIMARY KEY";if($_GET["create"]!=""&&$_POST["auto_increment_col"]){foreach(indexes($_GET["create"])as$v){if(in_array($_POST["fields"][$_POST["auto_increment_col"]]["orig"],$v["columns"],true)){$Ad="";break;}if($v["type"]=="PRIMARY"){$Ad=" UNIQUE";}}}return" AUTO_INCREMENT$Ad";}function
alter_table($h,$f,$o,$hb,$Ba,$yb,$R,$Pa,$tb){$u=array();foreach($o
as$d){$u[]=($d[1]?($h!=""?($d[0]!=""?"CHANGE ".idf_escape($d[0]):"ADD"):" ")." ".implode($d[1]).($h!=""?" $d[2]":""):"DROP ".idf_escape($d[0]));}$u=array_merge($u,$hb);$Zb="COMMENT=".q($Ba).($yb?" ENGINE=".q($yb):"").($R?" COLLATE ".q($R):"").($Pa!=""?" AUTO_INCREMENT=$Pa":"").$tb;if($h==""){return
queries("CREATE TABLE ".table($f)." (\n".implode(",\n",$u)."\n) $Zb");}if($h!=$f){$u[]="RENAME TO ".table($f);}$u[]=$Zb;return
queries("ALTER TABLE ".table($h)."\n".implode(",\n",$u));}function
alter_indexes($h,$u){foreach($u
as$c=>$b){$u[$c]=($b[2]?"\nDROP INDEX ":"\nADD $b[0] ".($b[0]=="PRIMARY"?"KEY ":"")).$b[1];}return
queries("ALTER TABLE ".table($h).implode(",",$u));}function
truncate_tables($D){return
apply_queries("TRUNCATE TABLE",$D);}function
drop_views($Y){return
queries("DROP VIEW ".implode(", ",array_map('table',$Y)));}function
drop_tables($D){return
queries("DROP TABLE ".implode(", ",array_map('table',$D)));}function
move_tables($D,$Y,$ta){$Tb=array();foreach(array_merge($D,$Y)as$h){$Tb[]=table($h)." TO ".idf_escape($ta).".".table($h);}return
queries("RENAME TABLE ".implode(", ",$Tb));}function
trigger($f){$E=get_rows("SHOW TRIGGERS WHERE `Trigger` = ".q($f));return
reset($E);}function
triggers($h){$e=array();foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($h,"%_")))as$a){$e[$a["Trigger"]]=array($a["Timing"],$a["Event"]);}return$e;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER"),"Type"=>array("FOR EACH ROW"),);}function
routine($f,$y){global$g,$Hb,$_c,$T;$Ef=array("bool","boolean","integer","double precision","real","dec","numeric","fixed","national char","national varchar");$pe="((".implode("|",array_merge(array_keys($T),$Ef)).")(?:\\s*\\(((?:[^'\")]*|$Hb)+)\\))?\\s*(zerofill\\s*)?(unsigned(?:\\s+zerofill)?)?)(?:\\s*(?:CHARSET|CHARACTER\\s+SET)\\s*['\"]?([^'\"\\s]+)['\"]?)?";$ha="\\s*(".($y=="FUNCTION"?"":implode("|",$_c)).")?\\s*(?:`((?:[^`]|``)*)`\\s*|\\b(\\S+)\\s+)$pe";$ga=$g->result("SHOW CREATE $y ".idf_escape($f),2);preg_match("~\\(((?:$ha\\s*,?)*)\\)".($y=="FUNCTION"?"\\s*RETURNS\\s+$pe":"")."\\s*(.*)~is",$ga,$k);$o=array();preg_match_all("~$ha\\s*,?~is",$k[1],$ka,PREG_SET_ORDER);foreach($ka
as$ab){$f=str_replace("``","`",$ab[2]).$ab[3];$o[]=array("field"=>$f,"type"=>strtolower($ab[5]),"length"=>preg_replace_callback("~$Hb~s",'normalize_enum',$ab[6]),"unsigned"=>strtolower(preg_replace('~\\s+~',' ',trim("$ab[8] $ab[7]"))),"full_type"=>$ab[4],"inout"=>strtoupper($ab[1]),"collation"=>strtolower($ab[9]),);}if($y!="FUNCTION"){return
array("fields"=>$o,"definition"=>$k[11]);}return
array("fields"=>$o,"returns"=>array("type"=>$k[12],"length"=>$k[13],"unsigned"=>$k[15],"collation"=>$k[16]),"definition"=>$k[17],);}function
routines(){return
get_rows("SELECT * FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = ".q(DB));}function
begin(){return
queries("BEGIN");}function
insert_into($h,$q){return
queries("INSERT INTO ".table($h)." (".implode(", ",array_keys($q)).")\nVALUES (".implode(", ",$q).")");}function
insert_update($h,$q,$Ia){foreach($q
as$c=>$b){$q[$c]="$c = $b";}$oa=implode(", ",$q);return
queries("INSERT INTO ".table($h)." SET $oa ON DUPLICATE KEY UPDATE $oa");}function
last_id(){global$g;return$g->result("SELECT LAST_INSERT_ID()");}function
explain($g,$j){return$g->query("EXPLAIN $j");}function
types(){return
array();}function
schemas(){return
array();}function
get_schema(){return"";}function
set_schema($Ka){return
true;}function
create_sql($h,$Pa){global$g;$e=$g->result("SHOW CREATE TABLE ".table($h),1);if(!$Pa){$e=preg_replace('~ AUTO_INCREMENT=[0-9]+~','',$e);}return$e;}function
truncate_sql($h){return"TRUNCATE ".table($h);}function
use_sql($ba){return"USE ".idf_escape($ba);}function
trigger_sql($h,$V){$e="";foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($h,"%_")),null,"-- ")as$a){$e.="\n".($V=='CREATE+ALTER'?"DROP TRIGGER IF EXISTS ".idf_escape($a["Trigger"]).";;\n":"")."CREATE TRIGGER ".idf_escape($a["Trigger"])." $a[Timing] $a[Event] ON ".table($a["Table"])." FOR EACH ROW\n$a[Statement];;\n";}return$e;}function
show_variables(){return
get_key_vals("SHOW VARIABLES");}function
show_status(){return
get_key_vals("SHOW STATUS");}function
support($vb){global$g;return!ereg("scheme|sequence|type".($g->server_info<5.1?"|event|partitioning".($g->server_info<5?"|view|routine|trigger":""):""),$vb);}$_="sql";$T=array();$Sa=array();foreach(array(lang(10)=>array("tinyint"=>3,"smallint"=>5,"mediumint"=>8,"int"=>10,"bigint"=>20,"decimal"=>66,"float"=>12,"double"=>21),lang(11)=>array("date"=>10,"datetime"=>19,"timestamp"=>19,"time"=>10,"year"=>4),lang(12)=>array("char"=>255,"varchar"=>65535,"tinytext"=>255,"text"=>65535,"mediumtext"=>16777215,"longtext"=>4294967295),lang(13)=>array("bit"=>20,"binary"=>255,"varbinary"=>65535,"tinyblob"=>255,"blob"=>65535,"mediumblob"=>16777215,"longblob"=>4294967295),lang(16)=>array("enum"=>65535,"set"=>64),)as$c=>$b){$T+=$b;$Sa[$c]=array_keys($b);}$sb=array("unsigned","zerofill","unsigned zerofill");$bc=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","REGEXP","IN","IS NULL","NOT LIKE","NOT REGEXP","NOT IN","IS NOT NULL");$W=array("char_length","date","from_unixtime","hex","lower","round","sec_to_time","time_to_sec","upper");$ob=array("avg","count","count distinct","group_concat","max","min","sum");$dc=array(array("char"=>"md5/sha1/password/encrypt/uuid","binary"=>"md5/sha1/hex","date|time"=>"now",),array("int|float|double|decimal"=>"+/-","date"=>"+ interval/- interval","time"=>"addtime/subtime","char|text"=>"concat",));}define("SERVER",$_GET[DRIVER]);define("DB",$_GET["db"]);define("ME",preg_replace('~^[^?]*/([^?]*).*~','\\1',$_SERVER["REQUEST_URI"]).'?'.(SID&&!$_COOKIE?SID.'&':'').(SERVER!==null?DRIVER."=".urlencode(SERVER).'&':'').(isset($_GET["username"])?"username=".urlencode($_GET["username"]).'&':'').(DB!=""?'db='.urlencode(DB).'&'.(isset($_GET["ns"])?"ns=".urlencode($_GET["ns"])."&":""):''));$Yc="3.0.1";class
Adminer{var$operators;function
name(){return"Adminer";}function
credentials(){return
array(SERVER,$_GET["username"],get_session("pwds"));}function
permanentLogin(){return
password_file();}function
database(){return
DB;}function
headers(){header("X-Frame-Options: deny");}function
loginForm(){global$ja;echo'<table cellspacing="0">
<tr><th>',lang(17),'<td>',html_select("driver",$ja,DRIVER),'<tr><th>',lang(18),'<td><input name="server" value="',h(SERVER),'">
<tr><th>',lang(19),'<td><input id="username" name="username" value="',h($_GET["username"]),'">
<tr><th>',lang(20),'<td><input type="password" name="password">
</table>
<script type="text/javascript">
document.getElementById(\'username\').focus();
</script>
',"<p><input type='submit' value='".lang(21)."'>\n",checkbox("permanent",1,$_COOKIE["adminer_permanent"],lang(22))."\n";}function
login($Hf,$S){return
true;}function
tableName($Wc){return
h($Wc["Name"]);}function
fieldName($d,$pb=0){return'<span title="'.h($d["full_type"]).'">'.h($d["field"]).'</span>';}function
selectLinks($Wc,$q=""){echo'<p class="tabs">';$La=array("select"=>lang(23),"table"=>lang(24));if(is_view($Wc)){$La["view"]=lang(25);}else{$La["create"]=lang(26);}if(isset($q)){$La["edit"]=lang(27);}foreach($La
as$c=>$b){echo" <a href='".h(ME)."$c=".urlencode($Wc["Name"]).($c=="edit"?$q:"")."'>".bold($b,isset($_GET[$c]))."</a>";}echo"\n";}function
backwardKeys($h,$Jf){return
array();}function
backwardKeysPrint($Kf,$a){}function
selectQuery($j){global$_;return"<p><a href='".h(remove_from_uri("page"))."&amp;page=last' title='".lang(28).": ".lang(29)."'>&gt;&gt;</a> <code class='jush-$_'>".h(str_replace("\n"," ",$j))."</code> <a href='".h(ME)."sql=".urlencode($j)."'>".lang(30)."</a>\n";}function
rowDescription($h){return"";}function
rowDescriptions($E,$Gf){return$E;}function
selectVal($b,$x,$d){$e=($b!="<i>NULL</i>"&&ereg("^char|binary",$d["type"])?"<code>$b</code>":$b);if(ereg('blob|bytea|raw|file',$d["type"])&&!is_utf8($b)){$e=lang(31,strlen(html_entity_decode($b,ENT_QUOTES)));}return($x?"<a href='$x'>$e</a>":$e);}function
editVal($b,$d){return(ereg("binary",$d["type"])?reset(unpack("H*",$b)):$b);}function
selectColumnsPrint($C,$B){global$W,$ob;print_fieldset("select",lang(32),$C);$l=0;$ge=array(lang(33)=>$W,lang(34)=>$ob);foreach($C
as$c=>$b){$b=$_GET["columns"][$c];echo"<div>".html_select("columns[$l][fun]",array(-1=>"")+$ge,$b["fun"]),"(<select name='columns[$l][col]'><option>".optionlist($B,$b["col"],true)."</select>)</div>\n";$l++;}echo"<div>".html_select("columns[$l][fun]",array(-1=>"")+$ge,"","this.nextSibling.nextSibling.onchange();"),"(<select name='columns[$l][col]' onchange='selectAddRow(this);'><option>".optionlist($B,null,true)."</select>)</div>\n","</div></fieldset>\n";}function
selectSearchPrint($t,$B,$K){print_fieldset("search",lang(35),$t);foreach($K
as$l=>$v){if($v["type"]=="FULLTEXT"){echo"(<i>".implode("</i>, <i>",array_map('h',$v["columns"]))."</i>) AGAINST"," <input name='fulltext[$l]' value='".h($_GET["fulltext"][$l])."'>",checkbox("boolean[$l]",1,isset($_GET["boolean"][$l]),"BOOL"),"<br>\n";}}$l=0;foreach((array)$_GET["where"]as$b){if("$b[col]$b[val]"!=""&&in_array($b["op"],$this->operators)){echo"<div><select name='where[$l][col]'><option value=''>(".lang(36).")".optionlist($B,$b["col"],true)."</select>",html_select("where[$l][op]",$this->operators,$b["op"]),"<input name='where[$l][val]' value='".h($b["val"])."'></div>\n";$l++;}}echo"<div><select name='where[$l][col]' onchange='selectAddRow(this);'><option value=''>(".lang(36).")".optionlist($B,null,true)."</select>",html_select("where[$l][op]",$this->operators),"<input name='where[$l][val]'></div>\n","</div></fieldset>\n";}function
selectOrderPrint($pb,$B,$K){print_fieldset("sort",lang(37),$pb);$l=0;foreach((array)$_GET["order"]as$c=>$b){if(isset($B[$b])){echo"<div><select name='order[$l]'><option>".optionlist($B,$b,true)."</select>",checkbox("desc[$l]",1,isset($_GET["desc"][$c]),lang(38))."</div>\n";$l++;}}echo"<div><select name='order[$l]' onchange='selectAddRow(this);'><option>".optionlist($B,null,true)."</select>",checkbox("desc[$l]",1,0,lang(38))."</div>\n","</div></fieldset>\n";}function
selectLimitPrint($M){echo"<fieldset><legend>".lang(39)."</legend><div>";echo"<input name='limit' size='3' value='".h($M)."'>","</div></fieldset>\n";}function
selectLengthPrint($zb){if(isset($zb)){echo"<fieldset><legend>".lang(40)."</legend><div>",'<input name="text_length" size="3" value="'.h($zb).'">',"</div></fieldset>\n";}}function
selectActionPrint(){echo"<fieldset><legend>".lang(41)."</legend><div>","<input type='submit' value='".lang(32)."'>","</div></fieldset>\n";}function
selectEmailPrint($If,$B){}function
selectColumnsProcess($B,$K){global$W,$ob;$C=array();$_a=array();foreach((array)$_GET["columns"]as$c=>$b){if($b["fun"]=="count"||(isset($B[$b["col"]])&&(!$b["fun"]||in_array($b["fun"],$W)||in_array($b["fun"],$ob)))){$C[$c]=apply_sql_function($b["fun"],(isset($B[$b["col"]])?idf_escape($b["col"]):"*"));if(!in_array($b["fun"],$ob)){$_a[]=$C[$c];}}}return
array($C,$_a);}function
selectSearchProcess($o,$K){global$_;$e=array();foreach($K
as$l=>$v){if($v["type"]=="FULLTEXT"&&$_GET["fulltext"][$l]!=""){$e[]="MATCH (".implode(", ",array_map('idf_escape',$v["columns"])).") AGAINST (".q($_GET["fulltext"][$l]).(isset($_GET["boolean"][$l])?" IN BOOLEAN MODE":"").")";}}foreach((array)$_GET["where"]as$b){if("$b[col]$b[val]"!=""&&in_array($b["op"],$this->operators)){$Wb=" $b[op]";if(ereg('IN$',$b["op"])){$Fb=process_length($b["val"]);$Wb.=" (".($Fb!=""?$Fb:"NULL").")";}elseif($b["op"]=="LIKE %%"){$Wb=" LIKE ".$this->processInput($o[$b["col"]],"%$b[val]%");}elseif(!ereg('NULL$',$b["op"])){$Wb.=" ".$this->processInput($o[$b["col"]],$b["val"]);}if($b["col"]!=""){$e[]=idf_escape($b["col"]).$Wb;}else{$ib=array();foreach($o
as$f=>$d){if(is_numeric($b["val"])||!ereg('int|float|double|decimal',$d["type"])){$f=idf_escape($f);$ib[]=($_=="sql"&&ereg('char|text|enum|set',$d["type"])&&!ereg('^utf8',$d["collation"])?"CONVERT($f USING utf8)":$f);}}$e[]=($ib?"(".implode("$Wb OR ",$ib)."$Wb)":"0");}}}return$e;}function
selectOrderProcess($o,$K){$e=array();foreach((array)$_GET["order"]as$c=>$b){if(isset($o[$b])||preg_match('~^((COUNT\\(DISTINCT |[A-Z0-9_]+\\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\\)|COUNT\\(\\*\\))$~',$b)){$e[]=(isset($o[$b])?idf_escape($b):$b).(isset($_GET["desc"][$c])?" DESC":"");}}return$e;}function
selectLimitProcess(){return(isset($_GET["limit"])?$_GET["limit"]:"30");}function
selectLengthProcess(){return(isset($_GET["text_length"])?$_GET["text_length"]:"100");}function
selectEmailProcess($t,$Gf){return
false;}function
messageQuery($j){global$_;restart_session();$U="sql-".count($_SESSION["messages"]);$gb=&get_session("queries");$gb[$_GET["db"]][]=(strlen($j)>1e6?ereg_replace('[\x80-\xFF]+$','',substr($j,0,1e6))."\n...":$j);return" <a href='#$U' onclick=\"return !toggle('$U');\">".lang(42)."</a><div id='$U' class='hidden'><pre class='jush-$_'>".shorten_utf8($j,1000).'</pre><p><a href="'.h(str_replace("db=".urlencode(DB),"db=".urlencode($_GET["db"]),ME).'sql=&history='.(count($gb[$_GET["db"]])-1)).'">'.lang(30).'</a></div>';}function
editFunctions($d){global$dc;$e=($d["null"]?"NULL/":"");foreach($dc
as$c=>$W){if(!$c||(!isset($_GET["call"])&&(isset($_GET["select"])||where($_GET)))){foreach($W
as$ha=>$b){if(!$ha||ereg($ha,$d["type"])){$e.="/$b";}}}}return
explode("/",$e);}function
editInput($h,$d,$Oa,$p){if($d["type"]=="enum"){return($d["null"]?"<label><input type='radio'$Oa value=''".(isset($p)||isset($_GET["select"])?"":" checked")."><i>NULL</i></label> ":"")."<label><input type='radio'$Oa value='0'".($p===0?" checked":"")."><i>".lang(43)."</i></label>";}return"";}function
processInput($d,$p,$P=""){$f=$d["field"];$e=q($p);if(ereg('^(now|getdate|uuid)$',$P)){$e="$P()";}elseif(ereg('^current_(date|timestamp)$',$P)){$e=$P;}elseif(ereg('^([+-]|\\|\\|)$',$P)){$e=idf_escape($f)." $P $e";}elseif(ereg('^[+-] interval$',$P)){$e=idf_escape($f)." $P ".(preg_match("~^([0-9]+|'[0-9.: -]') [A-Z_]+$~i",$p)?$p:$e);}elseif(ereg('^(addtime|subtime|concat)$',$P)){$e="$P(".idf_escape($f).", $e)";}elseif(ereg('^(md5|sha1|password|encrypt|hex)$',$P)){$e="$P($e)";}if(ereg("binary",$d["type"])){$e="unhex($e)";}return$e;}function
dumpOutput($C,$p=""){$e=array('text'=>lang(44),'file'=>lang(45));if(function_exists('gzencode')){$e['gz']='gzip';}if(function_exists('bzcompress')){$e['bz2']='bzip2';}return
html_select("output",$e,$p,$C);}function
dumpFormat($C,$p=""){return
html_select("format",array('sql'=>'SQL','csv'=>'CSV,','csv;'=>'CSV;'),$p,$C);}function
navigation($Xb){global$Yc,$g,$L,$_,$ja;echo'<h1>
<a href="http://www.adminer.org/" id="h1">',$this->name(),'</a>
<span class="version">',$Yc,'</span>
<a href="http://www.adminer.org/#download" id="version">',(version_compare($Yc,$_COOKIE["adminer_version"])<0?h($_COOKIE["adminer_version"]):""),'</a>
</h1>
';if($Xb=="auth"){$fb=true;foreach((array)$_SESSION["pwds"]as$Ib=>$Cf){foreach($Cf
as$F=>$xf){foreach($xf
as$Q=>$S){if(isset($S)){if($fb){echo"<p>\n";$fb=false;}echo"<a href='".h(auth_url($Ib,$F,$Q))."'>($ja[$Ib]) ".h($Q.($F!=""?"@$F":""))."</a><br>\n";}}}}}else{$z=get_databases();echo'<form action="" method="post">
<p class="logout">
';if(DB==""||!$Xb){echo"<a href='".h(ME)."sql='>".bold(lang(42),isset($_GET["sql"]))."</a>\n";if(support("dump")){echo"<a href='".h(ME)."dump=".urlencode(isset($_GET["table"])?$_GET["table"]:$_GET["select"])."'>".bold(lang(46),isset($_GET["dump"]))."</a>\n";}}echo'<input type="hidden" name="token" value="',$L,'">
<input type="submit" name="logout" value="',lang(47),'">
</p>
</form>
<form action="">
<p>
';hidden_fields_get();echo($z?html_select("db",array(""=>"(".lang(48).")")+$z,DB,"this.form.submit();"):'<input name="db" value="'.h(DB).'">'),'<input type="submit" value="',lang(6),'"',($z?" class='hidden'":""),'>
';if($Xb!="db"&&DB!=""&&$g->select_db(DB)){if(support("scheme")){echo"<br>".html_select("ns",array(""=>"(".lang(49).")")+schemas(),$_GET["ns"],"this.form.submit();");if($_GET["ns"]!=""){set_schema($_GET["ns"]);}}if($_GET["ns"]!==""&&!$Xb){$D=tables_list();if(!$D){echo"<p class='message'>".lang(4)."\n";}else{$this->tablesPrint($D);$La=array();foreach($D
as$h=>$y){$La[]=preg_quote($h,'/');}echo"<script type='text/javascript'>\n","var jushLinks = { $_: [ '".addcslashes(h(ME),"\\'/")."table=\$&', /\\b(".implode("|",$La).")\\b/g ] };\n";foreach(array("bac","bra","sqlite_quo","mssql_bra")as$b){echo"jushLinks.$b = jushLinks.$_;\n";}echo"</script>\n";}echo'<p><a href="'.h(ME).'create=">'.bold(lang(50),$_GET["create"]==="")."</a>\n";}}echo(isset($_GET["sql"])?'<input type="hidden" name="sql" value="">':(isset($_GET["schema"])?'<input type="hidden" name="schema" value="">':(isset($_GET["dump"])?'<input type="hidden" name="dump" value="">':""))),"</p></form>\n";}}function
tablesPrint($D){echo"<p id='tables'>\n";foreach($D
as$h=>$y){echo'<a href="'.h(ME).'select='.urlencode($h).'">'.bold(lang(51),$_GET["select"]==$h).'</a> ','<a href="'.h(ME).'table='.urlencode($h).'">'.bold($this->tableName(array("Name"=>$h)),$_GET["table"]==$h)."</a><br>\n";}}}$r=(function_exists('adminer_object')?adminer_object():new
Adminer);if(!isset($r->operators)){$r->operators=$bc;}function
page_header($ne,$n="",$ic=array(),$me=""){global$Wa,$Vb,$r,$g,$ja;header("Content-Type: text/html; charset=utf-8");$r->headers();$le=$ne.($me!=""?": ".h($me):"");$gc=($Vb?"https":"http");echo'<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html lang="',$Wa,'">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="Content-Script-Type" content="text/javascript">
<meta name="robots" content="noindex">
<title>',$le.(SERVER!=""&&SERVER!="localhost"?h(" - ".SERVER):"")." - ".$r->name(),'</title>
<link rel="shortcut icon" type="image/x-icon" href="',h(preg_replace("~\\?.*~","",$_SERVER["REQUEST_URI"]))."?file=favicon.ico&amp;version=3.0.1",'">
<link rel="stylesheet" type="text/css" href="',h(preg_replace("~\\?.*~","",$_SERVER["REQUEST_URI"]))."?file=default.css&amp;version=3.0.1";echo'">
';if(file_exists("adminer.css")){echo'<link rel="stylesheet" type="text/css" href="adminer.css">
';}echo'
<body onload="bodyLoad(\'',(is_object($g)?substr($g->server_info,0,3):""),'\', \'',$gc,'\');',(isset($_COOKIE["adminer_version"])?"":" verifyVersion('$gc');"),'">
<script type="text/javascript" src="',h(preg_replace("~\\?.*~","",$_SERVER["REQUEST_URI"]))."?file=functions.js&amp;version=3.0.1",'"></script>

<div id="content">
';if(isset($ic)){$x=substr(preg_replace('~(username|db|ns)=[^&]*&~','',ME),0,-1);echo'<p id="breadcrumb"><a href="'.($x?h($x):".").'">'.$ja[DRIVER].'</a> &raquo; ';$x=substr(preg_replace('~(db|ns)=[^&]*&~','',ME),0,-1);$F=(SERVER!=""?h(SERVER):lang(18));if($ic===false){echo"$F\n";}else{echo"<a href='".($x?h($x):".")."'>$F</a> &raquo; ";if($_GET["ns"]!=""||(DB!=""&&is_array($ic))){echo'<a href="'.h($x."&db=".urlencode(DB).(support("scheme")?"&ns=":"")).'">'.h(DB).'</a> &raquo; ';}if(is_array($ic)){if($_GET["ns"]!=""){echo'<a href="'.h(substr(ME,0,-1)).'">'.h($_GET["ns"]).'</a> &raquo; ';}foreach($ic
as$c=>$b){$hc=(is_array($b)?$b[1]:$b);if($hc!=""){echo'<a href="'.h(ME."$c=").urlencode(is_array($b)?$b[0]:$b).'">'.h($hc).'</a> &raquo; ';}}}echo"$ne\n";}}echo"<h2>$le</h2>\n";restart_session();if($_SESSION["messages"]){echo"<div class='message'>".implode("</div>\n<div class='message'>",$_SESSION["messages"])."</div>\n";$_SESSION["messages"]=array();}$z=&get_session("dbs");if(DB!=""&&$z&&!in_array(DB,$z,true)){$z=null;}if($n){echo"<div class='error'>$n</div>\n";}}function
page_footer($Xb=""){global$r;echo'</div>

';switch_lang();echo'<div id="menu">
';$r->navigation($Xb);echo'</div>
';}function
int32($ca){while($ca>=2147483648){$ca-=4294967296;}while($ca<=-2147483649){$ca+=4294967296;}return(int)$ca;}function
long2str($w,$_d){$ia='';foreach($w
as$b){$ia.=pack('V',$b);}if($_d){return
substr($ia,0,end($w));}return$ia;}function
str2long($ia,$_d){$w=array_values(unpack('V*',str_pad($ia,4*ceil(strlen($ia)/4),"\0")));if($_d){$w[]=strlen($ia);}return$w;}function
xxtea_mx($ya,$ua,$Ha,$Na){return
int32((($ya>>5&0x7FFFFFF)^$ua<<2)+(($ua>>3&0x1FFFFFFF)^$ya<<4))^int32(($Ha^$ua)+($Na^$ya));}function
encrypt_string($mc,$c){if($mc==""){return"";}$c=array_values(unpack("V*",pack("H*",md5($c))));$w=str2long($mc,true);$ca=count($w)-1;$ya=$w[$ca];$ua=$w[0];$ra=floor(6+52/($ca+1));$Ha=0;while($ra-->0){$Ha=int32($Ha+0x9E3779B9);$wc=$Ha>>2&3;for($wa=0;$wa<$ca;$wa++){$ua=$w[$wa+1];$Eb=xxtea_mx($ya,$ua,$Ha,$c[$wa&3^$wc]);$ya=int32($w[$wa]+$Eb);$w[$wa]=$ya;}$ua=$w[0];$Eb=xxtea_mx($ya,$ua,$Ha,$c[$wa&3^$wc]);$ya=int32($w[$ca]+$Eb);$w[$ca]=$ya;}return
long2str($w,false);}function
decrypt_string($mc,$c){if($mc==""){return"";}$c=array_values(unpack("V*",pack("H*",md5($c))));$w=str2long($mc,false);$ca=count($w)-1;$ya=$w[$ca];$ua=$w[0];$ra=floor(6+52/($ca+1));$Ha=int32($ra*0x9E3779B9);while($Ha){$wc=$Ha>>2&3;for($wa=$ca;$wa>0;$wa--){$ya=$w[$wa-1];$Eb=xxtea_mx($ya,$ua,$Ha,$c[$wa&3^$wc]);$ua=int32($w[$wa]-$Eb);$w[$wa]=$ua;}$ya=$w[$ca];$Eb=xxtea_mx($ya,$ua,$Ha,$c[$wa&3^$wc]);$ua=int32($w[0]-$Eb);$w[0]=$ua;$Ha=int32($Ha-0x9E3779B9);}return
long2str($w,true);}$g='';if(!$ja){page_header(lang(52),lang(53,implode(", ",$Ja)),null);page_footer("auth");exit;}$L=$_SESSION["token"];if(!$_SESSION["token"]){$_SESSION["token"]=rand(1,1e6);}$Ab=array();if($_COOKIE["adminer_permanent"]){foreach(explode(" ",$_COOKIE["adminer_permanent"])as$b){list($c)=explode(":",$b);$Ab[$c]=$b;}}if(isset($_POST["server"])){session_regenerate_id();$_SESSION["pwds"][$_POST["driver"]][$_POST["server"]][$_POST["username"]]=$_POST["password"];if($_POST["permanent"]){$c=base64_encode($_POST["driver"])."-".base64_encode($_POST["server"])."-".base64_encode($_POST["username"]);$Ec=$r->permanentLogin();$Ab[$c]="$c:".base64_encode($Ec?encrypt_string($_POST["password"],$Ec):"");cookie("adminer_permanent",implode(" ",$Ab));}if(count($_POST)==($_POST["permanent"]?5:4)||DRIVER!=$_POST["driver"]||SERVER!=$_POST["server"]||$_GET["username"]!==$_POST["username"]){redirect(auth_url($_POST["driver"],$_POST["server"],$_POST["username"]));}}elseif($_POST["logout"]){if($L&&$_POST["token"]!=$L){page_header(lang(47),lang(54));page_footer("db");exit;}else{foreach(array("pwds","dbs","queries")as$c){set_session($c,null);}$c=base64_encode(DRIVER)."-".base64_encode(SERVER)."-".base64_encode($_GET["username"]);if($Ab[$c]){unset($Ab[$c]);cookie("adminer_permanent",implode(" ",$Ab));}redirect(substr(preg_replace('~(username|db|ns)=[^&]*&~','',ME),0,-1),lang(55));}}elseif($Ab&&!$_SESSION["pwds"]){session_regenerate_id();$Ec=$r->permanentLogin();foreach($Ab
as$c=>$b){list(,$uf)=explode(":",$b);list($Ib,$F,$Q)=array_map('base64_decode',explode("-",$c));$_SESSION["pwds"][$Ib][$F][$Q]=decrypt_string(base64_decode($uf),$Ec);}}function
auth_error($Re=null){global$g,$r,$L;$Uc=session_name();$n="";if(!$_COOKIE[$Uc]&&$_GET[$Uc]&&ini_bool("session.use_only_cookies")){$n=lang(56);}elseif(isset($_GET["username"])){if(($_COOKIE[$Uc]||$_GET[$Uc])&&!$L){$n=lang(57);}else{$S=&get_session("pwds");if(isset($S)){$n=h($Re?$Re->getMessage():(is_string($g)?$g:lang(58)));$S=null;}}}page_header(lang(21),$n,null);echo"<form action='' method='post'>\n";$r->loginForm();echo"<div>";hidden_fields($_POST,array("driver","server","username","password","permanent"));echo"</div>\n","</form>\n";page_footer("auth");}if(isset($_GET["username"])&&class_exists("Min_DB")){$g=connect();}if(is_string($g)||!$r->login($_GET["username"],get_session("pwds"))){auth_error();exit;}$L=$_SESSION["token"];if(isset($_POST["server"])&&$_POST["token"]){$_POST["token"]=$L;}$n=($_POST?($_POST["token"]==$L?"":lang(54)):($_SERVER["REQUEST_METHOD"]!="POST"?"":lang(59,'"post_max_size"')));function
connect_error(){global$g,$L,$n,$ja;$z=array();if(DB!=""){page_header(lang(60).": ".h(DB),lang(61),true);}else{if($_POST["db"]&&!$n){queries_redirect(substr(ME,0,-1),lang(62),drop_databases($_POST["db"]));}page_header(lang(63),$n,false);echo"<p><a href='".h(ME)."database='>".lang(64)."</a>\n";foreach(array('privileges'=>lang(65),'processlist'=>lang(66),'variables'=>lang(67),'status'=>lang(68),)as$c=>$b){if(support($c)){echo"<a href='".h(ME)."$c='>$b</a>\n";}}echo"<p>".lang(69,$ja[DRIVER],"<b>$g->server_info</b>","<b>$g->extension</b>")."\n","<p>".lang(70,"<b>".h(logged_user())."</b>")."\n";$z=get_databases();if($z){$Dd=support("scheme");$X=collations();echo"<form action='' method='post'>\n","<table cellspacing='0' onclick='tableClick(event);'>\n","<thead><tr><td><input type='hidden' name='token' value='$L'>&nbsp;<th>".lang(60)."<td>".lang(71)."<td>".lang(72)."</thead>\n";foreach($z
as$s){$td=h(ME)."db=".urlencode($s);echo"<tr".odd()."><td>".checkbox("db[]",$s,in_array($s,(array)$_POST["db"])),"<th><a href='$td'>".h($s)."</a>","<td><a href='$td".($Dd?"&amp;ns=":"")."&amp;database='>".nbsp(db_collation($s,$X))."</a>","<td align='right'><a href='$td&amp;schema=' id='tables-".h($s)."'>?</a>","\n";}echo"</table>\n","<p><input type='submit' name='drop' value='".lang(73)."' onclick=\"return confirm('".lang(74)." (' + formChecked(this, /db/) + ')');\">\n","</form>\n";}}page_footer("db");if($z){echo"<script type='text/javascript' src='".h(ME."script=connect&token=$L")."'></script>\n";}}if(isset($_GET["status"])){$_GET["variables"]=$_GET["status"];}if(!(DB!=""?$g->select_db(DB):isset($_GET["sql"])||isset($_GET["dump"])||isset($_GET["database"])||isset($_GET["processlist"])||isset($_GET["privileges"])||isset($_GET["user"])||isset($_GET["variables"])||$_GET["script"]=="connect")){if(DB!=""){set_session("dbs",null);}connect_error();exit;}if(support("scheme")&&DB!=""&&$_GET["ns"]!==""){if(!isset($_GET["ns"])){redirect(preg_replace('~ns=[^&]*&~','',ME)."ns=".get_schema());}if(!set_schema($_GET["ns"])){page_header(lang(75).": ".h($_GET["ns"]),lang(76),true);page_footer("ns");exit;}}function
select($i,$I=null){$La=array();$K=array();$B=array();$Qe=array();$T=array();odd('');for($l=0;$a=$i->fetch_row();$l++){if(!$l){echo"<table cellspacing='0' class='nowrap'>\n","<thead><tr>";for($ma=0;$ma<count($a);$ma++){$d=$i->fetch_field();$Fa=$d->orgtable;$vc=$d->orgname;if($Fa!=""){if(!isset($K[$Fa])){$K[$Fa]=array();foreach(indexes($Fa,$I)as$v){if($v["type"]=="PRIMARY"){$K[$Fa]=array_flip($v["columns"]);break;}}$B[$Fa]=$K[$Fa];}if(isset($B[$Fa][$vc])){unset($B[$Fa][$vc]);$K[$Fa][$vc]=$ma;$La[$ma]=$Fa;}}if($d->charsetnr==63){$Qe[$ma]=true;}$T[$ma]=$d->type;echo"<th".($Fa!=""||$d->name!=$vc?" title='".h(($Fa!=""?"$Fa.":"").$vc)."'":"").">".h($d->name);}echo"</thead>\n";}echo"<tr".odd().">";foreach($a
as$c=>$b){if(!isset($b)){$b="<i>NULL</i>";}else{if($Qe[$c]&&!is_utf8($b)){$b="<i>".lang(31,strlen($b))."</i>";}elseif(!strlen($b)){$b="&nbsp;";}else{$b=h($b);if($T[$c]==254){$b="<code>$b</code>";}}if(isset($La[$c])&&!$B[$La[$c]]){$x="edit=".urlencode($La[$c]);foreach($K[$La[$c]]as$Bc=>$ma){$x.="&where".urlencode("[".bracket_escape($Bc)."]")."=".urlencode($a[$ma]);}$b="<a href='".h(ME.$x)."'>$b</a>";}}echo"<td>$b";}}echo($l?"</table>":"<p class='message'>".lang(77))."\n";}function
referencable_primary($yf){$e=array();foreach(table_status()as$Ca=>$h){if($Ca!=$yf&&fk_support($h)){foreach(fields($Ca)as$d){if($d["primary"]){if($e[$Ca]){unset($e[$Ca]);break;}$e[$Ca]=$d;}}}}return$e;}function
textarea($f,$p,$E=10,$ib=80){echo"<textarea name='$f' rows='$E' cols='$ib' style='width: 98%;' spellcheck='false' onkeydown='return textareaKeydown(this, event, true);'>".h($p)."</textarea>";}function
edit_type($c,$d,$X,$ea=array()){global$Sa,$T,$sb,$db;echo'<td><select name="',$c,'[type]" class="type" onfocus="lastType = selectValue(this);" onchange="editingTypeChange(this);">',optionlist((!$d["type"]||isset($T[$d["type"]])?array():array($d["type"]))+$Sa+($ea?array(lang(78)=>$ea):array()),$d["type"]),'</select>
<td><input name="',$c,'[length]" value="',h($d["length"]),'" size="3" onfocus="editingLengthFocus(this);"><td>',"<select name='$c"."[collation]'".(ereg('(char|text|enum|set)$',$d["type"])?"":" class='hidden'").'><option value="">('.lang(79).')'.optionlist($X,$d["collation"]).'</select>',($sb?"<select name='$c"."[unsigned]'".(!$d["type"]||ereg('(int|float|double|decimal)$',$d["type"])?"":" class='hidden'").'><option>'.optionlist($sb,$d["unsigned"]).'</select>':''),($ea?"<select name='$c"."[on_delete]'".(ereg("`",$d["type"])?"":" class='hidden'")."><option value=''>(".lang(80).")".optionlist($db,$d["on_delete"])."</select> ":" ");}function
process_length($da){global$Hb;return(preg_match("~^\\s*(?:$Hb)(?:\\s*,\\s*(?:$Hb))*\\s*\$~",$da)&&preg_match_all("~$Hb~",$da,$ka)?implode(",",$ka[0]):preg_replace('~[^0-9,+-]~','',$da));}function
process_type($d,$rc="COLLATE"){global$sb;return" $d[type]".($d["length"]!=""?"(".process_length($d["length"]).")":"").(ereg('int|float|double|decimal',$d["type"])&&in_array($d["unsigned"],$sb)?" $d[unsigned]":"").(ereg('char|text|enum|set',$d["type"])&&$d["collation"]?" $rc ".q($d["collation"]):"");}function
process_field($d,$Hc){return
array(idf_escape($d["field"]),process_type($Hc),($d["null"]?" NULL":" NOT NULL"),(isset($d["default"])?" DEFAULT ".($d["type"]=="timestamp"&&eregi("^CURRENT_TIMESTAMP$",$d["default"])?$d["default"]:q($d["default"])):""),($d["on_update"]?" ON UPDATE $d[on_update]":""),(support("comment")&&$d["comment"]!=""?" COMMENT ".q($d["comment"]):""),($d["auto_increment"]?auto_increment():null),);}function
type_class($y){foreach(array('char'=>'text','date'=>'time|year','binary'=>'blob','enum'=>'set',)as$c=>$b){if(ereg("$c|$b",$y)){return" class='$c'";}}}function
edit_fields($o,$X,$y="TABLE",$Xe=0,$ea=array(),$Jb=false){global$_c;foreach($o
as$d){if($d["comment"]!=""){$Jb=true;break;}}echo'<thead><tr class="wrap">
';if($y=="PROCEDURE"){echo'<td>&nbsp;';}echo'<th>',($y=="TABLE"?lang(81):lang(82)),'<td>',lang(83),'<textarea id="enum-edit" rows="4" cols="12" wrap="off" style="display: none;" onblur="editingLengthBlur(this);"></textarea>
<td>',lang(84),'<td>',lang(85);if($y=="TABLE"){echo'<td>NULL
<td><input type="radio" name="auto_increment_col" value=""><acronym title="',lang(86),'">AI</acronym>
<td class="hidden">',lang(87),(support("comment")?"<td".($Jb?"":" class='hidden'").">".lang(88):"");}echo'<td>',"<input type='image' name='add[".(support("move_col")?0:count($o))."]' src='".h(preg_replace("~\\?.*~","",$_SERVER["REQUEST_URI"]))."?file=plus.gif&amp;version=3.0.1' alt='+' title='".lang(89)."'>",'<script type="text/javascript">row_count = ',count($o),';</script>
</thead>
';foreach($o
as$l=>$d){$l++;$Ed=$d[($_POST?"orig":"field")];$ee=(isset($_POST["add"][$l-1])||(isset($d["field"])&&!$_POST["drop_col"][$l]))&&(support("drop_col")||$Ed=="");echo'<tr',($ee?"":" style='display: none;'"),'>
',($y=="PROCEDURE"?"<td>".html_select("fields[$l][inout]",$_c,$d["inout"]):""),'<th>';if($ee){echo'<input name="fields[',$l,'][field]" value="',h($d["field"]),'" onchange="',($d["field"]!=""||count($o)>1?"":"editingAddRow(this, $Xe); "),'editingNameChange(this);" maxlength="64">';}echo'<input type="hidden" name="fields[',$l,'][orig]" value="',h($Ed),'">
';edit_type("fields[$l]",$d,$X,$ea);if($y=="TABLE"){echo'<td>',checkbox("fields[$l][null]",1,$d["null"]),'<td><input type="radio" name="auto_increment_col" value="',$l,'"';if($d["auto_increment"]){echo' checked';}?> onclick="var field = this.form['fields[' + this.value + '][field]']; if (!field.value) { field.value = 'id'; field.onchange(); }">
<td class="hidden"><?php echo
checkbox("fields[$l][has_default]",1,$d["has_default"]),'<input name="fields[',$l,'][default]" value="',h($d["default"]),'" onchange="this.previousSibling.checked = true;">
',(support("comment")?"<td".($Jb?"":" class='hidden'")."><input name='fields[$l][comment]' value='".h($d["comment"])."' maxlength='255'>":"");}echo"<td>",(support("move_col")?"<input type='image' name='add[$l]' src='".h(preg_replace("~\\?.*~","",$_SERVER["REQUEST_URI"]))."?file=plus.gif&amp;version=3.0.1' alt='+' title='".lang(89)."' onclick='return !editingAddRow(this, $Xe, 1);'>&nbsp;"."<input type='image' name='up[$l]' src='".h(preg_replace("~\\?.*~","",$_SERVER["REQUEST_URI"]))."?file=up.gif&amp;version=3.0.1' alt='^' title='".lang(90)."'>&nbsp;"."<input type='image' name='down[$l]' src='".h(preg_replace("~\\?.*~","",$_SERVER["REQUEST_URI"]))."?file=down.gif&amp;version=3.0.1' alt='v' title='".lang(91)."'>&nbsp;":""),($Ed==""||support("drop_col")?"<input type='image' name='drop_col[$l]' src='".h(preg_replace("~\\?.*~","",$_SERVER["REQUEST_URI"]))."?file=cross.gif&amp;version=3.0.1' alt='x' title='".lang(92)."' onclick='return !editingRemoveRow(this);'>":""),"\n";}return$Jb;}function
process_fields(&$o){ksort($o);$O=0;if($_POST["up"]){$wb=0;foreach($o
as$c=>$d){if(key($_POST["up"])==$c){unset($o[$c]);array_splice($o,$wb,0,array($d));break;}if(isset($d["field"])){$wb=$O;}$O++;}}if($_POST["down"]){$qa=false;foreach($o
as$c=>$d){if(isset($d["field"])&&$qa){unset($o[key($_POST["down"])]);array_splice($o,$O,0,array($qa));break;}if(key($_POST["down"])==$c){$qa=$d;}$O++;}}$o=array_values($o);if($_POST["add"]){array_splice($o,key($_POST["add"]),0,array(array()));}}function
normalize_enum($k){return"'".str_replace("'","''",addcslashes(stripcslashes(str_replace($k[0][0].$k[0][0],$k[0][0],substr($k[0],1,-1))),'\\'))."'";}function
grant($fa,$pa,$B,$_b){if(!$pa){return
true;}if($pa==array("ALL PRIVILEGES","GRANT OPTION")){return($fa=="GRANT"?queries("$fa ALL PRIVILEGES$_b WITH GRANT OPTION"):queries("$fa ALL PRIVILEGES$_b")&&queries("$fa GRANT OPTION$_b"));}return
queries("$fa ".preg_replace('~(GRANT OPTION)\\([^)]*\\)~','\\1',implode("$B, ",$pa).$B).$_b);}function
drop_create($Da,$ga,$la,$Ue,$zf,$Bf,$f){if($_POST["drop"]){return
query_redirect($Da,$la,$Ue,true,!$_POST["dropped"]);}$Ya=$f!=""&&($_POST["dropped"]||queries($Da));$Af=queries($ga);if(!queries_redirect($la,($f!=""?$zf:$Bf),$Af)&&$Ya){restart_session();$_SESSION["messages"][]=$Ue;}return$Ya;}function
tar_file($Z,$rd){$e=pack("a100a8a8a8a12a12",$Z,644,0,0,decoct(strlen($rd)),decoct(time()));$Me=8*32;for($l=0;$l<strlen($e);$l++){$Me+=ord($e{$l});}$e.=sprintf("%06o",$Me)."\0 ";return$e.str_repeat("\0",512-strlen($e)).$rd.str_repeat("\0",511-(strlen($rd)+511)%
512);}function
dump_table($h,$V,$Pc=false){if($_POST["format"]!="sql"){echo"\xef\xbb\xbf";if($V){dump_csv(array_keys(fields($h)));}}elseif($V){$ga=create_sql($h,$_POST["auto_increment"]);if($ga){if($V=="DROP+CREATE"){echo"DROP ".($Pc?"VIEW":"TABLE")." IF EXISTS ".table($h).";\n";}if($Pc){$ga=preg_replace('~^([A-Z =]+) DEFINER=`'.str_replace("@","`@`",logged_user()).'`~','\\1',$ga);}echo($V!="CREATE+ALTER"?$ga:($Pc?substr_replace($ga," OR REPLACE",6,0):substr_replace($ga," IF NOT EXISTS",12,0))).";\n\n";}if($V=="CREATE+ALTER"&&!$Pc){$j="SELECT COLUMN_NAME, COLUMN_DEFAULT, IS_NULLABLE, COLLATION_NAME, COLUMN_TYPE, EXTRA, COLUMN_COMMENT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ".q($h)." ORDER BY ORDINAL_POSITION";echo"DELIMITER ;;
CREATE PROCEDURE adminer_alter (INOUT alter_command text) BEGIN
	DECLARE _column_name, _collation_name, after varchar(64) DEFAULT '';
	DECLARE _column_type, _column_default text;
	DECLARE _is_nullable char(3);
	DECLARE _extra varchar(30);
	DECLARE _column_comment varchar(255);
	DECLARE done, set_after bool DEFAULT 0;
	DECLARE add_columns text DEFAULT '";$o=array();$Kb="";foreach(get_rows($j)as$a){$va=$a["COLUMN_DEFAULT"];$a["default"]=(isset($va)?q($va):"NULL");$a["after"]=q($Kb);$a["alter"]=escape_string(idf_escape($a["COLUMN_NAME"])." $a[COLUMN_TYPE]".($a["COLLATION_NAME"]?" COLLATE $a[COLLATION_NAME]":"").(isset($va)?" DEFAULT ".($va=="CURRENT_TIMESTAMP"?$va:$a["default"]):"").($a["IS_NULLABLE"]=="YES"?"":" NOT NULL").($a["EXTRA"]?" $a[EXTRA]":"").($a["COLUMN_COMMENT"]?" COMMENT ".q($a["COLUMN_COMMENT"]):"").($Kb?" AFTER ".idf_escape($Kb):" FIRST"));echo", ADD $a[alter]";$o[]=$a;$Kb=$a["COLUMN_NAME"];}echo"';
	DECLARE columns CURSOR FOR $j;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
	SET @alter_table = '';
	OPEN columns;
	REPEAT
		FETCH columns INTO _column_name, _column_default, _is_nullable, _collation_name, _column_type, _extra, _column_comment;
		IF NOT done THEN
			SET set_after = 1;
			CASE _column_name";foreach($o
as$a){echo"
				WHEN ".q($a["COLUMN_NAME"])." THEN
					SET add_columns = REPLACE(add_columns, ', ADD $a[alter]', '');
					IF NOT (_column_default <=> $a[default]) OR _is_nullable != '$a[IS_NULLABLE]' OR _collation_name != '$a[COLLATION_NAME]' OR _column_type != ".q($a["COLUMN_TYPE"])." OR _extra != '$a[EXTRA]' OR _column_comment != ".q($a["COLUMN_COMMENT"])." OR after != $a[after] THEN
						SET @alter_table = CONCAT(@alter_table, ', MODIFY $a[alter]');
					END IF;";}echo"
				ELSE
					SET @alter_table = CONCAT(@alter_table, ', DROP ', _column_name);
					SET set_after = 0;
			END CASE;
			IF set_after THEN
				SET after = _column_name;
			END IF;
		END IF;
	UNTIL done END REPEAT;
	CLOSE columns;
	IF @alter_table != '' OR add_columns != '' THEN
		SET alter_command = CONCAT(alter_command, 'ALTER TABLE ".table($h)."', SUBSTR(CONCAT(add_columns, @alter_table), 2), ';\\n');
	END IF;
END;;
DELIMITER ;
CALL adminer_alter(@adminer_alter);
DROP PROCEDURE adminer_alter;

";}}}function
dump_data($h,$V,$C=""){global$g,$_;$Fe=($_=="sqlite"?0:1048576);if($V){if($_POST["format"]=="sql"&&$V=="TRUNCATE+INSERT"){echo
truncate_sql($h).";\n";}$o=fields($h);$i=$g->query(($C?$C:"SELECT * FROM ".table($h)),1);if($i){$tc="";$kb="";while($a=$i->fetch_assoc()){if($_POST["format"]!="sql"){dump_csv($a);}else{if(!$tc){$tc="INSERT INTO ".table($h)." (".implode(", ",array_map('idf_escape',array_keys($a))).") VALUES";}foreach($a
as$c=>$b){$a[$c]=(isset($b)?(ereg('int|float|double|decimal',$o[$c]["type"])?$b:q($b)):"NULL");}$ia=implode(",\t",$a);if($V=="INSERT+UPDATE"){$q=array();foreach($a
as$c=>$b){$q[]=idf_escape($c)." = $b";}echo"$tc ($ia) ON DUPLICATE KEY UPDATE ".implode(", ",$q).";\n";}else{$ia=($Fe?"\n":" ")."($ia)";if(!$kb){$kb=$tc.$ia;}elseif(strlen($kb)+2+strlen($ia)<$Fe){$kb.=",$ia";}else{$kb.=";\n";echo$kb;$kb=$tc.$ia;}}}}if($_POST["format"]=="sql"&&$V!="INSERT+UPDATE"&&$kb){$kb.=";\n";echo$kb;}}}}function
dump_headers($Ee,$_f=false){$Z=($Ee!=""?friendly_url($Ee):"adminer");$Gb=$_POST["output"];$Db=($_POST["format"]=="sql"?"sql":($_f?"tar":"csv"));header("Content-Type: ".($Gb=="bz2"?"application/x-bzip":($Gb=="gz"?"application/x-gzip":($Db=="tar"?"application/x-tar":($Db=="sql"||$Gb!="file"?"text/plain":"text/csv")."; charset=utf-8"))));if($Gb!="text"){header("Content-Disposition: attachment; filename=$Z.$Db".($Gb!="file"&&!ereg('[^0-9a-z]',$Gb)?".$Gb":""));}session_write_close();if($_POST["output"]=="bz2"){ob_start('bzcompress',1e6);}if($_POST["output"]=="gz"){ob_start('gzencode',1e6);}return$Db;}session_cache_limiter("");if(!ini_bool("session.use_cookies")||@ini_set("session.use_cookies",false)!==false){session_write_close();}$db=array("RESTRICT","CASCADE","SET NULL","NO ACTION");$cb=" onclick=\"return confirm('".lang(74)."');\"";$Hb='\'(?:\'\'|[^\'\\\\]|\\\\.)*\'|"(?:""|[^"\\\\]|\\\\.)*"';$_c=array("IN","OUT","INOUT");if(isset($_GET["select"])&&($_POST["edit"]||$_POST["clone"])&&!$_POST["save"]){$_GET["edit"]=$_GET["select"];}if(isset($_GET["callf"])){$_GET["call"]=$_GET["callf"];}if(isset($_GET["function"])){$_GET["procedure"]=$_GET["function"];}if(isset($_GET["download"])){$m=$_GET["download"];header("Content-Type: application/octet-stream");header("Content-Disposition: attachment; filename=".friendly_url("$m-".implode("_",$_GET["where"])).".".friendly_url($_GET["field"]));echo$g->result("SELECT".limit(idf_escape($_GET["field"])." FROM ".table($m)," WHERE ".where($_GET),1));exit;}elseif(isset($_GET["table"])){$m=$_GET["table"];$o=fields($m);if(!$o){$n=error();}$J=($o?table_status($m):array());page_header(($o&&is_view($J)?lang(93):lang(94)).": ".h($m),$n);$r->selectLinks($J);$Ba=$J["Comment"];if($Ba!=""){echo"<p>".lang(88).": ".h($Ba)."\n";}if($o){echo"<table cellspacing='0'>\n","<thead><tr><th>".lang(95)."<td>".lang(83).(support("comment")?"<td>".lang(88):"")."</thead>\n";foreach($o
as$d){echo"<tr".odd()."><th>".h($d["field"]),"<td>".h($d["full_type"]).($d["null"]?" <i>NULL</i>":"").($d["auto_increment"]?" <i>".lang(86)."</i>":""),(support("comment")?"<td>".nbsp($d["comment"]):""),"\n";}echo"</table>\n";if(!is_view($J)){echo"<h3>".lang(96)."</h3>\n";$K=indexes($m);if($K){echo"<table cellspacing='0'>\n";foreach($K
as$f=>$v){ksort($v["columns"]);$sc=array();foreach($v["columns"]as$c=>$b){$sc[]="<i>".h($b)."</i>".($v["lengths"][$c]?"(".$v["lengths"][$c].")":"");}echo"<tr title='".h($f)."'><th>$v[type]<td>".implode(", ",$sc)."\n";}echo"</table>\n";}echo'<p><a href="'.h(ME).'indexes='.urlencode($m).'">'.lang(97)."</a>\n";if(fk_support($J)){echo"<h3>".lang(78)."</h3>\n";$ea=foreign_keys($m);if($ea){echo"<table cellspacing='0'>\n","<thead><tr><th>".lang(98)."<td>".lang(99)."<td>".lang(80)."<td>".lang(100).($_!="sqlite"?"<td>&nbsp;":"")."</thead>\n";foreach($ea
as$f=>$A){$x=($A["db"]!=""?"<b>".h($A["db"])."</b>.":"").h($A["table"]);echo"<tr>","<th><i>".implode("</i>, <i>",array_map('h',$A["source"]))."</i>","<td><a href='".h($A["db"]!=""?preg_replace('~db=[^&]*~',"db=".urlencode($A["db"]),ME):ME)."table=".urlencode($A["table"])."'>$x</a>","(<i>".implode("</i>, <i>",array_map('h',$A["target"]))."</i>)","<td>$A[on_delete]\n","<td>$A[on_update]\n";if($_!="sqlite"){echo'<td><a href="'.h(ME.'foreign='.urlencode($m).'&name='.urlencode($f)).'">'.lang(101).'</a>';}}echo"</table>\n";}if($_!="sqlite"){echo'<p><a href="'.h(ME).'foreign='.urlencode($m).'">'.lang(102)."</a>\n";}}if(support("trigger")){echo"<h3>".lang(103)."</h3>\n";$uc=triggers($m);if($uc){echo"<table cellspacing='0'>\n";foreach($uc
as$c=>$b){echo"<tr valign='top'><td>$b[0]<td>$b[1]<th>".h($c)."<td><a href='".h(ME.'trigger='.urlencode($m).'&name='.urlencode($c))."'>".lang(101)."</a>\n";}echo"</table>\n";}echo'<p><a href="'.h(ME).'trigger='.urlencode($m).'">'.lang(104)."</a>\n";}}}}elseif(isset($_GET["schema"])){page_header(lang(105),"",array(),DB);$qb=array();$Ce=array();preg_match_all('~([^:]+):([-0-9.]+)x([-0-9.]+)(_|$)~',$_COOKIE["adminer_schema"],$ka,PREG_SET_ORDER);foreach($ka
as$l=>$k){$qb[$k[1]]=array($k[2],$k[3]);$Ce[]="\n\t'".addcslashes($k[1],"\r\n'\\/")."': [ $k[2], $k[3] ]";}$cc=0;$Ge=-1;$Ka=array();$He=array();$Le=array();foreach(table_status()as$a){if(!isset($a["Engine"])){continue;}$yc=0;$Ka[$a["Name"]]["fields"]=array();foreach(fields($a["Name"])as$f=>$d){$yc+=1.25;$d["pos"]=$yc;$Ka[$a["Name"]]["fields"][$f]=$d;}$Ka[$a["Name"]]["pos"]=($qb[$a["Name"]]?$qb[$a["Name"]]:array($cc,0));if(fk_support($a)){foreach(foreign_keys($a["Name"])as$b){if(!$b["db"]){$xa=$Ge;if($qb[$a["Name"]][1]||$qb[$b["table"]][1]){$xa=min(floatval($qb[$a["Name"]][1]),floatval($qb[$b["table"]][1]))-1;}else{$Ge-=.1;}while($Le[(string)$xa]){$xa-=.0001;}$Ka[$a["Name"]]["references"][$b["table"]][(string)$xa]=array($b["source"],$b["target"]);$He[$b["table"]][$a["Name"]][(string)$xa]=$b["target"];$Le[(string)$xa]=true;}}}$cc=max($cc,$Ka[$a["Name"]]["pos"][0]+2.5+$yc);}echo'<div id="schema" style="height: ',$cc,'em;">
<script type="text/javascript">
tablePos = {',implode(",",$Ce)."\n",'};
em = document.getElementById(\'schema\').offsetHeight / ',$cc,';
document.onmousemove = schemaMousemove;
document.onmouseup = schemaMouseup;
</script>
';foreach($Ka
as$f=>$h){echo"<div class='table' style='top: ".$h["pos"][0]."em; left: ".$h["pos"][1]."em;' onmousedown='schemaMousedown(this, event);'>",'<a href="'.h(ME).'table='.urlencode($f).'"><b>'.h($f)."</b></a><br>\n";foreach($h["fields"]as$d){$b='<span'.type_class($d["type"]).' title="'.h($d["full_type"].($d["null"]?" NULL":'')).'">'.h($d["field"]).'</span>';echo($d["primary"]?"<i>$b</i>":$b)."<br>\n";}foreach((array)$h["references"]as$ec=>$lc){foreach($lc
as$xa=>$Gc){$jc=$xa-$qb[$f][1];$l=0;foreach($Gc[0]as$Ga){echo"<div class='references' title='".h($ec)."' id='refs$xa-".($l++)."' style='left: $jc"."em; top: ".$h["fields"][$Ga]["pos"]."em; padding-top: .5em;'><div style='border-top: 1px solid Gray; width: ".(-$jc)."em;'></div></div>\n";}}}foreach((array)$He[$f]as$ec=>$lc){foreach($lc
as$xa=>$B){$jc=$xa-$qb[$f][1];$l=0;foreach($B
as$ta){echo"<div class='references' title='".h($ec)."' id='refd$xa-".($l++)."' style='left: $jc"."em; top: ".$h["fields"][$ta]["pos"]."em; height: 1.25em; background: url(".h(preg_replace("~\\?.*~","",$_SERVER["REQUEST_URI"]))."?file=arrow.gif) no-repeat right center;&amp;version=3.0.1'><div style='height: .5em; border-bottom: 1px solid Gray; width: ".(-$jc)."em;'></div></div>\n";}}}echo"</div>\n";}foreach($Ka
as$f=>$h){foreach((array)$h["references"]as$ec=>$lc){foreach($lc
as$xa=>$Gc){$Tc=$cc;$Bd=-10;foreach($Gc[0]as$c=>$Ga){$Je=$h["pos"][0]+$h["fields"][$Ga]["pos"];$Ie=$Ka[$ec]["pos"][0]+$Ka[$ec]["fields"][$Gc[1][$c]]["pos"];$Tc=min($Tc,$Je,$Ie);$Bd=max($Bd,$Je,$Ie);}echo"<div class='references' id='refl$xa' style='left: $xa"."em; top: $Tc"."em; padding: .5em 0;'><div style='border-right: 1px solid Gray; margin-top: 1px; height: ".($Bd-$Tc)."em;'></div></div>\n";}}}echo'</div>
';}elseif(isset($_GET["dump"])){$m=$_GET["dump"];if($_POST){$Ye="";foreach(array("output","format","db_style","table_style","data_style")as$c){$Ye.="&$c=".urlencode($_POST[$c]);}cookie("adminer_export",substr($Ye,1));$Db=dump_headers(($m!=""?$m:DB),(DB==""||count((array)$_POST["tables"]+(array)$_POST["data"])>1));if($_POST["format"]=="sql"){echo"-- Adminer $Yc ".$ja[DRIVER]." dump

".($_!="sql"?"":"SET NAMES utf8;
SET foreign_key_checks = 0;
SET time_zone = ".q($g->result("SELECT @@time_zone")).";
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

");}$V=$_POST["db_style"];$z=array(DB);if(DB==""){$z=$_POST["databases"];if(is_string($z)){$z=explode("\n",rtrim(str_replace("\r","",$z),"\n"));}}foreach((array)$z
as$s){if($g->select_db($s)){if($_POST["format"]=="sql"&&ereg('CREATE',$V)&&($ga=$g->result("SHOW CREATE DATABASE ".idf_escape($s),1))){if($V=="DROP+CREATE"){echo"DROP DATABASE IF EXISTS ".idf_escape($s).";\n";}echo($V=="CREATE+ALTER"?preg_replace('~^CREATE DATABASE ~','\\0IF NOT EXISTS ',$ga):$ga).";\n";}if($_POST["format"]=="sql"){if($V){echo
use_sql($s).";\n\n";}if(in_array("CREATE+ALTER",array($V,$_POST["table_style"]))){echo"SET @adminer_alter = '';\n\n";}$lb="";if($_POST["routines"]){foreach(array("FUNCTION","PROCEDURE")as$Ra){foreach(get_rows("SHOW $Ra STATUS WHERE Db = ".q($s),null,"-- ")as$a){$lb.=($V!='DROP+CREATE'?"DROP $Ra IF EXISTS ".idf_escape($a["Name"]).";;\n":"").$g->result("SHOW CREATE $Ra ".idf_escape($a["Name"]),2).";;\n\n";}}}if($_POST["events"]){foreach(get_rows("SHOW EVENTS",null,"-- ")as$a){$lb.=($V!='DROP+CREATE'?"DROP EVENT IF EXISTS ".idf_escape($a["Name"]).";;\n":"").$g->result("SHOW CREATE EVENT ".idf_escape($a["Name"]),3).";;\n\n";}}if($lb){echo"DELIMITER ;;\n\n$lb"."DELIMITER ;\n\n";}}if($_POST["table_style"]||$_POST["data_style"]){$Y=array();foreach(table_status()as$a){$h=(DB==""||in_array($a["Name"],(array)$_POST["tables"]));$Ld=(DB==""||in_array($a["Name"],(array)$_POST["data"]));if($h||$Ld){if(!is_view($a)){if($Db=="tar"){ob_start();}dump_table($a["Name"],($h?$_POST["table_style"]:""));if($Ld){dump_data($a["Name"],$_POST["data_style"]);}if($_POST["format"]=="sql"&&$_POST["triggers"]){$uc=trigger_sql($a["Name"],$_POST["table_style"]);if($uc){echo"\nDELIMITER ;;\n$uc\nDELIMITER ;\n";}}if($Db=="tar"){echo
tar_file((DB!=""?"":"$s/")."$a[Name].csv",ob_get_clean());}elseif($_POST["format"]=="sql"){echo"\n";}}elseif($_POST["format"]=="sql"){$Y[]=$a["Name"];}}}foreach($Y
as$Ac){dump_table($Ac,$_POST["table_style"],true);}if($Db=="tar"){echo
pack("x512");}}if($V=="CREATE+ALTER"&&$_POST["format"]=="sql"){$j="SELECT TABLE_NAME, ENGINE, TABLE_COLLATION, TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()";echo"DELIMITER ;;
CREATE PROCEDURE adminer_alter (INOUT alter_command text) BEGIN
	DECLARE _table_name, _engine, _table_collation varchar(64);
	DECLARE _table_comment varchar(64);
	DECLARE done bool DEFAULT 0;
	DECLARE tables CURSOR FOR $j;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
	OPEN tables;
	REPEAT
		FETCH tables INTO _table_name, _engine, _table_collation, _table_comment;
		IF NOT done THEN
			CASE _table_name";foreach(get_rows($j)as$a){$Ba=q($a["ENGINE"]=="InnoDB"?preg_replace('~(?:(.+); )?InnoDB free: .*~','\\1',$a["TABLE_COMMENT"]):$a["TABLE_COMMENT"]);echo"
				WHEN ".q($a["TABLE_NAME"])." THEN
					".(isset($a["ENGINE"])?"IF _engine != '$a[ENGINE]' OR _table_collation != '$a[TABLE_COLLATION]' OR _table_comment != $Ba THEN
						ALTER TABLE ".idf_escape($a["TABLE_NAME"])." ENGINE=$a[ENGINE] COLLATE=$a[TABLE_COLLATION] COMMENT=$Ba;
					END IF":"BEGIN END").";";}echo"
				ELSE
					SET alter_command = CONCAT(alter_command, 'DROP TABLE `', REPLACE(_table_name, '`', '``'), '`;\\n');
			END CASE;
		END IF;
	UNTIL done END REPEAT;
	CLOSE tables;
END;;
DELIMITER ;
CALL adminer_alter(@adminer_alter);
DROP PROCEDURE adminer_alter;
";}if(in_array("CREATE+ALTER",array($V,$_POST["table_style"]))&&$_POST["format"]=="sql"){echo"SELECT @adminer_alter;\n";}}}exit;}page_header(lang(106),"",($_GET["export"]!=""?array("table"=>$_GET["export"]):array()),DB);echo'
<form action="" method="post">
<table cellspacing="0">
';$Nd=array('','USE','DROP+CREATE','CREATE');$Od=array('','DROP+CREATE','CREATE');$Pd=array('','TRUNCATE+INSERT','INSERT');if($_=="sql"){$Nd[]='CREATE+ALTER';$Od[]='CREATE+ALTER';$Pd[]='INSERT+UPDATE';}parse_str($_COOKIE["adminer_export"],$a);if(!$a){$a=array("output"=>"text","format"=>"sql","db_style"=>(DB!=""?"":"CREATE"),"table_style"=>"DROP+CREATE","data_style"=>"INSERT");}$Ea=($_GET["dump"]=="");echo"<tr><th>".lang(107)."<td>".$r->dumpOutput(0,$a["output"])."\n","<tr><th>".lang(108)."<td>".$r->dumpFormat(0,$a["format"])."\n",($_=="sqlite"?"":"<tr><th>".lang(60)."<td>".html_select('db_style',$Nd,$a["db_style"]).(support("routine")?checkbox("routines",1,$Ea,lang(109)):"").(support("event")?checkbox("events",1,$Ea,lang(110)):"")),"<tr><th>".lang(72)."<td>".html_select('table_style',$Od,$a["table_style"]).checkbox("auto_increment",1,$a["table_style"],lang(86)).(support("trigger")?checkbox("triggers",1,$a["table_style"],lang(103)):""),"<tr><th>".lang(111)."<td>".html_select('data_style',$Pd,$a["data_style"]),'</table>
<p><input type="submit" value="',lang(106),'">

<table cellspacing="0">
';$vd=array();if(DB!=""){$Ea=($m!=""?"":" checked");echo"<thead><tr>","<th style='text-align: left;'><label><input type='checkbox' id='check-tables'$Ea onclick='formCheck(this, /^tables\\[/);'>".lang(72)."</label>","<th style='text-align: right;'><label>".lang(111)."<input type='checkbox' id='check-data'$Ea onclick='formCheck(this, /^data\\[/);'></label>","</thead>\n";$Y="";foreach(table_status()as$a){$f=$a["Name"];$pc=ereg_replace("_.*","",$f);$Ea=($m==""||$m==(substr($m,-1)=="%"?"$pc%":$f));$sc="<tr><td>".checkbox("tables[]",$f,$Ea,$f,"formUncheck('check-tables');");if(is_view($a)){$Y.="$sc\n";}else{echo"$sc<td align='right'><label>".($a["Engine"]=="InnoDB"&&$a["Rows"]?"~ ":"").$a["Rows"].checkbox("data[]",$f,$Ea,"","formUncheck('check-data');")."</label>\n";}$vd[$pc]++;}echo$Y;}else{echo"<thead><tr><th style='text-align: left;'><label><input type='checkbox' id='check-databases'".($m==""?" checked":"")." onclick='formCheck(this, /^databases\\[/);'>".lang(60)."</label></thead>\n";$z=get_databases();if($z){foreach($z
as$s){if(!information_schema($s)){$pc=ereg_replace("_.*","",$s);echo"<tr><td>".checkbox("databases[]",$s,$m==""||$m=="$pc%",$s,"formUncheck('check-databases');")."</label>\n";$vd[$pc]++;}}}else{echo"<tr><td><textarea name='databases' rows='10' cols='20' onkeydown='return textareaKeydown(this, event);'></textarea>";}}echo'</table>
</form>
';$fb=true;foreach($vd
as$c=>$b){if($c!=""&&$b>1){echo($fb?"<p>":" ")."<a href='".h(ME)."dump=".urlencode("$c%")."'>".h($c)."</a>";$fb=false;}}}elseif(isset($_GET["privileges"])){page_header(lang(65));$i=$g->query("SELECT User, Host FROM mysql.user ORDER BY Host, User");if(!$i){echo'<form action=""><p>
';hidden_fields_get();echo
lang(19),': <input name="user">
',lang(18),': <input name="host" value="localhost">
<input type="hidden" name="grant" value="">
<input type="submit" value="',lang(30),'">
</form>
';$i=$g->query("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', 1) AS User, SUBSTRING_INDEX(CURRENT_USER, '@', -1) AS Host");}echo"<table cellspacing='0'>\n","<thead><tr><th>&nbsp;<th>".lang(19)."<th>".lang(18)."</thead>\n";while($a=$i->fetch_assoc()){echo'<tr'.odd().'><td><a href="'.h(ME.'user='.urlencode($a["User"]).'&host='.urlencode($a["Host"])).'">'.lang(112).'</a><td>'.h($a["User"])."<td>".h($a["Host"])."\n";}echo"</table>\n",'<p><a href="'.h(ME).'user=">'.lang(113)."</a>";}elseif(isset($_GET["sql"])){restart_session();$sf=&get_session("queries");$gb=&$sf[DB];if(!$n&&$_POST["clear"]){$gb=array();redirect(remove_from_uri("history"));}page_header(lang(42),$n);if(!$n&&$_POST){$Ma=false;$j=$_POST["query"];if($_POST["webfile"]){$Ma=@fopen((file_exists("adminer.sql")?"adminer.sql":(file_exists("adminer.sql.gz")?"compress.zlib://adminer.sql.gz":"compress.bzip2://adminer.sql.bz2")),"rb");$j=($Ma?fread($Ma,1e6):false);}elseif($_FILES["sql_file"]["error"]!=4){$j=get_file("sql_file",true);}if(is_string($j)){if(function_exists('memory_get_usage')){@ini_set("memory_limit",2*strlen($j)+memory_get_usage()+8e6);}if($j!=""&&strlen($j)<1e6&&(!$gb||end($gb)!=$j)){$gb[]=$j;}$Kc="(\\s|/\\*.*\\*/|(#|-- )[^\n]*\n|--\n)";if(!ini_bool("session.use_cookies")){session_write_close();}$pd=";";$O=0;$Zd=true;$I=connect();if(is_object($I)&&DB!=""){$I->select_db(DB);}$ac=0;$jd="";while($j!=""){if(!$O&&preg_match('~^\\s*DELIMITER\\s+(.+)~i',$j,$k)){$pd=$k[1];$j=substr($j,strlen($k[0]));}else{preg_match('('.preg_quote($pd).'|[\'`"]|/\\*|-- |#|$)',$j,$k,PREG_OFFSET_CAPTURE,$O);$qa=$k[0][0];$O=$k[0][1]+strlen($qa);if(!$qa&&$Ma&&!feof($Ma)){$j.=fread($Ma,1e5);}else{if(!$qa&&rtrim($j)==""){break;}if(!$qa||$qa==$pd){$Zd=false;$ra=substr($j,0,$k[0][1]);$ac++;echo"<pre class='jush-$_' id='sql-$ac'>".shorten_utf8(trim($ra),1000)."</pre>\n";ob_flush();flush();$Jd=explode(" ",microtime());if(!$g->multi_query($ra)){echo"<p class='error'>".lang(114).": ".error()."\n";$jd.=" <a href='#sql-$ac'>$ac</a>";if($_POST["error_stops"]){break;}}else{if(is_object($I)&&preg_match("~^$Kc*(USE)\\b~isU",$ra)){$I->query($ra);}do{$i=$g->store_result();$sd=explode(" ",microtime());$ae=" <span class='time'>(".lang(115,max(0,$sd[0]-$Jd[0]+$sd[1]-$Jd[1])).")</span>";if(is_object($i)){select($i,$I);echo"<p>".($i->num_rows?lang(116,$i->num_rows):"").$ae;if($I&&preg_match("~^($Kc|\\()*SELECT\\b~isU",$ra)){$U="explain-$ac";echo", <a href='#$U' onclick=\"return !toggle('$U');\">EXPLAIN</a>\n","<div id='$U' class='hidden'>\n";select(explain($I,$ra));echo"</div>\n";}}else{if(preg_match("~^$Kc*(CREATE|DROP|ALTER)$Kc+(DATABASE|SCHEMA)\\b~isU",$ra)){restart_session();set_session("dbs",null);session_write_close();}echo"<p class='message' title='".h($g->info)."'>".lang(117,$g->affected_rows)."$ae\n";}$Jd=$sd;}while($g->next_result());}$j=substr($j,$O);$O=0;}else{while(preg_match('~'.($qa=='/*'?'\\*/':(ereg('-- |#',$qa)?"\n":"$qa|\\\\.")).'|$~s',$j,$k,PREG_OFFSET_CAPTURE,$O)){$ia=$k[0][0];$O=$k[0][1]+strlen($ia);if(!$ia&&$Ma&&!feof($Ma)){$j.=fread($Ma,1e6);}elseif($ia[0]!="\\"){break;}}}}}}if($jd&&$ac>1){echo"<p class='error'>".lang(114).": $jd\n";}if($Zd){echo"<p class='message'>".lang(118)."\n";}}else{echo"<p class='error'>".upload_error($j)."\n";}}echo'
<form action="" method="post" enctype="multipart/form-data">
<p>';$ra=$_GET["sql"];if($_POST){$ra=$_POST["query"];}elseif($_GET["history"]!=""){$ra=$gb[$_GET["history"]];}textarea("query",$ra,20);echo($_POST?"":"<script type='text/javascript'>document.getElementsByTagName('textarea')[0].focus();</script>\n"),"<p>".(ini_bool("file_uploads")?lang(119).': <input type="file" name="sql_file">':lang(120)),'<p>
<input type="hidden" name="token" value="',$L,'">
<input type="submit" value="',lang(121),'" title="Ctrl+Enter">
',checkbox("error_stops",1,$_POST["error_stops"],lang(122));print_fieldset("webfile",lang(123),$_POST["webfile"]);$Hd=array();foreach(array("gz"=>"zlib","bz2"=>"bz2")as$c=>$b){if(extension_loaded($b)){$Hd[]=".$c";}}echo
lang(124,"<code>adminer.sql".($Hd?"[".implode("|",$Hd)."]":"")."</code>"),' <input type="submit" name="webfile" value="'.lang(125).'">',"</div></fieldset>\n";if($gb){print_fieldset("history",lang(126),$_GET["history"]!="");foreach($gb
as$c=>$b){echo'<a href="'.h(ME."sql=&history=$c").'">'.lang(30)."</a> <code class='jush-$_'>".shorten_utf8(ltrim(str_replace("\n"," ",str_replace("\r","",preg_replace('~^(#|-- ).*~m','',$b)))),80,"</code>")."<br>\n";}echo"<input type='submit' name='clear' value='".lang(127)."'>\n","</div></fieldset>\n";}echo'
</form>
';}elseif(isset($_GET["edit"])){$m=$_GET["edit"];$t=(isset($_GET["select"])?(count($_POST["check"])==1?where_check($_POST["check"][0]):""):where($_GET));$oa=(isset($_GET["select"])?$_POST["edit"]:$t);$o=fields($m);foreach($o
as$f=>$d){if(!isset($d["privileges"][$oa?"update":"insert"])||$r->fieldName($d)==""){unset($o[$f]);}}if($_POST&&!$n&&!isset($_GET["select"])){$la=$_POST["referer"];if($_POST["insert"]){$la=($oa?null:$_SERVER["REQUEST_URI"]);}elseif(!ereg('^.+&select=.+$',$la)){$la=ME."select=".urlencode($m);}if(isset($_POST["delete"])){query_redirect("DELETE".limit1("FROM ".table($m)," WHERE $t"),$la,lang(128));}else{$q=array();foreach($o
as$f=>$d){$b=process_input($d);if($b!==false&&$b!==null){$q[idf_escape($f)]=($oa?"\n".idf_escape($f)." = $b":$b);}}if($oa){if(!$q){redirect($la);}query_redirect("UPDATE".limit1(table($m)." SET".implode(",",$q),"\nWHERE $t"),$la,lang(129));}else{$i=insert_into($m,$q);$be=($i?last_id():0);queries_redirect($la,lang(130,($be?" $be":"")),$i);}}}$Ca=$r->tableName(table_status($m));page_header(($oa?lang(30):lang(131)),$n,array("select"=>array($m,$Ca)),$Ca);$a=null;if($_POST["save"]){$a=(array)$_POST["fields"];}elseif($t){$C=array();foreach($o
as$f=>$d){if(isset($d["privileges"]["select"])){$C[]=($_POST["clone"]&&$d["auto_increment"]?"'' AS ":(ereg("enum|set",$d["type"])?"1*".idf_escape($f)." AS ":"")).idf_escape($f);}}$a=array();if($C){$E=get_rows("SELECT".limit(implode(", ",$C)." FROM ".table($m)," WHERE $t",(isset($_GET["select"])?2:1)));$a=(isset($_GET["select"])&&count($E)!=1?null:reset($E));}}echo'
<form action="" method="post" enctype="multipart/form-data">
';if($o){echo"<table cellspacing='0'>\n";foreach($o
as$f=>$d){echo"<tr><th>".$r->fieldName($d);$va=$_GET["set"][bracket_escape($f)];$p=(isset($a)?($a[$f]!=""&&ereg("enum|set",$d["type"])?intval($a[$f]):$a[$f]):(!$oa&&$d["auto_increment"]?"":(isset($_GET["select"])?false:(isset($va)?$va:$d["default"]))));if(!$_POST["save"]&&is_string($p)){$p=$r->editVal($p,$d);}$P=($_POST["save"]?(string)$_POST["function"][$f]:($t&&$d["on_update"]=="CURRENT_TIMESTAMP"?"now":($p===false?null:(isset($p)?'':'NULL'))));if($d["type"]=="timestamp"&&$p=="CURRENT_TIMESTAMP"){$p="";$P="now";}input($d,$p,$P);echo"\n";}echo"</table>\n";}echo'<p>
<input type="hidden" name="token" value="',$L,'">
<input type="hidden" name="referer" value="',h(isset($_POST["referer"])?$_POST["referer"]:$_SERVER["HTTP_REFERER"]),'">
<input type="hidden" name="save" value="1">
';if(isset($_GET["select"])){hidden_fields(array("check"=>(array)$_POST["check"],"clone"=>$_POST["clone"],"all"=>$_POST["all"]));}if($o){echo"<input type='submit' value='".lang(132)."'>\n";if(!isset($_GET["select"])){echo'<input type="submit" name="insert" value="'.($oa?lang(133):lang(134))."\">\n";}}if($oa){echo"<input type='submit' name='delete' value='".lang(135)."'$cb>\n";}echo'</form>
';}elseif(isset($_GET["create"])){$m=$_GET["create"];$ce=array('HASH','LINEAR HASH','KEY','LINEAR KEY','RANGE','LIST');$Xd=referencable_primary($m);$ea=array();foreach($Xd
as$Ca=>$d){$ea[str_replace("`","``",$Ca)."`".str_replace("`","``",$d["field"])]=$Ca;}$Ic=array();$bd=array();if($m!=""){$Ic=fields($m);$bd=table_status($m);}if($_POST&&!$n&&!$_POST["add"]&&!$_POST["drop_col"]&&!$_POST["up"]&&!$_POST["down"]){if($_POST["drop"]){query_redirect("DROP TABLE ".table($m),substr(ME,0,-1),lang(136));}else{$o=array();$hb=array();ksort($_POST["fields"]);$wd=reset($Ic);$Kb="FIRST";foreach($_POST["fields"]as$c=>$d){$A=$ea[$d["type"]];$Hc=(isset($A)?$Xd[$A]:$d);if($d["field"]!=""){if(!$d["has_default"]){$d["default"]=null;}$va=eregi_replace(" *on update CURRENT_TIMESTAMP","",$d["default"]);if($va!=$d["default"]){$d["on_update"]="CURRENT_TIMESTAMP";$d["default"]=$va;}if($c==$_POST["auto_increment_col"]){$d["auto_increment"]=true;}$Td=process_field($d,$Hc);if($Td!=process_field($wd,$wd)){$o[]=array($d["orig"],$Td,$Kb);}if(isset($A)){$hb[]=($m!=""?"ADD":" ")." FOREIGN KEY (".idf_escape($d["field"]).") REFERENCES ".idf_escape($ea[$d["type"]])." (".idf_escape($Hc["field"]).")".(in_array($d["on_delete"],$db)?" ON DELETE $d[on_delete]":"");}$Kb="AFTER ".idf_escape($d["field"]);}elseif($d["orig"]!=""){$o[]=array($d["orig"]);}if($d["orig"]!=""){$wd=next($Ic);}}$tb="";if(in_array($_POST["partition_by"],$ce)){$Gd=array();if($_POST["partition_by"]=='RANGE'||$_POST["partition_by"]=='LIST'){foreach(array_filter($_POST["partition_names"])as$c=>$b){$p=$_POST["partition_values"][$c];$Gd[]="\nPARTITION ".idf_escape($b)." VALUES ".($_POST["partition_by"]=='RANGE'?"LESS THAN":"IN").($p!=""?" ($p)":" MAXVALUE");}}$tb.="\nPARTITION BY $_POST[partition_by]($_POST[partition])".($Gd?" (".implode(",",$Gd)."\n)":($_POST["partitions"]?" PARTITIONS ".intval($_POST["partitions"]):""));}elseif($m!=""&&support("partitioning")){$tb.="\nREMOVE PARTITIONING";}$za=lang(137);if($m==""){cookie("adminer_engine",$_POST["Engine"]);$za=lang(138);}queries_redirect(ME."table=".urlencode($_POST["name"]),$za,alter_table($m,$_POST["name"],$o,$hb,$_POST["Comment"],($_POST["Engine"]&&$_POST["Engine"]!=$bd["Engine"]?$_POST["Engine"]:""),($_POST["Collation"]&&$_POST["Collation"]!=$bd["Collation"]?$_POST["Collation"]:""),($_POST["Auto_increment"]!=""?preg_replace('~\\D+~','',$_POST["Auto_increment"]):""),$tb));}}page_header(($m!=""?lang(26):lang(139)),$n,array("table"=>$m),$m);$a=array("Engine"=>$_COOKIE["adminer_engine"],"fields"=>array(array("field"=>"")),"partition_names"=>array(""),);if($_POST){$a=$_POST;if($a["auto_increment_col"]){$a["fields"][$a["auto_increment_col"]]["auto_increment"]=true;}process_fields($a["fields"]);}elseif($m!=""){$a=$bd;$a["name"]=$m;$a["fields"]=array();if(!$_GET["auto_increment"]){$a["Auto_increment"]="";}foreach($Ic
as$d){$d["has_default"]=isset($d["default"]);if($d["on_update"]){$d["default"].=" ON UPDATE $d[on_update]";}$a["fields"][]=$d;}if(support("partitioning")){$Ub="FROM information_schema.PARTITIONS WHERE TABLE_SCHEMA = ".q(DB)." AND TABLE_NAME = ".q($m);$i=$g->query("SELECT PARTITION_METHOD, PARTITION_ORDINAL_POSITION, PARTITION_EXPRESSION $Ub ORDER BY PARTITION_ORDINAL_POSITION LIMIT 1");list($a["partition_by"],$a["partitions"],$a["partition"])=$i->fetch_row();$a["partition_names"]=array();$a["partition_values"]=array();foreach(get_rows("SELECT PARTITION_NAME, PARTITION_DESCRIPTION $Ub AND PARTITION_NAME != '' ORDER BY PARTITION_ORDINAL_POSITION")as$cd){$a["partition_names"][]=$cd["PARTITION_NAME"];$a["partition_values"][]=$cd["PARTITION_DESCRIPTION"];}$a["partition_names"][]="";}}$X=collations();$gd=floor(extension_loaded("suhosin")?(min(ini_get("suhosin.request.max_vars"),ini_get("suhosin.post.max_vars"))-13)/10:0);if($gd&&count($a["fields"])>$gd){echo"<p class='error'>".h(lang(140,'suhosin.post.max_vars','suhosin.request.max_vars'))."\n";}$kd=engines();foreach($kd
as$yb){if(!strcasecmp($yb,$a["Engine"])){$a["Engine"]=$yb;break;}}echo'
<form action="" method="post" id="form">
<p>
',lang(141),': <input name="name" maxlength="64" value="',h($a["name"]),'">
',($kd?html_select("Engine",array(""=>"(".lang(142).")")+$kd,$a["Engine"]):""),' ',($X&&!ereg("sqlite|mssql",$_)?html_select("Collation",array(""=>"(".lang(79).")")+$X,$a["Collation"]):""),' <input type="submit" value="',lang(132),'">
<table cellspacing="0" id="edit-fields" class="nowrap">
';$Jb=edit_fields($a["fields"],$X,"TABLE",$gd,$ea,$a["Comment"]!="");echo'</table>
<p>
',lang(86),': <input name="Auto_increment" size="6" value="',h($a["Auto_increment"]),'">
<script type="text/javascript">
document.write(\'<label><input type="checkbox" onclick="columnShow(this.checked, 5);">',lang(87),'<\\/label>\');
</script>
',(support("comment")?checkbox("","",$Jb,lang(88),"columnShow(this.checked, 6); toggle('Comment'); if (this.checked) this.form['Comment'].focus();").' <input id="Comment" name="Comment" value="'.h($a["Comment"]).'" maxlength="60"'.($Jb?'':' class="hidden"').'>':''),'<p>
<input type="hidden" name="token" value="',$L,'">
<input type="submit" value="',lang(132),'">
';if(strlen($_GET["create"])){echo'<input type="submit" name="drop" value="',lang(73),'"',$cb,'>';}if(support("partitioning")){$Vd=ereg('RANGE|LIST',$a["partition_by"]);print_fieldset("partition",lang(143),$a["partition_by"]);echo'<p>
',html_select("partition_by",array(-1=>"")+$ce,$a["partition_by"],"partitionByChange(this);"),'(<input name="partition" value="',h($a["partition"]),'">)
',lang(144),': <input name="partitions" size="2" value="',h($a["partitions"]),'"',($Vd||!$a["partition_by"]?" class='hidden'":""),'>
<table cellspacing="0" id="partition-table"',($Vd?"":" class='hidden'"),'>
<thead><tr><th>',lang(145),'<th>',lang(146),'</thead>
';foreach($a["partition_names"]as$c=>$b){echo'<tr>','<td><input name="partition_names[]" value="'.h($b).'"'.($c==count($a["partition_names"])-1?' onchange="partitionNameChange(this);"':'').'>','<td><input name="partition_values[]" value="'.h($a["partition_values"][$c]).'">';}echo'</table>
</div></fieldset>
';}echo'</form>
';}elseif(isset($_GET["indexes"])){$m=$_GET["indexes"];$dd=array("PRIMARY","UNIQUE","INDEX");$J=table_status($m);if(ereg("MyISAM|Maria",$J["Engine"])){$dd[]="FULLTEXT";}$K=indexes($m);if($_=="sqlite"){unset($dd[0]);unset($K[""]);}if($_POST&&!$n&&!$_POST["add"]){$u=array();foreach($_POST["indexes"]as$v){if(in_array($v["type"],$dd)){$B=array();$Qb=array();$q=array();ksort($v["columns"]);foreach($v["columns"]as$c=>$H){if($H!=""){$da=$v["lengths"][$c];$q[]=idf_escape($H).($da?"(".intval($da).")":"");$B[]=$H;$Qb[]=($da?$da:null);}}if($B){foreach($K
as$f=>$Cb){ksort($Cb["columns"]);ksort($Cb["lengths"]);if($v["type"]==$Cb["type"]&&array_values($Cb["columns"])===$B&&(!$Cb["lengths"]||array_values($Cb["lengths"])===$Qb)){unset($K[$f]);continue
2;}}$u[]=array($v["type"],"(".implode(", ",$q).")");}}}foreach($K
as$f=>$Cb){$u[]=array($Cb["type"],idf_escape($f),"DROP");}if(!$u){redirect(ME."table=".urlencode($m));}queries_redirect(ME."table=".urlencode($m),lang(147),alter_indexes($m,$u));}page_header(lang(96),$n,array("table"=>$m),$m);$o=array_keys(fields($m));$a=array("indexes"=>$K);if($_POST){$a=$_POST;if($_POST["add"]){foreach($a["indexes"]as$c=>$v){if($v["columns"][count($v["columns"])]!=""){$a["indexes"][$c]["columns"][]="";}}$v=end($a["indexes"]);if($v["type"]||array_filter($v["columns"],'strlen')||array_filter($v["lengths"],'strlen')){$a["indexes"][]=array("columns"=>array(1=>""));}}}else{foreach($a["indexes"]as$c=>$v){$a["indexes"][$c]["columns"][]="";}$a["indexes"][]=array("columns"=>array(1=>""));}echo'
<form action="" method="post">
<table cellspacing="0" class="nowrap">
<thead><tr><th>',lang(148),'<th>',lang(149),'</thead>
';$ma=1;foreach($a["indexes"]as$v){echo"<tr><td>".html_select("indexes[$ma][type]",array(-1=>"")+$dd,$v["type"],($ma==count($a["indexes"])?"indexesAddRow(this);":1))."<td>";ksort($v["columns"]);$l=1;foreach($v["columns"]as$H){echo"<span>".html_select("indexes[$ma][columns][$l]",array(-1=>"")+$o,$H,($l==count($v["columns"])?"indexesAddColumn(this);":1)),"<input name='indexes[$ma][lengths][$l]' size='2' value='".h($v["lengths"][$l])."'> </span>";$l++;}$ma++;}echo'</table>
<p>
<input type="hidden" name="token" value="',$L,'">
<input type="submit" value="',lang(132),'">
<noscript><p><input type="submit" name="add" value="',lang(89),'"></noscript>
</form>
';}elseif(isset($_GET["database"])){if($_POST&&!$n&&!isset($_POST["add_x"])){restart_session();if($_POST["drop"]){$_GET["db"]="";queries_redirect(remove_from_uri("db|database"),lang(150),drop_databases(array(DB)));}elseif(DB!==$_POST["name"]){if(DB!=""){$_GET["db"]=$_POST["name"];queries_redirect(preg_replace('~db=[^&]*&~','',ME)."db=".urlencode($_POST["name"]),lang(151),rename_database($_POST["name"],$_POST["collation"]));}else{$z=explode("\n",str_replace("\r","",$_POST["name"]));$Ud=true;$wb="";foreach($z
as$s){if(count($z)==1||$s!=""){if(!create_database($s,$_POST["collation"])){$Ud=false;}$wb=$s;}}queries_redirect(ME."db=".urlencode($wb),lang(152),$Ud);}}else{if(!$_POST["collation"]){redirect(substr(ME,0,-1));}query_redirect("ALTER DATABASE ".idf_escape($_POST["name"]).(eregi('^[a-z0-9_]+$',$_POST["collation"])?" COLLATE $_POST[collation]":""),substr(ME,0,-1),lang(153));}}page_header(DB!=""?lang(154):lang(155),$n,array(),DB);$X=collations();$f=DB;$rc=null;if($_POST){$f=$_POST["name"];$rc=$_POST["collation"];}elseif(DB!=""){$rc=db_collation(DB,$X);}elseif($_=="sql"){foreach(get_vals("SHOW GRANTS")as$fa){if(preg_match('~ ON (`(([^\\\\`]|``|\\\\.)*)%`\\.\\*)?~',$fa,$k)&&$k[1]){$f=stripcslashes(idf_unescape("`$k[2]`"));break;}}}echo'
<form action="" method="post">
<p>
',($_POST["add_x"]||strpos($f,"\n")?'<textarea name="name" rows="10" cols="40" onkeydown="return textareaKeydown(this, event);">'.h($f).'</textarea><br>':'<input name="name" value="'.h($f).'" maxlength="64">')."\n".($X?html_select("collation",array(""=>"(".lang(79).")")+$X,$rc):""),'<input type="hidden" name="token" value="',$L,'">
<input type="submit" value="',lang(132),'">
';if(DB!=""){echo"<input type='submit' name='drop' value='".lang(73)."'$cb>\n";}elseif(!$_POST["add_x"]&&$_GET["db"]==""){echo"<input type='image' name='add' src='".h(preg_replace("~\\?.*~","",$_SERVER["REQUEST_URI"]))."?file=plus.gif&amp;version=3.0.1' alt='+' title='".lang(89)."'>\n";}echo'</form>
';}elseif(isset($_GET["scheme"])){if($_POST&&!$n){$x=preg_replace('~ns=[^&]*&~','',ME)."ns=";if($_POST["drop"]){query_redirect("DROP SCHEMA ".idf_escape($_GET["ns"]),$x,lang(156));}else{$x.=urlencode($_POST["name"]);if($_GET["ns"]==""){query_redirect("CREATE SCHEMA ".idf_escape($_POST["name"]),$x,lang(157));}elseif($_GET["ns"]!=$_POST["name"]){query_redirect("ALTER SCHEMA ".idf_escape($_GET["ns"])." RENAME TO ".idf_escape($_POST["name"]),$x,lang(158));}else{redirect($x);}}}page_header($_GET["ns"]!=""?lang(159):lang(160),$n);$a=array("name"=>$_GET["ns"]);if($_POST){$a=$_POST;}echo'
<form action="" method="post">
<p><input name="name" value="',h($a["name"]),'">
<input type="hidden" name="token" value="',$L,'">
<input type="submit" value="',lang(132),'">
';if($_GET["ns"]!=""){echo"<input type='submit' name='drop' value='".lang(73)."'$cb>\n";}echo'</form>
';}elseif(isset($_GET["call"])){$Va=$_GET["call"];page_header(lang(161).": ".h($Va),$n);$Ra=routine($Va,(isset($_GET["callf"])?"FUNCTION":"PROCEDURE"));$Fb=array();$lb=array();foreach($Ra["fields"]as$l=>$d){if(substr($d["inout"],-3)=="OUT"){$lb[$l]="@".idf_escape($d["field"])." AS ".idf_escape($d["field"]);}if(!$d["inout"]||substr($d["inout"],0,2)=="IN"){$Fb[]=$l;}}if(!$n&&$_POST){$Sd=array();foreach($Ra["fields"]as$c=>$d){if(in_array($c,$Fb)){$b=process_input($d);if($b===false){$b="''";}if(isset($lb[$c])){$g->query("SET @".idf_escape($d["field"])." = $b");}}$Sd[]=(isset($lb[$c])?"@".idf_escape($d["field"]):$b);}$j=(isset($_GET["callf"])?"SELECT":"CALL")." ".idf_escape($Va)."(".implode(", ",$Sd).")";echo"<p><code class='jush-$_'>".h($j)."</code> <a href='".h(ME)."sql=".urlencode($j)."'>".lang(30)."</a>\n";if(!$g->multi_query($j)){echo"<p class='error'>".error()."\n";}else{do{$i=$g->store_result();if(is_object($i)){select($i);}else{echo"<p class='message'>".lang(162,$g->affected_rows)."\n";}}while($g->next_result());if($lb){select($g->query("SELECT ".implode(", ",$lb)));}}}echo'
<form action="" method="post">
';if($Fb){echo"<table cellspacing='0'>\n";foreach($Fb
as$c){$d=$Ra["fields"][$c];$f=$d["field"];echo"<tr><th>".$r->fieldName($d);$p=$_POST["fields"][$f];if($p!=""&&ereg("enum|set",$d["type"])){$p=intval($p);}input($d,$p,(string)$_POST["function"][$f]);echo"\n";}echo"</table>\n";}echo'<p>
<input type="hidden" name="token" value="',$L,'">
<input type="submit" value="',lang(161),'">
</form>
';}elseif(isset($_GET["foreign"])){$m=$_GET["foreign"];if($_POST&&!$n&&!$_POST["add"]&&!$_POST["change"]&&!$_POST["change-js"]){if($_POST["drop"]){query_redirect("ALTER TABLE ".table($m)."\nDROP ".($_=="sql"?"FOREIGN KEY ":"CONSTRAINT ").idf_escape($_GET["name"]),ME."table=".urlencode($m),lang(163));}else{$Ga=array_filter($_POST["source"],'strlen');ksort($Ga);$ta=array();foreach($Ga
as$c=>$b){$ta[$c]=$_POST["target"][$c];}query_redirect("ALTER TABLE ".table($m).($_GET["name"]!=""?"\nDROP FOREIGN KEY ".idf_escape($_GET["name"]).",":"")."\nADD FOREIGN KEY (".implode(", ",array_map('idf_escape',$Ga)).") REFERENCES ".table($_POST["table"])." (".implode(", ",array_map('idf_escape',$ta)).")".(in_array($_POST["on_delete"],$db)?" ON DELETE $_POST[on_delete]":"").(in_array($_POST["on_update"],$db)?" ON UPDATE $_POST[on_update]":""),ME."table=".urlencode($m),($_GET["name"]!=""?lang(164):lang(165)));$n=lang(166)."<br>$n";}}page_header(lang(167),$n,array("table"=>$m),$m);$a=array("table"=>$m,"source"=>array(""));if($_POST){$a=$_POST;ksort($a["source"]);if($_POST["add"]){$a["source"][]="";}elseif($_POST["change"]||$_POST["change-js"]){$a["target"]=array();}}elseif($_GET["name"]!=""){$ea=foreign_keys($m);$a=$ea[$_GET["name"]];$a["source"][]="";}$Ga=array_keys(fields($m));$ta=($m===$a["table"]?$Ga:array_keys(fields($a["table"])));$Wd=array();foreach(table_status()as$f=>$J){if(fk_support($J)){$Wd[]=$f;}}echo'
<form action="" method="post">
<p>
';if($a["db"]==""){echo
lang(168),':
',html_select("table",$Wd,$a["table"],"this.form['change-js'].value = '1'; this.form.submit();"),'<input type="hidden" name="change-js" value="">
<noscript><p><input type="submit" name="change" value="',lang(169),'"></noscript>
<table cellspacing="0">
<thead><tr><th>',lang(98),'<th>',lang(99),'</thead>
';$ma=0;foreach($a["source"]as$c=>$b){echo"<tr>","<td>".html_select("source[".intval($c)."]",array(-1=>"")+$Ga,$b,($ma==count($a["source"])-1?"foreignAddRow(this);":1)),"<td>".html_select("target[".intval($c)."]",$ta,$a["target"][$c]);$ma++;}echo'</table>
<p>
',lang(80),': ',html_select("on_delete",array(-1=>"")+$db,$a["on_delete"]),' ',lang(100),': ',html_select("on_update",array(-1=>"")+$db,$a["on_update"]),'<p>
<input type="submit" value="',lang(132),'">
<noscript><p><input type="submit" name="add" value="',lang(170),'"></noscript>
';}if($_GET["name"]!=""){echo'<input type="submit" name="drop" value="',lang(73),'"',$cb,'>';}echo'<input type="hidden" name="token" value="',$L,'">
</form>
';}elseif(isset($_GET["view"])){$m=$_GET["view"];$Ya=false;if($_POST&&!$n){$Ya=drop_create("DROP VIEW ".table($m),"CREATE VIEW ".table($_POST["name"])." AS\n$_POST[select]",($_POST["drop"]?substr(ME,0,-1):ME."table=".urlencode($_POST["name"])),lang(171),lang(172),lang(173),$m);}page_header(($m!=""?lang(25):lang(174)),$n,array("table"=>$m),$m);$a=array();if($_POST){$a=$_POST;}elseif($m!=""){$a=view($m);$a["name"]=$m;}echo'
<form action="" method="post">
<p>',lang(175),': <input name="name" value="',h($a["name"]),'" maxlength="64">
<p>';textarea("select",$a["select"]);echo'<p>
<input type="hidden" name="token" value="',$L,'">
';if($Ya){echo'<input type="hidden" name="dropped" value="1">';}echo'<input type="submit" value="',lang(132),'">
</form>
';}elseif(isset($_GET["event"])){$mb=$_GET["event"];$Qd=array("YEAR","QUARTER","MONTH","DAY","HOUR","MINUTE","WEEK","SECOND","YEAR_MONTH","DAY_HOUR","DAY_MINUTE","DAY_SECOND","HOUR_MINUTE","HOUR_SECOND","MINUTE_SECOND");$id=array("ENABLED"=>"ENABLE","DISABLED"=>"DISABLE","SLAVESIDE_DISABLED"=>"DISABLE ON SLAVE");if($_POST&&!$n){if($_POST["drop"]){query_redirect("DROP EVENT ".idf_escape($mb),substr(ME,0,-1),lang(176));}elseif(in_array($_POST["INTERVAL_FIELD"],$Qd)&&isset($id[$_POST["STATUS"]])){$de="\nON SCHEDULE ".($_POST["INTERVAL_VALUE"]?"EVERY ".q($_POST["INTERVAL_VALUE"])." $_POST[INTERVAL_FIELD]".($_POST["STARTS"]?" STARTS ".q($_POST["STARTS"]):"").($_POST["ENDS"]?" ENDS ".q($_POST["ENDS"]):""):"AT ".q($_POST["STARTS"]))." ON COMPLETION".($_POST["ON_COMPLETION"]?"":" NOT")." PRESERVE";query_redirect(($mb!=""?"ALTER EVENT ".idf_escape($mb).$de.($mb!=$_POST["EVENT_NAME"]?"\nRENAME TO ".idf_escape($_POST["EVENT_NAME"]):""):"CREATE EVENT ".idf_escape($_POST["EVENT_NAME"]).$de)."\n".$id[$_POST["STATUS"]]." COMMENT ".q($_POST["EVENT_COMMENT"])." DO\n$_POST[EVENT_DEFINITION]",substr(ME,0,-1),($mb!=""?lang(177):lang(178)));}}page_header(($mb!=""?lang(179).": ".h($mb):lang(180)),$n);$a=array();if($_POST){$a=$_POST;}elseif($mb!=""){$E=get_rows("SELECT * FROM information_schema.EVENTS WHERE EVENT_SCHEMA = ".q(DB)." AND EVENT_NAME = ".q($mb));$a=reset($E);}echo'
<form action="" method="post">
<table cellspacing="0">
<tr><th>',lang(175),'<td><input name="EVENT_NAME" value="',h($a["EVENT_NAME"]),'" maxlength="64">
<tr><th>',lang(181),'<td><input name="STARTS" value="',h("$a[EXECUTE_AT]$a[STARTS]"),'">
<tr><th>',lang(182),'<td><input name="ENDS" value="',h($a["ENDS"]),'">
<tr><th>',lang(183),'<td><input name="INTERVAL_VALUE" value="',h($a["INTERVAL_VALUE"]),'" size="6"> ',html_select("INTERVAL_FIELD",$Qd,$a["INTERVAL_FIELD"]),'<tr><th>',lang(68),'<td>',html_select("STATUS",$id,$a["STATUS"]),'<tr><th>',lang(88),'<td><input name="EVENT_COMMENT" value="',h($a["EVENT_COMMENT"]),'" maxlength="64">
<tr><th>&nbsp;<td>',checkbox("ON_COMPLETION","PRESERVE",$a["ON_COMPLETION"]=="PRESERVE",lang(184)),'</table>
<p>';textarea("EVENT_DEFINITION",$a["EVENT_DEFINITION"]);echo'<p>
<input type="hidden" name="token" value="',$L,'">
<input type="submit" value="',lang(132),'">
';if($mb!=""){echo'<input type="submit" name="drop" value="',lang(73),'"',$cb,'>';}echo'</form>
';}elseif(isset($_GET["procedure"])){$Va=$_GET["procedure"];$Ra=(isset($_GET["function"])?"FUNCTION":"PROCEDURE");$Ya=false;if($_POST&&!$n&&!$_POST["add"]&&!$_POST["drop_col"]&&!$_POST["up"]&&!$_POST["down"]){$q=array();$o=(array)$_POST["fields"];ksort($o);foreach($o
as$d){if($d["field"]!=""){$q[]=(in_array($d["inout"],$_c)?"$d[inout] ":"").idf_escape($d["field"]).process_type($d,"CHARACTER SET");}}$Ya=drop_create("DROP $Ra ".idf_escape($Va),"CREATE $Ra ".idf_escape($_POST["name"])." (".implode(", ",$q).")".(isset($_GET["function"])?" RETURNS".process_type($_POST["returns"],"CHARACTER SET"):"")."\n$_POST[definition]",substr(ME,0,-1),lang(185),lang(186),lang(187),$Va);}page_header(($Va!=""?(isset($_GET["function"])?lang(188):lang(189)).": ".h($Va):(isset($_GET["function"])?lang(190):lang(191))),$n);$X=get_vals("SHOW CHARACTER SET");sort($X);$a=array("fields"=>array());if($_POST){$a=$_POST;$a["fields"]=(array)$a["fields"];process_fields($a["fields"]);}elseif($Va!=""){$a=routine($Va,$Ra);$a["name"]=$Va;}echo'
<form action="" method="post" id="form">
<p>',lang(175),': <input name="name" value="',h($a["name"]),'" maxlength="64">
<table cellspacing="0" class="nowrap">
';edit_fields($a["fields"],$X,$Ra);if(isset($_GET["function"])){echo"<tr><td>".lang(192);edit_type("returns",$a["returns"],$X);}echo'</table>
<p>';textarea("definition",$a["definition"]);echo'<p>
<input type="hidden" name="token" value="',$L,'">
';if($Ya){echo'<input type="hidden" name="dropped" value="1">';}echo'<input type="submit" value="',lang(132),'">
';if($Va!=""){echo'<input type="submit" name="drop" value="',lang(73),'"',$cb,'>';}echo'</form>
';}elseif(isset($_GET["sequence"])){$Bb=$_GET["sequence"];if($_POST&&!$n){$x=substr(ME,0,-1);if($_POST["drop"]){query_redirect("DROP SEQUENCE ".idf_escape($Bb),$x,lang(193));}elseif($Bb==""){query_redirect("CREATE SEQUENCE ".idf_escape($_POST["name"]),$x,lang(194));}elseif($Bb!=$_POST["name"]){query_redirect("ALTER SEQUENCE ".idf_escape($Bb)." RENAME TO ".idf_escape($_POST["name"]),$x,lang(195));}else{redirect($x);}}page_header($Bb!=""?lang(196).": ".h($Bb):lang(197),$n);$a=array("name"=>$Bb);if($_POST){$a=$_POST;}echo'
<form action="" method="post">
<p><input name="name" value="',h($a["name"]),'">
<input type="hidden" name="token" value="',$L,'">
<input type="submit" value="',lang(132),'">
';if($Bb!=""){echo"<input type='submit' name='drop' value='".lang(73)."'$cb>\n";}echo'</form>
';}elseif(isset($_GET["type"])){$Dc=$_GET["type"];if($_POST&&!$n){$x=substr(ME,0,-1);if($_POST["drop"]){query_redirect("DROP TYPE ".idf_escape($Dc),$x,lang(198));}else{query_redirect("CREATE TYPE ".idf_escape($_POST["name"])." $_POST[as]",$x,lang(199));}}page_header($Dc!=""?lang(200).": ".h($Dc):lang(201),$n);$a["as"]="AS ";if($_POST){$a=$_POST;}echo'
<form action="" method="post">
<p>
<input type="hidden" name="token" value="',$L,'">
';if($Dc!=""){echo"<input type='submit' name='drop' value='".lang(73)."'$cb>\n";}else{echo"<input name='name' value='".h($a['name'])."'>\n";textarea("as",$a["as"]);echo"<p><input type='submit' value='".lang(132)."'>\n";}echo'</form>
';}elseif(isset($_GET["trigger"])){$m=$_GET["trigger"];$Cc=trigger_options();$Kd=array("INSERT","UPDATE","DELETE");$Ya=false;if($_POST&&!$n&&in_array($_POST["Timing"],$Cc["Timing"])&&in_array($_POST["Event"],$Kd)&&in_array($_POST["Type"],$Cc["Type"])){$Md=" $_POST[Timing] $_POST[Event]";$_b=" ON ".table($m);$Ya=drop_create("DROP TRIGGER ".idf_escape($_GET["name"]).($_=="pgsql"?$_b:""),"CREATE TRIGGER ".idf_escape($_POST["Trigger"]).($_=="mssql"?$_b.$Md:$Md.$_b)." $_POST[Type]\n$_POST[Statement]",ME."table=".urlencode($m),lang(202),lang(203),lang(204),$_GET["name"]);}page_header(($_GET["name"]!=""?lang(205).": ".h($_GET["name"]):lang(206)),$n,array("table"=>$m));$a=array("Trigger"=>$m."_bi");if($_POST){$a=$_POST;}elseif($_GET["name"]!=""){$a=trigger($_GET["name"]);}echo'
<form action="" method="post" id="form">
<table cellspacing="0">
<tr><th>',lang(207),'<td>',html_select("Timing",$Cc["Timing"],$a["Timing"],"if (/^".h(preg_quote($m,"/"))."_[ba][iud]$/.test(this.form['Trigger'].value)) this.form['Trigger'].value = '".h(addcslashes($m,"\r\n'\\"))."_' + selectValue(this).charAt(0).toLowerCase() + selectValue(this.form['Event']).charAt(0).toLowerCase();"),'<tr><th>',lang(208),'<td>',html_select("Event",$Kd,$a["Event"],"this.form['Timing'].onchange();"),'<tr><th>',lang(83),'<td>',html_select("Type",$Cc["Type"],$a["Type"]),'</table>
<p>',lang(175),': <input name="Trigger" value="',h($a["Trigger"]),'" maxlength="64">
<p>';textarea("Statement",$a["Statement"]);echo'<p>
<input type="hidden" name="token" value="',$L,'">
';if($Ya){echo'<input type="hidden" name="dropped" value="1">';}echo'<input type="submit" value="',lang(132),'">
';if($_GET["name"]!=""){echo'<input type="submit" name="drop" value="',lang(73),'"',$cb,'>';}echo'</form>
';}elseif(isset($_GET["user"])){$yd=$_GET["user"];$pa=array(""=>array("All privileges"=>""));foreach(get_rows("SHOW PRIVILEGES")as$a){foreach(explode(",",($a["Privilege"]=="Grant option"?"":$a["Context"]))as$Mc){$pa[$Mc][$a["Privilege"]]=$a["Comment"];}}$pa["Server Admin"]+=$pa["File access on server"];$pa["Databases"]["Create routine"]=$pa["Procedures"]["Create routine"];unset($pa["Procedures"]["Create routine"]);$pa["Columns"]=array();foreach(array("Select","Insert","Update","References")as$b){$pa["Columns"][$b]=$pa["Tables"][$b];}unset($pa["Server Admin"]["Usage"]);foreach($pa["Tables"]as$c=>$b){unset($pa["Databases"][$c]);}$qc=array();if($_POST){foreach($_POST["objects"]as$c=>$b){$qc[$b]=(array)$qc[$b]+(array)$_POST["grants"][$c];}}$nb=array();$Rc="";if(isset($_GET["host"])&&($i=$g->query("SHOW GRANTS FOR ".q($yd)."@".q($_GET["host"])))){while($a=$i->fetch_row()){if(preg_match('~GRANT (.*) ON (.*) TO ~',$a[0],$k)&&preg_match_all('~ *([^(,]*[^ ,(])( *\\([^)]+\\))?~',$k[1],$ka,PREG_SET_ORDER)){foreach($ka
as$b){if($b[1]!="USAGE"){$nb["$k[2]$b[2]"][$b[1]]=true;}if(ereg(' WITH GRANT OPTION',$a[0])){$nb["$k[2]$b[2]"]["GRANT OPTION"]=true;}}}if(preg_match("~ IDENTIFIED BY PASSWORD '([^']+)~",$a[0],$k)){$Rc=$k[1];}}}if($_POST&&!$n){$Rb=(isset($_GET["host"])?q($yd)."@".q($_GET["host"]):"''");$jb=q($_POST["user"])."@".q($_POST["host"]);$xd=q($_POST["pass"]);if($_POST["drop"]){query_redirect("DROP USER $Rb",ME."privileges=",lang(209));}else{if($Rb!=$jb){$n=!queries(($g->server_info<5?"GRANT USAGE ON *.* TO":"CREATE USER")." $jb IDENTIFIED BY".($_POST["hashed"]?" PASSWORD":"")." $xd");}elseif($_POST["pass"]!=$Rc||!$_POST["hashed"]){queries("SET PASSWORD FOR $jb = ".($_POST["hashed"]?$xd:"PASSWORD($xd)"));}if(!$n){$xc=array();foreach($qc
as$Xa=>$fa){if(isset($_GET["grant"])){$fa=array_filter($fa);}$fa=array_keys($fa);if(isset($_GET["grant"])){$xc=array_diff(array_keys(array_filter($qc[$Xa],'strlen')),$fa);}elseif($Rb==$jb){$oe=array_keys((array)$nb[$Xa]);$xc=array_diff($oe,$fa);$fa=array_diff($fa,$oe);unset($nb[$Xa]);}if(preg_match('~^(.+)\\s*(\\(.*\\))?$~U',$Xa,$k)&&(!grant("REVOKE",$xc,$k[2]," ON $k[1] FROM $jb")||!grant("GRANT",$fa,$k[2]," ON $k[1] TO $jb"))){$n=true;break;}}}if(!$n&&isset($_GET["host"])){if($Rb!=$jb){queries("DROP USER $Rb");}elseif(!isset($_GET["grant"])){foreach($nb
as$Xa=>$xc){if(preg_match('~^(.+)(\\(.*\\))?$~U',$Xa,$k)){grant("REVOKE",array_keys($xc),$k[2]," ON $k[1] FROM $jb");}}}}queries_redirect(ME."privileges=",(isset($_GET["host"])?lang(210):lang(211)),!$n);if($Rb!=$jb){$g->query("DROP USER $jb");}}}page_header((isset($_GET["host"])?lang(19).": ".h("$yd@$_GET[host]"):lang(113)),$n,array("privileges"=>array('',lang(65))));if($_POST){$a=$_POST;$nb=$qc;}else{$a=$_GET+array("host"=>$g->result("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', -1)"));$a["pass"]=$Rc;if($Rc!=""){$a["hashed"]=true;}$nb[""]=true;}echo'<form action="" method="post">
<table cellspacing="0">
<tr><th>',lang(19),'<td><input name="user" maxlength="16" value="',h($a["user"]),'">
<tr><th>',lang(18),'<td><input name="host" maxlength="60" value="',h($a["host"]),'">
<tr><th>',lang(20),'<td><input id="pass" name="pass" value="',h($a["pass"]),'">
';if(!$a["hashed"]){echo'<script type="text/javascript">typePassword(document.getElementById(\'pass\'));</script>';}echo
checkbox("hashed",1,$a["hashed"],lang(212),"typePassword(this.form['pass'], this.checked);"),'</table>

';echo"<table cellspacing='0'>\n","<thead><tr><th colspan='2'><a href='http://dev.mysql.com/doc/refman/".substr($g->server_info,0,3)."/en/grant.html'>".lang(65)."</a>";$l=0;foreach($nb
as$Xa=>$fa){echo'<th>'.($Xa!="*.*"?"<input name='objects[$l]' value='".h($Xa)."' size='10'>":"<input type='hidden' name='objects[$l]' value='*.*' size='10'>*.*");$l++;}echo"</thead>\n";foreach(array(""=>"","Server Admin"=>lang(18),"Databases"=>lang(60),"Tables"=>lang(94),"Columns"=>lang(95),"Procedures"=>lang(213),)as$Mc=>$hc){foreach((array)$pa[$Mc]as$kc=>$Ba){echo"<tr".odd()."><td".($hc?">$hc<td":" colspan='2'").' lang="en" title="'.h($Ba).'">'.h($kc);$l=0;foreach($nb
as$Xa=>$fa){$f="'grants[$l][".h(strtoupper($kc))."]'";$p=$fa[strtoupper($kc)];if($Mc=="Server Admin"&&$Xa!=(isset($nb["*.*"])?"*.*":"")){echo"<td>&nbsp;";}elseif(isset($_GET["grant"])){echo"<td><select name=$f><option><option value='1'".($p?" selected":"").">".lang(214)."<option value='0'".($p=="0"?" selected":"").">".lang(215)."</select>";}else{echo"<td align='center'><input type='checkbox' name=$f value='1'".($p?" checked":"").($kc=="All privileges"?" id='grants-$l-all'":($kc=="Grant option"?"":" onclick=\"if (this.checked) formUncheck('grants-$l-all');\"")).">";}$l++;}}}echo"</table>\n",'<p>
<input type="hidden" name="token" value="',$L,'">
<input type="submit" value="',lang(132),'">
';if(isset($_GET["host"])){echo'<input type="submit" name="drop" value="',lang(73),'"',$cb,'>';}echo'</form>
';}elseif(isset($_GET["processlist"])){if($_POST&&!$n){$ed=0;foreach((array)$_POST["kill"]as$b){if(queries("KILL ".ereg_replace("[^0-9]+","",$b))){$ed++;}}queries_redirect(ME."processlist=",lang(216,$ed),$ed||!$_POST["kill"]);}page_header(lang(66),$n);echo'
<form action="" method="post">
<table cellspacing="0" onclick="tableClick(event);" class="nowrap">
';foreach(get_rows("SHOW FULL PROCESSLIST")as$l=>$a){if(!$l){echo"<thead><tr lang='en'><th>&nbsp;<th>".implode("<th>",array_keys($a))."</thead>\n";}echo"<tr".odd()."><td>".checkbox("kill[]",$a["Id"],0)."<td>".implode("<td>",array_map('nbsp',$a))."\n";}echo'</table>
<p>
<input type="hidden" name="token" value="',$L,'">
<input type="submit" value="',lang(217),'">
</form>
';}elseif(isset($_GET["select"])){$m=$_GET["select"];$J=table_status($m);$K=indexes($m);$o=fields($m,1);$ea=column_foreign_keys($m);$je=array();$B=array();$zb=null;foreach($o
as$c=>$d){$f=$r->fieldName($d);if(isset($d["privileges"]["select"])&&$f!=""){$B[$c]=html_entity_decode(strip_tags($f));if(ereg('text|lob',$d["type"])){$zb=$r->selectLengthProcess();}}$je+=$d["privileges"];}list($C,$_a)=$r->selectColumnsProcess($B,$K);$t=$r->selectSearchProcess($o,$K);$pb=$r->selectOrderProcess($o,$K);$M=$r->selectLimitProcess();$Ub=($C?implode(", ",$C):"*")."\nFROM ".table($m);$ld=($_a&&count($_a)<count($C)?"\nGROUP BY ".implode(", ",$_a):"").($pb?"\nORDER BY ".implode(", ",$pb):"");if($_POST&&!$n){$he="(".implode(") OR (",array_map('where_check',(array)$_POST["check"])).")";$Ia=$ad=null;foreach($K
as$v){if($v["type"]=="PRIMARY"){$Ia=array_flip($v["columns"]);$ad=($C?$Ia:array());break;}}foreach($C
as$c=>$b){$b=$_GET["columns"][$c];if(!$b["fun"]){unset($ad[$b["col"]]);}}if($_POST["export"]){dump_headers($m);dump_table($m,"");if($_POST["format"]!="sql"){$a=array_keys($o);if($C){$a=array();foreach($C
as$b){$a[]=(ereg('^`.*`$',$b)?idf_unescape($b):$b);}}dump_csv($a);}if(!is_array($_POST["check"])||$ad===array()){$Fd=$t;if(is_array($_POST["check"])){$Fd[]="($he)";}dump_data($m,"INSERT","SELECT $Ub".($Fd?"\nWHERE ".implode(" AND ",$Fd):"").$ld);}else{$qe=array();foreach($_POST["check"]as$b){$qe[]="(SELECT".limit($Ub,"\nWHERE ".($t?implode(" AND ",$t)." AND ":"").where_check($b).$ld,1).")";}dump_data($m,"INSERT",implode(" UNION ALL ",$qe));}exit;}if(!$r->selectEmailProcess($t,$ea)){if($_POST["save"]||$_POST["delete"]){$i=true;$rb=0;$j=table($m);$q=array();if(!$_POST["delete"]){foreach($B
as$f=>$b){$b=process_input($o[$f]);if($b!==null){if($_POST["clone"]){$q[idf_escape($f)]=($b!==false?$b:idf_escape($f));}elseif($b!==false){$q[]=idf_escape($f)." = $b";}}}$j.=($_POST["clone"]?" (".implode(", ",array_keys($q)).")\nSELECT ".implode(", ",$q)."\nFROM ".table($m):" SET\n".implode(",\n",$q));}if($_POST["delete"]||$q){$Oc="UPDATE";if($_POST["delete"]){$Oc="DELETE";$j="FROM $j";}if($_POST["clone"]){$Oc="INSERT";$j="INTO $j";}if($_POST["all"]||($ad===array()&&$_POST["check"])||count($_a)<count($C)){$i=queries($Oc." $j".($_POST["all"]?($t?"\nWHERE ".implode(" AND ",$t):""):"\nWHERE $he"));$rb=$g->affected_rows;}else{foreach((array)$_POST["check"]as$b){$i=queries($Oc.limit1($j,"\nWHERE ".where_check($b)));if(!$i){break;}$rb+=$g->affected_rows;}}}queries_redirect(remove_from_uri("page"),lang(218,$rb),$i);}elseif(!$_POST["import"]){if(!$_POST["val"]){$n=lang(219);}else{$i=true;$rb=0;foreach($_POST["val"]as$ub=>$a){$q=array();foreach($a
as$c=>$b){$c=bracket_escape($c,1);$q[]=idf_escape($c)." = ".(ereg('char|text',$o[$c]["type"])||$b!=""?$r->processInput($o[$c],$b):"NULL");}$i=queries("UPDATE".limit1(table($m)." SET ".implode(", ",$q)," WHERE ".where_check($ub).($t?" AND ".implode(" AND ",$t):"")));if(!$i){break;}$rb+=$g->affected_rows;}queries_redirect(remove_from_uri(),lang(218,$rb),$i);}}elseif(is_string($sa=get_file("csv_file",true))){$sa=preg_replace("~^\xEF\xBB\xBF~",'',$sa);$i=true;$ib=array_keys($o);preg_match_all('~(?>"[^"]*"|[^"\\r\\n]+)+~',$sa,$ka);$rb=count($ka[0]);begin();$Ta=($_POST["separator"]=="csv"?",":";");foreach($ka[0]as$c=>$b){preg_match_all("~((\"[^\"]*\")+|[^$Ta]*)$Ta~",$b.$Ta,$nd);if(!$c&&!array_diff($nd[1],$ib)){$ib=$nd[1];$rb--;}else{$q=array();foreach($nd[1]as$l=>$Bc){$q[idf_escape($ib[$l])]=($Bc==""&&$o[$ib[$l]]["null"]?"NULL":q(str_replace('""','"',preg_replace('~^"|"$~','',$Bc))));}$i=insert_update($m,$q,$Ia);if(!$i){break;}}}if($i){queries("COMMIT");}queries_redirect(remove_from_uri("page"),lang(220,$rb),$i);queries("ROLLBACK");}else{$n=upload_error($sa);}}}$Ca=$r->tableName($J);page_header(lang(32).": $Ca",$n);session_write_close();$q=null;if(isset($je["insert"])){$q="";foreach((array)$_GET["where"]as$b){if(count($ea[$b["col"]])==1&&($b["op"]=="="||(!$b["op"]&&!ereg('[_%]',$b["val"])))){$q.="&set".urlencode("[".bracket_escape($b["col"])."]")."=".urlencode($b["val"]);}}}$r->selectLinks($J,$q);if(!$B){echo"<p class='error'>".lang(221).($o?".":": ".error())."\n";}else{echo"<form action='' id='form'>\n","<div style='display: none;'>";hidden_fields_get();echo(DB!=""?'<input type="hidden" name="db" value="'.h(DB).'">'.(isset($_GET["ns"])?'<input type="hidden" name="ns" value="'.h($_GET["ns"]).'">':""):"");echo'<input type="hidden" name="select" value="'.h($m).'">',"</div>\n";$r->selectColumnsPrint($C,$B);$r->selectSearchPrint($t,$B,$K);$r->selectOrderPrint($pb,$B,$K);$r->selectLimitPrint($M);$r->selectLengthPrint($zb);$r->selectActionPrint($zb);echo"</form>\n";$aa=$_GET["page"];if($aa=="last"){$Qa=$g->result("SELECT COUNT(*) FROM ".table($m).($t?" WHERE ".implode(" AND ",$t):""));$aa=floor(($Qa-1)/$M);}$j="SELECT".limit((intval($M)&&$_a&&count($_a)<count($C)&&$_=="sql"?"SQL_CALC_FOUND_ROWS ":"").$Ub,($t?"\nWHERE ".implode(" AND ",$t):"").$ld,($M!=""?intval($M):null),($aa?$M*$aa:0),"\n");echo$r->selectQuery($j);$i=$g->query($j);if(!$i){echo"<p class='error'>".error()."\n";}else{if($_=="mssql"){$i->seek($M*$aa);}$Lc=array();echo"<form action='' method='post' enctype='multipart/form-data'>\n";$E=array();while($a=$i->fetch_assoc()){$E[]=$a;}if($_GET["page"]!="last"){$Qa=(intval($M)&&$_a&&count($_a)<count($C)?($_=="sql"?$g->result(" SELECT FOUND_ROWS()"):$g->result("SELECT COUNT(*) FROM ($j) x")):count($E));}if(!$E){echo"<p class='message'>".lang(77)."\n";}else{$te=$r->backwardKeys($m,$Ca);echo"<table cellspacing='0' class='nowrap' onclick='tableClick(event);'>\n","<thead><tr>".(!$_a&&$C?"":"<td><input type='checkbox' id='all-page' onclick='formCheck(this, /check/);'> <a href='".h($_GET["modify"]?remove_from_uri("modify"):$_SERVER["REQUEST_URI"]."&modify=1")."'>".lang(112)."</a>");$Cd=array();$W=array();reset($C);$pb=1;foreach($E[0]as$c=>$b){$b=$_GET["columns"][key($C)];$d=$o[$C?$b["col"]:$c];$f=($d?$r->fieldName($d,$pb):"*");if($f!=""){$pb++;$Cd[$c]=$f;echo'<th><a href="'.h(remove_from_uri('(order|desc)[^=]*|page').'&order%5B0%5D='.urlencode($c).($_GET["order"][0]==$c&&!$_GET["desc"][0]?'&desc%5B0%5D=1':'')).'">'.apply_sql_function($b["fun"],$f)."</a>";}$W[$c]=$b["fun"];next($C);}$Qb=array();if($_GET["modify"]){foreach($E
as$a){foreach($a
as$c=>$b){$Qb[$c]=max($Qb[$c],min(40,strlen(utf8_decode($b))));}}}echo($te?"<th>".lang(222):"")."</thead>\n";foreach($r->rowDescriptions($E,$ea)as$ca=>$a){$md=unique_array($E[$ca],$K);$ub="";foreach($md
as$c=>$b){$ub.="&".(isset($b)?urlencode("where[".bracket_escape($c)."]")."=".urlencode($b):"null%5B%5D=".urlencode($c));}echo"<tr".odd().">".(!$_a&&$C?"":"<td>".checkbox("check[]",substr($ub,1),in_array(substr($ub,1),(array)$_POST["check"]),"","this.form['all'].checked = false; formUncheck('all-page');").(count($_a)<count($C)||information_schema(DB)?"":" <a href='".h(ME."edit=".urlencode($m).$ub)."'>".lang(112)."</a>"));foreach($a
as$c=>$b){if(isset($Cd[$c])){$d=$o[$c];if($b!=""&&(!isset($Lc[$c])||$Lc[$c]!="")){$Lc[$c]=(is_mail($b)?$Cd[$c]:"");}$x="";$b=$r->editVal($b,$d);if(!isset($b)){$b="<i>NULL</i>";}else{if(ereg('blob|bytea|raw|file',$d["type"])&&$b!=""){$x=h(ME.'download='.urlencode($m).'&field='.urlencode($c).$ub);}if($b==""){$b="&nbsp;";}elseif($zb!=""&&ereg('text|blob',$d["type"])&&is_utf8($b)){$b=shorten_utf8($b,max(0,intval($zb)));}else{$b=h($b);}if(!$x){foreach((array)$ea[$c]as$A){if(count($ea[$c])==1||count($A["source"])==1){foreach($A["source"]as$l=>$Ga){$x.=where_link($l,$A["target"][$l],$E[$ca][$Ga]);}$x=h(($A["db"]!=""?preg_replace('~([?&]db=)[^&]+~','\\1'.urlencode($A["db"]),ME):ME).'select='.urlencode($A["table"]).$x);break;}}}if($c=="COUNT(*)"){$x=h(ME."select=".urlencode($m));$l=0;foreach((array)$_GET["where"]as$w){if(!array_key_exists($w["col"],$md)){$x.=h(where_link($l++,$w["col"],$w["val"],$w["op"]));}}foreach($md
as$Na=>$w){$x.=h(where_link($l++,$Na,$w,(isset($w)?"=":"IS NULL")));}}}if(!$x){if(is_mail($b)){$x="mailto:$b";}if($gc=is_url($a[$c])){$x=($gc=="http"&&$Vb?$a[$c]:"$gc://www.adminer.org/redirect/?url=".urlencode($a[$c]));}}$U=h("val[$ub][".bracket_escape($c)."]");$p=$_POST["val"][$ub][bracket_escape($c)];$We=h(isset($p)?$p:$a[$c]);$re=strpos($b,"<i>...</i>");$we=is_utf8($b)&&!$re&&$E[$ca][$c]==$a[$c]&&!$W[$c];$xe=ereg('text|lob',$d["type"]);echo(($_GET["modify"]&&$we)||isset($p)?"<td>".($xe?"<textarea name='$U' cols='30' rows='".(substr_count($a[$c],"\n")+1)."' onkeydown='return textareaKeydown(this, event);'>$We</textarea>":"<input name='$U' value='$We' size='$Qb[$c]'>"):"<td id='$U' ondblclick=\"".($we?"selectDblClick(this, event".($xe?", 1":"").")":"alert('".h($re?lang(223):lang(224))."')").";\">".$r->selectVal($b,$x,$d));}}$r->backwardKeysPrint($te,$E[$ca]);echo"</tr>\n";}echo"</table>\n";}parse_str($_COOKIE["adminer_export"],$hd);if($E||$aa){$zd=true;if($_GET["page"]!="last"&&intval($M)&&count($_a)>=count($C)&&($Qa>=$M||$aa)){$Qa=$J["Rows"];if(!isset($Qa)||$t||2*$aa*$M>$Qa||($J["Engine"]=="InnoDB"&&$Qa<1e4)){ob_flush();flush();$Qa=$g->result("SELECT COUNT(*) FROM ".table($m).($t?" WHERE ".implode(" AND ",$t):""));}else{$zd=false;}}echo"<p class='pages'>";if(intval($M)&&$Qa>$M){$qd=floor(($Qa-1)/$M);echo'<a href="'.h(remove_from_uri("page"))."\" onclick=\"var page = +prompt('".lang(28)."', '".($aa+1)."'); if (!isNaN(page) &amp;&amp; page) location.href = this.href + (page != 1 ? '&amp;page=' + (page - 1) : ''); return false;\">".lang(28)."</a>:".pagination(0,$aa).($aa>5?" ...":"");for($l=max(1,$aa-4);$l<min($qd,$aa+5);$l++){echo
pagination($l,$aa);}echo($aa+5<$qd?" ...":"").($zd?pagination($qd,$aa):' <a href="'.h(remove_from_uri()."&page=last").'">'.lang(29)."</a>");}echo" (".($zd?"":"~ ").lang(116,$Qa).") ".checkbox("all",1,0,lang(225))."\n";if(!information_schema(DB)){echo'<fieldset><legend>',lang(30),'</legend><div>
<input type="submit" value="',lang(132),'" title="',lang(219),'">
<input type="submit" name="edit" value="',lang(30),'">
<input type="submit" name="clone" value="',lang(226),'">
<input type="submit" name="delete" value="',lang(135),'" onclick="return confirm(\'',lang(74);?> (' + (this.form['all'].checked ? <?php echo$Qa,' : formChecked(this, /check/)) + \')\');">
</div></fieldset>
';}print_fieldset("export",lang(106));echo$r->dumpOutput(1,$hd["output"])." ".$r->dumpFormat(1,$hd["format"]);echo" <input type='submit' name='export' value='".lang(106)."'>\n","</div></fieldset>\n";}print_fieldset("import",lang(227),!$E);echo"<input type='hidden' name='token' value='$L'><input type='file' name='csv_file'> ",html_select("separator",array("csv"=>"CSV,","csv;"=>"CSV;"),$hd["format"],1);echo" <input type='submit' name='import' value='".lang(228)."'>\n","</div></fieldset>\n";$r->selectEmailPrint(array_filter($Lc,'strlen'),$B);echo"</form>\n";}}}elseif(isset($_GET["variables"])){$Zb=isset($_GET["status"]);page_header($Zb?lang(68):lang(67));$Ve=($Zb?show_status():show_variables());if(!$Ve){echo"<p class='message'>".lang(77)."\n";}else{echo"<table cellspacing='0'>\n";foreach($Ve
as$c=>$b){echo"<tr>","<th><code class='jush-".$_.($Zb?"status":"set")."'>".h($c)."</code>","<td>".nbsp($b);}echo"</table>\n";}}elseif(isset($_GET["script"])){header("Content-Type: text/javascript; charset=utf-8");if($_GET["token"]!=$L){exit;}if($_GET["script"]=="db"){$Jc=array("Data_length"=>0,"Index_length"=>0,"Data_free"=>0);foreach(table_status()as$a){$U=addcslashes($a["Name"],"\\'/");echo"setHtml('Comment-$U', '".addcslashes(nbsp($a["Comment"]),"'\\")."');\n";if(!is_view($a)){foreach(array("Engine","Collation")as$c){echo"setHtml('$c-$U', '".addcslashes(nbsp($a[$c]),"'\\")."');\n";}foreach($Jc+array("Auto_increment"=>0,"Rows"=>0)as$c=>$b){if($a[$c]!=""){$b=number_format($a[$c],0,'.',lang(229));echo"setHtml('$c-$U', '".($c=="Rows"&&$a["Engine"]=="InnoDB"&&$b?"~ $b":$b)."');\n";if(isset($Jc[$c])){$Jc[$c]+=($a["Engine"]!="InnoDB"||$c!="Data_free"?$a[$c]:0);}}elseif(array_key_exists($c,$a)){echo"setHtml('$c-$U');\n";}}}}foreach($Jc
as$c=>$b){echo"setHtml('sum-$c', '".number_format($b,0,'.',lang(229))."');\n";}}else{foreach(count_tables(get_databases())as$s=>$b){echo"setHtml('tables-".addcslashes($s,"\\'/")."', '$b');\n";}}exit;}else{$Ne=array_merge((array)$_POST["tables"],(array)$_POST["views"]);if($Ne&&!$n&&!$_POST["search"]){$i=true;$za="";if($_=="sql"&&count($_POST["tables"])>1&&($_POST["drop"]||$_POST["truncate"])){queries("SET foreign_key_checks = 0");}if($_POST["truncate"]){if($_POST["tables"]){$i=truncate_tables($_POST["tables"]);}$za=lang(230);}elseif($_POST["move"]){$i=move_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$za=lang(231);}elseif($_POST["drop"]){if($_POST["views"]){$i=drop_views($_POST["views"]);}if($i&&$_POST["tables"]){$i=drop_tables($_POST["tables"]);}$za=lang(232);}elseif($_POST["tables"]&&($i=queries(($_POST["optimize"]?"OPTIMIZE":($_POST["check"]?"CHECK":($_POST["repair"]?"REPAIR":"ANALYZE")))." TABLE ".implode(", ",array_map('idf_escape',$_POST["tables"]))))){while($a=$i->fetch_assoc()){$za.="<b>".h($a["Table"])."</b>: ".h($a["Msg_text"])."<br>";}}queries_redirect(substr(ME,0,-1),$za,$i);}page_header(($_GET["ns"]==""?lang(60).": ".h(DB):lang(75).": ".h($_GET["ns"])),$n,true);echo'<p>'.($_GET["ns"]==""?'<a href="'.h(ME).'database=">'.lang(154)."</a>\n":"");if(support("scheme")){echo"<a href='".h(ME)."scheme='>".($_GET["ns"]!=""?lang(159):lang(160))."</a>\n";}if($_GET["ns"]!==""){echo'<a href="'.h(ME).'schema=">'.lang(105)."</a>\n","<h3>".lang(233)."</h3>\n";$Sc=tables_list();if(!$Sc){echo"<p class='message'>".lang(4)."\n";}else{echo"<form action='' method='post'>\n","<p>".lang(234).": <input name='query' value='".h($_POST["query"])."'> <input type='submit' name='search' value='".lang(35)."'>\n";if($_POST["search"]&&$_POST["query"]!=""){search_tables();}echo"<table cellspacing='0' class='nowrap' onclick='tableClick(event);'>\n",'<thead><tr class="wrap"><td><input id="check-all" type="checkbox" onclick="formCheck(this, /^(tables|views)\[/);"><th>'.lang(94).'<td>'.lang(235).'<td>'.lang(71).'<td>'.lang(236).'<td>'.lang(237).'<td>'.lang(238).'<td>'.lang(86).'<td>'.lang(239).(support("comment")?'<td>'.lang(88):'')."</thead>\n";foreach($Sc
as$f=>$y){$Ac=(isset($y)&&!eregi("table",$y));echo'<tr'.odd().'><td>'.checkbox(($Ac?"views[]":"tables[]"),$f,in_array($f,$Ne,true),"","formUncheck('check-all');"),'<th><a href="'.h(ME).'table='.urlencode($f).'">'.h($f).'</a>';if($Ac){echo'<td colspan="6"><a href="'.h(ME)."view=".urlencode($f).'">'.lang(93).'</a>','<td align="right"><a href="'.h(ME)."select=".urlencode($f).'">?</a>';}else{echo"<td id='Engine-".h($f)."'>&nbsp;<td id='Collation-".h($f)."'>&nbsp;";foreach(array("Data_length"=>"create","Index_length"=>"indexes","Data_free"=>"edit","Auto_increment"=>"auto_increment=1&create","Rows"=>"select")as$c=>$x){echo"<td align='right'><a href='".h(ME."$x=").urlencode($f)."' id='$c-".h($f)."'>?</a>";}}echo(support("comment")?"<td id='Comment-".h($f)."'>&nbsp;":"");}echo"<tr><td>&nbsp;<th>".lang(240,count($Sc)),"<td>".nbsp($g->result("SELECT @@storage_engine")),"<td>".nbsp(db_collation(DB,collations()));foreach(array("Data_length","Index_length","Data_free")as$c){echo"<td align='right' id='sum-$c'>&nbsp;";}echo"</table>\n";if(!information_schema(DB)){echo"<p><input type='hidden' name='token' value='$L'>".($_=="sql"?"<input type='submit' value='".lang(241)."'> <input type='submit' name='optimize' value='".lang(242)."'> <input type='submit' name='check' value='".lang(243)."'> <input type='submit' name='repair' value='".lang(244)."'> ":"")."<input type='submit' name='truncate' value='".lang(245)."' onclick=\"return confirm('".lang(74)." (' + formChecked(this, /tables/) + ')');\"> <input type='submit' name='drop' value='".lang(73)."' onclick=\"return confirm('".lang(74)." (' + formChecked(this, /tables|views/) + ')');\">\n";$z=(support("scheme")?schemas():get_databases());if(count($z)!=1&&$_!="sqlite"){$s=(isset($_POST["target"])?$_POST["target"]:(support("scheme")?$_GET["ns"]:DB));echo"<p>".lang(246).($z?": ".html_select("target",$z,$s):': <input name="target" value="'.h($s).'">')." <input type='submit' name='move' value='".lang(247)."'>\n";}}echo"</form>\n";}echo'<p><a href="'.h(ME).'create=">'.lang(139)."</a>\n";if(support("view")){echo'<a href="'.h(ME).'view=">'.lang(174)."</a>\n";}if(support("routine")){echo"<h3>".lang(109)."</h3>\n";$De=routines();if($De){echo"<table cellspacing='0'>\n",'<thead><tr><th>'.lang(175).'<td>'.lang(83).'<td>'.lang(192)."<td>&nbsp;</thead>\n";odd('');foreach($De
as$a){echo'<tr'.odd().'>','<th><a href="'.h(ME).($a["ROUTINE_TYPE"]=="FUNCTION"?'callf=':'call=').urlencode($a["ROUTINE_NAME"]).'">'.h($a["ROUTINE_NAME"]).'</a>','<td>'.h($a["ROUTINE_TYPE"]),'<td>'.h($a["DTD_IDENTIFIER"]),'<td><a href="'.h(ME).($a["ROUTINE_TYPE"]=="FUNCTION"?'function=':'procedure=').urlencode($a["ROUTINE_NAME"]).'">'.lang(101)."</a>";}echo"</table>\n";}echo'<p><a href="'.h(ME).'procedure=">'.lang(191).'</a> <a href="'.h(ME).'function=">'.lang(190)."</a>\n";}if(support("sequence")){echo"<h3>".lang(248)."</h3>\n";$Rd=get_vals("SELECT sequence_name FROM information_schema.sequences WHERE sequence_schema = current_schema()");if($Rd){echo"<table cellspacing='0'>\n","<thead><tr><th>".lang(175)."</thead>\n";odd('');foreach($Rd
as$b){echo"<tr".odd()."><th><a href='".h(ME)."sequence=".urlencode($b)."'>".h($b)."</a>\n";}echo"</table>\n";}echo"<p><a href='".h(ME)."sequence='>".lang(197)."</a>\n";}if(support("type")){echo"<h3>".lang(9)."</h3>\n";$T=types();if($T){echo"<table cellspacing='0'>\n","<thead><tr><th>".lang(175)."</thead>\n";odd('');foreach($T
as$b){echo"<tr".odd()."><th><a href='".h(ME)."type=".urlencode($b)."'>".h($b)."</a>\n";}echo"</table>\n";}echo"<p><a href='".h(ME)."type='>".lang(201)."</a>\n";}if(support("event")){echo"<h3>".lang(110)."</h3>\n";$E=get_rows("SHOW EVENTS");if($E){echo"<table cellspacing='0'>\n","<thead><tr><th>".lang(175)."<td>".lang(249)."<td>".lang(181)."<td>".lang(182)."</thead>\n";foreach($E
as$a){echo"<tr>",'<th><a href="'.h(ME).'event='.urlencode($a["Name"]).'">'.h($a["Name"])."</a>","<td>".($a["Execute at"]?lang(250)."<td>".$a["Execute at"]:lang(183)." ".$a["Interval value"]." ".$a["Interval field"]."<td>$a[Starts]"),"<td>$a[Ends]";}echo"</table>\n";}echo'<p><a href="'.h(ME).'event=">'.lang(180)."</a>\n";}if($Sc){page_footer();echo"<script type='text/javascript' src='".h(ME."script=db&token=$L")."'></script>\n";exit;}}}page_footer();
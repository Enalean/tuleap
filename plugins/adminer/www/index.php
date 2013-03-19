<?php
/** Adminer - Compact database management
* @link http://www.adminer.org/
* @author Jakub Vrana, http://www.vrana.cz/
* @copyright 2007 Jakub Vrana
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
* @version 3.5.1
*/error_reporting(6135);$Xb=!ereg('^(unsafe_raw)?$',ini_get("filter.default"));if($Xb||ini_get("filter.default_flags")){foreach(array('_GET','_POST','_COOKIE','_SERVER')as$X){$Ff=filter_input_array(constant("INPUT$X"),FILTER_UNSAFE_RAW);if($Ff)$$X=$Ff;}}if(isset($_GET["file"])){header("Expires: ".gmdate("D, d M Y H:i:s",time()+365*24*60*60)." GMT");if($_GET["file"]=="favicon.ico"){header("Content-Type: image/x-icon");echo
base64_decode("AAABAAEAEBAQAAEABAAoAQAAFgAAACgAAAAQAAAAIAAAAAEABAAAAAAAwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA////AAAA/wBhTgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAERERAAAAAAETMzEQAAAAATERExAAAAABMRETEAAAAAExERMQAAAAATERExAAAAABMRETEAAAAAEzMzMREREQATERExEhEhABEzMxEhEREAAREREhERIRAAAAARIRESEAAAAAESEiEQAAAAABEREQAAAAAAAAAAD//9UAwP/VAIB/AACAf/AAgH+kAIB/gACAfwAAgH8AAIABAACAAf8AgAH/AMAA/wD+AP8A/wAIAf+B1QD//9UA");}elseif($_GET["file"]=="default.css"){header("Content-Type: text/css; charset=utf-8");echo'body{color:#000;background:#fff;font:90%/1.25 Verdana,Arial,Helvetica,sans-serif;margin:0;}a{color:blue;}a:visited{color:navy;}a:hover{color:red;}a.text{text-decoration:none;}h1{font-size:150%;margin:0;padding:.8em 1em;border-bottom:1px solid #999;font-weight:normal;color:#777;background:#eee;}h2{font-size:150%;margin:0 0 20px -18px;padding:.8em 1em;border-bottom:1px solid #000;color:#000;font-weight:normal;background:#ddf;}h3{font-weight:normal;font-size:130%;margin:1em 0 0;}form{margin:0;}table{margin:1em 20px 0 0;border:0;border-top:1px solid #999;border-left:1px solid #999;font-size:90%;}td,th{border:0;border-right:1px solid #999;border-bottom:1px solid #999;padding:.2em .3em;}th{background:#eee;text-align:left;}thead th{text-align:center;}thead td,thead th{background:#ddf;}fieldset{display:inline;vertical-align:top;padding:.5em .8em;margin:.8em .5em 0 0;border:1px solid #999;}p{margin:.8em 20px 0 0;}img{vertical-align:middle;border:0;}td img{max-width:200px;max-height:200px;}code{background:#eee;}tbody tr:hover td,tbody tr:hover th{background:#eee;}pre{margin:1em 0 0;}input[type=image]{vertical-align:middle;}.version{color:#777;font-size:67%;}.js .hidden,.nojs .jsonly{display:none;}.js .column{position:absolute;background:#ddf;padding:.3em 1ex .3em 0;margin-top:-.3em;}.nowrap td,.nowrap th,td.nowrap{white-space:pre;}.wrap td{white-space:normal;}.error{color:red;background:#fee;}.error b{background:#fff;font-weight:normal;}.message{color:green;background:#efe;}.error,.message{padding:.5em .8em;margin:1em 20px 0 0;}.char{color:#007F00;}.date{color:#7F007F;}.enum{color:#007F7F;}.binary{color:red;}.odd td{background:#F5F5F5;}.js .checked td,.js .checked th{background:#ddf;}.time{color:silver;font-size:70%;}.function{text-align:right;}.number{text-align:right;}.datetime{text-align:right;}.type{width:15ex;width:auto\\9;}.options select{width:20ex;width:auto\\9;}.active{font-weight:bold;}.sqlarea{width:98%;}.icon{width:18px;height:18px;}#menu{position:absolute;margin:10px 0 0;padding:0 0 30px 0;top:2em;left:0;width:19em;white-space:nowrap;}#menu p{padding:.8em 1em;margin:0;border-bottom:1px solid #ccc;}#dbs{overflow:hidden;}#logins,#tables{overflow:auto;}#logins a,#tables a{background:#fff;}#content{margin:2em 0 0 21em;padding:10px 20px 20px 0;}#lang{position:absolute;top:0;left:0;line-height:1.8em;padding:.3em 1em;}#breadcrumb{white-space:nowrap;position:absolute;top:0;left:21em;background:#eee;height:2em;line-height:1.8em;padding:0 1em;margin:0 0 0 -18px;}#h1{color:#777;text-decoration:none;font-style:italic;}#version{font-size:67%;color:red;}#schema{margin-left:60px;position:relative;-moz-user-select:none;-webkit-user-select:none;}#schema .table{border:1px solid silver;padding:0 2px;cursor:move;position:absolute;}#schema .references{position:absolute;}.rtl h2{margin:0 -18px 20px 0;}.rtl p,.rtl table,.rtl .error,.rtl .message{margin:1em 0 0 20px;}.rtl #content{margin:2em 21em 0 0;padding:10px 0 20px 20px;}.rtl #breadcrumb{left:auto;right:21em;margin:0 -18px 0 0;}.rtl #lang,.rtl #menu{left:auto;right:0;}@media print{#lang,#menu{display:none;}#content{margin-left:1em;}#breadcrumb{left:1em;}.nowrap td,.nowrap th,td.nowrap{white-space:normal;}}';}elseif($_GET["file"]=="functions.js"){header("Content-Type: text/javascript; charset=utf-8");?>function
toggle(id){var
el=document.getElementById(id);el.className=(el.className=='hidden'?'':'hidden');return true;}function
cookie(assign,days){var
date=new
Date();date.setDate(date.getDate()+days);document.cookie=assign+'; expires='+date;}function
verifyVersion(){cookie('adminer_version=0',1);var
script=document.createElement('script');script.src=location.protocol+'//www.adminer.org/version.php';document.body.appendChild(script);}function
selectValue(select){var
selected=select.options[select.selectedIndex];return((selected.attributes.value||{}).specified?selected.value:selected.text);}function
parentTag(el,tag){var
re=new
RegExp('^'+tag+'$','i');while(!re.test(el.tagName)){el=el.parentNode;}return el;}function
trCheck(el){var
tr=parentTag(el,'tr');tr.className=tr.className.replace(/(^|\s)checked(\s|$)/,'$2')+(el.checked?' checked':'');}function
formCheck(el,name){var
elems=el.form.elements;for(var
i=0;i<elems.length;i++){if(name.test(elems[i].name)){elems[i].checked=el.checked;trCheck(elems[i]);}}}function
tableCheck(){var
tables=document.getElementsByTagName('table');for(var
i=0;i<tables.length;i++){if(/(^|\s)checkable(\s|$)/.test(tables[i].className)){var
trs=tables[i].getElementsByTagName('tr');for(var
j=0;j<trs.length;j++){trCheck(trs[j].firstChild.firstChild);}}}}function
formUncheck(id){var
el=document.getElementById(id);el.checked=false;trCheck(el);}function
formChecked(el,name){var
checked=0;var
elems=el.form.elements;for(var
i=0;i<elems.length;i++){if(name.test(elems[i].name)&&elems[i].checked){checked++;}}return checked;}function
tableClick(event){var
click=(!window.getSelection||getSelection().isCollapsed);var
el=event.target||event.srcElement;while(!/^tr$/i.test(el.tagName)){if(/^(table|a|input|textarea)$/i.test(el.tagName)){if(el.type!='checkbox'){return;}checkboxClick(event,el);click=false;}el=el.parentNode;}el=el.firstChild.firstChild;if(click){el.click&&el.click();el.onclick&&el.onclick();}trCheck(el);}var
lastChecked;function
checkboxClick(event,el){if(!el.name){return;}if(event.shiftKey&&(!lastChecked||lastChecked.name==el.name)){var
checked=(lastChecked?lastChecked.checked:true);var
inputs=parentTag(el,'table').getElementsByTagName('input');var
checking=!lastChecked;for(var
i=0;i<inputs.length;i++){var
input=inputs[i];if(input.name===el.name){if(checking){input.checked=checked;trCheck(input);}if(input===el||input===lastChecked){if(checking){break;}checking=true;}}}}else{lastChecked=el;}}function
setHtml(id,html){var
el=document.getElementById(id);if(el){if(html==undefined){el.parentNode.innerHTML='&nbsp;';}else{el.innerHTML=html;}}}function
nodePosition(el){var
pos=0;while(el=el.previousSibling){pos++;}return pos;}function
pageClick(href,page,event){if(!isNaN(page)&&page){href+=(page!=1?'&page='+(page-1):'');location.href=href;}}function
menuOver(el){el.style.overflow='visible';}function
menuOut(el){el.style.overflow='auto';}function
selectAddRow(field){field.onchange=function(){selectFieldChange(field.form);};field.onchange();var
row=field.parentNode.cloneNode(true);var
selects=row.getElementsByTagName('select');for(var
i=0;i<selects.length;i++){selects[i].name=selects[i].name.replace(/[a-z]\[\d+/,'$&1');selects[i].selectedIndex=0;}var
inputs=row.getElementsByTagName('input');if(inputs.length){inputs[0].name=inputs[0].name.replace(/[a-z]\[\d+/,'$&1');inputs[0].value='';inputs[0].className='';}field.parentNode.parentNode.appendChild(row);}function
columnMouse(el,className){var
spans=el.getElementsByTagName('span');for(var
i=0;i<spans.length;i++){if(/column/.test(spans[i].className)){spans[i].className='column'+(className||'');}}}function
selectSearch(name){var
el=document.getElementById('fieldset-search');el.className='';var
divs=el.getElementsByTagName('div');for(var
i=0;i<divs.length;i++){var
div=divs[i];if(/select/i.test(div.firstChild.tagName)&&selectValue(div.firstChild)==name){break;}}if(i==divs.length){div.firstChild.value=name;div.firstChild.onchange();}div.lastChild.focus();}function
bodyKeydown(event,button){var
target=event.target||event.srcElement;if(event.ctrlKey&&(event.keyCode==13||event.keyCode==10)&&!event.altKey&&!event.metaKey&&/select|textarea|input/i.test(target.tagName)){target.blur();if(button){target.form[button].click();}else{target.form.submit();}return false;}return true;}function
editingKeydown(event){if((event.keyCode==40||event.keyCode==38)&&event.ctrlKey&&!event.altKey&&!event.metaKey){var
target=event.target||event.srcElement;var
sibling=(event.keyCode==40?'nextSibling':'previousSibling');var
el=target.parentNode.parentNode[sibling];if(el&&(/^tr$/i.test(el.tagName)||(el=el[sibling]))&&/^tr$/i.test(el.tagName)&&(el=el.childNodes[nodePosition(target.parentNode)])&&(el=el.childNodes[nodePosition(target)])){el.focus();}return false;}if(event.shiftKey&&!bodyKeydown(event,'insert')){eventStop(event);return false;}return true;}function
functionChange(select){var
input=select.form[select.name.replace(/^function/,'fields')];if(selectValue(select)){if(input.origMaxLength===undefined){input.origMaxLength=input.maxLength;}input.removeAttribute('maxlength');}else
if(input.origMaxLength>=0){input.maxLength=input.origMaxLength;}}function
ajax(url,callback,data){var
request=(window.XMLHttpRequest?new
XMLHttpRequest():(window.ActiveXObject?new
ActiveXObject('Microsoft.XMLHTTP'):false));if(request){request.open((data?'POST':'GET'),url);if(data){request.setRequestHeader('Content-Type','application/x-www-form-urlencoded');}request.setRequestHeader('X-Requested-With','XMLHttpRequest');request.onreadystatechange=function(){if(request.readyState==4){callback(request);}};request.send(data);}return request;}function
ajaxSetHtml(url){return ajax(url,function(request){if(request.status){var
data=eval('('+request.responseText+')');for(var
key
in
data){setHtml(key,data[key]);}}});}function
selectDblClick(td,event,text){if(/input|textarea/i.test(td.firstChild.tagName)){return;}var
original=td.innerHTML;var
input=document.createElement(text?'textarea':'input');input.onkeydown=function(event){if(!event){event=window.event;}if(event.keyCode==27&&!(event.ctrlKey||event.shiftKey||event.altKey||event.metaKey)){td.innerHTML=original;}};var
pos=event.rangeOffset;var
value=td.firstChild.alt||td.textContent||td.innerText;input.style.width=Math.max(td.clientWidth-14,20)+'px';if(text){var
rows=1;value.replace(/\n/g,function(){rows++;});input.rows=rows;}if(value=='\u00A0'||td.getElementsByTagName('i').length){value='';}if(document.selection){var
range=document.selection.createRange();range.moveToPoint(event.clientX,event.clientY);var
range2=range.duplicate();range2.moveToElementText(td);range2.setEndPoint('EndToEnd',range);pos=range2.text.length;}td.innerHTML='';td.appendChild(input);input.focus();if(text==2){return ajax(location.href+'&'+encodeURIComponent(td.id)+'=',function(request){if(request.status){input.value=request.responseText;input.name=td.id;}});}input.value=value;input.name=td.id;input.selectionStart=pos;input.selectionEnd=pos;if(document.selection){var
range=document.selection.createRange();range.moveEnd('character',-input.value.length+pos);range.select();}}function
eventStop(event){if(event.stopPropagation){event.stopPropagation();}else{event.cancelBubble=true;}}var
jushRoot=location.protocol + '//www.adminer.org/static/';function
bodyLoad(version){if(jushRoot){var
link=document.createElement('link');link.rel='stylesheet';link.type='text/css';link.href=jushRoot+'jush.css';document.getElementsByTagName('head')[0].appendChild(link);var
script=document.createElement('script');script.src=jushRoot+'jush.js';script.onload=function(){if(window.jush){jush.create_links=' target="_blank" rel="noreferrer"';jush.urls.sql_sqlset=jush.urls.sql[0]=jush.urls.sqlset[0]=jush.urls.sqlstatus[0]='http://dev.mysql.com/doc/refman/'+version+'/en/$key';var
pgsql='http://www.postgresql.org/docs/'+version+'/static/';jush.urls.pgsql_pgsqlset=jush.urls.pgsql[0]=pgsql+'$key';jush.urls.pgsqlset[0]=pgsql+'runtime-config-$key.html#GUC-$1';if(window.jushLinks){jush.custom_links=jushLinks;}jush.highlight_tag('code',0);}};script.onreadystatechange=function(){if(/^(loaded|complete)$/.test(script.readyState)){script.onload();}};document.body.appendChild(script);}}function
formField(form,name){for(var
i=0;i<form.length;i++){if(form[i].name==name){return form[i];}}}function
typePassword(el,disable){try{el.type=(disable?'text':'password');}catch(e){}}function
loginDriver(driver){var
trs=parentTag(driver,'table').rows;for(var
i=1;i<trs.length-1;i++){trs[i].className=(/sqlite/.test(driver.value)?'hidden':'');}}function
textareaKeydown(target,event){if(!event.shiftKey&&!event.altKey&&!event.ctrlKey&&!event.metaKey){if(event.keyCode==9){if(target.setSelectionRange){var
start=target.selectionStart;var
scrolled=target.scrollTop;target.value=target.value.substr(0,start)+'\t'+target.value.substr(target.selectionEnd);target.setSelectionRange(start+1,start+1);target.scrollTop=scrolled;return false;}else
if(target.createTextRange){document.selection.createRange().text='\t';return false;}}if(event.keyCode==27){var
els=target.form.elements;for(var
i=1;i<els.length;i++){if(els[i-1]==target){els[i].focus();break;}}return false;}}return true;}function
selectFieldChange(form){var
ok=(function(){var
inputs=form.getElementsByTagName('input');for(var
i=0;i<inputs.length;i++){if(inputs[i].value&&/^fulltext/.test(inputs[i].name)){return true;}}var
ok=form.limit.value;var
selects=form.getElementsByTagName('select');var
group=false;var
columns={};for(var
i=0;i<selects.length;i++){var
select=selects[i];var
col=selectValue(select);var
match=/^(where.+)col\]/.exec(select.name);if(match){var
op=selectValue(form[match[1]+'op]']);var
val=form[match[1]+'val]'].value;if(col
in
indexColumns&&(!/LIKE|REGEXP/.test(op)||(op=='LIKE'&&val.charAt(0)!='%'))){return true;}else
if(col||val){ok=false;}}if((match=/^(columns.+)fun\]/.exec(select.name))){if(/^(avg|count|count distinct|group_concat|max|min|sum)$/.test(col)){group=true;}var
val=selectValue(form[match[1]+'col]']);if(val){columns[col&&col!='count'?'':val]=1;}}if(col&&/^order/.test(select.name)){if(!(col
in
indexColumns)){ok=false;}break;}}if(group){for(var
col
in
columns){if(!(col
in
indexColumns)){ok=false;}}}return ok;})();setHtml('noindex',(ok?'':'!'));}var
added='.',rowCount;function
delimiterEqual(val,a,b){return(val==a+'_'+b||val==a+b||val==a+b.charAt(0).toUpperCase()+b.substr(1));}function
idfEscape(s){return s.replace(/`/,'``');}function
editingNameChange(field){var
name=field.name.substr(0,field.name.length-7);var
type=formField(field.form,name+'[type]');var
opts=type.options;var
candidate;var
val=field.value;for(var
i=opts.length;i--;){var
match=/(.+)`(.+)/.exec(opts[i].value);if(!match){if(candidate&&i==opts.length-2&&val==opts[candidate].value.replace(/.+`/,'')&&name=='fields[1]'){return;}break;}var
table=match[1];var
column=match[2];var
tables=[table,table.replace(/s$/,''),table.replace(/es$/,'')];for(var
j=0;j<tables.length;j++){table=tables[j];if(val==column||val==table||delimiterEqual(val,table,column)||delimiterEqual(val,column,table)){if(candidate){return;}candidate=i;break;}}}if(candidate){type.selectedIndex=candidate;type.onchange();}}function
editingAddRow(button,allowed,focus){if(allowed&&rowCount>=allowed){return false;}var
match=/(\d+)(\.\d+)?/.exec(button.name);var
x=match[0]+(match[2]?added.substr(match[2].length):added)+'1';var
row=parentTag(button,'tr');var
row2=row.cloneNode(true);var
tags=row.getElementsByTagName('select');var
tags2=row2.getElementsByTagName('select');for(var
i=0;i<tags.length;i++){tags2[i].name=tags[i].name.replace(/([0-9.]+)/,x);tags2[i].selectedIndex=tags[i].selectedIndex;}tags=row.getElementsByTagName('input');tags2=row2.getElementsByTagName('input');var
input=tags2[0];for(var
i=0;i<tags.length;i++){if(tags[i].name=='auto_increment_col'){tags2[i].value=x;tags2[i].checked=false;}tags2[i].name=tags[i].name.replace(/([0-9.]+)/,x);if(/\[(orig|field|comment|default)/.test(tags[i].name)){tags2[i].value='';}if(/\[(has_default)/.test(tags[i].name)){tags2[i].checked=false;}}tags[0].onchange=function(){editingNameChange(tags[0]);};row.parentNode.insertBefore(row2,row.nextSibling);if(focus){input.onchange=function(){editingNameChange(input);};input.focus();}added+='0';rowCount++;return true;}function
editingRemoveRow(button){var
field=formField(button.form,button.name.replace(/drop_col(.+)/,'fields$1[field]'));field.parentNode.removeChild(field);parentTag(button,'tr').style.display='none';return true;}var
lastType='';function
editingTypeChange(type){var
name=type.name.substr(0,type.name.length-6);var
text=selectValue(type);for(var
i=0;i<type.form.elements.length;i++){var
el=type.form.elements[i];if(el.name==name+'[length]'&&!((/(char|binary)$/.test(lastType)&&/(char|binary)$/.test(text))||(/(enum|set)$/.test(lastType)&&/(enum|set)$/.test(text)))){el.value='';}if(lastType=='timestamp'&&el.name==name+'[has_default]'&&/timestamp/i.test(formField(type.form,name+'[default]').value)){el.checked=false;}if(el.name==name+'[collation]'){el.className=(/(char|text|enum|set)$/.test(text)?'':'hidden');}if(el.name==name+'[unsigned]'){el.className=(/(int|float|double|decimal)$/.test(text)?'':'hidden');}if(el.name==name+'[on_delete]'){el.className=(/`/.test(text)?'':'hidden');}}}function
editingLengthFocus(field){var
td=field.parentNode;if(/(enum|set)$/.test(selectValue(td.previousSibling.firstChild))){var
edit=document.getElementById('enum-edit');var
val=field.value;edit.value=(/^'.+','.+'$/.test(val)?val.substr(1,val.length-2).replace(/','/g,"\n").replace(/''/g,"'"):val);td.appendChild(edit);field.style.display='none';edit.style.display='inline';edit.focus();}}function
editingLengthBlur(edit){var
field=edit.parentNode.firstChild;var
val=edit.value;field.value=(/\n/.test(val)?"'"+val.replace(/\n+$/,'').replace(/'/g,"''").replace(/\n/g,"','")+"'":val);field.style.display='inline';edit.style.display='none';}function
columnShow(checked,column){var
trs=document.getElementById('edit-fields').getElementsByTagName('tr');for(var
i=0;i<trs.length;i++){trs[i].getElementsByTagName('td')[column].className=(checked?'':'hidden');}}function
partitionByChange(el){var
partitionTable=/RANGE|LIST/.test(selectValue(el));el.form['partitions'].className=(partitionTable||!el.selectedIndex?'hidden':'');document.getElementById('partition-table').className=(partitionTable?'':'hidden');}function
partitionNameChange(el){var
row=parentTag(el,'tr').cloneNode(true);row.firstChild.firstChild.value='';parentTag(el,'table').appendChild(row);el.onchange=function(){};}function
foreignAddRow(field){field.onchange=function(){};var
row=parentTag(field,'tr').cloneNode(true);var
selects=row.getElementsByTagName('select');for(var
i=0;i<selects.length;i++){selects[i].name=selects[i].name.replace(/\]/,'1$&');selects[i].selectedIndex=0;}parentTag(field,'table').appendChild(row);}function
indexesAddRow(field){field.onchange=function(){};var
row=parentTag(field,'tr').cloneNode(true);var
selects=row.getElementsByTagName('select');for(var
i=0;i<selects.length;i++){selects[i].name=selects[i].name.replace(/indexes\[\d+/,'$&1');selects[i].selectedIndex=0;}var
inputs=row.getElementsByTagName('input');for(var
i=0;i<inputs.length;i++){inputs[i].name=inputs[i].name.replace(/indexes\[\d+/,'$&1');inputs[i].value='';}parentTag(field,'table').appendChild(row);}function
indexesChangeColumn(field,prefix){var
columns=parentTag(field,'td').getElementsByTagName('select');var
names=[];for(var
i=0;i<columns.length;i++){var
value=selectValue(columns[i]);if(value){names.push(value);}}field.form[field.name.replace(/\].*/,'][name]')].value=prefix+names.join('_');}function
indexesAddColumn(field,prefix){field.onchange=function(){indexesChangeColumn(field,prefix);};var
select=field.form[field.name.replace(/\].*/,'][type]')];if(!select.selectedIndex){select.selectedIndex=3;select.onchange();}var
column=field.parentNode.cloneNode(true);select=column.getElementsByTagName('select')[0];select.name=select.name.replace(/\]\[\d+/,'$&1');select.selectedIndex=0;var
input=column.getElementsByTagName('input')[0];input.name=input.name.replace(/\]\[\d+/,'$&1');input.value='';parentTag(field,'td').appendChild(column);field.onchange();}var
that,x,y;function
schemaMousedown(el,event){if((event.which?event.which:event.button)==1){that=el;x=event.clientX-el.offsetLeft;y=event.clientY-el.offsetTop;}}function
schemaMousemove(ev){if(that!==undefined){ev=ev||event;var
left=(ev.clientX-x)/em;var
top=(ev.clientY-y)/em;var
divs=that.getElementsByTagName('div');var
lineSet={};for(var
i=0;i<divs.length;i++){if(divs[i].className=='references'){var
div2=document.getElementById((/^refs/.test(divs[i].id)?'refd':'refs')+divs[i].id.substr(4));var
ref=(tablePos[divs[i].title]?tablePos[divs[i].title]:[div2.parentNode.offsetTop/em,0]);var
left1=-1;var
id=divs[i].id.replace(/^ref.(.+)-.+/,'$1');if(divs[i].parentNode!=div2.parentNode){left1=Math.min(0,ref[1]-left)-1;divs[i].style.left=left1+'em';divs[i].getElementsByTagName('div')[0].style.width=-left1+'em';var
left2=Math.min(0,left-ref[1])-1;div2.style.left=left2+'em';div2.getElementsByTagName('div')[0].style.width=-left2+'em';}if(!lineSet[id]){var
line=document.getElementById(divs[i].id.replace(/^....(.+)-.+$/,'refl$1'));var
top1=top+divs[i].offsetTop/em;var
top2=top+div2.offsetTop/em;if(divs[i].parentNode!=div2.parentNode){top2+=ref[0]-top;line.getElementsByTagName('div')[0].style.height=Math.abs(top1-top2)+'em';}line.style.left=(left+left1)+'em';line.style.top=Math.min(top1,top2)+'em';lineSet[id]=true;}}}that.style.left=left+'em';that.style.top=top+'em';}}function
schemaMouseup(ev,db){if(that!==undefined){ev=ev||event;tablePos[that.firstChild.firstChild.firstChild.data]=[(ev.clientY-y)/em,(ev.clientX-x)/em];that=undefined;var
s='';for(var
key
in
tablePos){s+='_'+key+':'+Math.round(tablePos[key][0]*10000)/10000+'x'+Math.round(tablePos[key][1]*10000)/10000;}s=encodeURIComponent(s.substr(1));var
link=document.getElementById('schema-link');link.href=link.href.replace(/[^=]+$/,'')+s;cookie('adminer_schema-'+db+'='+s,30);}}<?php
}else{header("Content-Type: image/gif");switch($_GET["file"]){case"plus.gif":echo
base64_decode("R0lGODdhEgASAKEAAO7u7gAAAJmZmQAAACwAAAAAEgASAAACIYSPqcvtD00I8cwqKb5v+q8pIAhxlRmhZYi17iPE8kzLBQA7");break;case"cross.gif":echo
base64_decode("R0lGODdhEgASAKEAAO7u7gAAAJmZmQAAACwAAAAAEgASAAACI4SPqcvtDyMKYdZGb355wy6BX3dhlOEx57FK7gtHwkzXNl0AADs=");break;case"up.gif":echo
base64_decode("R0lGODdhEgASAKEAAO7u7gAAAJmZmQAAACwAAAAAEgASAAACIISPqcvtD00IUU4K730T9J5hFTiKEXmaYcW2rgDH8hwXADs=");break;case"down.gif":echo
base64_decode("R0lGODdhEgASAKEAAO7u7gAAAJmZmQAAACwAAAAAEgASAAACIISPqcvtD00I8cwqKb5bV/5cosdMJtmcHca2lQDH8hwXADs=");break;case"arrow.gif":echo
base64_decode("R0lGODlhCAAKAIAAAICAgP///yH5BAEAAAEALAAAAAAIAAoAAAIPBIJplrGLnpQRqtOy3rsAADs=");break;}}exit;}function
connection(){global$f;return$f;}function
adminer(){global$b;return$b;}function
idf_unescape($sc){$Ic=substr($sc,-1);return
str_replace($Ic.$Ic,$Ic,substr($sc,1,-1));}function
escape_string($X){return
substr(q($X),1,-1);}function
remove_slashes($ie,$Xb=false){if(get_magic_quotes_gpc()){while(list($x,$X)=each($ie)){foreach($X
as$Dc=>$W){unset($ie[$x][$Dc]);if(is_array($W)){$ie[$x][stripslashes($Dc)]=$W;$ie[]=&$ie[$x][stripslashes($Dc)];}else$ie[$x][stripslashes($Dc)]=($Xb?$W:stripslashes($W));}}}}function
bracket_escape($sc,$xa=false){static$tf=array(':'=>':1',']'=>':2','['=>':3');return
strtr($sc,($xa?array_flip($tf):$tf));}function
h($Q){return
htmlspecialchars(str_replace("\0","",$Q),ENT_QUOTES);}function
nbsp($Q){return(trim($Q)!=""?h($Q):"&nbsp;");}function
nl_br($Q){return
str_replace("\n","<br>",$Q);}function
checkbox($D,$Y,$Ha,$Gc="",$xd="",$Cc=false){static$s=0;$s++;$J="<input type='checkbox' name='$D' value='".h($Y)."'".($Ha?" checked":"").($xd?' onclick="'.h($xd).'"':'').($Cc?" class='jsonly'":"")." id='checkbox-$s'>";return($Gc!=""?"<label for='checkbox-$s'>$J".h($Gc)."</label>":$J);}function
optionlist($_d,$Fe=null,$Lf=false){$J="";foreach($_d
as$Dc=>$W){$Ad=array($Dc=>$W);if(is_array($W)){$J.='<optgroup label="'.h($Dc).'">';$Ad=$W;}foreach($Ad
as$x=>$X)$J.='<option'.($Lf||is_string($x)?' value="'.h($x).'"':'').(($Lf||is_string($x)?(string)$x:$X)===$Fe?' selected':'').'>'.h($X);if(is_array($W))$J.='</optgroup>';}return$J;}function
html_select($D,$_d,$Y="",$wd=true){if($wd)return"<select name='".h($D)."'".(is_string($wd)?' onchange="'.h($wd).'"':"").">".optionlist($_d,$Y)."</select>";$J="";foreach($_d
as$x=>$X)$J.="<label><input type='radio' name='".h($D)."' value='".h($x)."'".($x==$Y?" checked":"").">".h($X)."</label>";return$J;}function
confirm($Za=""){return" onclick=\"return confirm('".'Are you sure?'.($Za?" (' + $Za + ')":"")."');\"";}function
print_fieldset($s,$Nc,$Rf=false,$xd=""){echo"<fieldset><legend><a href='#fieldset-$s' onclick=\"".h($xd)."return !toggle('fieldset-$s');\">$Nc</a></legend><div id='fieldset-$s'".($Rf?"":" class='hidden'").">\n";}function
bold($Ba){return($Ba?" class='active'":"");}function
odd($J=' class="odd"'){static$r=0;if(!$J)$r=-1;return($r++%
2?$J:'');}function
js_escape($Q){return
addcslashes($Q,"\r\n'\\/");}function
json_row($x,$X=null){static$Yb=true;if($Yb)echo"{";if($x!=""){echo($Yb?"":",")."\n\t\"".addcslashes($x,"\r\n\"\\").'": '.($X!==null?'"'.addcslashes($X,"\r\n\"\\").'"':'undefined');$Yb=false;}else{echo"\n}\n";$Yb=true;}}function
ini_bool($wc){$X=ini_get($wc);return(eregi('^(on|true|yes)$',$X)||(int)$X);}function
sid(){static$J;if($J===null)$J=(SID&&!($_COOKIE&&ini_bool("session.use_cookies")));return$J;}function
q($Q){global$f;return$f->quote($Q);}function
get_vals($H,$Oa=0){global$f;$J=array();$I=$f->query($H);if(is_object($I)){while($K=$I->fetch_row())$J[]=$K[$Oa];}return$J;}function
get_key_vals($H,$g=null){global$f;if(!is_object($g))$g=$f;$J=array();$I=$g->query($H);if(is_object($I)){while($K=$I->fetch_row())$J[$K[0]]=$K[1];}return$J;}function
get_rows($H,$g=null,$j="<p class='error'>"){global$f;$Va=(is_object($g)?$g:$f);$J=array();$I=$Va->query($H);if(is_object($I)){while($K=$I->fetch_assoc())$J[]=$K;}elseif(!$I&&!is_object($g)&&$j&&defined("PAGE_HEADER"))echo$j.error()."\n";return$J;}function
unique_array($K,$u){foreach($u
as$t){if(ereg("PRIMARY|UNIQUE",$t["type"])){$J=array();foreach($t["columns"]as$x){if(!isset($K[$x]))continue
2;$J[$x]=$K[$x];}return$J;}}$J=array();foreach($K
as$x=>$X){if(!preg_match('~^(COUNT\\((\\*|(DISTINCT )?`(?:[^`]|``)+`)\\)|(AVG|GROUP_CONCAT|MAX|MIN|SUM)\\(`(?:[^`]|``)+`\\))$~',$x))$J[$x]=$X;}return$J;}function
where($Z){global$w;$J=array();foreach((array)$Z["where"]as$x=>$X)$J[]=idf_escape(bracket_escape($x,1)).(($w=="sql"&&ereg('\\.',$X))||$w=="mssql"?" LIKE ".exact_value(addcslashes($X,"%_\\")):" = ".exact_value($X));foreach((array)$Z["null"]as$x)$J[]=idf_escape($x)." IS NULL";return
implode(" AND ",$J);}function
where_check($X){parse_str($X,$Ga);remove_slashes(array(&$Ga));return
where($Ga);}function
where_link($r,$Oa,$Y,$yd="="){return"&where%5B$r%5D%5Bcol%5D=".urlencode($Oa)."&where%5B$r%5D%5Bop%5D=".urlencode(($Y!==null?$yd:"IS NULL"))."&where%5B$r%5D%5Bval%5D=".urlencode($Y);}function
cookie($D,$Y){global$ba;$Nd=array($D,(ereg("\n",$Y)?"":$Y),time()+2592000,preg_replace('~\\?.*~','',$_SERVER["REQUEST_URI"]),"",$ba);if(version_compare(PHP_VERSION,'5.2.0')>=0)$Nd[]=true;return
call_user_func_array('setcookie',$Nd);}function
restart_session(){if(!ini_bool("session.use_cookies"))session_start();}function&get_session($x){return$_SESSION[$x][DRIVER][SERVER][$_GET["username"]];}function
set_session($x,$X){$_SESSION[$x][DRIVER][SERVER][$_GET["username"]]=$X;}function
auth_url($qb,$O,$Mf,$i=null){global$rb;preg_match('~([^?]*)\\??(.*)~',remove_from_uri(implode("|",array_keys($rb))."|username|".($i!==null?"db|":"").session_name()),$A);return"$A[1]?".(sid()?SID."&":"").($qb!="server"||$O!=""?urlencode($qb)."=".urlencode($O)."&":"")."username=".urlencode($Mf).($i!=""?"&db=".urlencode($i):"").($A[2]?"&$A[2]":"");}function
is_ajax(){return($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest");}function
redirect($_,$B=null){if($B!==null){restart_session();$_SESSION["messages"][preg_replace('~^[^?]*~','',($_!==null?$_:$_SERVER["REQUEST_URI"]))][]=$B;}if($_!==null){if($_=="")$_=".";header("Location: $_");exit;}}function
query_redirect($H,$_,$B,$ne=true,$Nb=true,$Tb=false){global$f,$j,$b;if($Nb)$Tb=!$f->query($H);$Ne="";if($H)$Ne=$b->messageQuery("$H;");if($Tb){$j=error().$Ne;return
false;}if($ne)redirect($_,$B.$Ne);return
true;}function
queries($H=null){global$f;static$le=array();if($H===null)return
implode(";\n",$le);$le[]=(ereg(';$',$H)?"DELIMITER ;;\n$H;\nDELIMITER ":$H);return$f->query($H);}function
apply_queries($H,$ef,$Ib='table'){foreach($ef
as$S){if(!queries("$H ".$Ib($S)))return
false;}return
true;}function
queries_redirect($_,$B,$ne){return
query_redirect(queries(),$_,$B,$ne,false,!$ne);}function
remove_from_uri($Md=""){return
substr(preg_replace("~(?<=[?&])($Md".(SID?"":"|".session_name()).")=[^&]*&~",'',"$_SERVER[REQUEST_URI]&"),0,-1);}function
pagination($E,$eb){return" ".($E==$eb?$E+1:'<a href="'.h(remove_from_uri("page").($E?"&page=$E":"")).'">'.($E+1)."</a>");}function
get_file($x,$jb=false){$Vb=$_FILES[$x];if(!$Vb||$Vb["error"])return$Vb["error"];$J=file_get_contents($jb&&ereg('\\.gz$',$Vb["name"])?"compress.zlib://$Vb[tmp_name]":($jb&&ereg('\\.bz2$',$Vb["name"])?"compress.bzip2://$Vb[tmp_name]":$Vb["tmp_name"]));if($jb){$Oe=substr($J,0,3);if(function_exists("iconv")&&ereg("^\xFE\xFF|^\xFF\xFE",$Oe,$te))$J=iconv("utf-16","utf-8",$J);elseif($Oe=="\xEF\xBB\xBF")$J=substr($J,3);}return$J;}function
upload_error($j){$Zc=($j==UPLOAD_ERR_INI_SIZE?ini_get("upload_max_filesize"):0);return($j?'Unable to upload a file.'.($Zc?" ".sprintf('Maximum allowed file size is %sB.',$Zc):""):'File does not exist.');}function
repeat_pattern($F,$Oc){return
str_repeat("$F{0,65535}",$Oc/65535)."$F{0,".($Oc
%
65535)."}";}function
is_utf8($X){return(preg_match('~~u',$X)&&!preg_match('~[\\0-\\x8\\xB\\xC\\xE-\\x1F]~',$X));}function
shorten_utf8($Q,$Oc=80,$Ue=""){if(!preg_match("(^(".repeat_pattern("[\t\r\n -\x{FFFF}]",$Oc).")($)?)u",$Q,$A))preg_match("(^(".repeat_pattern("[\t\r\n -~]",$Oc).")($)?)",$Q,$A);return
h($A[1]).$Ue.(isset($A[2])?"":"<i>...</i>");}function
friendly_url($X){return
preg_replace('~[^a-z0-9_]~i','-',$X);}function
hidden_fields($ie,$tc=array()){while(list($x,$X)=each($ie)){if(is_array($X)){foreach($X
as$Dc=>$W)$ie[$x."[$Dc]"]=$W;}elseif(!in_array($x,$tc))echo'<input type="hidden" name="'.h($x).'" value="'.h($X).'">';}}function
hidden_fields_get(){echo(sid()?'<input type="hidden" name="'.session_name().'" value="'.h(session_id()).'">':''),(SERVER!==null?'<input type="hidden" name="'.DRIVER.'" value="'.h(SERVER).'">':""),'<input type="hidden" name="username" value="'.h($_GET["username"]).'">';}function
column_foreign_keys($S){global$b;$J=array();foreach($b->foreignKeys($S)as$m){foreach($m["source"]as$X)$J[$X][]=$m;}return$J;}function
enum_input($V,$ua,$k,$Y,$Bb=null){global$b;preg_match_all("~'((?:[^']|'')*)'~",$k["length"],$Uc);$J=($Bb!==null?"<label><input type='$V'$ua value='$Bb'".((is_array($Y)?in_array($Bb,$Y):$Y===0)?" checked":"")."><i>".'empty'."</i></label>":"");foreach($Uc[1]as$r=>$X){$X=stripcslashes(str_replace("''","'",$X));$Ha=(is_int($Y)?$Y==$r+1:(is_array($Y)?in_array($r+1,$Y):$Y===$X));$J.=" <label><input type='$V'$ua value='".($r+1)."'".($Ha?' checked':'').'>'.h($b->editVal($X,$k)).'</label>';}return$J;}function
input($k,$Y,$o){global$Af,$b,$w;$D=h(bracket_escape($k["field"]));echo"<td class='function'>";$ve=($w=="mssql"&&$k["auto_increment"]);if($ve&&!$_POST["save"])$o=null;$ic=(isset($_GET["select"])||$ve?array("orig"=>'original'):array())+$b->editFunctions($k);$ua=" name='fields[$D]'";if($k["type"]=="enum")echo
nbsp($ic[""])."<td>".$b->editInput($_GET["edit"],$k,$ua,$Y);else{$Yb=0;foreach($ic
as$x=>$X){if($x===""||!$X)break;$Yb++;}$wd=($Yb?" onchange=\"var f = this.form['function[".h(js_escape(bracket_escape($k["field"])))."]']; if ($Yb > f.selectedIndex) f.selectedIndex = $Yb;\"":"");$ua.=$wd;echo(count($ic)>1?html_select("function[$D]",$ic,$o===null||in_array($o,$ic)||isset($ic[$o])?$o:"","functionChange(this);"):nbsp(reset($ic))).'<td>';$yc=$b->editInput($_GET["edit"],$k,$ua,$Y);if($yc!="")echo$yc;elseif($k["type"]=="set"){preg_match_all("~'((?:[^']|'')*)'~",$k["length"],$Uc);foreach($Uc[1]as$r=>$X){$X=stripcslashes(str_replace("''","'",$X));$Ha=(is_int($Y)?($Y>>$r)&1:in_array($X,explode(",",$Y),true));echo" <label><input type='checkbox' name='fields[$D][$r]' value='".(1<<$r)."'".($Ha?' checked':'')."$wd>".h($b->editVal($X,$k)).'</label>';}}elseif(ereg('blob|bytea|raw|file',$k["type"])&&ini_bool("file_uploads"))echo"<input type='file' name='fields-$D'$wd>";elseif(ereg('text|lob',$k["type"]))echo"<textarea ".($w!="sqlite"||ereg("\n",$Y)?"cols='50' rows='12'":"cols='30' rows='1' style='height: 1.2em;'")."$ua>".h($Y).'</textarea>';else{$ad=(!ereg('int',$k["type"])&&preg_match('~^(\\d+)(,(\\d+))?$~',$k["length"],$A)?((ereg("binary",$k["type"])?2:1)*$A[1]+($A[3]?1:0)+($A[2]&&!$k["unsigned"]?1:0)):($Af[$k["type"]]?$Af[$k["type"]]+($k["unsigned"]?0:1):0));echo"<input value='".h($Y)."'".($ad?" maxlength='$ad'":"").(ereg('char|binary',$k["type"])&&$ad>20?" size='40'":"")."$ua>";}}}function
process_input($k){global$b;$sc=bracket_escape($k["field"]);$o=$_POST["function"][$sc];$Y=$_POST["fields"][$sc];if($k["type"]=="enum"){if($Y==-1)return
false;if($Y=="")return"NULL";return+$Y;}if($k["auto_increment"]&&$Y=="")return
null;if($o=="orig")return($k["on_update"]=="CURRENT_TIMESTAMP"?idf_escape($k["field"]):false);if($o=="NULL")return"NULL";if($k["type"]=="set")return
array_sum((array)$Y);if(ereg('blob|bytea|raw|file',$k["type"])&&ini_bool("file_uploads")){$Vb=get_file("fields-$sc");if(!is_string($Vb))return
false;return
q($Vb);}return$b->processInput($k,$Y,$o);}function
search_tables(){global$b,$f;$_GET["where"][0]["op"]="LIKE %%";$_GET["where"][0]["val"]=$_POST["query"];$dc=false;foreach(table_status()as$S=>$T){$D=$b->tableName($T);if(isset($T["Engine"])&&$D!=""&&(!$_POST["tables"]||in_array($S,$_POST["tables"]))){$I=$f->query("SELECT".limit("1 FROM ".table($S)," WHERE ".implode(" AND ",$b->selectSearchProcess(fields($S),array())),1));if($I->fetch_row()){if(!$dc){echo"<ul>\n";$dc=true;}echo"<li><a href='".h(ME."select=".urlencode($S)."&where[0][op]=".urlencode($_GET["where"][0]["op"])."&where[0][val]=".urlencode($_GET["where"][0]["val"]))."'>$D</a>\n";}}}echo($dc?"</ul>":"<p class='message'>".'No tables.')."\n";}function
dump_headers($rc,$hd=false){global$b;$J=$b->dumpHeaders($rc,$hd);$Kd=$_POST["output"];if($Kd!="text")header("Content-Disposition: attachment; filename=".$b->dumpFilename($rc).".$J".($Kd!="file"&&!ereg('[^0-9a-z]',$Kd)?".$Kd":""));session_write_close();return$J;}function
dump_csv($K){foreach($K
as$x=>$X){if(preg_match("~[\"\n,;\t]~",$X)||$X==="")$K[$x]='"'.str_replace('"','""',$X).'"';}echo
implode(($_POST["format"]=="csv"?",":($_POST["format"]=="tsv"?"\t":";")),$K)."\r\n";}function
apply_sql_function($o,$Oa){return($o?($o=="unixepoch"?"DATETIME($Oa, '$o')":($o=="count distinct"?"COUNT(DISTINCT ":strtoupper("$o("))."$Oa)"):$Oa);}function
password_file(){$nb=ini_get("upload_tmp_dir");if(!$nb){if(function_exists('sys_get_temp_dir'))$nb=sys_get_temp_dir();else{$Wb=@tempnam("","");if(!$Wb)return
false;$nb=dirname($Wb);unlink($Wb);}}$Wb="$nb/adminer.key";$J=@file_get_contents($Wb);if($J)return$J;$fc=@fopen($Wb,"w");if($fc){$J=md5(uniqid(mt_rand(),true));fwrite($fc,$J);fclose($fc);}return$J;}function
is_mail($zb){$ta='[-a-z0-9!#$%&\'*+/=?^_`{|}~]';$pb='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';$F="$ta+(\\.$ta+)*@($pb?\\.)+$pb";return
preg_match("(^$F(,\\s*$F)*\$)i",$zb);}function
is_url($Q){$pb='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';return(preg_match("~^(https?)://($pb?\\.)+$pb(:\\d+)?(/.*)?(\\?.*)?(#.*)?\$~i",$Q,$A)?strtolower($A[1]):"");}global$b,$f,$rb,$xb,$Fb,$j,$ic,$lc,$ba,$xc,$w,$ca,$Hc,$vd,$Se,$U,$vf,$Af,$Hf,$ga;if(!$_SERVER["REQUEST_URI"])$_SERVER["REQUEST_URI"]=$_SERVER["ORIG_PATH_INFO"];if(!strpos($_SERVER["REQUEST_URI"],'?')&&$_SERVER["QUERY_STRING"]!="")$_SERVER["REQUEST_URI"].="?$_SERVER[QUERY_STRING]";$ba=$_SERVER["HTTPS"]&&strcasecmp($_SERVER["HTTPS"],"off");@ini_set("session.use_trans_sid",false);if(!defined("SID")){session_name("adminer_sid");$Nd=array(0,preg_replace('~\\?.*~','',$_SERVER["REQUEST_URI"]),"",$ba);if(version_compare(PHP_VERSION,'5.2.0')>=0)$Nd[]=true;call_user_func_array('session_set_cookie_params',$Nd);session_start();}remove_slashes(array(&$_GET,&$_POST,&$_COOKIE),$Xb);if(function_exists("set_magic_quotes_runtime"))set_magic_quotes_runtime(false);@set_time_limit(0);@ini_set("zend.ze1_compatibility_mode",false);@ini_set("precision",20);function
get_lang(){return'en';}function
lang($uf,$nd){$Xd=($nd==1?0:1);$uf=str_replace("%d","%s",$uf[$Xd]);$nd=number_format($nd,0,".",',');return
sprintf($uf,$nd);}if(extension_loaded('pdo')){class
Min_PDO
extends
PDO{var$_result,$server_info,$affected_rows,$error;function
__construct(){global$b;$Xd=array_search("",$b->operators);if($Xd!==false)unset($b->operators[$Xd]);}function
dsn($ub,$Mf,$Ud,$Mb='auth_error'){set_exception_handler($Mb);parent::__construct($ub,$Mf,$Ud);restore_exception_handler();$this->setAttribute(13,array('Min_PDOStatement'));$this->server_info=$this->getAttribute(4);}function
query($H,$Bf=false){$I=parent::query($H);$this->error="";if(!$I){$Gb=$this->errorInfo();$this->error=$Gb[2];return
false;}$this->store_result($I);return$I;}function
multi_query($H){return$this->_result=$this->query($H);}function
store_result($I=null){if(!$I)$I=$this->_result;if($I->columnCount()){$I->num_rows=$I->rowCount();return$I;}$this->affected_rows=$I->rowCount();return
true;}function
next_result(){$this->_result->_offset=0;return@$this->_result->nextRowset();}function
result($H,$k=0){$I=$this->query($H);if(!$I)return
false;$K=$I->fetch();return$K[$k];}}class
Min_PDOStatement
extends
PDOStatement{var$_offset=0,$num_rows;function
fetch_assoc(){return$this->fetch(2);}function
fetch_row(){return$this->fetch(3);}function
fetch_field(){$K=(object)$this->getColumnMeta($this->_offset++);$K->orgtable=$K->table;$K->orgname=$K->name;$K->charsetnr=(in_array("blob",(array)$K->flags)?63:0);return$K;}}}$rb=array();$rb=array("server"=>"MySQL")+$rb;if(!defined("DRIVER")){$ae=array("MySQLi","MySQL","PDO_MySQL");define("DRIVER","server");if(extension_loaded("mysqli")){class
Min_DB
extends
MySQLi{var$extension="MySQLi";function
Min_DB(){parent::init();}function
connect($O,$Mf,$Ud){mysqli_report(MYSQLI_REPORT_OFF);list($pc,$Wd)=explode(":",$O,2);$J=@$this->real_connect(($O!=""?$pc:ini_get("mysqli.default_host")),($O.$Mf!=""?$Mf:ini_get("mysqli.default_user")),($O.$Mf.$Ud!=""?$Ud:ini_get("mysqli.default_pw")),null,(is_numeric($Wd)?$Wd:ini_get("mysqli.default_port")),(!is_numeric($Wd)?$Wd:null));if($J){if(method_exists($this,'set_charset'))$this->set_charset("utf8");else$this->query("SET NAMES utf8");}return$J;}function
result($H,$k=0){$I=$this->query($H);if(!$I)return
false;$K=$I->fetch_array();return$K[$k];}function
quote($Q){return"'".$this->escape_string($Q)."'";}}}elseif(extension_loaded("mysql")&&!(ini_get("sql.safe_mode")&&extension_loaded("pdo_mysql"))){class
Min_DB{var$extension="MySQL",$server_info,$affected_rows,$error,$_link,$_result;function
connect($O,$Mf,$Ud){$this->_link=@mysql_connect(($O!=""?$O:ini_get("mysql.default_host")),("$O$Mf"!=""?$Mf:ini_get("mysql.default_user")),("$O$Mf$Ud"!=""?$Ud:ini_get("mysql.default_password")),true,131072);if($this->_link){$this->server_info=mysql_get_server_info($this->_link);if(function_exists('mysql_set_charset'))mysql_set_charset("utf8",$this->_link);else$this->query("SET NAMES utf8");}else$this->error=mysql_error();return(bool)$this->_link;}function
quote($Q){return"'".mysql_real_escape_string($Q,$this->_link)."'";}function
select_db($hb){return
mysql_select_db($hb,$this->_link);}function
query($H,$Bf=false){$I=@($Bf?mysql_unbuffered_query($H,$this->_link):mysql_query($H,$this->_link));$this->error="";if(!$I){$this->error=mysql_error($this->_link);return
false;}if($I===true){$this->affected_rows=mysql_affected_rows($this->_link);$this->info=mysql_info($this->_link);return
true;}return
new
Min_Result($I);}function
multi_query($H){return$this->_result=$this->query($H);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($H,$k=0){$I=$this->query($H);if(!$I||!$I->num_rows)return
false;return
mysql_result($I->_result,0,$k);}}class
Min_Result{var$num_rows,$_result,$_offset=0;function
Min_Result($I){$this->_result=$I;$this->num_rows=mysql_num_rows($I);}function
fetch_assoc(){return
mysql_fetch_assoc($this->_result);}function
fetch_row(){return
mysql_fetch_row($this->_result);}function
fetch_field(){$J=mysql_fetch_field($this->_result,$this->_offset++);$J->orgtable=$J->table;$J->orgname=$J->name;$J->charsetnr=($J->blob?63:0);return$J;}function
__destruct(){mysql_free_result($this->_result);}}}elseif(extension_loaded("pdo_mysql")){class
Min_DB
extends
Min_PDO{var$extension="PDO_MySQL";function
connect($O,$Mf,$Ud){$this->dsn("mysql:host=".str_replace(":",";unix_socket=",preg_replace('~:(\\d)~',';port=\\1',$O)),$Mf,$Ud);$this->query("SET NAMES utf8");return
true;}function
select_db($hb){return$this->query("USE ".idf_escape($hb));}function
query($H,$Bf=false){$this->setAttribute(1000,!$Bf);return
parent::query($H,$Bf);}}}function
idf_escape($sc){return"`".str_replace("`","``",$sc)."`";}function
table($sc){return
idf_escape($sc);}function
connect(){global$b;$f=new
Min_DB;$db=$b->credentials();if($f->connect($db[0],$db[1],$db[2])){$f->query("SET sql_quote_show_create = 1, autocommit = 1");return$f;}$J=$f->error;if(function_exists('iconv')&&!is_utf8($J)&&strlen($M=iconv("windows-1250","utf-8",$J))>strlen($J))$J=$M;return$J;}function
get_databases($Zb=true){global$f;$J=&get_session("dbs");if($J===null){if($Zb){restart_session();ob_flush();flush();}$J=get_vals($f->server_info>=5?"SELECT SCHEMA_NAME FROM information_schema.SCHEMATA":"SHOW DATABASES");}return$J;}function
limit($H,$Z,$y,$pd=0,$He=" "){return" $H$Z".($y!==null?$He."LIMIT $y".($pd?" OFFSET $pd":""):"");}function
limit1($H,$Z){return
limit($H,$Z,1);}function
db_collation($i,$d){global$f;$J=null;$ab=$f->result("SHOW CREATE DATABASE ".idf_escape($i),1);if(preg_match('~ COLLATE ([^ ]+)~',$ab,$A))$J=$A[1];elseif(preg_match('~ CHARACTER SET ([^ ]+)~',$ab,$A))$J=$d[$A[1]][-1];return$J;}function
engines(){$J=array();foreach(get_rows("SHOW ENGINES")as$K){if(ereg("YES|DEFAULT",$K["Support"]))$J[]=$K["Engine"];}return$J;}function
logged_user(){global$f;return$f->result("SELECT USER()");}function
tables_list(){global$f;return
get_key_vals("SHOW".($f->server_info>=5?" FULL":"")." TABLES");}function
count_tables($h){$J=array();foreach($h
as$i)$J[$i]=count(get_vals("SHOW TABLES IN ".idf_escape($i)));return$J;}function
table_status($D=""){$J=array();foreach(get_rows("SHOW TABLE STATUS".($D!=""?" LIKE ".q(addcslashes($D,"%_")):""))as$K){if($K["Engine"]=="InnoDB")$K["Comment"]=preg_replace('~(?:(.+); )?InnoDB free: .*~','\\1',$K["Comment"]);if(!isset($K["Rows"]))$K["Comment"]="";if($D!="")return$K;$J[$K["Name"]]=$K;}return$J;}function
is_view($T){return!isset($T["Rows"]);}function
fk_support($T){return
eregi("InnoDB|IBMDB2I",$T["Engine"]);}function
fields($S){$J=array();foreach(get_rows("SHOW FULL COLUMNS FROM ".table($S))as$K){preg_match('~^([^( ]+)(?:\\((.+)\\))?( unsigned)?( zerofill)?$~',$K["Type"],$A);$J[$K["Field"]]=array("field"=>$K["Field"],"full_type"=>$K["Type"],"type"=>$A[1],"length"=>$A[2],"unsigned"=>ltrim($A[3].$A[4]),"default"=>($K["Default"]!=""||ereg("char",$A[1])?$K["Default"]:null),"null"=>($K["Null"]=="YES"),"auto_increment"=>($K["Extra"]=="auto_increment"),"on_update"=>(eregi('^on update (.+)',$K["Extra"],$A)?$A[1]:""),"collation"=>$K["Collation"],"privileges"=>array_flip(explode(",",$K["Privileges"])),"comment"=>$K["Comment"],"primary"=>($K["Key"]=="PRI"),);}return$J;}function
indexes($S,$g=null){$J=array();foreach(get_rows("SHOW INDEX FROM ".table($S),$g)as$K){$J[$K["Key_name"]]["type"]=($K["Key_name"]=="PRIMARY"?"PRIMARY":($K["Index_type"]=="FULLTEXT"?"FULLTEXT":($K["Non_unique"]?"INDEX":"UNIQUE")));$J[$K["Key_name"]]["columns"][]=$K["Column_name"];$J[$K["Key_name"]]["lengths"][]=$K["Sub_part"];}return$J;}function
foreign_keys($S){global$f,$vd;static$F='`(?:[^`]|``)+`';$J=array();$bb=$f->result("SHOW CREATE TABLE ".table($S),1);if($bb){preg_match_all("~CONSTRAINT ($F) FOREIGN KEY \\(((?:$F,? ?)+)\\) REFERENCES ($F)(?:\\.($F))? \\(((?:$F,? ?)+)\\)(?: ON DELETE ($vd))?(?: ON UPDATE ($vd))?~",$bb,$Uc,PREG_SET_ORDER);foreach($Uc
as$A){preg_match_all("~$F~",$A[2],$Le);preg_match_all("~$F~",$A[5],$hf);$J[idf_unescape($A[1])]=array("db"=>idf_unescape($A[4]!=""?$A[3]:$A[4]),"table"=>idf_unescape($A[4]!=""?$A[4]:$A[3]),"source"=>array_map('idf_unescape',$Le[0]),"target"=>array_map('idf_unescape',$hf[0]),"on_delete"=>($A[6]?$A[6]:"RESTRICT"),"on_update"=>($A[7]?$A[7]:"RESTRICT"),);}}return$J;}function
view($D){global$f;return
array("select"=>preg_replace('~^(?:[^`]|`[^`]*`)*\\s+AS\\s+~isU','',$f->result("SHOW CREATE VIEW ".table($D),1)));}function
collations(){$J=array();foreach(get_rows("SHOW COLLATION")as$K){if($K["Default"])$J[$K["Charset"]][-1]=$K["Collation"];else$J[$K["Charset"]][]=$K["Collation"];}ksort($J);foreach($J
as$x=>$X)asort($J[$x]);return$J;}function
information_schema($i){global$f;return($f->server_info>=5&&$i=="information_schema");}function
error(){global$f;return
h(preg_replace('~^You have an error.*syntax to use~U',"Syntax error",$f->error));}function
error_line(){global$f;if(ereg(' at line ([0-9]+)$',$f->error,$te))return$te[1]-1;}function
exact_value($X){return
q($X)." COLLATE utf8_bin";}function
create_database($i,$Ma){set_session("dbs",null);return
queries("CREATE DATABASE ".idf_escape($i).($Ma?" COLLATE ".q($Ma):""));}function
drop_databases($h){set_session("dbs",null);return
apply_queries("DROP DATABASE",$h,'idf_escape');}function
rename_database($D,$Ma){if(create_database($D,$Ma)){$ue=array();foreach(tables_list()as$S=>$V)$ue[]=table($S)." TO ".idf_escape($D).".".table($S);if(!$ue||queries("RENAME TABLE ".implode(", ",$ue))){queries("DROP DATABASE ".idf_escape(DB));return
true;}}return
false;}function
auto_increment(){$wa=" PRIMARY KEY";if($_GET["create"]!=""&&$_POST["auto_increment_col"]){foreach(indexes($_GET["create"])as$t){if(in_array($_POST["fields"][$_POST["auto_increment_col"]]["orig"],$t["columns"],true)){$wa="";break;}if($t["type"]=="PRIMARY")$wa=" UNIQUE";}}return" AUTO_INCREMENT$wa";}function
alter_table($S,$D,$l,$ac,$Ra,$Db,$Ma,$va,$Rd){$sa=array();foreach($l
as$k)$sa[]=($k[1]?($S!=""?($k[0]!=""?"CHANGE ".idf_escape($k[0]):"ADD"):" ")." ".implode($k[1]).($S!=""?$k[2]:""):"DROP ".idf_escape($k[0]));$sa=array_merge($sa,$ac);$Pe="COMMENT=".q($Ra).($Db?" ENGINE=".q($Db):"").($Ma?" COLLATE ".q($Ma):"").($va!=""?" AUTO_INCREMENT=$va":"").$Rd;if($S=="")return
queries("CREATE TABLE ".table($D)." (\n".implode(",\n",$sa)."\n) $Pe");if($S!=$D)$sa[]="RENAME TO ".table($D);$sa[]=$Pe;return
queries("ALTER TABLE ".table($S)."\n".implode(",\n",$sa));}function
alter_indexes($S,$sa){foreach($sa
as$x=>$X)$sa[$x]=($X[2]=="DROP"?"\nDROP INDEX ".idf_escape($X[1]):"\nADD $X[0] ".($X[0]=="PRIMARY"?"KEY ":"").($X[1]!=""?idf_escape($X[1])." ":"").$X[2]);return
queries("ALTER TABLE ".table($S).implode(",",$sa));}function
truncate_tables($ef){return
apply_queries("TRUNCATE TABLE",$ef);}function
drop_views($Qf){return
queries("DROP VIEW ".implode(", ",array_map('table',$Qf)));}function
drop_tables($ef){return
queries("DROP TABLE ".implode(", ",array_map('table',$ef)));}function
move_tables($ef,$Qf,$hf){$ue=array();foreach(array_merge($ef,$Qf)as$S)$ue[]=table($S)." TO ".idf_escape($hf).".".table($S);return
queries("RENAME TABLE ".implode(", ",$ue));}function
copy_tables($ef,$Qf,$hf){queries("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");foreach($ef
as$S){$D=($hf==DB?table("copy_$S"):idf_escape($hf).".".table($S));if(!queries("DROP TABLE IF EXISTS $D")||!queries("CREATE TABLE $D LIKE ".table($S))||!queries("INSERT INTO $D SELECT * FROM ".table($S)))return
false;}foreach($Qf
as$S){$D=($hf==DB?table("copy_$S"):idf_escape($hf).".".table($S));$Pf=view($S);if(!queries("DROP VIEW IF EXISTS $D")||!queries("CREATE VIEW $D AS $Pf[select]"))return
false;}return
true;}function
trigger($D){if($D=="")return
array();$L=get_rows("SHOW TRIGGERS WHERE `Trigger` = ".q($D));return
reset($L);}function
triggers($S){$J=array();foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($S,"%_")))as$K)$J[$K["Trigger"]]=array($K["Timing"],$K["Event"]);return$J;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER"),"Type"=>array("FOR EACH ROW"),);}function
routine($D,$V){global$f,$Fb,$xc,$Af;$pa=array("bool","boolean","integer","double precision","real","dec","numeric","fixed","national char","national varchar");$_f="((".implode("|",array_merge(array_keys($Af),$pa)).")\\b(?:\\s*\\(((?:[^'\")]*|$Fb)+)\\))?\\s*(zerofill\\s*)?(unsigned(?:\\s+zerofill)?)?)(?:\\s*(?:CHARSET|CHARACTER\\s+SET)\\s*['\"]?([^'\"\\s]+)['\"]?)?";$F="\\s*(".($V=="FUNCTION"?"":$xc).")?\\s*(?:`((?:[^`]|``)*)`\\s*|\\b(\\S+)\\s+)$_f";$ab=$f->result("SHOW CREATE $V ".idf_escape($D),2);preg_match("~\\(((?:$F\\s*,?)*)\\)\\s*".($V=="FUNCTION"?"RETURNS\\s+$_f\\s+":"")."(.*)~is",$ab,$A);$l=array();preg_match_all("~$F\\s*,?~is",$A[1],$Uc,PREG_SET_ORDER);foreach($Uc
as$Md){$D=str_replace("``","`",$Md[2]).$Md[3];$l[]=array("field"=>$D,"type"=>strtolower($Md[5]),"length"=>preg_replace_callback("~$Fb~s",'normalize_enum',$Md[6]),"unsigned"=>strtolower(preg_replace('~\\s+~',' ',trim("$Md[8] $Md[7]"))),"full_type"=>$Md[4],"inout"=>strtoupper($Md[1]),"collation"=>strtolower($Md[9]),);}if($V!="FUNCTION")return
array("fields"=>$l,"definition"=>$A[11]);return
array("fields"=>$l,"returns"=>array("type"=>$A[12],"length"=>$A[13],"unsigned"=>$A[15],"collation"=>$A[16]),"definition"=>$A[17],"language"=>"SQL",);}function
routines(){return
get_rows("SELECT * FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = ".q(DB));}function
routine_languages(){return
array();}function
begin(){return
queries("BEGIN");}function
insert_into($S,$P){return
queries("INSERT INTO ".table($S)." (".implode(", ",array_keys($P)).")\nVALUES (".implode(", ",$P).")");}function
insert_update($S,$P,$de){foreach($P
as$x=>$X)$P[$x]="$x = $X";$If=implode(", ",$P);return
queries("INSERT INTO ".table($S)." SET $If ON DUPLICATE KEY UPDATE $If");}function
last_id(){global$f;return$f->result("SELECT LAST_INSERT_ID()");}function
explain($f,$H){return$f->query("EXPLAIN $H");}function
found_rows($T,$Z){return($Z||$T["Engine"]!="InnoDB"?null:$T["Rows"]);}function
types(){return
array();}function
schemas(){return
array();}function
get_schema(){return"";}function
set_schema($De){return
true;}function
create_sql($S,$va){global$f;$J=$f->result("SHOW CREATE TABLE ".table($S),1);if(!$va)$J=preg_replace('~ AUTO_INCREMENT=\\d+~','',$J);return$J;}function
truncate_sql($S){return"TRUNCATE ".table($S);}function
use_sql($hb){return"USE ".idf_escape($hb);}function
trigger_sql($S,$R){$J="";foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($S,"%_")),null,"-- ")as$K)$J.="\n".($R=='CREATE+ALTER'?"DROP TRIGGER IF EXISTS ".idf_escape($K["Trigger"]).";;\n":"")."CREATE TRIGGER ".idf_escape($K["Trigger"])." $K[Timing] $K[Event] ON ".table($K["Table"])." FOR EACH ROW\n$K[Statement];;\n";return$J;}function
show_variables(){return
get_key_vals("SHOW VARIABLES");}function
process_list(){return
get_rows("SHOW FULL PROCESSLIST");}function
show_status(){return
get_key_vals("SHOW STATUS");}function
support($Ub){global$f;return!ereg("scheme|sequence|type".($f->server_info<5.1?"|event|partitioning".($f->server_info<5?"|view|routine|trigger":""):""),$Ub);}$w="sql";$Af=array();$Se=array();foreach(array('Numbers'=>array("tinyint"=>3,"smallint"=>5,"mediumint"=>8,"int"=>10,"bigint"=>20,"decimal"=>66,"float"=>12,"double"=>21),'Date and time'=>array("date"=>10,"datetime"=>19,"timestamp"=>19,"time"=>10,"year"=>4),'Strings'=>array("char"=>255,"varchar"=>65535,"tinytext"=>255,"text"=>65535,"mediumtext"=>16777215,"longtext"=>4294967295),'Binary'=>array("bit"=>20,"binary"=>255,"varbinary"=>65535,"tinyblob"=>255,"blob"=>65535,"mediumblob"=>16777215,"longblob"=>4294967295),'Lists'=>array("enum"=>65535,"set"=>64),)as$x=>$X){$Af+=$X;$Se[$x]=array_keys($X);}$Hf=array("unsigned","zerofill","unsigned zerofill");$zd=array("=","<",">","<=",">=","!=","LIKE","LIKE %%","REGEXP","IN","IS NULL","NOT LIKE","NOT REGEXP","NOT IN","IS NOT NULL","");$ic=array("char_length","date","from_unixtime","hex","lower","round","sec_to_time","time_to_sec","upper");$lc=array("avg","count","count distinct","group_concat","max","min","sum");$xb=array(array("char"=>"md5/sha1/password/encrypt/uuid","binary"=>"md5/sha1/hex","date|time"=>"now",),array("int|float|double|decimal"=>"+/-","date"=>"+ interval/- interval","time"=>"addtime/subtime","char|text"=>"concat",));}define("SERVER",$_GET[DRIVER]);define("DB",$_GET["db"]);define("ME",preg_replace('~^[^?]*/([^?]*).*~','\\1',$_SERVER["REQUEST_URI"]).'?'.(sid()?SID.'&':'').(SERVER!==null?DRIVER."=".urlencode(SERVER).'&':'').(isset($_GET["username"])?"username=".urlencode($_GET["username"]).'&':'').(DB!=""?'db='.urlencode(DB).'&'.(isset($_GET["ns"])?"ns=".urlencode($_GET["ns"])."&":""):''));$ga="3.5.1";class
Adminer{var$operators;function
name(){return"<a href='http://www.adminer.org/' id='h1'>Adminer</a>";}function
credentials(){return
array(SERVER,$_GET["username"],get_session("pwds"));}function
permanentLogin(){return
password_file();}function
database(){return
DB;}function
databases($Zb=true){return
get_databases($Zb);}function
headers(){return
true;}function
head(){return
true;}function
loginForm(){global$rb;echo'<table cellspacing="0">
<tr><th>System<td>',html_select("auth[driver]",$rb,DRIVER,"loginDriver(this);"),'<tr><th>Server<td><input name="auth[server]" value="',h(SERVER),'" title="hostname[:port]">
<tr><th>Username<td><input id="username" name="auth[username]" value="',h($_GET["username"]),'">
<tr><th>Password<td><input type="password" name="auth[password]">
<tr><th>Database<td><input name="auth[db]" value="',h($_GET["db"]);?>">
</table>
<script type="text/javascript">
var username = document.getElementById('username');
username.focus();
username.form['auth[driver]'].onchange();
</script>
<?php

echo"<p><input type='submit' value='".'Login'."'>\n",checkbox("auth[permanent]",1,$_COOKIE["adminer_permanent"],'Permanent login')."\n";}function
login($Sc,$Ud){return
true;}function
tableName($Ze){return
h($Ze["Name"]);}function
fieldName($k,$Bd=0){return'<span title="'.h($k["full_type"]).'">'.h($k["field"]).'</span>';}function
selectLinks($Ze,$P=""){echo'<p class="tabs">';$Rc=array("select"=>'Select data',"table"=>'Show structure');if(is_view($Ze))$Rc["view"]='Alter view';else$Rc["create"]='Alter table';if($P!==null)$Rc["edit"]='New item';foreach($Rc
as$x=>$X)echo" <a href='".h(ME)."$x=".urlencode($Ze["Name"]).($x=="edit"?$P:"")."'".bold(isset($_GET[$x])).">$X</a>";echo"\n";}function
foreignKeys($S){return
foreign_keys($S);}function
backwardKeys($S,$Ye){return
array();}function
backwardKeysPrint($ya,$K){}function
selectQuery($H){global$w;return"<p><a href='".h(remove_from_uri("page"))."&amp;page=last' title='".'Last page'."'>&gt;&gt;</a> <code class='jush-$w'>".h(str_replace("\n"," ",$H))."</code> <a href='".h(ME)."sql=".urlencode($H)."'>".'Edit'."</a></p>\n";}function
rowDescription($S){return"";}function
rowDescriptions($L,$bc){return$L;}function
selectVal($X,$z,$k){$J=($X===null?"<i>NULL</i>":(ereg("char|binary",$k["type"])&&!ereg("var",$k["type"])?"<code>$X</code>":$X));if(ereg('blob|bytea|raw|file',$k["type"])&&!is_utf8($X))$J=lang(array('%d byte','%d bytes'),strlen($X));return($z?"<a href='$z'>$J</a>":$J);}function
editVal($X,$k){return(ereg("binary",$k["type"])?reset(unpack("H*",$X)):$X);}function
selectColumnsPrint($N,$e){global$ic,$lc;print_fieldset("select",'Select',$N);$r=0;$hc=array('Functions'=>$ic,'Aggregation'=>$lc);foreach($N
as$x=>$X){$X=$_GET["columns"][$x];echo"<div>".html_select("columns[$r][fun]",array(-1=>"")+$hc,$X["fun"]),"(<select name='columns[$r][col]' onchange='selectFieldChange(this.form);'><option>".optionlist($e,$X["col"],true)."</select>)</div>\n";$r++;}echo"<div>".html_select("columns[$r][fun]",array(-1=>"")+$hc,"","this.nextSibling.nextSibling.onchange();"),"(<select name='columns[$r][col]' onchange='selectAddRow(this);'><option>".optionlist($e,null,true)."</select>)</div>\n","</div></fieldset>\n";}function
selectSearchPrint($Z,$e,$u){print_fieldset("search",'Search',$Z);foreach($u
as$r=>$t){if($t["type"]=="FULLTEXT"){echo"(<i>".implode("</i>, <i>",array_map('h',$t["columns"]))."</i>) AGAINST"," <input name='fulltext[$r]' value='".h($_GET["fulltext"][$r])."' onchange='selectFieldChange(this.form);'>",checkbox("boolean[$r]",1,isset($_GET["boolean"][$r]),"BOOL"),"<br>\n";}}$_GET["where"]=(array)$_GET["where"];reset($_GET["where"]);$Fa="this.nextSibling.onchange();";for($r=0;$r<=count($_GET["where"]);$r++){list(,$X)=each($_GET["where"]);if(!$X||("$X[col]$X[val]"!=""&&in_array($X["op"],$this->operators))){echo"<div><select name='where[$r][col]' onchange='$Fa'><option value=''>(".'anywhere'.")".optionlist($e,$X["col"],true)."</select>",html_select("where[$r][op]",$this->operators,$X["op"],$Fa),"<input name='where[$r][val]' value='".h($X["val"])."' onchange='".($X?"selectFieldChange(this.form)":"selectAddRow(this)").";'></div>\n";}}echo"</div></fieldset>\n";}function
selectOrderPrint($Bd,$e,$u){print_fieldset("sort",'Sort',$Bd);$r=0;foreach((array)$_GET["order"]as$x=>$X){if(isset($e[$X])){echo"<div><select name='order[$r]' onchange='selectFieldChange(this.form);'><option>".optionlist($e,$X,true)."</select>",checkbox("desc[$r]",1,isset($_GET["desc"][$x]),'descending')."</div>\n";$r++;}}echo"<div><select name='order[$r]' onchange='selectAddRow(this);'><option>".optionlist($e,null,true)."</select>","<label><input type='checkbox' name='desc[$r]' value='1'>".'descending'."</label></div>\n";echo"</div></fieldset>\n";}function
selectLimitPrint($y){echo"<fieldset><legend>".'Limit'."</legend><div>";echo"<input name='limit' size='3' value='".h($y)."' onchange='selectFieldChange(this.form);'>","</div></fieldset>\n";}function
selectLengthPrint($kf){if($kf!==null){echo"<fieldset><legend>".'Text length'."</legend><div>",'<input name="text_length" size="3" value="'.h($kf).'">',"</div></fieldset>\n";}}function
selectActionPrint($u){echo"<fieldset><legend>".'Action'."</legend><div>","<input type='submit' value='".'Select'."'>"," <span id='noindex' title='".'Full table scan'."'></span>","<script type='text/javascript'>\n","var indexColumns = ";$e=array();foreach($u
as$t){if($t["type"]!="FULLTEXT")$e[reset($t["columns"])]=1;}$e[""]=1;foreach($e
as$x=>$X)json_row($x);echo";\n","selectFieldChange(document.getElementById('form'));\n","</script>\n","</div></fieldset>\n";}function
selectCommandPrint(){return!information_schema(DB);}function
selectImportPrint(){return
true;}function
selectEmailPrint($_b,$e){}function
selectColumnsProcess($e,$u){global$ic,$lc;$N=array();$q=array();foreach((array)$_GET["columns"]as$x=>$X){if($X["fun"]=="count"||(isset($e[$X["col"]])&&(!$X["fun"]||in_array($X["fun"],$ic)||in_array($X["fun"],$lc)))){$N[$x]=apply_sql_function($X["fun"],(isset($e[$X["col"]])?idf_escape($X["col"]):"*"));if(!in_array($X["fun"],$lc))$q[]=$N[$x];}}return
array($N,$q);}function
selectSearchProcess($l,$u){global$w;$J=array();foreach($u
as$r=>$t){if($t["type"]=="FULLTEXT"&&$_GET["fulltext"][$r]!="")$J[]="MATCH (".implode(", ",array_map('idf_escape',$t["columns"])).") AGAINST (".q($_GET["fulltext"][$r]).(isset($_GET["boolean"][$r])?" IN BOOLEAN MODE":"").")";}foreach((array)$_GET["where"]as$X){if("$X[col]$X[val]"!=""&&in_array($X["op"],$this->operators)){$Ua=" $X[op]";if(ereg('IN$',$X["op"])){$uc=process_length($X["val"]);$Ua.=" (".($uc!=""?$uc:"NULL").")";}elseif(!$X["op"])$Ua.=$X["val"];elseif($X["op"]=="LIKE %%")$Ua=" LIKE ".$this->processInput($l[$X["col"]],"%$X[val]%");elseif(!ereg('NULL$',$X["op"]))$Ua.=" ".$this->processInput($l[$X["col"]],$X["val"]);if($X["col"]!="")$J[]=idf_escape($X["col"]).$Ua;else{$Na=array();foreach($l
as$D=>$k){if(is_numeric($X["val"])||!ereg('int|float|double|decimal|bit',$k["type"])){$D=idf_escape($D);$Na[]=($w=="sql"&&ereg('char|text|enum|set',$k["type"])&&!ereg('^utf8',$k["collation"])?"CONVERT($D USING utf8)":$D);}}$J[]=($Na?"(".implode("$Ua OR ",$Na)."$Ua)":"0");}}}return$J;}function
selectOrderProcess($l,$u){$J=array();foreach((array)$_GET["order"]as$x=>$X){if(isset($l[$X])||preg_match('~^((COUNT\\(DISTINCT |[A-Z0-9_]+\\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\\)|COUNT\\(\\*\\))$~',$X))$J[]=(isset($l[$X])?idf_escape($X):$X).(isset($_GET["desc"][$x])?" DESC":"");}return$J;}function
selectLimitProcess(){return(isset($_GET["limit"])?$_GET["limit"]:"30");}function
selectLengthProcess(){return(isset($_GET["text_length"])?$_GET["text_length"]:"100");}function
selectEmailProcess($Z,$bc){return
false;}function
messageQuery($H){global$w;static$Za=0;restart_session();$s="sql-".($Za++);$nc=&get_session("queries");if(strlen($H)>1e6)$H=ereg_replace('[\x80-\xFF]+$','',substr($H,0,1e6))."\n...";$nc[$_GET["db"]][]=array($H,time());return" <span class='time'>".@date("H:i:s")."</span> <a href='#$s' onclick=\"return !toggle('$s');\">".'SQL command'."</a><div id='$s' class='hidden'><pre><code class='jush-$w'>".shorten_utf8($H,1000).'</code></pre><p><a href="'.h(str_replace("db=".urlencode(DB),"db=".urlencode($_GET["db"]),ME).'sql=&history='.(count($nc[$_GET["db"]])-1)).'">'.'Edit'.'</a></div>';}function
editFunctions($k){global$xb;$J=($k["null"]?"NULL/":"");foreach($xb
as$x=>$ic){if(!$x||(!isset($_GET["call"])&&(isset($_GET["select"])||where($_GET)))){foreach($ic
as$F=>$X){if(!$F||ereg($F,$k["type"]))$J.="/$X";}if($x&&!ereg('set|blob|bytea|raw|file',$k["type"]))$J.="/=";}}return
explode("/",$J);}function
editInput($S,$k,$ua,$Y){if($k["type"]=="enum")return(isset($_GET["select"])?"<label><input type='radio'$ua value='-1' checked><i>".'original'."</i></label> ":"").($k["null"]?"<label><input type='radio'$ua value=''".($Y!==null||isset($_GET["select"])?"":" checked")."><i>NULL</i></label> ":"").enum_input("radio",$ua,$k,$Y,0);return"";}function
processInput($k,$Y,$o=""){if($o=="=")return$Y;$D=$k["field"];$J=($k["type"]=="bit"&&ereg("^([0-9]+|b'[0-1]+')\$",$Y)?$Y:q($Y));if(ereg('^(now|getdate|uuid)$',$o))$J="$o()";elseif(ereg('^current_(date|timestamp)$',$o))$J=$o;elseif(ereg('^([+-]|\\|\\|)$',$o))$J=idf_escape($D)." $o $J";elseif(ereg('^[+-] interval$',$o))$J=idf_escape($D)." $o ".(preg_match("~^(\\d+|'[0-9.: -]') [A-Z_]+$~i",$Y)?$Y:$J);elseif(ereg('^(addtime|subtime|concat)$',$o))$J="$o(".idf_escape($D).", $J)";elseif(ereg('^(md5|sha1|password|encrypt|hex)$',$o))$J="$o($J)";if(ereg("binary",$k["type"]))$J="unhex($J)";return$J;}function
dumpOutput(){$J=array('text'=>'open','file'=>'save');if(function_exists('gzencode'))$J['gz']='gzip';if(function_exists('bzcompress'))$J['bz2']='bzip2';return$J;}function
dumpFormat(){return
array('sql'=>'SQL','csv'=>'CSV,','csv;'=>'CSV;','tsv'=>'TSV');}function
dumpTable($S,$R,$Bc=false){if($_POST["format"]!="sql"){echo"\xef\xbb\xbf";if($R)dump_csv(array_keys(fields($S)));}elseif($R){$ab=create_sql($S,$_POST["auto_increment"]);if($ab){if($R=="DROP+CREATE")echo"DROP ".($Bc?"VIEW":"TABLE")." IF EXISTS ".table($S).";\n";if($Bc)$ab=remove_definer($ab);echo($R!="CREATE+ALTER"?$ab:($Bc?substr_replace($ab," OR REPLACE",6,0):substr_replace($ab," IF NOT EXISTS",12,0))).";\n\n";}if($R=="CREATE+ALTER"&&!$Bc){$H="SELECT COLUMN_NAME, COLUMN_DEFAULT, IS_NULLABLE, COLLATION_NAME, COLUMN_TYPE, EXTRA, COLUMN_COMMENT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ".q($S)." ORDER BY ORDINAL_POSITION";echo"DELIMITER ;;
CREATE PROCEDURE adminer_alter (INOUT alter_command text) BEGIN
	DECLARE _column_name, _collation_name, after varchar(64) DEFAULT '';
	DECLARE _column_type, _column_default text;
	DECLARE _is_nullable char(3);
	DECLARE _extra varchar(30);
	DECLARE _column_comment varchar(255);
	DECLARE done, set_after bool DEFAULT 0;
	DECLARE add_columns text DEFAULT '";$l=array();$oa="";foreach(get_rows($H)as$K){$kb=$K["COLUMN_DEFAULT"];$K["default"]=($kb!==null?q($kb):"NULL");$K["after"]=q($oa);$K["alter"]=escape_string(idf_escape($K["COLUMN_NAME"])." $K[COLUMN_TYPE]".($K["COLLATION_NAME"]?" COLLATE $K[COLLATION_NAME]":"").($kb!==null?" DEFAULT ".($kb=="CURRENT_TIMESTAMP"?$kb:$K["default"]):"").($K["IS_NULLABLE"]=="YES"?"":" NOT NULL").($K["EXTRA"]?" $K[EXTRA]":"").($K["COLUMN_COMMENT"]?" COMMENT ".q($K["COLUMN_COMMENT"]):"").($oa?" AFTER ".idf_escape($oa):" FIRST"));echo", ADD $K[alter]";$l[]=$K;$oa=$K["COLUMN_NAME"];}echo"';
	DECLARE columns CURSOR FOR $H;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
	SET @alter_table = '';
	OPEN columns;
	REPEAT
		FETCH columns INTO _column_name, _column_default, _is_nullable, _collation_name, _column_type, _extra, _column_comment;
		IF NOT done THEN
			SET set_after = 1;
			CASE _column_name";foreach($l
as$K)echo"
				WHEN ".q($K["COLUMN_NAME"])." THEN
					SET add_columns = REPLACE(add_columns, ', ADD $K[alter]', IF(
						_column_default <=> $K[default] AND _is_nullable = '$K[IS_NULLABLE]' AND _collation_name <=> ".(isset($K["COLLATION_NAME"])?"'$K[COLLATION_NAME]'":"NULL")." AND _column_type = ".q($K["COLUMN_TYPE"])." AND _extra = '$K[EXTRA]' AND _column_comment = ".q($K["COLUMN_COMMENT"])." AND after = $K[after]
					, '', ', MODIFY $K[alter]'));";echo"
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
		SET alter_command = CONCAT(alter_command, 'ALTER TABLE ".table($S)."', SUBSTR(CONCAT(add_columns, @alter_table), 2), ';\\n');
	END IF;
END;;
DELIMITER ;
CALL adminer_alter(@adminer_alter);
DROP PROCEDURE adminer_alter;

";}}}function
dumpData($S,$R,$H){global$f,$w;$Wc=($w=="sqlite"?0:1048576);if($R){if($_POST["format"]=="sql"&&$R=="TRUNCATE+INSERT")echo
truncate_sql($S).";\n";if($_POST["format"]=="sql")$l=fields($S);$I=$f->query($H,1);if($I){$zc="";$Da="";$Ec=array();while($K=$I->fetch_row()){if(!$Ec){foreach($K
as$X){$k=$I->fetch_field();$Ec[]=$k->name;}}if($_POST["format"]!="sql"){if($R=="table"){dump_csv($Ec);$R="INSERT";}dump_csv($K);}else{if(!$zc)$zc="INSERT INTO ".table($S)." (".implode(", ",array_map('idf_escape',$Ec)).") VALUES";foreach($K
as$x=>$X)$K[$x]=($X!==null?(ereg('int|float|double|decimal|bit',$l[$Ec[$x]]["type"])?$X:q($X)):"NULL");$M=implode(",\t",$K);if($R=="INSERT+UPDATE"){$P=array();foreach($K
as$x=>$X)$P[]=idf_escape($Ec[$x])." = $X";echo"$zc ($M) ON DUPLICATE KEY UPDATE ".implode(", ",$P).";\n";}else{$M=($Wc?"\n":" ")."($M)";if(!$Da)$Da=$zc.$M;elseif(strlen($Da)+4+strlen($M)<$Wc)$Da.=",$M";else{echo"$Da;\n";$Da=$zc.$M;}}}}if($_POST["format"]=="sql"&&$R!="INSERT+UPDATE"&&$Da){$Da.=";\n";echo$Da;}}elseif($_POST["format"]=="sql")echo"-- ".str_replace("\n"," ",$f->error)."\n";}}function
dumpFilename($rc){return
friendly_url($rc!=""?$rc:(SERVER!=""?SERVER:"localhost"));}function
dumpHeaders($rc,$hd=false){$Kd=$_POST["output"];$Rb=($_POST["format"]=="sql"?"sql":($hd?"tar":"csv"));header("Content-Type: ".($Kd=="bz2"?"application/x-bzip":($Kd=="gz"?"application/x-gzip":($Rb=="tar"?"application/x-tar":($Rb=="sql"||$Kd!="file"?"text/plain":"text/csv")."; charset=utf-8"))));if($Kd=="bz2")ob_start('bzcompress',1e6);if($Kd=="gz")ob_start('gzencode',1e6);return$Rb;}function
homepage(){echo'<p>'.($_GET["ns"]==""?'<a href="'.h(ME).'database=">'.'Alter database'."</a>\n":""),(support("scheme")?"<a href='".h(ME)."scheme='>".($_GET["ns"]!=""?'Alter schema':'Create schema')."</a>\n":""),($_GET["ns"]!==""?'<a href="'.h(ME).'schema=">'.'Database schema'."</a>\n":""),(support("privileges")?"<a href='".h(ME)."privileges='>".'Privileges'."</a>\n":"");return
true;}function
navigation($gd){global$ga,$f,$U,$w,$rb;echo'<h1>
',$this->name(),' <span class="version">',$ga,'</span>
<a href="http://www.adminer.org/#download" id="version">',(version_compare($ga,$_COOKIE["adminer_version"])<0?h($_COOKIE["adminer_version"]):""),'</a>
</h1>
';if($gd=="auth"){$Yb=true;foreach((array)$_SESSION["pwds"]as$qb=>$Je){foreach($Je
as$O=>$Nf){foreach($Nf
as$Mf=>$Ud){if($Ud!==null){if($Yb){echo"<p id='logins' onmouseover='menuOver(this);' onmouseout='menuOut(this);'>\n";$Yb=false;}echo"<a href='".h(auth_url($qb,$O,$Mf))."'>($rb[$qb]) ".h($Mf.($O!=""?"@$O":""))."</a><br>\n";}}}}}else{$h=$this->databases();echo'<form action="" method="post">
<p class="logout">
';if(DB==""||!$gd){echo"<a href='".h(ME)."sql='".bold(isset($_GET["sql"])).">".'SQL command'."</a>\n";if(support("dump"))echo"<a href='".h(ME)."dump=".urlencode(isset($_GET["table"])?$_GET["table"]:$_GET["select"])."' id='dump'".bold(isset($_GET["dump"])).">".'Dump'."</a>\n";}echo'<input type="submit" name="logout" value="Logout">
<input type="hidden" name="token" value="',$U,'">
</p>
</form>
<form action="">
<p id="dbs">
';hidden_fields_get();echo($h?html_select("db",array(""=>"(".'database'.")")+$h,DB,"this.form.submit();"):'<input name="db" value="'.h(DB).'">'),'<input type="submit" value="Use"',($h?" class='hidden'":""),'>
';if($gd!="db"&&DB!=""&&$f->select_db(DB)){}echo(isset($_GET["sql"])?'<input type="hidden" name="sql" value="">':(isset($_GET["schema"])?'<input type="hidden" name="schema" value="">':(isset($_GET["dump"])?'<input type="hidden" name="dump" value="">':""))),"</p></form>\n";if($_GET["ns"]!==""&&!$gd&&DB!=""){echo'<p><a href="'.h(ME).'create="'.bold($_GET["create"]==="").">".'Create new table'."</a>\n";$ef=tables_list();if(!$ef)echo"<p class='message'>".'No tables.'."\n";else{$this->tablesPrint($ef);$Rc=array();foreach($ef
as$S=>$V)$Rc[]=preg_quote($S,'/');echo"<script type='text/javascript'>\n","var jushLinks = { $w: [ '".js_escape(ME)."table=\$&', /\\b(".implode("|",$Rc).")\\b/g ] };\n";foreach(array("bac","bra","sqlite_quo","mssql_bra")as$X)echo"jushLinks.$X = jushLinks.$w;\n";echo"</script>\n";}}}}function
tablesPrint($ef){echo"<p id='tables' onmouseover='menuOver(this);' onmouseout='menuOut(this);'>\n";foreach($ef
as$S=>$V){echo'<a href="'.h(ME).'select='.urlencode($S).'"'.bold($_GET["select"]==$S).">".'select'."</a> ",'<a href="'.h(ME).'table='.urlencode($S).'"'.bold($_GET["table"]==$S)." title='".'Show structure'."'>".$this->tableName(array("Name"=>$S))."</a><br>\n";}}}$b=(function_exists('adminer_object')?adminer_object():new
Adminer);if($b->operators===null)$b->operators=$zd;function
page_header($nf,$j="",$Ca=array(),$of=""){global$ca,$b,$f,$rb;header("Content-Type: text/html; charset=utf-8");if($b->headers()){header("X-Frame-Options: deny");header("X-XSS-Protection: 0");}$pf=$nf.($of!=""?": ".h($of):"");$qf=strip_tags($pf.(SERVER!=""&&SERVER!="localhost"?h(" - ".SERVER):"")." - ".$b->name());echo'<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html lang="en" dir="ltr">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="Content-Script-Type" content="text/javascript">
<meta name="robots" content="noindex">
<title>',$qf,'</title>
<link rel="stylesheet" type="text/css" href="',h(preg_replace("~\\?.*~","",ME))."?file=default.css&amp;version=3.5.1",'">
<script type="text/javascript" src="',h(preg_replace("~\\?.*~","",ME))."?file=functions.js&amp;version=3.5.1",'"></script>
';if($b->head()){echo'<link rel="shortcut icon" type="image/x-icon" href="',h(preg_replace("~\\?.*~","",ME))."?file=favicon.ico&amp;version=3.5.1",'" id="favicon">
';if(file_exists("adminer.css")){echo'<link rel="stylesheet" type="text/css" href="adminer.css">
';}}echo'
<body class="ltr nojs" onkeydown="bodyKeydown(event);" onload="bodyLoad(\'',(is_object($f)?substr($f->server_info,0,3):""),'\');',(isset($_COOKIE["adminer_version"])?"":" verifyVersion();"),'">
<script type="text/javascript">
document.body.className = document.body.className.replace(/ nojs/, \' js\');
</script>

<div id="content">
';if($Ca!==null){$z=substr(preg_replace('~(username|db|ns)=[^&]*&~','',ME),0,-1);echo'<p id="breadcrumb"><a href="'.h($z?$z:".").'">'.$rb[DRIVER].'</a> &raquo; ';$z=substr(preg_replace('~(db|ns)=[^&]*&~','',ME),0,-1);$O=(SERVER!=""?h(SERVER):'Server');if($Ca===false)echo"$O\n";else{echo"<a href='".($z?h($z):".")."' accesskey='1' title='Alt+Shift+1'>$O</a> &raquo; ";if($_GET["ns"]!=""||(DB!=""&&is_array($Ca)))echo'<a href="'.h($z."&db=".urlencode(DB).(support("scheme")?"&ns=":"")).'">'.h(DB).'</a> &raquo; ';if(is_array($Ca)){if($_GET["ns"]!="")echo'<a href="'.h(substr(ME,0,-1)).'">'.h($_GET["ns"]).'</a> &raquo; ';foreach($Ca
as$x=>$X){$mb=(is_array($X)?$X[1]:$X);if($mb!="")echo'<a href="'.h(ME."$x=").urlencode(is_array($X)?$X[0]:$X).'">'.h($mb).'</a> &raquo; ';}}echo"$nf\n";}}echo"<h2>$pf</h2>\n";restart_session();$Jf=preg_replace('~^[^?]*~','',$_SERVER["REQUEST_URI"]);$ed=$_SESSION["messages"][$Jf];if($ed){echo"<div class='message'>".implode("</div>\n<div class='message'>",$ed)."</div>\n";unset($_SESSION["messages"][$Jf]);}$h=&get_session("dbs");if(DB!=""&&$h&&!in_array(DB,$h,true))$h=null;if($j)echo"<div class='error'>$j</div>\n";define("PAGE_HEADER",1);}function
page_footer($gd=""){global$b;echo'</div>

<div id="menu">
';$b->navigation($gd);echo'</div>
';}function
int32($C){while($C>=2147483648)$C-=4294967296;while($C<=-2147483649)$C+=4294967296;return(int)$C;}function
long2str($W,$Sf){$M='';foreach($W
as$X)$M.=pack('V',$X);if($Sf)return
substr($M,0,end($W));return$M;}function
str2long($M,$Sf){$W=array_values(unpack('V*',str_pad($M,4*ceil(strlen($M)/4),"\0")));if($Sf)$W[]=strlen($M);return$W;}function
xxtea_mx($Wf,$Vf,$We,$Dc){return
int32((($Wf>>5&0x7FFFFFF)^$Vf<<2)+(($Vf>>3&0x1FFFFFFF)^$Wf<<4))^int32(($We^$Vf)+($Dc^$Wf));}function
encrypt_string($Re,$x){if($Re=="")return"";$x=array_values(unpack("V*",pack("H*",md5($x))));$W=str2long($Re,true);$C=count($W)-1;$Wf=$W[$C];$Vf=$W[0];$G=floor(6+52/($C+1));$We=0;while($G-->0){$We=int32($We+0x9E3779B9);$wb=$We>>2&3;for($Ld=0;$Ld<$C;$Ld++){$Vf=$W[$Ld+1];$id=xxtea_mx($Wf,$Vf,$We,$x[$Ld&3^$wb]);$Wf=int32($W[$Ld]+$id);$W[$Ld]=$Wf;}$Vf=$W[0];$id=xxtea_mx($Wf,$Vf,$We,$x[$Ld&3^$wb]);$Wf=int32($W[$C]+$id);$W[$C]=$Wf;}return
long2str($W,false);}function
decrypt_string($Re,$x){if($Re=="")return"";$x=array_values(unpack("V*",pack("H*",md5($x))));$W=str2long($Re,false);$C=count($W)-1;$Wf=$W[$C];$Vf=$W[0];$G=floor(6+52/($C+1));$We=int32($G*0x9E3779B9);while($We){$wb=$We>>2&3;for($Ld=$C;$Ld>0;$Ld--){$Wf=$W[$Ld-1];$id=xxtea_mx($Wf,$Vf,$We,$x[$Ld&3^$wb]);$Vf=int32($W[$Ld]-$id);$W[$Ld]=$Vf;}$Wf=$W[$C];$id=xxtea_mx($Wf,$Vf,$We,$x[$Ld&3^$wb]);$Vf=int32($W[0]-$id);$W[0]=$Vf;$We=int32($We-0x9E3779B9);}return
long2str($W,true);}$f='';$U=$_SESSION["token"];if(!$_SESSION["token"])$_SESSION["token"]=rand(1,1e6);$Vd=array();if($_COOKIE["adminer_permanent"]){foreach(explode(" ",$_COOKIE["adminer_permanent"])as$X){list($x)=explode(":",$X);$Vd[$x]=$X;}}$c=$_POST["auth"];if($c){session_regenerate_id();$_SESSION["pwds"][$c["driver"]][$c["server"]][$c["username"]]=$c["password"];if($c["permanent"]){$x=base64_encode($c["driver"])."-".base64_encode($c["server"])."-".base64_encode($c["username"]);$fe=$b->permanentLogin();$Vd[$x]="$x:".base64_encode($fe?encrypt_string($c["password"],$fe):"");cookie("adminer_permanent",implode(" ",$Vd));}if(count($_POST)==1||DRIVER!=$c["driver"]||SERVER!=$c["server"]||$_GET["username"]!==$c["username"]||DB!=$c["db"])redirect(auth_url($c["driver"],$c["server"],$c["username"],$c["db"]));}elseif($_POST["logout"]){if($U&&$_POST["token"]!=$U){page_header('Logout','Invalid CSRF token. Send the form again.');page_footer("db");exit;}else{foreach(array("pwds","dbs","queries")as$x)set_session($x,null);$x=base64_encode(DRIVER)."-".base64_encode(SERVER)."-".base64_encode($_GET["username"]);if($Vd[$x]){unset($Vd[$x]);cookie("adminer_permanent",implode(" ",$Vd));}redirect(substr(preg_replace('~(username|db|ns)=[^&]*&~','',ME),0,-1),'Logout successful.');}}elseif($Vd&&!$_SESSION["pwds"]){session_regenerate_id();$fe=$b->permanentLogin();foreach($Vd
as$x=>$X){list(,$Ja)=explode(":",$X);list($qb,$O,$Mf)=array_map('base64_decode',explode("-",$x));$_SESSION["pwds"][$qb][$O][$Mf]=decrypt_string(base64_decode($Ja),$fe);}}function
auth_error($Lb=null){global$f,$b,$U;$Ke=session_name();$j="";if(!$_COOKIE[$Ke]&&$_GET[$Ke]&&ini_bool("session.use_only_cookies"))$j='Session support must be enabled.';elseif(isset($_GET["username"])){if(($_COOKIE[$Ke]||$_GET[$Ke])&&!$U)$j='Session expired, please login again.';else{$Ud=&get_session("pwds");if($Ud!==null){$j=h($Lb?$Lb->getMessage():(is_string($f)?$f:'Invalid credentials.'));$Ud=null;}}}page_header('Login',$j,null);echo"<form action='' method='post'>\n";$b->loginForm();echo"<div>";hidden_fields($_POST,array("auth"));echo"</div>\n","</form>\n";page_footer("auth");}if(isset($_GET["username"])){if(!class_exists("Min_DB")){unset($_SESSION["pwds"][DRIVER]);page_header('No extension',sprintf('None of the supported PHP extensions (%s) are available.',implode(", ",$ae)),false);page_footer("auth");exit;}$f=connect();}if(is_string($f)||!$b->login($_GET["username"],get_session("pwds"))){auth_error();exit;}$U=$_SESSION["token"];if($c&&$_POST["token"])$_POST["token"]=$U;$j=($_POST?($_POST["token"]==$U?"":'Invalid CSRF token. Send the form again.'):($_SERVER["REQUEST_METHOD"]!="POST"?"":sprintf('Too big POST data. Reduce the data or increase the %s configuration directive.','"post_max_size"')));function
connect_error(){global$b,$f,$U,$j,$rb;$h=array();if(DB!="")page_header('Database'.": ".h(DB),'Invalid database.',true);else{if($_POST["db"]&&!$j)queries_redirect(substr(ME,0,-1),'Databases have been dropped.',drop_databases($_POST["db"]));page_header('Select database',$j,false);echo"<p><a href='".h(ME)."database='>".'Create new database'."</a>\n";foreach(array('privileges'=>'Privileges','processlist'=>'Process list','variables'=>'Variables','status'=>'Status',)as$x=>$X){if(support($x))echo"<a href='".h(ME)."$x='>$X</a>\n";}echo"<p>".sprintf('%s version: %s through PHP extension %s',$rb[DRIVER],"<b>$f->server_info</b>","<b>$f->extension</b>")."\n","<p>".sprintf('Logged as: %s',"<b>".h(logged_user())."</b>")."\n";if($_GET["refresh"])set_session("dbs",null);$h=$b->databases();if($h){$Ee=support("scheme");$d=collations();echo"<form action='' method='post'>\n","<table cellspacing='0' class='checkable' onclick='tableClick(event);'>\n","<thead><tr><td>&nbsp;<th>".'Database'."<td>".'Collation'."<td>".'Tables'."</thead>\n";foreach($h
as$i){$ye=h(ME)."db=".urlencode($i);echo"<tr".odd()."><td>".checkbox("db[]",$i,in_array($i,(array)$_POST["db"])),"<th><a href='$ye'>".h($i)."</a>","<td><a href='$ye".($Ee?"&amp;ns=":"")."&amp;database=' title='".'Alter database'."'>".nbsp(db_collation($i,$d))."</a>","<td align='right'><a href='$ye&amp;schema=' id='tables-".h($i)."' title='".'Database schema'."'>?</a>","\n";}echo"</table>\n","<script type='text/javascript'>tableCheck();</script>\n","<p><input type='submit' name='drop' value='".'Drop'."'".confirm("formChecked(this, /db/)").">\n","<input type='hidden' name='token' value='$U'>\n","<a href='".h(ME)."refresh=1'>".'Refresh'."</a>\n","</form>\n";}}page_footer("db");if($h)echo"<script type='text/javascript'>ajaxSetHtml('".js_escape(ME)."script=connect');</script>\n";}if(isset($_GET["status"]))$_GET["variables"]=$_GET["status"];if(!(DB!=""?$f->select_db(DB):isset($_GET["sql"])||isset($_GET["dump"])||isset($_GET["database"])||isset($_GET["processlist"])||isset($_GET["privileges"])||isset($_GET["user"])||isset($_GET["variables"])||$_GET["script"]=="connect")){if(DB!="")set_session("dbs",null);connect_error();exit;}function
select($I,$g=null,$qc="",$Ed=array()){$Rc=array();$u=array();$e=array();$Aa=array();$Af=array();$J=array();odd('');for($r=0;$K=$I->fetch_row();$r++){if(!$r){echo"<table cellspacing='0' class='nowrap'>\n","<thead><tr>";for($v=0;$v<count($K);$v++){$k=$I->fetch_field();$D=$k->name;$Dd=$k->orgtable;$Cd=$k->orgname;$J[$k->table]=$Dd;if($qc)$Rc[$v]=($D=="table"?"table=":($D=="possible_keys"?"indexes=":null));elseif($Dd!=""){if(!isset($u[$Dd])){$u[$Dd]=array();foreach(indexes($Dd,$g)as$t){if($t["type"]=="PRIMARY"){$u[$Dd]=array_flip($t["columns"]);break;}}$e[$Dd]=$u[$Dd];}if(isset($e[$Dd][$Cd])){unset($e[$Dd][$Cd]);$u[$Dd][$Cd]=$v;$Rc[$v]=$Dd;}}if($k->charsetnr==63)$Aa[$v]=true;$Af[$v]=$k->type;$D=h($D);echo"<th".($Dd!=""||$k->name!=$Cd?" title='".h(($Dd!=""?"$Dd.":"").$Cd)."'":"").">".($qc?"<a href='$qc".strtolower($D)."' target='_blank' rel='noreferrer'>$D</a>":$D);}echo"</thead>\n";}echo"<tr".odd().">";foreach($K
as$x=>$X){if($X===null)$X="<i>NULL</i>";elseif($Aa[$x]&&!is_utf8($X))$X="<i>".lang(array('%d byte','%d bytes'),strlen($X))."</i>";elseif(!strlen($X))$X="&nbsp;";else{$X=h($X);if($Af[$x]==254)$X="<code>$X</code>";}if(isset($Rc[$x])&&!$e[$Rc[$x]]){if($qc){$S=$K[array_search("table=",$Rc)];$z=$Rc[$x].urlencode($Ed[$S]!=""?$Ed[$S]:$S);}else{$z="edit=".urlencode($Rc[$x]);foreach($u[$Rc[$x]]as$Ka=>$v)$z.="&where".urlencode("[".bracket_escape($Ka)."]")."=".urlencode($K[$v]);}$X="<a href='".h(ME.$z)."'>$X</a>";}echo"<td>$X";}}echo($r?"</table>":"<p class='message'>".'No rows.')."\n";return$J;}function
referencable_primary($Ge){$J=array();foreach(table_status()as$af=>$S){if($af!=$Ge&&fk_support($S)){foreach(fields($af)as$k){if($k["primary"]){if($J[$af]){unset($J[$af]);break;}$J[$af]=$k;}}}}return$J;}function
textarea($D,$Y,$L=10,$Na=80){echo"<textarea name='$D' rows='$L' cols='$Na' class='sqlarea' spellcheck='false' wrap='off' onkeydown='return textareaKeydown(this, event);'>";if(is_array($Y)){foreach($Y
as$X)echo
h($X[0])."\n\n\n";}else
echo
h($Y);echo"</textarea>";}function
format_time($Oe,$Cb){return" <span class='time'>(".sprintf('%.3f s',max(0,array_sum(explode(" ",$Cb))-array_sum(explode(" ",$Oe)))).")</span>";}function
edit_type($x,$k,$d,$n=array()){global$Se,$Af,$Hf,$vd;echo'<td><select name="',$x,'[type]" class="type" onfocus="lastType = selectValue(this);" onchange="editingTypeChange(this);">',optionlist((!$k["type"]||isset($Af[$k["type"]])?array():array($k["type"]))+$Se+($n?array('Foreign keys'=>$n):array()),$k["type"]),'</select>
<td><input name="',$x,'[length]" value="',h($k["length"]),'" size="3" onfocus="editingLengthFocus(this);"><td class="options">',"<select name='$x"."[collation]'".(ereg('(char|text|enum|set)$',$k["type"])?"":" class='hidden'").'><option value="">('.'collation'.')'.optionlist($d,$k["collation"]).'</select>',($Hf?"<select name='$x"."[unsigned]'".(!$k["type"]||ereg('(int|float|double|decimal)$',$k["type"])?"":" class='hidden'").'><option>'.optionlist($Hf,$k["unsigned"]).'</select>':''),($n?"<select name='$x"."[on_delete]'".(ereg("`",$k["type"])?"":" class='hidden'")."><option value=''>(".'ON DELETE'.")".optionlist(explode("|",$vd),$k["on_delete"])."</select> ":" ");}function
process_length($Oc){global$Fb;return(preg_match("~^\\s*(?:$Fb)(?:\\s*,\\s*(?:$Fb))*\\s*\$~",$Oc)&&preg_match_all("~$Fb~",$Oc,$Uc)?implode(",",$Uc[0]):preg_replace('~[^0-9,+-]~','',$Oc));}function
process_type($k,$La="COLLATE"){global$Hf;return" $k[type]".($k["length"]!=""?"(".process_length($k["length"]).")":"").(ereg('int|float|double|decimal',$k["type"])&&in_array($k["unsigned"],$Hf)?" $k[unsigned]":"").(ereg('char|text|enum|set',$k["type"])&&$k["collation"]?" $La ".q($k["collation"]):"");}function
process_field($k,$zf){return
array(idf_escape(trim($k["field"])),process_type($zf),($k["null"]?" NULL":" NOT NULL"),(isset($k["default"])?" DEFAULT ".(($k["type"]=="timestamp"&&eregi('^CURRENT_TIMESTAMP$',$k["default"]))||($k["type"]=="bit"&&ereg("^([0-9]+|b'[0-1]+')\$",$k["default"]))?$k["default"]:q($k["default"])):""),($k["on_update"]?" ON UPDATE $k[on_update]":""),(support("comment")&&$k["comment"]!=""?" COMMENT ".q($k["comment"]):""),($k["auto_increment"]?auto_increment():null),);}function
type_class($V){foreach(array('char'=>'text','date'=>'time|year','binary'=>'blob','enum'=>'set',)as$x=>$X){if(ereg("$x|$X",$V))return" class='$x'";}}function
edit_fields($l,$d,$V="TABLE",$ra=0,$n=array(),$Sa=false){global$xc;echo'<thead><tr class="wrap">
';if($V=="PROCEDURE"){echo'<td>&nbsp;';}echo'<th>',($V=="TABLE"?'Column name':'Parameter name'),'<td>Type<textarea id="enum-edit" rows="4" cols="12" wrap="off" style="display: none;" onblur="editingLengthBlur(this);"></textarea>
<td>Length
<td>Options
';if($V=="TABLE"){echo'<td>NULL
<td><input type="radio" name="auto_increment_col" value=""><acronym title="Auto Increment">AI</acronym>
<td',($_POST["defaults"]?"":" class='hidden'"),'>Default values
',(support("comment")?"<td".($Sa?"":" class='hidden'").">".'Comment':"");}echo'<td>',"<input type='image' class='icon' name='add[".(support("move_col")?0:count($l))."]' src='".h(preg_replace("~\\?.*~","",ME))."?file=plus.gif&amp;version=3.5.1' alt='+' title='".'Add next'."'>",'<script type="text/javascript">row_count = ',count($l),';</script>
</thead>
<tbody onkeydown="return editingKeydown(event);">
';foreach($l
as$r=>$k){$r++;$Fd=$k[($_POST?"orig":"field")];$ob=(isset($_POST["add"][$r-1])||(isset($k["field"])&&!$_POST["drop_col"][$r]))&&(support("drop_col")||$Fd=="");echo'<tr',($ob?"":" style='display: none;'"),'>
',($V=="PROCEDURE"?"<td>".html_select("fields[$r][inout]",explode("|",$xc),$k["inout"]):""),'<th>';if($ob){echo'<input name="fields[',$r,'][field]" value="',h($k["field"]),'" onchange="',($k["field"]!=""||count($l)>1?"":"editingAddRow(this, $ra); "),'editingNameChange(this);" maxlength="64">';}echo'<input type="hidden" name="fields[',$r,'][orig]" value="',h($Fd),'">
';edit_type("fields[$r]",$k,$d,$n);if($V=="TABLE"){echo'<td>',checkbox("fields[$r][null]",1,$k["null"]),'<td><input type="radio" name="auto_increment_col" value="',$r,'"';if($k["auto_increment"]){echo' checked';}?> onclick="var field = this.form['fields[' + this.value + '][field]']; if (!field.value) { field.value = 'id'; field.onchange(); }">
<td<?php echo($_POST["defaults"]?"":" class='hidden'"),'>',checkbox("fields[$r][has_default]",1,$k["has_default"]),'<input name="fields[',$r,'][default]" value="',h($k["default"]),'" onchange="this.previousSibling.checked = true;">
',(support("comment")?"<td".($Sa?"":" class='hidden'")."><input name='fields[$r][comment]' value='".h($k["comment"])."' maxlength='255'>":"");}echo"<td>",(support("move_col")?"<input type='image' class='icon' name='add[$r]' src='".h(preg_replace("~\\?.*~","",ME))."?file=plus.gif&amp;version=3.5.1' alt='+' title='".'Add next'."' onclick='return !editingAddRow(this, $ra, 1);'>&nbsp;"."<input type='image' class='icon' name='up[$r]' src='".h(preg_replace("~\\?.*~","",ME))."?file=up.gif&amp;version=3.5.1' alt='^' title='".'Move up'."'>&nbsp;"."<input type='image' class='icon' name='down[$r]' src='".h(preg_replace("~\\?.*~","",ME))."?file=down.gif&amp;version=3.5.1' alt='v' title='".'Move down'."'>&nbsp;":""),($Fd==""||support("drop_col")?"<input type='image' class='icon' name='drop_col[$r]' src='".h(preg_replace("~\\?.*~","",ME))."?file=cross.gif&amp;version=3.5.1' alt='x' title='".'Remove'."' onclick='return !editingRemoveRow(this);'>":""),"\n";}}function
process_fields(&$l){ksort($l);$pd=0;if($_POST["up"]){$Ic=0;foreach($l
as$x=>$k){if(key($_POST["up"])==$x){unset($l[$x]);array_splice($l,$Ic,0,array($k));break;}if(isset($k["field"]))$Ic=$pd;$pd++;}}if($_POST["down"]){$dc=false;foreach($l
as$x=>$k){if(isset($k["field"])&&$dc){unset($l[key($_POST["down"])]);array_splice($l,$pd,0,array($dc));break;}if(key($_POST["down"])==$x)$dc=$k;$pd++;}}$l=array_values($l);if($_POST["add"])array_splice($l,key($_POST["add"]),0,array(array()));}function
normalize_enum($A){return"'".str_replace("'","''",addcslashes(stripcslashes(str_replace($A[0][0].$A[0][0],$A[0][0],substr($A[0],1,-1))),'\\'))."'";}function
grant($p,$he,$e,$ud){if(!$he)return
true;if($he==array("ALL PRIVILEGES","GRANT OPTION"))return($p=="GRANT"?queries("$p ALL PRIVILEGES$ud WITH GRANT OPTION"):queries("$p ALL PRIVILEGES$ud")&&queries("$p GRANT OPTION$ud"));return
queries("$p ".preg_replace('~(GRANT OPTION)\\([^)]*\\)~','\\1',implode("$e, ",$he).$e).$ud);}function
drop_create($sb,$ab,$_,$dd,$bd,$cd,$D){if($_POST["drop"])return
query_redirect($sb,$_,$dd,true,!$_POST["dropped"]);$tb=$D!=""&&($_POST["dropped"]||queries($sb));$cb=queries($ab);if(!queries_redirect($_,($D!=""?$bd:$cd),$cb)&&$tb)redirect(null,$dd);return$tb;}function
remove_definer($H){return
preg_replace('~^([A-Z =]+) DEFINER=`'.preg_replace('~@(.*)~','`@`(%|\\1)',logged_user()).'`~','\\1',$H);}function
tar_file($Wb,$Wa){$J=pack("a100a8a8a8a12a12",$Wb,644,0,0,decoct(strlen($Wa)),decoct(time()));$Ia=8*32;for($r=0;$r<strlen($J);$r++)$Ia+=ord($J[$r]);$J.=sprintf("%06o",$Ia)."\0 ";return$J.str_repeat("\0",512-strlen($J)).$Wa.str_repeat("\0",511-(strlen($Wa)+511)%
512);}function
ini_bytes($wc){$X=ini_get($wc);switch(strtolower(substr($X,-1))){case'g':$X*=1024;case'm':$X*=1024;case'k':$X*=1024;}return$X;}session_cache_limiter("");if(!ini_bool("session.use_cookies")||@ini_set("session.use_cookies",false)!==false)session_write_close();$vd="RESTRICT|NO ACTION|CASCADE|SET NULL|SET DEFAULT";$Fb="'(?:''|[^'\\\\]|\\\\.)*+'";$xc="IN|OUT|INOUT";if(isset($_GET["select"])&&($_POST["edit"]||$_POST["clone"])&&!$_POST["save"])$_GET["edit"]=$_GET["select"];if(isset($_GET["callf"]))$_GET["call"]=$_GET["callf"];if(isset($_GET["function"]))$_GET["procedure"]=$_GET["function"];if(isset($_GET["download"])){$a=$_GET["download"];header("Content-Type: application/octet-stream");header("Content-Disposition: attachment; filename=".friendly_url("$a-".implode("_",$_GET["where"])).".".friendly_url($_GET["field"]));echo$f->result("SELECT".limit(idf_escape($_GET["field"])." FROM ".table($a)," WHERE ".where($_GET),1));exit;}elseif(isset($_GET["table"])){$a=$_GET["table"];$l=fields($a);if(!$l)$j=error();$T=($l?table_status($a):array());page_header(($l&&is_view($T)?'View':'Table').": ".h($a),$j);$b->selectLinks($T);$Ra=$T["Comment"];if($Ra!="")echo"<p>".'Comment'.": ".h($Ra)."\n";if($l){echo"<table cellspacing='0'>\n","<thead><tr><th>".'Column'."<td>".'Type'.(support("comment")?"<td>".'Comment':"")."</thead>\n";foreach($l
as$k){echo"<tr".odd()."><th>".h($k["field"]),"<td title='".h($k["collation"])."'>".h($k["full_type"]).($k["null"]?" <i>NULL</i>":"").($k["auto_increment"]?" <i>".'Auto Increment'."</i>":""),(isset($k["default"])?" [<b>".h($k["default"])."</b>]":""),(support("comment")?"<td>".nbsp($k["comment"]):""),"\n";}echo"</table>\n";if(!is_view($T)){echo"<h3>".'Indexes'."</h3>\n";$u=indexes($a);if($u){echo"<table cellspacing='0'>\n";foreach($u
as$D=>$t){ksort($t["columns"]);$ee=array();foreach($t["columns"]as$x=>$X)$ee[]="<i>".h($X)."</i>".($t["lengths"][$x]?"(".$t["lengths"][$x].")":"");echo"<tr title='".h($D)."'><th>$t[type]<td>".implode(", ",$ee)."\n";}echo"</table>\n";}echo'<p><a href="'.h(ME).'indexes='.urlencode($a).'">'.'Alter indexes'."</a>\n";if(fk_support($T)){echo"<h3>".'Foreign keys'."</h3>\n";$n=foreign_keys($a);if($n){echo"<table cellspacing='0'>\n","<thead><tr><th>".'Source'."<td>".'Target'."<td>".'ON DELETE'."<td>".'ON UPDATE'.($w!="sqlite"?"<td>&nbsp;":"")."</thead>\n";foreach($n
as$D=>$m){echo"<tr title='".h($D)."'>","<th><i>".implode("</i>, <i>",array_map('h',$m["source"]))."</i>","<td><a href='".h($m["db"]!=""?preg_replace('~db=[^&]*~',"db=".urlencode($m["db"]),ME):($m["ns"]!=""?preg_replace('~ns=[^&]*~',"ns=".urlencode($m["ns"]),ME):ME))."table=".urlencode($m["table"])."'>".($m["db"]!=""?"<b>".h($m["db"])."</b>.":"").($m["ns"]!=""?"<b>".h($m["ns"])."</b>.":"").h($m["table"])."</a>","(<i>".implode("</i>, <i>",array_map('h',$m["target"]))."</i>)","<td>".nbsp($m["on_delete"])."\n","<td>".nbsp($m["on_update"])."\n",($w=="sqlite"?"":'<td><a href="'.h(ME.'foreign='.urlencode($a).'&name='.urlencode($D)).'">'.'Alter'.'</a>');}echo"</table>\n";}if($w!="sqlite")echo'<p><a href="'.h(ME).'foreign='.urlencode($a).'">'.'Add foreign key'."</a>\n";}if(support("trigger")){echo"<h3>".'Triggers'."</h3>\n";$yf=triggers($a);if($yf){echo"<table cellspacing='0'>\n";foreach($yf
as$x=>$X)echo"<tr valign='top'><td>$X[0]<td>$X[1]<th>".h($x)."<td><a href='".h(ME.'trigger='.urlencode($a).'&name='.urlencode($x))."'>".'Alter'."</a>\n";echo"</table>\n";}echo'<p><a href="'.h(ME).'trigger='.urlencode($a).'">'.'Add trigger'."</a>\n";}}}}elseif(isset($_GET["schema"])){page_header('Database schema',"",array(),DB.($_GET["ns"]?".$_GET[ns]":""));$bf=array();$cf=array();$D="adminer_schema";$ea=($_GET["schema"]?$_GET["schema"]:$_COOKIE[($_COOKIE["$D-".DB]?"$D-".DB:$D)]);preg_match_all('~([^:]+):([-0-9.]+)x([-0-9.]+)(_|$)~',$ea,$Uc,PREG_SET_ORDER);foreach($Uc
as$r=>$A){$bf[$A[1]]=array($A[2],$A[3]);$cf[]="\n\t'".js_escape($A[1])."': [ $A[2], $A[3] ]";}$rf=0;$_a=-1;$De=array();$re=array();$Mc=array();foreach(table_status()as$T){if(!isset($T["Engine"]))continue;$Xd=0;$De[$T["Name"]]["fields"]=array();foreach(fields($T["Name"])as$D=>$k){$Xd+=1.25;$k["pos"]=$Xd;$De[$T["Name"]]["fields"][$D]=$k;}$De[$T["Name"]]["pos"]=($bf[$T["Name"]]?$bf[$T["Name"]]:array($rf,0));foreach($b->foreignKeys($T["Name"])as$X){if(!$X["db"]){$Kc=$_a;if($bf[$T["Name"]][1]||$bf[$X["table"]][1])$Kc=min(floatval($bf[$T["Name"]][1]),floatval($bf[$X["table"]][1]))-1;else$_a-=.1;while($Mc[(string)$Kc])$Kc-=.0001;$De[$T["Name"]]["references"][$X["table"]][(string)$Kc]=array($X["source"],$X["target"]);$re[$X["table"]][$T["Name"]][(string)$Kc]=$X["target"];$Mc[(string)$Kc]=true;}}$rf=max($rf,$De[$T["Name"]]["pos"][0]+2.5+$Xd);}echo'<div id="schema" style="height: ',$rf,'em;" onselectstart="return false;">
<script type="text/javascript">
var tablePos = {',implode(",",$cf)."\n",'};
var em = document.getElementById(\'schema\').offsetHeight / ',$rf,';
document.onmousemove = schemaMousemove;
document.onmouseup = function (ev) {
	schemaMouseup(ev, \'',js_escape(DB),'\');
};
</script>
';foreach($De
as$D=>$S){echo"<div class='table' style='top: ".$S["pos"][0]."em; left: ".$S["pos"][1]."em;' onmousedown='schemaMousedown(this, event);'>",'<a href="'.h(ME).'table='.urlencode($D).'"><b>'.h($D)."</b></a>";foreach($S["fields"]as$k){$X='<span'.type_class($k["type"]).' title="'.h($k["full_type"].($k["null"]?" NULL":'')).'">'.h($k["field"]).'</span>';echo"<br>".($k["primary"]?"<i>$X</i>":$X);}foreach((array)$S["references"]as$if=>$se){foreach($se
as$Kc=>$oe){$Lc=$Kc-$bf[$D][1];$r=0;foreach($oe[0]as$Le)echo"\n<div class='references' title='".h($if)."' id='refs$Kc-".($r++)."' style='left: $Lc"."em; top: ".$S["fields"][$Le]["pos"]."em; padding-top: .5em;'><div style='border-top: 1px solid Gray; width: ".(-$Lc)."em;'></div></div>";}}foreach((array)$re[$D]as$if=>$se){foreach($se
as$Kc=>$e){$Lc=$Kc-$bf[$D][1];$r=0;foreach($e
as$hf)echo"\n<div class='references' title='".h($if)."' id='refd$Kc-".($r++)."' style='left: $Lc"."em; top: ".$S["fields"][$hf]["pos"]."em; height: 1.25em; background: url(".h(preg_replace("~\\?.*~","",ME))."?file=arrow.gif) no-repeat right center;&amp;version=3.5.1'><div style='height: .5em; border-bottom: 1px solid Gray; width: ".(-$Lc)."em;'></div></div>";}}echo"\n</div>\n";}foreach($De
as$D=>$S){foreach((array)$S["references"]as$if=>$se){foreach($se
as$Kc=>$oe){$fd=$rf;$Yc=-10;foreach($oe[0]as$x=>$Le){$Yd=$S["pos"][0]+$S["fields"][$Le]["pos"];$Zd=$De[$if]["pos"][0]+$De[$if]["fields"][$oe[1][$x]]["pos"];$fd=min($fd,$Yd,$Zd);$Yc=max($Yc,$Yd,$Zd);}echo"<div class='references' id='refl$Kc' style='left: $Kc"."em; top: $fd"."em; padding: .5em 0;'><div style='border-right: 1px solid Gray; margin-top: 1px; height: ".($Yc-$fd)."em;'></div></div>\n";}}}echo'</div>
<p><a href="',h(ME."schema=".urlencode($ea)),'" id="schema-link">Permanent link</a>
';}elseif(isset($_GET["dump"])){$a=$_GET["dump"];if($_POST){$Ya="";foreach(array("output","format","db_style","routines","events","table_style","auto_increment","triggers","data_style")as$x)$Ya.="&$x=".urlencode($_POST[$x]);cookie("adminer_export",substr($Ya,1));$Rb=dump_headers(($a!=""?$a:DB),(DB==""||count((array)$_POST["tables"]+(array)$_POST["data"])>1));$Ac=($_POST["format"]=="sql");if($Ac)echo"-- Adminer $ga ".$rb[DRIVER]." dump

".($w!="sql"?"":"SET NAMES utf8;
SET foreign_key_checks = 0;
SET time_zone = ".q($f->result("SELECT @@time_zone")).";
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

");$R=$_POST["db_style"];$h=array(DB);if(DB==""){$h=$_POST["databases"];if(is_string($h))$h=explode("\n",rtrim(str_replace("\r","",$h),"\n"));}foreach((array)$h
as$i){if($f->select_db($i)){if($Ac&&ereg('CREATE',$R)&&($ab=$f->result("SHOW CREATE DATABASE ".idf_escape($i),1))){if($R=="DROP+CREATE")echo"DROP DATABASE IF EXISTS ".idf_escape($i).";\n";echo($R=="CREATE+ALTER"?preg_replace('~^CREATE DATABASE ~','\\0IF NOT EXISTS ',$ab):$ab).";\n";}if($Ac){if($R)echo
use_sql($i).";\n\n";if(in_array("CREATE+ALTER",array($R,$_POST["table_style"])))echo"SET @adminer_alter = '';\n\n";$Jd="";if($_POST["routines"]){foreach(array("FUNCTION","PROCEDURE")as$ze){foreach(get_rows("SHOW $ze STATUS WHERE Db = ".q($i),null,"-- ")as$K)$Jd.=($R!='DROP+CREATE'?"DROP $ze IF EXISTS ".idf_escape($K["Name"]).";;\n":"").remove_definer($f->result("SHOW CREATE $ze ".idf_escape($K["Name"]),2)).";;\n\n";}}if($_POST["events"]){foreach(get_rows("SHOW EVENTS",null,"-- ")as$K)$Jd.=($R!='DROP+CREATE'?"DROP EVENT IF EXISTS ".idf_escape($K["Name"]).";;\n":"").remove_definer($f->result("SHOW CREATE EVENT ".idf_escape($K["Name"]),3)).";;\n\n";}if($Jd)echo"DELIMITER ;;\n\n$Jd"."DELIMITER ;\n\n";}if($_POST["table_style"]||$_POST["data_style"]){$Qf=array();foreach(table_status()as$T){$S=(DB==""||in_array($T["Name"],(array)$_POST["tables"]));$fb=(DB==""||in_array($T["Name"],(array)$_POST["data"]));if($S||$fb){if(!is_view($T)){if($Rb=="tar")ob_start();$b->dumpTable($T["Name"],($S?$_POST["table_style"]:""));if($fb)$b->dumpData($T["Name"],$_POST["data_style"],"SELECT * FROM ".table($T["Name"]));if($Ac&&$_POST["triggers"]&&$S&&($yf=trigger_sql($T["Name"],$_POST["table_style"])))echo"\nDELIMITER ;;\n$yf\nDELIMITER ;\n";if($Rb=="tar")echo
tar_file((DB!=""?"":"$i/")."$T[Name].csv",ob_get_clean());elseif($Ac)echo"\n";}elseif($Ac)$Qf[]=$T["Name"];}}foreach($Qf
as$Pf)$b->dumpTable($Pf,$_POST["table_style"],true);if($Rb=="tar")echo
pack("x512");}if($R=="CREATE+ALTER"&&$Ac){$H="SELECT TABLE_NAME, ENGINE, TABLE_COLLATION, TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()";echo"DELIMITER ;;
CREATE PROCEDURE adminer_alter (INOUT alter_command text) BEGIN
	DECLARE _table_name, _engine, _table_collation varchar(64);
	DECLARE _table_comment varchar(64);
	DECLARE done bool DEFAULT 0;
	DECLARE tables CURSOR FOR $H;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
	OPEN tables;
	REPEAT
		FETCH tables INTO _table_name, _engine, _table_collation, _table_comment;
		IF NOT done THEN
			CASE _table_name";foreach(get_rows($H)as$K){$Ra=q($K["ENGINE"]=="InnoDB"?preg_replace('~(?:(.+); )?InnoDB free: .*~','\\1',$K["TABLE_COMMENT"]):$K["TABLE_COMMENT"]);echo"
				WHEN ".q($K["TABLE_NAME"])." THEN
					".(isset($K["ENGINE"])?"IF _engine != '$K[ENGINE]' OR _table_collation != '$K[TABLE_COLLATION]' OR _table_comment != $Ra THEN
						ALTER TABLE ".idf_escape($K["TABLE_NAME"])." ENGINE=$K[ENGINE] COLLATE=$K[TABLE_COLLATION] COMMENT=$Ra;
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
";}if(in_array("CREATE+ALTER",array($R,$_POST["table_style"]))&&$Ac)echo"SELECT @adminer_alter;\n";}}if($Ac)echo"-- ".$f->result("SELECT NOW()")."\n";exit;}page_header('Export',"",($_GET["export"]!=""?array("table"=>$_GET["export"]):array()),DB);echo'
<form action="" method="post">
<table cellspacing="0">
';$ib=array('','USE','DROP+CREATE','CREATE');$df=array('','DROP+CREATE','CREATE');$gb=array('','TRUNCATE+INSERT','INSERT');if($w=="sql"){$ib[]='CREATE+ALTER';$df[]='CREATE+ALTER';$gb[]='INSERT+UPDATE';}parse_str($_COOKIE["adminer_export"],$K);if(!$K)$K=array("output"=>"text","format"=>"sql","db_style"=>(DB!=""?"":"CREATE"),"table_style"=>"DROP+CREATE","data_style"=>"INSERT");if(!isset($K["events"])){$K["routines"]=$K["events"]=($_GET["dump"]=="");$K["triggers"]=$K["table_style"];}echo"<tr><th>".'Output'."<td>".html_select("output",$b->dumpOutput(),$K["output"],0)."\n";echo"<tr><th>".'Format'."<td>".html_select("format",$b->dumpFormat(),$K["format"],0)."\n";echo($w=="sqlite"?"":"<tr><th>".'Database'."<td>".html_select('db_style',$ib,$K["db_style"]).(support("routine")?checkbox("routines",1,$K["routines"],'Routines'):"").(support("event")?checkbox("events",1,$K["events"],'Events'):"")),"<tr><th>".'Tables'."<td>".html_select('table_style',$df,$K["table_style"]).checkbox("auto_increment",1,$K["auto_increment"],'Auto Increment').(support("trigger")?checkbox("triggers",1,$K["triggers"],'Triggers'):""),"<tr><th>".'Data'."<td>".html_select('data_style',$gb,$K["data_style"]),'</table>
<p><input type="submit" value="Export">

<table cellspacing="0">
';$ce=array();if(DB!=""){$Ha=($a!=""?"":" checked");echo"<thead><tr>","<th style='text-align: left;'><label><input type='checkbox' id='check-tables'$Ha onclick='formCheck(this, /^tables\\[/);'>".'Tables'."</label>","<th style='text-align: right;'><label>".'Data'."<input type='checkbox' id='check-data'$Ha onclick='formCheck(this, /^data\\[/);'></label>","</thead>\n";$Qf="";foreach(table_status()as$T){$D=$T["Name"];$be=ereg_replace("_.*","",$D);$Ha=($a==""||$a==(substr($a,-1)=="%"?"$be%":$D));$ee="<tr><td>".checkbox("tables[]",$D,$Ha,$D,"checkboxClick(event, this); formUncheck('check-tables');");if(is_view($T))$Qf.="$ee\n";else
echo"$ee<td align='right'><label>".($T["Engine"]=="InnoDB"&&$T["Rows"]?"~ ":"").$T["Rows"].checkbox("data[]",$D,$Ha,"","checkboxClick(event, this); formUncheck('check-data');")."</label>\n";$ce[$be]++;}echo$Qf;}else{echo"<thead><tr><th style='text-align: left;'><label><input type='checkbox' id='check-databases'".($a==""?" checked":"")." onclick='formCheck(this, /^databases\\[/);'>".'Database'."</label></thead>\n";$h=$b->databases();if($h){foreach($h
as$i){if(!information_schema($i)){$be=ereg_replace("_.*","",$i);echo"<tr><td>".checkbox("databases[]",$i,$a==""||$a=="$be%",$i,"formUncheck('check-databases');")."</label>\n";$ce[$be]++;}}}else
echo"<tr><td><textarea name='databases' rows='10' cols='20'></textarea>";}echo'</table>
</form>
';$Yb=true;foreach($ce
as$x=>$X){if($x!=""&&$X>1){echo($Yb?"<p>":" ")."<a href='".h(ME)."dump=".urlencode("$x%")."'>".h($x)."</a>";$Yb=false;}}}elseif(isset($_GET["privileges"])){page_header('Privileges');$I=$f->query("SELECT User, Host FROM mysql.".(DB==""?"user":"db WHERE ".q(DB)." LIKE Db")." ORDER BY Host, User");$p=$I;if(!$I)$I=$f->query("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', 1) AS User, SUBSTRING_INDEX(CURRENT_USER, '@', -1) AS Host");echo"<form action=''><p>\n";hidden_fields_get();echo"<input type='hidden' name='db' value='".h(DB)."'>\n",($p?"":"<input type='hidden' name='grant' value=''>\n"),"<table cellspacing='0'>\n","<thead><tr><th>".'Username'."<th>".'Server'."<th>&nbsp;</thead>\n";while($K=$I->fetch_assoc())echo'<tr'.odd().'><td>'.h($K["User"])."<td>".h($K["Host"]).'<td><a href="'.h(ME.'user='.urlencode($K["User"]).'&host='.urlencode($K["Host"])).'">'.'Edit'."</a>\n";if(!$p||DB!="")echo"<tr".odd()."><td><input name='user'><td><input name='host' value='localhost'><td><input type='submit' value='".'Edit'."'>\n";echo"</table>\n","</form>\n",'<p><a href="'.h(ME).'user=">'.'Create user'."</a>";}elseif(isset($_GET["sql"])){if(!$j&&$_POST["export"]){dump_headers("sql");$b->dumpTable("","");$b->dumpData("","table",$_POST["query"]);exit;}restart_session();$oc=&get_session("queries");$nc=&$oc[DB];if(!$j&&$_POST["clear"]){$nc=array();redirect(remove_from_uri("history"));}page_header('SQL command',$j);if(!$j&&$_POST){$fc=false;$H=$_POST["query"];if($_POST["webfile"]){$fc=@fopen((file_exists("adminer.sql")?"adminer.sql":(file_exists("adminer.sql.gz")?"compress.zlib://adminer.sql.gz":"compress.bzip2://adminer.sql.bz2")),"rb");$H=($fc?fread($fc,1e6):false);}elseif($_FILES&&$_FILES["sql_file"]["error"]!=UPLOAD_ERR_NO_FILE)$H=get_file("sql_file",true);if(is_string($H)){if(function_exists('memory_get_usage'))@ini_set("memory_limit",max(ini_bytes("memory_limit"),2*strlen($H)+memory_get_usage()+8e6));if($H!=""&&strlen($H)<1e6){$G=$H.(ereg(";[ \t\r\n]*\$",$H)?"":";");if(!$nc||reset(end($nc))!=$G)$nc[]=array($G,time());}$Me="(?:\\s|/\\*.*\\*/|(?:#|-- )[^\n]*\n|--\n)";if(!ini_bool("session.use_cookies"))session_write_close();$lb=";";$pd=0;$Bb=true;$g=connect();if(is_object($g)&&DB!="")$g->select_db(DB);$Qa=0;$Hb=array();$Qc=0;$Od='[\'"'.($w=="sql"?'`#':($w=="sqlite"?'`[':($w=="mssql"?'[':''))).']|/\\*|-- |$'.($w=="pgsql"?'|\\$[^$]*\\$':'');$sf=microtime();parse_str($_COOKIE["adminer_export"],$ka);$vb=$b->dumpFormat();unset($vb["sql"]);while($H!=""){if(!$pd&&preg_match("~^$Me*DELIMITER\\s+(\\S+)~i",$H,$A)){$lb=$A[1];$H=substr($H,strlen($A[0]));}else{preg_match('('.preg_quote($lb)."\\s*|$Od)",$H,$A,PREG_OFFSET_CAPTURE,$pd);list($dc,$Xd)=$A[0];if(!$dc&&$fc&&!feof($fc))$H.=fread($fc,1e5);else{if(!$dc&&rtrim($H)=="")break;$pd=$Xd+strlen($dc);if($dc&&rtrim($dc)!=$lb){while(preg_match('('.($dc=='/*'?'\\*/':($dc=='['?']':(ereg('^-- |^#',$dc)?"\n":preg_quote($dc)."|\\\\."))).'|$)s',$H,$A,PREG_OFFSET_CAPTURE,$pd)){$M=$A[0][0];if(!$M&&$fc&&!feof($fc))$H.=fread($fc,1e5);else{$pd=$A[0][1]+strlen($M);if($M[0]!="\\")break;}}}else{$Bb=false;$G=substr($H,0,$Xd);$Qa++;$ee="<pre id='sql-$Qa'><code class='jush-$w'>".shorten_utf8(trim($G),1000)."</code></pre>\n";if(!$_POST["only_errors"]){echo$ee;ob_flush();flush();}$Oe=microtime();if($f->multi_query($G)&&is_object($g)&&preg_match("~^$Me*USE\\b~isU",$G))$g->query($G);do{$I=$f->store_result();$Cb=microtime();$lf=format_time($Oe,$Cb).(strlen($G)<1000?" <a href='".h(ME)."sql=".urlencode(trim($G))."'>".'Edit'."</a>":"");if($f->error){echo($_POST["only_errors"]?$ee:""),"<p class='error'>".'Error in query'.": ".error()."\n";$Hb[]=" <a href='#sql-$Qa'>$Qa</a>";if($_POST["error_stops"])break
2;}elseif(is_object($I)){$Ed=select($I,$g);if(!$_POST["only_errors"]){echo"<form action='' method='post'>\n","<p>".($I->num_rows?lang(array('%d row','%d rows'),$I->num_rows):"").$lf;$s="export-$Qa";$Qb=", <a href='#$s' onclick=\"return !toggle('$s');\">".'Export'."</a><span id='$s' class='hidden'>: ".html_select("output",$b->dumpOutput(),$ka["output"])." ".html_select("format",$vb,$ka["format"])."<input type='hidden' name='query' value='".h($G)."'>"." <input type='submit' name='export' value='".'Export'."'><input type='hidden' name='token' value='$U'></span>\n";if($g&&preg_match("~^($Me|\\()*SELECT\\b~isU",$G)&&($Pb=explain($g,$G))){$s="explain-$Qa";echo", <a href='#$s' onclick=\"return !toggle('$s');\">EXPLAIN</a>$Qb","<div id='$s' class='hidden'>\n";select($Pb,$g,($w=="sql"?"http://dev.mysql.com/doc/refman/".substr($f->server_info,0,3)."/en/explain-output.html#explain_":""),$Ed);echo"</div>\n";}else
echo$Qb;echo"</form>\n";}}else{if(preg_match("~^$Me*(CREATE|DROP|ALTER)$Me+(DATABASE|SCHEMA)\\b~isU",$G)){restart_session();set_session("dbs",null);session_write_close();}if(!$_POST["only_errors"])echo"<p class='message' title='".h($f->info)."'>".lang(array('Query executed OK, %d row affected.','Query executed OK, %d rows affected.'),$f->affected_rows)."$lf\n";}$Oe=$Cb;}while($f->next_result());$Qc+=substr_count($G.$dc,"\n");$H=substr($H,$pd);$pd=0;}}}}if($Bb)echo"<p class='message'>".'No commands to execute.'."\n";elseif($_POST["only_errors"])echo"<p class='message'>".lang(array('%d query executed OK.','%d queries executed OK.'),$Qa-count($Hb)).format_time($sf,microtime())."\n";elseif($Hb&&$Qa>1)echo"<p class='error'>".'Error in query'.": ".implode("",$Hb)."\n";}else
echo"<p class='error'>".upload_error($H)."\n";}echo'
<form action="" method="post" enctype="multipart/form-data" id="form">
<p>';$G=$_GET["sql"];if($_POST)$G=$_POST["query"];elseif($_GET["history"]=="all")$G=$nc;elseif($_GET["history"]!="")$G=$nc[$_GET["history"]][0];textarea("query",$G,20);echo($_POST?"":"<script type='text/javascript'>document.getElementsByTagName('textarea')[0].focus();</script>\n"),"<p>".(ini_bool("file_uploads")?'File upload'.': <input type="file" name="sql_file"'.($_FILES&&$_FILES["sql_file"]["error"]!=4?'':' onchange="this.form[\'only_errors\'].checked = true;"').'> (&lt; '.ini_get("upload_max_filesize").'B)':'File uploads are disabled.'),'<p>
<input type="submit" value="Execute" title="Ctrl+Enter">
<input type="hidden" name="token" value="',$U,'">
',checkbox("error_stops",1,$_POST["error_stops"],'Stop on error')."\n",checkbox("only_errors",1,$_POST["only_errors"],'Show only errors')."\n";print_fieldset("webfile",'From server',$_POST["webfile"],"document.getElementById('form')['only_errors'].checked = true; ");$Ta=array();foreach(array("gz"=>"zlib","bz2"=>"bz2")as$x=>$X){if(extension_loaded($X))$Ta[]=".$x";}echo
sprintf('Webserver file %s',"<code>adminer.sql".($Ta?"[".implode("|",$Ta)."]":"")."</code>"),' <input type="submit" name="webfile" value="'.'Run file'.'">',"</div></fieldset>\n";if($nc){print_fieldset("history",'History',$_GET["history"]!="");foreach($nc
as$x=>$X){list($G,$lf)=$X;echo'<a href="'.h(ME."sql=&history=$x").'">'.'Edit'."</a> <span class='time'>".@date("H:i:s",$lf)."</span> <code class='jush-$w'>".shorten_utf8(ltrim(str_replace("\n"," ",str_replace("\r","",preg_replace('~^(#|-- ).*~m','',$G)))),80,"</code>")."<br>\n";}echo"<input type='submit' name='clear' value='".'Clear'."'>\n","<a href='".h(ME."sql=&history=all")."'>".'Edit all'."</a>\n","</div></fieldset>\n";}echo'
</form>
';}elseif(isset($_GET["edit"])){$a=$_GET["edit"];$Z=(isset($_GET["select"])?(count($_POST["check"])==1?where_check($_POST["check"][0]):""):where($_GET));$If=(isset($_GET["select"])?$_POST["edit"]:$Z);$l=fields($a);foreach($l
as$D=>$k){if(!isset($k["privileges"][$If?"update":"insert"])||$b->fieldName($k)=="")unset($l[$D]);}if($_POST&&!$j&&!isset($_GET["select"])){$_=$_POST["referer"];if($_POST["insert"])$_=($If?null:$_SERVER["REQUEST_URI"]);elseif(!ereg('^.+&select=.+$',$_))$_=ME."select=".urlencode($a);if(isset($_POST["delete"]))query_redirect("DELETE".limit1("FROM ".table($a)," WHERE $Z"),$_,'Item has been deleted.');else{$P=array();foreach($l
as$D=>$k){$X=process_input($k);if($X!==false&&$X!==null)$P[idf_escape($D)]=($If?"\n".idf_escape($D)." = $X":$X);}if($If){if(!$P)redirect($_);query_redirect("UPDATE".limit1(table($a)." SET".implode(",",$P),"\nWHERE $Z"),$_,'Item has been updated.');}else{$I=insert_into($a,$P);$Jc=($I?last_id():0);queries_redirect($_,sprintf('Item%s has been inserted.',($Jc?" $Jc":"")),$I);}}}$af=$b->tableName(table_status($a));page_header(($If?'Edit':'Insert'),$j,array("select"=>array($a,$af)),$af);$K=null;if($_POST["save"])$K=(array)$_POST["fields"];elseif($Z){$N=array();foreach($l
as$D=>$k){if(isset($k["privileges"]["select"]))$N[]=($_POST["clone"]&&$k["auto_increment"]?"'' AS ":($w=="sql"&&ereg("enum|set",$k["type"])?"1*".idf_escape($D)." AS ":"")).idf_escape($D);}$K=array();if($N){$L=get_rows("SELECT".limit(implode(", ",$N)." FROM ".table($a)," WHERE $Z",(isset($_GET["select"])?2:1)));$K=(isset($_GET["select"])&&count($L)!=1?null:reset($L));}}if($K===false)echo"<p class='error'>".'No rows.'."\n";echo'
<form action="" method="post" enctype="multipart/form-data" id="form">
';if($l){echo"<table cellspacing='0' onkeydown='return editingKeydown(event);'>\n";foreach($l
as$D=>$k){echo"<tr><th>".$b->fieldName($k);$kb=$_GET["set"][bracket_escape($D)];$Y=($K!==null?($K[$D]!=""&&$w=="sql"&&ereg("enum|set",$k["type"])?(is_array($K[$D])?array_sum($K[$D]):+$K[$D]):$K[$D]):(!$If&&$k["auto_increment"]?"":(isset($_GET["select"])?false:($kb!==null?$kb:$k["default"]))));if(!$_POST["save"]&&is_string($Y))$Y=$b->editVal($Y,$k);$o=($_POST["save"]?(string)$_POST["function"][$D]:($If&&$k["on_update"]=="CURRENT_TIMESTAMP"?"now":($Y===false?null:($Y!==null?'':'NULL'))));if($k["type"]=="timestamp"&&$Y=="CURRENT_TIMESTAMP"){$Y="";$o="now";}input($k,$Y,$o);echo"\n";}echo"</table>\n";}echo'<p>
';if($l){echo"<input type='submit' value='".'Save'."'>\n";if(!isset($_GET["select"]))echo"<input type='submit' name='insert' value='".($If?'Save and continue edit':'Save and insert next')."' title='Ctrl+Shift+Enter'>\n";}echo($If?"<input type='submit' name='delete' value='".'Delete'."' onclick=\"return confirm('".'Are you sure?'."');\">\n":($_POST||!$l?"":"<script type='text/javascript'>document.getElementById('form').getElementsByTagName('td')[1].firstChild.focus();</script>\n"));if(isset($_GET["select"]))hidden_fields(array("check"=>(array)$_POST["check"],"clone"=>$_POST["clone"],"all"=>$_POST["all"]));echo'<input type="hidden" name="referer" value="',h(isset($_POST["referer"])?$_POST["referer"]:$_SERVER["HTTP_REFERER"]),'">
<input type="hidden" name="save" value="1">
<input type="hidden" name="token" value="',$U,'">
</form>
';}elseif(isset($_GET["create"])){$a=$_GET["create"];$Pd=array('HASH','LINEAR HASH','KEY','LINEAR KEY','RANGE','LIST');$qe=referencable_primary($a);$n=array();foreach($qe
as$af=>$k)$n[str_replace("`","``",$af)."`".str_replace("`","``",$k["field"])]=$af;$Hd=array();$Id=array();if($a!=""){$Hd=fields($a);$Id=table_status($a);}if($_POST&&!$_POST["fields"])$_POST["fields"]=array();if($_POST&&!$j&&!$_POST["add"]&&!$_POST["drop_col"]&&!$_POST["up"]&&!$_POST["down"]){if($_POST["drop"])query_redirect("DROP TABLE ".table($a),substr(ME,0,-1),'Table has been dropped.');else{$l=array();$qa=array();$Kf=false;$ac=array();ksort($_POST["fields"]);$Gd=reset($Hd);$oa=" FIRST";foreach($_POST["fields"]as$x=>$k){$m=$n[$k["type"]];$zf=($m!==null?$qe[$m]:$k);if($k["field"]!=""){if(!$k["has_default"])$k["default"]=null;$kb=eregi_replace(" *on update CURRENT_TIMESTAMP","",$k["default"]);if($kb!=$k["default"]){$k["on_update"]="CURRENT_TIMESTAMP";$k["default"]=$kb;}if($x==$_POST["auto_increment_col"])$k["auto_increment"]=true;$je=process_field($k,$zf);$qa[]=array($k["orig"],$je,$oa);if($je!=process_field($Gd,$Gd)){$l[]=array($k["orig"],$je,$oa);if($k["orig"]!=""||$oa)$Kf=true;}if($m!==null)$ac[idf_escape($k["field"])]=($a!=""&&$w!="sqlite"?"ADD":" ")." FOREIGN KEY (".idf_escape($k["field"]).") REFERENCES ".table($n[$k["type"]])." (".idf_escape($zf["field"]).")".(ereg("^($vd)\$",$k["on_delete"])?" ON DELETE $k[on_delete]":"");$oa=" AFTER ".idf_escape($k["field"]);}elseif($k["orig"]!=""){$Kf=true;$l[]=array($k["orig"]);}if($k["orig"]!=""){$Gd=next($Hd);if(!$Gd)$oa="";}}$Rd="";if(in_array($_POST["partition_by"],$Pd)){$Sd=array();if($_POST["partition_by"]=='RANGE'||$_POST["partition_by"]=='LIST'){foreach(array_filter($_POST["partition_names"])as$x=>$X){$Y=$_POST["partition_values"][$x];$Sd[]="\nPARTITION ".idf_escape($X)." VALUES ".($_POST["partition_by"]=='RANGE'?"LESS THAN":"IN").($Y!=""?" ($Y)":" MAXVALUE");}}$Rd.="\nPARTITION BY $_POST[partition_by]($_POST[partition])".($Sd?" (".implode(",",$Sd)."\n)":($_POST["partitions"]?" PARTITIONS ".(+$_POST["partitions"]):""));}elseif($a!=""&&support("partitioning"))$Rd.="\nREMOVE PARTITIONING";$B='Table has been altered.';if($a==""){cookie("adminer_engine",$_POST["Engine"]);$B='Table has been created.';}$D=trim($_POST["name"]);queries_redirect(ME."table=".urlencode($D),$B,alter_table($a,$D,($w=="sqlite"&&($Kf||$ac)?$qa:$l),$ac,$_POST["Comment"],($_POST["Engine"]&&$_POST["Engine"]!=$Id["Engine"]?$_POST["Engine"]:""),($_POST["Collation"]&&$_POST["Collation"]!=$Id["Collation"]?$_POST["Collation"]:""),($_POST["Auto_increment"]!=""?+$_POST["Auto_increment"]:""),$Rd));}}page_header(($a!=""?'Alter table':'Create table'),$j,array("table"=>$a),$a);$K=array("Engine"=>$_COOKIE["adminer_engine"],"fields"=>array(array("field"=>"","type"=>(isset($Af["int"])?"int":(isset($Af["integer"])?"integer":"")))),"partition_names"=>array(""),);if($_POST){$K=$_POST;if($K["auto_increment_col"])$K["fields"][$K["auto_increment_col"]]["auto_increment"]=true;process_fields($K["fields"]);}elseif($a!=""){$K=$Id;$K["name"]=$a;$K["fields"]=array();if(!$_GET["auto_increment"])$K["Auto_increment"]="";foreach($Hd
as$k){$k["has_default"]=isset($k["default"]);if($k["on_update"])$k["default"].=" ON UPDATE $k[on_update]";$K["fields"][]=$k;}if(support("partitioning")){$gc="FROM information_schema.PARTITIONS WHERE TABLE_SCHEMA = ".q(DB)." AND TABLE_NAME = ".q($a);$I=$f->query("SELECT PARTITION_METHOD, PARTITION_ORDINAL_POSITION, PARTITION_EXPRESSION $gc ORDER BY PARTITION_ORDINAL_POSITION DESC LIMIT 1");list($K["partition_by"],$K["partitions"],$K["partition"])=$I->fetch_row();$K["partition_names"]=array();$K["partition_values"]=array();foreach(get_rows("SELECT PARTITION_NAME, PARTITION_DESCRIPTION $gc AND PARTITION_NAME != '' ORDER BY PARTITION_ORDINAL_POSITION")as$Be){$K["partition_names"][]=$Be["PARTITION_NAME"];$K["partition_values"][]=$Be["PARTITION_DESCRIPTION"];}$K["partition_names"][]="";}}$d=collations();$Ve=floor(extension_loaded("suhosin")?(min(ini_get("suhosin.request.max_vars"),ini_get("suhosin.post.max_vars"))-13)/10:0);if($Ve&&count($K["fields"])>$Ve)echo"<p class='error'>".h(sprintf('Maximum number of allowed fields exceeded. Please increase %s and %s.','suhosin.post.max_vars','suhosin.request.max_vars'))."\n";$Eb=engines();foreach($Eb
as$Db){if(!strcasecmp($Db,$K["Engine"])){$K["Engine"]=$Db;break;}}echo'
<form action="" method="post" id="form">
<p>
Table name: <input name="name" maxlength="64" value="',h($K["name"]),'">
';if($a==""&&!$_POST){?><script type='text/javascript'>document.getElementById('form')['name'].focus();</script><?php }echo($Eb?html_select("Engine",array(""=>"(".'engine'.")")+$Eb,$K["Engine"]):""),' ',($d&&!ereg("sqlite|mssql",$w)?html_select("Collation",array(""=>"(".'collation'.")")+$d,$K["Collation"]):""),' <input type="submit" value="Save">
<table cellspacing="0" id="edit-fields" class="nowrap">
';$Sa=($_POST?$_POST["comments"]:$K["Comment"]!="");if(!$_POST&&!$Sa){foreach($K["fields"]as$k){if($k["comment"]!=""){$Sa=true;break;}}}edit_fields($K["fields"],$d,"TABLE",$Ve,$n,$Sa);echo'</table>
<p>
Auto Increment: <input name="Auto_increment" size="6" value="',h($K["Auto_increment"]),'">
<label class="jsonly"><input type="checkbox" name="defaults" value="1"',($_POST["defaults"]?" checked":""),' onclick="columnShow(this.checked, 5);">Default values</label>
',(support("comment")?checkbox("comments",1,$Sa,'Comment',"columnShow(this.checked, 6); toggle('Comment'); if (this.checked) this.form['Comment'].focus();",true).' <input id="Comment" name="Comment" value="'.h($K["Comment"]).'" maxlength="60"'.($Sa?'':' class="hidden"').'>':''),'<p>
<input type="submit" value="Save">
';if($_GET["create"]!=""){echo'<input type="submit" name="drop" value="Drop"',confirm(),'>';}echo'<input type="hidden" name="token" value="',$U,'">
';if(support("partitioning")){$Qd=ereg('RANGE|LIST',$K["partition_by"]);print_fieldset("partition",'Partition by',$K["partition_by"]);echo'<p>
',html_select("partition_by",array(-1=>"")+$Pd,$K["partition_by"],"partitionByChange(this);"),'(<input name="partition" value="',h($K["partition"]),'">)
Partitions: <input name="partitions" size="2" value="',h($K["partitions"]),'"',($Qd||!$K["partition_by"]?" class='hidden'":""),'>
<table cellspacing="0" id="partition-table"',($Qd?"":" class='hidden'"),'>
<thead><tr><th>Partition name<th>Values</thead>
';foreach($K["partition_names"]as$x=>$X){echo'<tr>','<td><input name="partition_names[]" value="'.h($X).'"'.($x==count($K["partition_names"])-1?' onchange="partitionNameChange(this);"':'').'>','<td><input name="partition_values[]" value="'.h($K["partition_values"][$x]).'">';}echo'</table>
</div></fieldset>
';}echo'</form>
';}elseif(isset($_GET["indexes"])){$a=$_GET["indexes"];$vc=array("PRIMARY","UNIQUE","INDEX");$T=table_status($a);if(eregi("MyISAM|M?aria",$T["Engine"]))$vc[]="FULLTEXT";$u=indexes($a);if($w=="sqlite"){unset($vc[0]);unset($u[""]);}if($_POST&&!$j&&!$_POST["add"]){$sa=array();foreach($_POST["indexes"]as$t){$D=$t["name"];if(in_array($t["type"],$vc)){$e=array();$Pc=array();$P=array();ksort($t["columns"]);foreach($t["columns"]as$x=>$Oa){if($Oa!=""){$Oc=$t["lengths"][$x];$P[]=idf_escape($Oa).($Oc?"(".(+$Oc).")":"");$e[]=$Oa;$Pc[]=($Oc?$Oc:null);}}if($e){$Ob=$u[$D];if($Ob){ksort($Ob["columns"]);ksort($Ob["lengths"]);if($t["type"]==$Ob["type"]&&array_values($Ob["columns"])===$e&&(!$Ob["lengths"]||array_values($Ob["lengths"])===$Pc)){unset($u[$D]);continue;}}$sa[]=array($t["type"],$D,"(".implode(", ",$P).")");}}}foreach($u
as$D=>$Ob)$sa[]=array($Ob["type"],$D,"DROP");if(!$sa)redirect(ME."table=".urlencode($a));queries_redirect(ME."table=".urlencode($a),'Indexes have been altered.',alter_indexes($a,$sa));}page_header('Indexes',$j,array("table"=>$a),$a);$l=array_keys(fields($a));$K=array("indexes"=>$u);if($_POST){$K=$_POST;if($_POST["add"]){foreach($K["indexes"]as$x=>$t){if($t["columns"][count($t["columns"])]!="")$K["indexes"][$x]["columns"][]="";}$t=end($K["indexes"]);if($t["type"]||array_filter($t["columns"],'strlen')||array_filter($t["lengths"],'strlen'))$K["indexes"][]=array("columns"=>array(1=>""));}}else{foreach($K["indexes"]as$x=>$t){$K["indexes"][$x]["name"]=$x;$K["indexes"][$x]["columns"][]="";}$K["indexes"][]=array("columns"=>array(1=>""));}echo'
<form action="" method="post">
<table cellspacing="0" class="nowrap">
<thead><tr><th>Index Type<th>Column (length)<th>Name</thead>
';$v=1;foreach($K["indexes"]as$t){echo"<tr><td>".html_select("indexes[$v][type]",array(-1=>"")+$vc,$t["type"],($v==count($K["indexes"])?"indexesAddRow(this);":1))."<td>";ksort($t["columns"]);$r=1;foreach($t["columns"]as$x=>$Oa){echo"<span>".html_select("indexes[$v][columns][$r]",array(-1=>"")+$l,$Oa,($r==count($t["columns"])?"indexesAddColumn":"indexesChangeColumn")."(this, '".js_escape($w=="sql"?"":$_GET["indexes"]."_")."');"),"<input name='indexes[$v][lengths][$r]' size='2' value='".h($t["lengths"][$x])."'> </span>";$r++;}echo"<td><input name='indexes[$v][name]' value='".h($t["name"])."'>\n";$v++;}echo'</table>
<p>
<input type="submit" value="Save">
<noscript><p><input type="submit" name="add" value="Add next"></noscript>
<input type="hidden" name="token" value="',$U,'">
</form>
';}elseif(isset($_GET["database"])){if($_POST&&!$j&&!isset($_POST["add_x"])){restart_session();$D=trim($_POST["name"]);if($_POST["drop"]){$_GET["db"]="";queries_redirect(remove_from_uri("db|database"),'Database has been dropped.',drop_databases(array(DB)));}elseif(DB!==$D){if(DB!=""){$_GET["db"]=$D;queries_redirect(preg_replace('~db=[^&]*&~','',ME)."db=".urlencode($D),'Database has been renamed.',rename_database($D,$_POST["collation"]));}else{$h=explode("\n",str_replace("\r","",$D));$Te=true;$Ic="";foreach($h
as$i){if(count($h)==1||$i!=""){if(!create_database($i,$_POST["collation"]))$Te=false;$Ic=$i;}}queries_redirect(ME."db=".urlencode($Ic),'Database has been created.',$Te);}}else{if(!$_POST["collation"])redirect(substr(ME,0,-1));query_redirect("ALTER DATABASE ".idf_escape($D).(eregi('^[a-z0-9_]+$',$_POST["collation"])?" COLLATE $_POST[collation]":""),substr(ME,0,-1),'Database has been altered.');}}page_header(DB!=""?'Alter database':'Create database',$j,array(),DB);$d=collations();$D=DB;$La=null;if($_POST){$D=$_POST["name"];$La=$_POST["collation"];}elseif(DB!="")$La=db_collation(DB,$d);elseif($w=="sql"){foreach(get_vals("SHOW GRANTS")as$p){if(preg_match('~ ON (`(([^\\\\`]|``|\\\\.)*)%`\\.\\*)?~',$p,$A)&&$A[1]){$D=stripcslashes(idf_unescape("`$A[2]`"));break;}}}echo'
<form action="" method="post">
<p>
',($_POST["add_x"]||strpos($D,"\n")?'<textarea id="name" name="name" rows="10" cols="40">'.h($D).'</textarea><br>':'<input id="name" name="name" value="'.h($D).'" maxlength="64">')."\n".($d?html_select("collation",array(""=>"(".'collation'.")")+$d,$La):"");?>
<script type='text/javascript'>document.getElementById('name').focus();</script>
<input type="submit" value="Save">
<?php
if(DB!="")echo"<input type='submit' name='drop' value='".'Drop'."'".confirm().">\n";elseif(!$_POST["add_x"]&&$_GET["db"]=="")echo"<input type='image' name='add' src='".h(preg_replace("~\\?.*~","",ME))."?file=plus.gif&amp;version=3.5.1' alt='+' title='".'Add next'."'>\n";echo'<input type="hidden" name="token" value="',$U,'">
</form>
';}elseif(isset($_GET["call"])){$da=$_GET["call"];page_header('Call'.": ".h($da),$j);$ze=routine($da,(isset($_GET["callf"])?"FUNCTION":"PROCEDURE"));$uc=array();$Jd=array();foreach($ze["fields"]as$r=>$k){if(substr($k["inout"],-3)=="OUT")$Jd[$r]="@".idf_escape($k["field"])." AS ".idf_escape($k["field"]);if(!$k["inout"]||substr($k["inout"],0,2)=="IN")$uc[]=$r;}if(!$j&&$_POST){$Ea=array();foreach($ze["fields"]as$x=>$k){if(in_array($x,$uc)){$X=process_input($k);if($X===false)$X="''";if(isset($Jd[$x]))$f->query("SET @".idf_escape($k["field"])." = $X");}$Ea[]=(isset($Jd[$x])?"@".idf_escape($k["field"]):$X);}$H=(isset($_GET["callf"])?"SELECT":"CALL")." ".idf_escape($da)."(".implode(", ",$Ea).")";echo"<p><code class='jush-$w'>".h($H)."</code> <a href='".h(ME)."sql=".urlencode($H)."'>".'Edit'."</a>\n";if(!$f->multi_query($H))echo"<p class='error'>".error()."\n";else{$g=connect();if(is_object($g))$g->select_db(DB);do{$I=$f->store_result();if(is_object($I))select($I,$g);else
echo"<p class='message'>".lang(array('Routine has been called, %d row affected.','Routine has been called, %d rows affected.'),$f->affected_rows)."\n";}while($f->next_result());if($Jd)select($f->query("SELECT ".implode(", ",$Jd)));}}echo'
<form action="" method="post">
';if($uc){echo"<table cellspacing='0'>\n";foreach($uc
as$x){$k=$ze["fields"][$x];$D=$k["field"];echo"<tr><th>".$b->fieldName($k);$Y=$_POST["fields"][$D];if($Y!=""){if($k["type"]=="enum")$Y=+$Y;if($k["type"]=="set")$Y=array_sum($Y);}input($k,$Y,(string)$_POST["function"][$D]);echo"\n";}echo"</table>\n";}echo'<p>
<input type="submit" value="Call">
<input type="hidden" name="token" value="',$U,'">
</form>
';}elseif(isset($_GET["foreign"])){$a=$_GET["foreign"];if($_POST&&!$j&&!$_POST["add"]&&!$_POST["change"]&&!$_POST["change-js"]){if($_POST["drop"])query_redirect("ALTER TABLE ".table($a)."\nDROP ".($w=="sql"?"FOREIGN KEY ":"CONSTRAINT ").idf_escape($_GET["name"]),ME."table=".urlencode($a),'Foreign key has been dropped.');else{$Le=array_filter($_POST["source"],'strlen');ksort($Le);$hf=array();foreach($Le
as$x=>$X)$hf[$x]=$_POST["target"][$x];query_redirect("ALTER TABLE ".table($a).($_GET["name"]!=""?"\nDROP ".($w=="sql"?"FOREIGN KEY ":"CONSTRAINT ").idf_escape($_GET["name"]).",":"")."\nADD FOREIGN KEY (".implode(", ",array_map('idf_escape',$Le)).") REFERENCES ".table($_POST["table"])." (".implode(", ",array_map('idf_escape',$hf)).")".(ereg("^($vd)\$",$_POST["on_delete"])?" ON DELETE $_POST[on_delete]":"").(ereg("^($vd)\$",$_POST["on_update"])?" ON UPDATE $_POST[on_update]":""),ME."table=".urlencode($a),($_GET["name"]!=""?'Foreign key has been altered.':'Foreign key has been created.'));$j='Source and target columns must have the same data type, there must be an index on the target columns and referenced data must exist.'."<br>$j";}}page_header('Foreign key',$j,array("table"=>$a),$a);$K=array("table"=>$a,"source"=>array(""));if($_POST){$K=$_POST;ksort($K["source"]);if($_POST["add"])$K["source"][]="";elseif($_POST["change"]||$_POST["change-js"])$K["target"]=array();}elseif($_GET["name"]!=""){$n=foreign_keys($a);$K=$n[$_GET["name"]];$K["source"][]="";}$Le=array_keys(fields($a));$hf=($a===$K["table"]?$Le:array_keys(fields($K["table"])));$pe=array();foreach(table_status()as$D=>$T){if(fk_support($T))$pe[]=$D;}echo'
<form action="" method="post">
<p>
';if($K["db"]==""&&$K["ns"]==""){echo'Target table:
',html_select("table",$pe,$K["table"],"this.form['change-js'].value = '1'; this.form.submit();"),'<input type="hidden" name="change-js" value="">
<noscript><p><input type="submit" name="change" value="Change"></noscript>
<table cellspacing="0">
<thead><tr><th>Source<th>Target</thead>
';$v=0;foreach($K["source"]as$x=>$X){echo"<tr>","<td>".html_select("source[".(+$x)."]",array(-1=>"")+$Le,$X,($v==count($K["source"])-1?"foreignAddRow(this);":1)),"<td>".html_select("target[".(+$x)."]",$hf,$K["target"][$x]);$v++;}echo'</table>
<p>
ON DELETE: ',html_select("on_delete",array(-1=>"")+explode("|",$vd),$K["on_delete"]),' ON UPDATE: ',html_select("on_update",array(-1=>"")+explode("|",$vd),$K["on_update"]),'<p>
<input type="submit" value="Save">
<noscript><p><input type="submit" name="add" value="Add column"></noscript>
';}if($_GET["name"]!=""){echo'<input type="submit" name="drop" value="Drop"',confirm(),'>';}echo'<input type="hidden" name="token" value="',$U,'">
</form>
';}elseif(isset($_GET["view"])){$a=$_GET["view"];$tb=false;if($_POST&&!$j){$D=trim($_POST["name"]);$tb=drop_create("DROP VIEW ".table($a),"CREATE VIEW ".table($D)." AS\n$_POST[select]",($_POST["drop"]?substr(ME,0,-1):ME."table=".urlencode($D)),'View has been dropped.','View has been altered.','View has been created.',$a);}page_header(($a!=""?'Alter view':'Create view'),$j,array("table"=>$a),$a);$K=$_POST;if(!$K&&$a!=""){$K=view($a);$K["name"]=$a;}echo'
<form action="" method="post">
<p>Name: <input name="name" value="',h($K["name"]),'" maxlength="64">
<p>';textarea("select",$K["select"]);echo'<p>
';if($tb){echo'<input type="hidden" name="dropped" value="1">';}echo'<input type="submit" value="Save">
';if($_GET["view"]!=""){echo'<input type="submit" name="drop" value="Drop"',confirm(),'>';}echo'<input type="hidden" name="token" value="',$U,'">
</form>
';}elseif(isset($_GET["event"])){$aa=$_GET["event"];$_c=array("YEAR","QUARTER","MONTH","DAY","HOUR","MINUTE","WEEK","SECOND","YEAR_MONTH","DAY_HOUR","DAY_MINUTE","DAY_SECOND","HOUR_MINUTE","HOUR_SECOND","MINUTE_SECOND");$Qe=array("ENABLED"=>"ENABLE","DISABLED"=>"DISABLE","SLAVESIDE_DISABLED"=>"DISABLE ON SLAVE");if($_POST&&!$j){if($_POST["drop"])query_redirect("DROP EVENT ".idf_escape($aa),substr(ME,0,-1),'Event has been dropped.');elseif(in_array($_POST["INTERVAL_FIELD"],$_c)&&isset($Qe[$_POST["STATUS"]])){$Ce="\nON SCHEDULE ".($_POST["INTERVAL_VALUE"]?"EVERY ".q($_POST["INTERVAL_VALUE"])." $_POST[INTERVAL_FIELD]".($_POST["STARTS"]?" STARTS ".q($_POST["STARTS"]):"").($_POST["ENDS"]?" ENDS ".q($_POST["ENDS"]):""):"AT ".q($_POST["STARTS"]))." ON COMPLETION".($_POST["ON_COMPLETION"]?"":" NOT")." PRESERVE";queries_redirect(substr(ME,0,-1),($aa!=""?'Event has been altered.':'Event has been created.'),queries(($aa!=""?"ALTER EVENT ".idf_escape($aa).$Ce.($aa!=$_POST["EVENT_NAME"]?"\nRENAME TO ".idf_escape($_POST["EVENT_NAME"]):""):"CREATE EVENT ".idf_escape($_POST["EVENT_NAME"]).$Ce)."\n".$Qe[$_POST["STATUS"]]." COMMENT ".q($_POST["EVENT_COMMENT"]).rtrim(" DO\n$_POST[EVENT_DEFINITION]",";").";"));}}page_header(($aa!=""?'Alter event'.": ".h($aa):'Create event'),$j);$K=$_POST;if(!$K&&$aa!=""){$L=get_rows("SELECT * FROM information_schema.EVENTS WHERE EVENT_SCHEMA = ".q(DB)." AND EVENT_NAME = ".q($aa));$K=reset($L);}echo'
<form action="" method="post">
<table cellspacing="0">
<tr><th>Name<td><input name="EVENT_NAME" value="',h($K["EVENT_NAME"]),'" maxlength="64">
<tr><th>Start<td><input name="STARTS" value="',h("$K[EXECUTE_AT]$K[STARTS]"),'">
<tr><th>End<td><input name="ENDS" value="',h($K["ENDS"]),'">
<tr><th>Every<td><input name="INTERVAL_VALUE" value="',h($K["INTERVAL_VALUE"]),'" size="6"> ',html_select("INTERVAL_FIELD",$_c,$K["INTERVAL_FIELD"]),'<tr><th>Status<td>',html_select("STATUS",$Qe,$K["STATUS"]),'<tr><th>Comment<td><input name="EVENT_COMMENT" value="',h($K["EVENT_COMMENT"]),'" maxlength="64">
<tr><th>&nbsp;<td>',checkbox("ON_COMPLETION","PRESERVE",$K["ON_COMPLETION"]=="PRESERVE",'On completion preserve'),'</table>
<p>';textarea("EVENT_DEFINITION",$K["EVENT_DEFINITION"]);echo'<p>
<input type="submit" value="Save">
';if($aa!=""){echo'<input type="submit" name="drop" value="Drop"',confirm(),'>';}echo'<input type="hidden" name="token" value="',$U,'">
</form>
';}elseif(isset($_GET["procedure"])){$da=$_GET["procedure"];$ze=(isset($_GET["function"])?"FUNCTION":"PROCEDURE");$_e=routine_languages();$tb=false;if($_POST&&!$j&&!$_POST["add"]&&!$_POST["drop_col"]&&!$_POST["up"]&&!$_POST["down"]){$P=array();$l=(array)$_POST["fields"];ksort($l);foreach($l
as$k){if($k["field"]!="")$P[]=(ereg("^($xc)\$",$k["inout"])?"$k[inout] ":"").idf_escape($k["field"]).process_type($k,"CHARACTER SET");}$tb=drop_create("DROP $ze ".idf_escape($da),"CREATE $ze ".idf_escape(trim($_POST["name"]))." (".implode(", ",$P).")".(isset($_GET["function"])?" RETURNS".process_type($_POST["returns"],"CHARACTER SET"):"").(in_array($_POST["language"],$_e)?" LANGUAGE $_POST[language]":"").rtrim("\n$_POST[definition]",";").";",substr(ME,0,-1),'Routine has been dropped.','Routine has been altered.','Routine has been created.',$da);}page_header(($da!=""?(isset($_GET["function"])?'Alter function':'Alter procedure').": ".h($da):(isset($_GET["function"])?'Create function':'Create procedure')),$j);$d=get_vals("SHOW CHARACTER SET");sort($d);$K=array("fields"=>array());if($_POST){$K=$_POST;$K["fields"]=(array)$K["fields"];process_fields($K["fields"]);}elseif($da!=""){$K=routine($da,$ze);$K["name"]=$da;}echo'
<form action="" method="post" id="form">
<p>Name: <input name="name" value="',h($K["name"]),'" maxlength="64">
',($_e?'Language'.": ".html_select("language",$_e,$K["language"]):""),'<table cellspacing="0" class="nowrap">
';edit_fields($K["fields"],$d,$ze);if(isset($_GET["function"])){echo"<tr><td>".'Return type';edit_type("returns",$K["returns"],$d);}echo'</table>
<p>';textarea("definition",$K["definition"]);echo'<p>
<input type="submit" value="Save">
';if($da!=""){echo'<input type="submit" name="drop" value="Drop"',confirm(),'>';}if($tb){echo'<input type="hidden" name="dropped" value="1">';}echo'<input type="hidden" name="token" value="',$U,'">
</form>
';}elseif(isset($_GET["trigger"])){$a=$_GET["trigger"];$xf=trigger_options();$wf=array("INSERT","UPDATE","DELETE");$tb=false;if($_POST&&!$j&&in_array($_POST["Timing"],$xf["Timing"])&&in_array($_POST["Event"],$wf)&&in_array($_POST["Type"],$xf["Type"])){$mf=" $_POST[Timing] $_POST[Event]";$ud=" ON ".table($a);$tb=drop_create("DROP TRIGGER ".idf_escape($_GET["name"]).($w=="pgsql"?$ud:""),"CREATE TRIGGER ".idf_escape($_POST["Trigger"]).($w=="mssql"?$ud.$mf:$mf.$ud).rtrim(" $_POST[Type]\n$_POST[Statement]",";").";",ME."table=".urlencode($a),'Trigger has been dropped.','Trigger has been altered.','Trigger has been created.',$_GET["name"]);}page_header(($_GET["name"]!=""?'Alter trigger'.": ".h($_GET["name"]):'Create trigger'),$j,array("table"=>$a));$K=$_POST;if(!$K)$K=trigger($_GET["name"])+array("Trigger"=>$a."_bi");echo'
<form action="" method="post" id="form">
<table cellspacing="0">
<tr><th>Time<td>',html_select("Timing",$xf["Timing"],$K["Timing"],"if (/^".preg_quote($a,"/")."_[ba][iud]$/.test(this.form['Trigger'].value)) this.form['Trigger'].value = '".js_escape($a)."_' + selectValue(this).charAt(0).toLowerCase() + selectValue(this.form['Event']).charAt(0).toLowerCase();"),'<tr><th>Event<td>',html_select("Event",$wf,$K["Event"],"this.form['Timing'].onchange();"),'<tr><th>Type<td>',html_select("Type",$xf["Type"],$K["Type"]),'</table>
<p>Name: <input name="Trigger" value="',h($K["Trigger"]),'" maxlength="64">
<p>';textarea("Statement",$K["Statement"]);echo'<p>
<input type="submit" value="Save">
';if($_GET["name"]!=""){echo'<input type="submit" name="drop" value="Drop"',confirm(),'>';}if($tb){echo'<input type="hidden" name="dropped" value="1">';}echo'<input type="hidden" name="token" value="',$U,'">
</form>
';}elseif(isset($_GET["user"])){$fa=$_GET["user"];$he=array(""=>array("All privileges"=>""));foreach(get_rows("SHOW PRIVILEGES")as$K){foreach(explode(",",($K["Privilege"]=="Grant option"?"":$K["Context"]))as$Xa)$he[$Xa][$K["Privilege"]]=$K["Comment"];}$he["Server Admin"]+=$he["File access on server"];$he["Databases"]["Create routine"]=$he["Procedures"]["Create routine"];unset($he["Procedures"]["Create routine"]);$he["Columns"]=array();foreach(array("Select","Insert","Update","References")as$X)$he["Columns"][$X]=$he["Tables"][$X];unset($he["Server Admin"]["Usage"]);foreach($he["Tables"]as$x=>$X)unset($he["Databases"][$x]);$kd=array();if($_POST){foreach($_POST["objects"]as$x=>$X)$kd[$X]=(array)$kd[$X]+(array)$_POST["grants"][$x];}$jc=array();$sd="";if(isset($_GET["host"])&&($I=$f->query("SHOW GRANTS FOR ".q($fa)."@".q($_GET["host"])))){while($K=$I->fetch_row()){if(preg_match('~GRANT (.*) ON (.*) TO ~',$K[0],$A)&&preg_match_all('~ *([^(,]*[^ ,(])( *\\([^)]+\\))?~',$A[1],$Uc,PREG_SET_ORDER)){foreach($Uc
as$X){if($X[1]!="USAGE")$jc["$A[2]$X[2]"][$X[1]]=true;if(ereg(' WITH GRANT OPTION',$K[0]))$jc["$A[2]$X[2]"]["GRANT OPTION"]=true;}}if(preg_match("~ IDENTIFIED BY PASSWORD '([^']+)~",$K[0],$A))$sd=$A[1];}}if($_POST&&!$j){$td=(isset($_GET["host"])?q($fa)."@".q($_GET["host"]):"''");$ld=q($_POST["user"])."@".q($_POST["host"]);$Td=q($_POST["pass"]);if($_POST["drop"])query_redirect("DROP USER $td",ME."privileges=",'User has been dropped.');else{$cb=false;if($td!=$ld){$cb=queries(($f->server_info<5?"GRANT USAGE ON *.* TO":"CREATE USER")." $ld IDENTIFIED BY".($_POST["hashed"]?" PASSWORD":"")." $Td");$j=!$cb;}elseif($_POST["pass"]!=$sd||!$_POST["hashed"])queries("SET PASSWORD FOR $ld = ".($_POST["hashed"]?$Td:"PASSWORD($Td)"));if(!$j){$we=array();foreach($kd
as$od=>$p){if(isset($_GET["grant"]))$p=array_filter($p);$p=array_keys($p);if(isset($_GET["grant"]))$we=array_diff(array_keys(array_filter($kd[$od],'strlen')),$p);elseif($td==$ld){$rd=array_keys((array)$jc[$od]);$we=array_diff($rd,$p);$p=array_diff($p,$rd);unset($jc[$od]);}if(preg_match('~^(.+)\\s*(\\(.*\\))?$~U',$od,$A)&&(!grant("REVOKE",$we,$A[2]," ON $A[1] FROM $ld")||!grant("GRANT",$p,$A[2]," ON $A[1] TO $ld"))){$j=true;break;}}}if(!$j&&isset($_GET["host"])){if($td!=$ld)queries("DROP USER $td");elseif(!isset($_GET["grant"])){foreach($jc
as$od=>$we){if(preg_match('~^(.+)(\\(.*\\))?$~U',$od,$A))grant("REVOKE",array_keys($we),$A[2]," ON $A[1] FROM $ld");}}}queries_redirect(ME."privileges=",(isset($_GET["host"])?'User has been altered.':'User has been created.'),!$j);if($cb)$f->query("DROP USER $ld");}}page_header((isset($_GET["host"])?'Username'.": ".h("$fa@$_GET[host]"):'Create user'),$j,array("privileges"=>array('','Privileges')));if($_POST){$K=$_POST;$jc=$kd;}else{$K=$_GET+array("host"=>$f->result("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', -1)"));$K["pass"]=$sd;if($sd!="")$K["hashed"]=true;$jc[(DB!=""&&!isset($_GET["host"])?idf_escape(addcslashes(DB,"%_")):"").".*"]=array();}echo'<form action="" method="post">
<table cellspacing="0">
<tr><th>Server<td><input name="host" maxlength="60" value="',h($K["host"]),'">
<tr><th>Username<td><input name="user" maxlength="16" value="',h($K["user"]),'">
<tr><th>Password<td><input id="pass" name="pass" value="',h($K["pass"]),'">
';if(!$K["hashed"]){echo'<script type="text/javascript">typePassword(document.getElementById(\'pass\'));</script>';}echo
checkbox("hashed",1,$K["hashed"],'Hashed',"typePassword(this.form['pass'], this.checked);"),'</table>

';echo"<table cellspacing='0'>\n","<thead><tr><th colspan='2'><a href='http://dev.mysql.com/doc/refman/".substr($f->server_info,0,3)."/en/grant.html#priv_level' target='_blank' rel='noreferrer'>".'Privileges'."</a>";$r=0;foreach($jc
as$od=>$p){echo'<th>'.($od!="*.*"?"<input name='objects[$r]' value='".h($od)."' size='10'>":"<input type='hidden' name='objects[$r]' value='*.*' size='10'>*.*");$r++;}echo"</thead>\n";foreach(array(""=>"","Server Admin"=>'Server',"Databases"=>'Database',"Tables"=>'Table',"Columns"=>'Column',"Procedures"=>'Routine',)as$Xa=>$mb){foreach((array)$he[$Xa]as$ge=>$Ra){echo"<tr".odd()."><td".($mb?">$mb<td":" colspan='2'").' lang="en" title="'.h($Ra).'">'.h($ge);$r=0;foreach($jc
as$od=>$p){$D="'grants[$r][".h(strtoupper($ge))."]'";$Y=$p[strtoupper($ge)];if($Xa=="Server Admin"&&$od!=(isset($jc["*.*"])?"*.*":".*"))echo"<td>&nbsp;";elseif(isset($_GET["grant"]))echo"<td><select name=$D><option><option value='1'".($Y?" selected":"").">".'Grant'."<option value='0'".($Y=="0"?" selected":"").">".'Revoke'."</select>";else
echo"<td align='center'><input type='checkbox' name=$D value='1'".($Y?" checked":"").($ge=="All privileges"?" id='grants-$r-all'":($ge=="Grant option"?"":" onclick=\"if (this.checked) formUncheck('grants-$r-all');\"")).">";$r++;}}}echo"</table>\n",'<p>
<input type="submit" value="Save">
';if(isset($_GET["host"])){echo'<input type="submit" name="drop" value="Drop"',confirm(),'>';}echo'<input type="hidden" name="token" value="',$U,'">
</form>
';}elseif(isset($_GET["processlist"])){if(support("kill")&&$_POST&&!$j){$Fc=0;foreach((array)$_POST["kill"]as$X){if(queries("KILL ".(+$X)))$Fc++;}queries_redirect(ME."processlist=",lang(array('%d process has been killed.','%d processes have been killed.'),$Fc),$Fc||!$_POST["kill"]);}page_header('Process list',$j);echo'
<form action="" method="post">
<table cellspacing="0" onclick="tableClick(event);" class="nowrap checkable">
';$r=-1;foreach(process_list()as$r=>$K){if(!$r)echo"<thead><tr lang='en'>".(support("kill")?"<th>&nbsp;":"")."<th>".implode("<th>",array_keys($K))."</thead>\n";echo"<tr".odd().">".(support("kill")?"<td>".checkbox("kill[]",$K["Id"],0):"");foreach($K
as$x=>$X)echo"<td>".(($w=="sql"&&$x=="Info"&&ereg("Query|Killed",$K["Command"])&&$X!="")||($w=="pgsql"&&$x=="current_query"&&$X!="<IDLE>")||($w=="oracle"&&$x=="sql_text"&&$X!="")?"<code class='jush-$w'>".shorten_utf8($X,100,"</code>").' <a href="'.h(ME.($K["db"]!=""?"db=".urlencode($K["db"])."&":"")."sql=".urlencode($X)).'">'.'Edit'.'</a>':nbsp($X));echo"\n";}echo'</table>
<script type=\'text/javascript\'>tableCheck();</script>
<p>
';if(support("kill")){echo($r+1)."/".sprintf('%d in total',$f->result("SELECT @@max_connections")),"<p><input type='submit' value='".'Kill'."'>\n";}echo'<input type="hidden" name="token" value="',$U,'">
</form>
';}elseif(isset($_GET["select"])){$a=$_GET["select"];$T=table_status($a);$u=indexes($a);$l=fields($a);$n=column_foreign_keys($a);$qd="";if($T["Oid"]=="t"){$qd=($w=="sqlite"?"rowid":"oid");$u[]=array("type"=>"PRIMARY","columns"=>array($qd));}parse_str($_COOKIE["adminer_import"],$la);$xe=array();$e=array();$kf=null;foreach($l
as$x=>$k){$D=$b->fieldName($k);if(isset($k["privileges"]["select"])&&$D!=""){$e[$x]=html_entity_decode(strip_tags($D));if(ereg('text|lob',$k["type"]))$kf=$b->selectLengthProcess();}$xe+=$k["privileges"];}list($N,$q)=$b->selectColumnsProcess($e,$u);$Z=$b->selectSearchProcess($l,$u);$Bd=$b->selectOrderProcess($l,$u);$y=$b->selectLimitProcess();$gc=($N?implode(", ",$N):($qd?"$qd, ":"")."*")."\nFROM ".table($a);$kc=($q&&count($q)<count($N)?"\nGROUP BY ".implode(", ",$q):"").($Bd?"\nORDER BY ".implode(", ",$Bd):"");if($_GET["val"]&&is_ajax()){header("Content-Type: text/plain; charset=utf-8");foreach($_GET["val"]as$Ef=>$K)echo$f->result("SELECT".limit(idf_escape(key($K))." FROM ".table($a)," WHERE ".where_check($Ef).($Z?" AND ".implode(" AND ",$Z):"").($Bd?" ORDER BY ".implode(", ",$Bd):""),1));exit;}if($_POST&&!$j){$Uf="(".implode(") OR (",array_map('where_check',(array)$_POST["check"])).")";$de=$Gf=null;foreach($u
as$t){if($t["type"]=="PRIMARY"){$de=array_flip($t["columns"]);$Gf=($N?$de:array());break;}}foreach((array)$Gf
as$x=>$X){if(in_array(idf_escape($x),$N))unset($Gf[$x]);}if($_POST["export"]){cookie("adminer_import","output=".urlencode($_POST["output"])."&format=".urlencode($_POST["format"]));dump_headers($a);$b->dumpTable($a,"");if(!is_array($_POST["check"])||$Gf===array()){$Tf=$Z;if(is_array($_POST["check"]))$Tf[]="($Uf)";$H="SELECT $gc".($Tf?"\nWHERE ".implode(" AND ",$Tf):"").$kc;}else{$Cf=array();foreach($_POST["check"]as$X)$Cf[]="(SELECT".limit($gc,"\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X).$kc,1).")";$H=implode(" UNION ALL ",$Cf);}$b->dumpData($a,"table",$H);exit;}if(!$b->selectEmailProcess($Z,$n)){if($_POST["save"]||$_POST["delete"]){$I=true;$ma=0;$H=table($a);$P=array();if(!$_POST["delete"]){foreach($e
as$D=>$X){$X=process_input($l[$D]);if($X!==null){if($_POST["clone"])$P[idf_escape($D)]=($X!==false?$X:idf_escape($D));elseif($X!==false)$P[]=idf_escape($D)." = $X";}}$H.=($_POST["clone"]?" (".implode(", ",array_keys($P)).")\nSELECT ".implode(", ",$P)."\nFROM ".table($a):" SET\n".implode(",\n",$P));}if($_POST["delete"]||$P){$Pa="UPDATE";if($_POST["delete"]){$Pa="DELETE";$H="FROM $H";}if($_POST["clone"]){$Pa="INSERT";$H="INTO $H";}if($_POST["all"]||($Gf===array()&&$_POST["check"])||count($q)<count($N)){$I=queries("$Pa $H".($_POST["all"]?($Z?"\nWHERE ".implode(" AND ",$Z):""):"\nWHERE $Uf"));$ma=$f->affected_rows;}else{foreach((array)$_POST["check"]as$X){$I=queries($Pa.limit1($H,"\nWHERE ".where_check($X)));if(!$I)break;$ma+=$f->affected_rows;}}}$B=lang(array('%d item has been affected.','%d items have been affected.'),$ma);if($_POST["clone"]&&$I&&$ma==1){$Jc=last_id();if($Jc)$B=sprintf('Item%s has been inserted.'," $Jc");}queries_redirect(remove_from_uri("page"),$B,$I);}elseif(!$_POST["import"]){if(!$_POST["val"])$j='Double click on a value to modify it.';else{$I=true;$ma=0;foreach($_POST["val"]as$Ef=>$K){$P=array();foreach($K
as$x=>$X){$x=bracket_escape($x,1);$P[]=idf_escape($x)." = ".(ereg('char|text',$l[$x]["type"])||$X!=""?$b->processInput($l[$x],$X):"NULL");}$H=table($a)." SET ".implode(", ",$P);$Tf=" WHERE ".where_check($Ef).($Z?" AND ".implode(" AND ",$Z):"");$I=queries("UPDATE".(count($q)<count($N)?" $H$Tf":limit1($H,$Tf)));if(!$I)break;$ma+=$f->affected_rows;}queries_redirect(remove_from_uri(),lang(array('%d item has been affected.','%d items have been affected.'),$ma),$I);}}elseif(is_string($Vb=get_file("csv_file",true))){cookie("adminer_import","output=".urlencode($la["output"])."&format=".urlencode($_POST["separator"]));$I=true;$Na=array_keys($l);preg_match_all('~(?>"[^"]*"|[^"\\r\\n]+)+~',$Vb,$Uc);$ma=count($Uc[0]);begin();$He=($_POST["separator"]=="csv"?",":($_POST["separator"]=="tsv"?"\t":";"));foreach($Uc[0]as$x=>$X){preg_match_all("~((\"[^\"]*\")+|[^$He]*)$He~",$X.$He,$Vc);if(!$x&&!array_diff($Vc[1],$Na)){$Na=$Vc[1];$ma--;}else{$P=array();foreach($Vc[1]as$r=>$Ka)$P[idf_escape($Na[$r])]=($Ka==""&&$l[$Na[$r]]["null"]?"NULL":q(str_replace('""','"',preg_replace('~^"|"$~','',$Ka))));$I=insert_update($a,$P,$de);if(!$I)break;}}if($I)queries("COMMIT");queries_redirect(remove_from_uri("page"),lang(array('%d row has been imported.','%d rows have been imported.'),$ma),$I);queries("ROLLBACK");}else$j=upload_error($Vb);}}$af=$b->tableName($T);page_header('Select'.": $af",$j);session_write_close();$P=null;if(isset($xe["insert"])){$P="";foreach((array)$_GET["where"]as$X){if(count($n[$X["col"]])==1&&($X["op"]=="="||(!$X["op"]&&!ereg('[_%]',$X["val"]))))$P.="&set".urlencode("[".bracket_escape($X["col"])."]")."=".urlencode($X["val"]);}}$b->selectLinks($T,$P);if(!$e)echo"<p class='error'>".'Unable to select the table'.($l?".":": ".error())."\n";else{echo"<form action='' id='form'>\n","<div style='display: none;'>";hidden_fields_get();echo(DB!=""?'<input type="hidden" name="db" value="'.h(DB).'">'.(isset($_GET["ns"])?'<input type="hidden" name="ns" value="'.h($_GET["ns"]).'">':""):"");echo'<input type="hidden" name="select" value="'.h($a).'">',"</div>\n";$b->selectColumnsPrint($N,$e);$b->selectSearchPrint($Z,$e,$u);$b->selectOrderPrint($Bd,$e,$u);$b->selectLimitPrint($y);$b->selectLengthPrint($kf);$b->selectActionPrint($u);echo"</form>\n";$E=$_GET["page"];if($E=="last"){$ec=$f->result("SELECT COUNT(*) FROM ".table($a).($Z?" WHERE ".implode(" AND ",$Z):""));$E=floor(max(0,$ec-1)/$y);}$H="SELECT".limit((+$y&&$q&&count($q)<count($N)&&$w=="sql"?"SQL_CALC_FOUND_ROWS ":"").$gc,($Z?"\nWHERE ".implode(" AND ",$Z):"").$kc,($y!=""?+$y:null),($E?$y*$E:0),"\n");echo$b->selectQuery($H);$I=$f->query($H);if(!$I)echo"<p class='error'>".error()."\n";else{if($w=="mssql")$I->seek($y*$E);$Ab=array();echo"<form action='' method='post' enctype='multipart/form-data'>\n";$L=array();while($K=$I->fetch_assoc()){if($E&&$w=="oracle")unset($K["RNUM"]);$L[]=$K;}if($_GET["page"]!="last")$ec=(+$y&&$q&&count($q)<count($N)?($w=="sql"?$f->result(" SELECT FOUND_ROWS()"):$f->result("SELECT COUNT(*) FROM ($H) x")):count($L));if(!$L)echo"<p class='message'>".'No rows.'."\n";else{$za=$b->backwardKeys($a,$af);echo"<table cellspacing='0' class='nowrap checkable' onclick='tableClick(event);' onkeydown='return editingKeydown(event);'>\n","<thead><tr>".(!$q&&$N?"":"<td><input type='checkbox' id='all-page' onclick='formCheck(this, /check/);'> <a href='".h($_GET["modify"]?remove_from_uri("modify"):$_SERVER["REQUEST_URI"]."&modify=1")."'>".'edit'."</a>");$jd=array();$ic=array();reset($N);$me=1;foreach($L[0]as$x=>$X){if($x!=$qd){$X=$_GET["columns"][key($N)];$k=$l[$N?($X?$X["col"]:current($N)):$x];$D=($k?$b->fieldName($k,$me):"*");if($D!=""){$me++;$jd[$x]=$D;$Oa=idf_escape($x);$qc=remove_from_uri('(order|desc)[^=]*|page').'&order%5B0%5D='.urlencode($x);$mb="&desc%5B0%5D=1";echo'<th onmouseover="columnMouse(this);" onmouseout="columnMouse(this, \' hidden\');">','<a href="'.h($qc.($Bd[0]==$Oa||$Bd[0]==$x||(!$Bd&&count($q)<count($N)&&$q[0]==$Oa)?$mb:'')).'">';echo(!$N||$X?apply_sql_function($X["fun"],$D):h(current($N)))."</a>";echo"<span class='column hidden'>","<a href='".h($qc.$mb)."' title='".'descending'."' class='text'> ↓</a>";if(!$X["fun"])echo'<a href="#fieldset-search" onclick="selectSearch(\''.h(js_escape($x)).'\'); return false;" title="'.'Search'.'" class="text jsonly"> =</a>';echo"</span>";}$ic[$x]=$X["fun"];next($N);}}$Pc=array();if($_GET["modify"]){foreach($L
as$K){foreach($K
as$x=>$X)$Pc[$x]=max($Pc[$x],min(40,strlen(utf8_decode($X))));}}echo($za?"<th>".'Relations':"")."</thead>\n";foreach($b->rowDescriptions($L,$n)as$C=>$K){$Df=unique_array($L[$C],$u);$Ef="";foreach($Df
as$x=>$X)$Ef.="&".($X!==null?urlencode("where[".bracket_escape($x)."]")."=".urlencode($X):"null%5B%5D=".urlencode($x));echo"<tr".odd().">".(!$q&&$N?"":"<td>".checkbox("check[]",substr($Ef,1),in_array(substr($Ef,1),(array)$_POST["check"]),"","this.form['all'].checked = false; formUncheck('all-page');").(count($q)<count($N)||information_schema(DB)?"":" <a href='".h(ME."edit=".urlencode($a).$Ef)."'>".'edit'."</a>"));foreach($K
as$x=>$X){if(isset($jd[$x])){$k=$l[$x];if($X!=""&&(!isset($Ab[$x])||$Ab[$x]!=""))$Ab[$x]=(is_mail($X)?$jd[$x]:"");$z="";$X=$b->editVal($X,$k);if($X!==null){if(ereg('blob|bytea|raw|file',$k["type"])&&$X!="")$z=h(ME.'download='.urlencode($a).'&field='.urlencode($x).$Ef);if($X==="")$X="&nbsp;";elseif(is_utf8($X)){if($kf!=""&&ereg('text|blob',$k["type"]))$X=shorten_utf8($X,max(0,+$kf));else$X=h($X);}if(!$z){foreach((array)$n[$x]as$m){if(count($n[$x])==1||end($m["source"])==$x){$z="";foreach($m["source"]as$r=>$Le)$z.=where_link($r,$m["target"][$r],$L[$C][$Le]);$z=h(($m["db"]!=""?preg_replace('~([?&]db=)[^&]+~','\\1'.urlencode($m["db"]),ME):ME).'select='.urlencode($m["table"]).$z);if(count($m["source"])==1)break;}}}if($x=="COUNT(*)"){$z=h(ME."select=".urlencode($a));$r=0;foreach((array)$_GET["where"]as$W){if(!array_key_exists($W["col"],$Df))$z.=h(where_link($r++,$W["col"],$W["val"],$W["op"]));}foreach($Df
as$Dc=>$W)$z.=h(where_link($r++,$Dc,$W));}}if(!$z){if(is_mail($X))$z="mailto:$X";if($ke=is_url($K[$x]))$z=($ke=="http"&&$ba?$K[$x]:"$ke://www.adminer.org/redirect/?url=".urlencode($K[$x]));}$s=h("val[$Ef][".bracket_escape($x)."]");$Y=$_POST["val"][$Ef][bracket_escape($x)];$mc=h($Y!==null?$Y:$K[$x]);$Tc=strpos($X,"<i>...</i>");$yb=is_utf8($X)&&$L[$C][$x]==$K[$x]&&!$ic[$x];$jf=ereg('text|lob',$k["type"]);echo(($_GET["modify"]&&$yb)||$Y!==null?"<td>".($jf?"<textarea name='$s' cols='30' rows='".(substr_count($K[$x],"\n")+1)."'>$mc</textarea>":"<input name='$s' value='$mc' size='$Pc[$x]'>"):"<td id='$s' ondblclick=\"".($yb?"selectDblClick(this, event".($Tc?", 2":($jf?", 1":"")).")":"alert('".h('Use edit link to modify this value.')."')").";\">".$b->selectVal($X,$z,$k));}}if($za)echo"<td>";$b->backwardKeysPrint($za,$L[$C]);echo"</tr>\n";}echo"</table>\n",(!$q&&$N?"":"<script type='text/javascript'>tableCheck();</script>\n");}if($L||$E){$Kb=true;if($_GET["page"]!="last"&&+$y&&count($q)>=count($N)&&($ec>=$y||$E)){$ec=found_rows($T,$Z);if($ec<max(1e4,2*($E+1)*$y)){ob_flush();flush();$ec=$f->result("SELECT COUNT(*) FROM ".table($a).($Z?" WHERE ".implode(" AND ",$Z):""));}else$Kb=false;}echo"<p class='pages'>";if(+$y&&$ec>$y){$Xc=floor(($ec-1)/$y);echo'<a href="'.h(remove_from_uri("page"))."\" onclick=\"pageClick(this.href, +prompt('".'Page'."', '".($E+1)."'), event); return false;\">".'Page'."</a>:",pagination(0,$E).($E>5?" ...":"");for($r=max(1,$E-4);$r<min($Xc,$E+5);$r++)echo
pagination($r,$E);echo($E+5<$Xc?" ...":"").($Kb?pagination($Xc,$E):' <a href="'.h(remove_from_uri()."&page=last").'">'.'last'."</a>");}echo" (".($Kb?"":"~ ").lang(array('%d row','%d rows'),$ec).") ".checkbox("all",1,0,'whole result')."\n";if($b->selectCommandPrint()){echo'<fieldset><legend>Edit</legend><div>
<input type="submit" value="Save"',($_GET["modify"]?'':' title="'.'Double click on a value to modify it.'.'" class="jsonly"');?>>
<input type="submit" name="edit" value="Edit">
<input type="submit" name="clone" value="Clone">
<input type="submit" name="delete" value="Delete" onclick="return confirm('Are you sure? (' + (this.form['all'].checked ? <?php echo$ec,' : formChecked(this, /check/)) + \')\');">
</div></fieldset>
';}$cc=$b->dumpFormat();if($cc){print_fieldset("export",'Export');$Kd=$b->dumpOutput();echo($Kd?html_select("output",$Kd,$la["output"])." ":""),html_select("format",$cc,$la["format"])," <input type='submit' name='export' value='".'Export'."'>\n","</div></fieldset>\n";}}if($b->selectImportPrint()){print_fieldset("import",'Import',!$L);echo"<input type='file' name='csv_file'> ",html_select("separator",array("csv"=>"CSV,","csv;"=>"CSV;","tsv"=>"TSV"),$la["format"],1);echo" <input type='submit' name='import' value='".'Import'."'>","<input type='hidden' name='token' value='$U'>\n","</div></fieldset>\n";}$b->selectEmailPrint(array_filter($Ab,'strlen'),$e);echo"</form>\n";}}}elseif(isset($_GET["variables"])){$Pe=isset($_GET["status"]);page_header($Pe?'Status':'Variables');$Of=($Pe?show_status():show_variables());if(!$Of)echo"<p class='message'>".'No rows.'."\n";else{echo"<table cellspacing='0'>\n";foreach($Of
as$x=>$X){echo"<tr>","<th><code class='jush-".$w.($Pe?"status":"set")."'>".h($x)."</code>","<td>".nbsp($X);}echo"</table>\n";}}elseif(isset($_GET["script"])){header("Content-Type: text/javascript; charset=utf-8");if($_GET["script"]=="db"){$Xe=array("Data_length"=>0,"Index_length"=>0,"Data_free"=>0);foreach(table_status()as$T){$s=js_escape($T["Name"]);json_row("Comment-$s",nbsp($T["Comment"]));if(!is_view($T)){foreach(array("Engine","Collation")as$x)json_row("$x-$s",nbsp($T[$x]));foreach($Xe+array("Auto_increment"=>0,"Rows"=>0)as$x=>$X){if($T[$x]!=""){$X=number_format($T[$x],0,'.',',');json_row("$x-$s",($x=="Rows"&&$X&&$T["Engine"]==($Ne=="pgsql"?"table":"InnoDB")?"~ $X":$X));if(isset($Xe[$x]))$Xe[$x]+=($T["Engine"]!="InnoDB"||$x!="Data_free"?$T[$x]:0);}elseif(array_key_exists($x,$T))json_row("$x-$s");}}}foreach($Xe
as$x=>$X)json_row("sum-$x",number_format($X,0,'.',','));json_row("");}else{foreach(count_tables($b->databases())as$i=>$X)json_row("tables-".js_escape($i),$X);json_row("");}exit;}else{$gf=array_merge((array)$_POST["tables"],(array)$_POST["views"]);if($gf&&!$j&&!$_POST["search"]){$I=true;$B="";if($w=="sql"&&count($_POST["tables"])>1&&($_POST["drop"]||$_POST["truncate"]||$_POST["copy"]))queries("SET foreign_key_checks = 0");if($_POST["truncate"]){if($_POST["tables"])$I=truncate_tables($_POST["tables"]);$B='Tables have been truncated.';}elseif($_POST["move"]){$I=move_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$B='Tables have been moved.';}elseif($_POST["copy"]){$I=copy_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$B='Tables have been copied.';}elseif($_POST["drop"]){if($_POST["views"])$I=drop_views($_POST["views"]);if($I&&$_POST["tables"])$I=drop_tables($_POST["tables"]);$B='Tables have been dropped.';}elseif($w!="sql"){$I=($w=="sqlite"?queries("VACUUM"):apply_queries("VACUUM".($_POST["optimize"]?"":" ANALYZE"),$_POST["tables"]));$B='Tables have been optimized.';}elseif($_POST["tables"]&&($I=queries(($_POST["optimize"]?"OPTIMIZE":($_POST["check"]?"CHECK":($_POST["repair"]?"REPAIR":"ANALYZE")))." TABLE ".implode(", ",array_map('idf_escape',$_POST["tables"]))))){while($K=$I->fetch_assoc())$B.="<b>".h($K["Table"])."</b>: ".h($K["Msg_text"])."<br>";}queries_redirect(substr(ME,0,-1),$B,$I);}page_header(($_GET["ns"]==""?'Database'.": ".h(DB):'Schema'.": ".h($_GET["ns"])),$j,true);if($b->homepage()){if($_GET["ns"]!==""){echo"<h3>".'Tables and views'."</h3>\n";$ff=tables_list();if(!$ff)echo"<p class='message'>".'No tables.'."\n";else{echo"<form action='' method='post'>\n","<p>".'Search data in tables'.": <input name='query' value='".h($_POST["query"])."'> <input type='submit' name='search' value='".'Search'."'>\n";if($_POST["search"]&&$_POST["query"]!="")search_tables();echo"<table cellspacing='0' class='nowrap checkable' onclick='tableClick(event);'>\n",'<thead><tr class="wrap"><td><input id="check-all" type="checkbox" onclick="formCheck(this, /^(tables|views)\[/);">','<th>'.'Table','<td>'.'Engine','<td>'.'Collation','<td>'.'Data Length','<td>'.'Index Length','<td>'.'Data Free','<td>'.'Auto Increment','<td>'.'Rows',(support("comment")?'<td>'.'Comment':''),"</thead>\n";foreach($ff
as$D=>$V){$Pf=($V!==null&&!eregi("table",$V));echo'<tr'.odd().'><td>'.checkbox(($Pf?"views[]":"tables[]"),$D,in_array($D,$gf,true),"","formUncheck('check-all');"),'<th><a href="'.h(ME).'table='.urlencode($D).'" title="'.'Show structure'.'">'.h($D).'</a>';if($Pf){echo'<td colspan="6"><a href="'.h(ME)."view=".urlencode($D).'" title="'.'Alter view'.'">'.'View'.'</a>','<td align="right"><a href="'.h(ME)."select=".urlencode($D).'" title="'.'Select data'.'">?</a>';}else{foreach(array("Engine"=>array(),"Collation"=>array(),"Data_length"=>array("create",'Alter table'),"Index_length"=>array("indexes",'Alter indexes'),"Data_free"=>array("edit",'New item'),"Auto_increment"=>array("auto_increment=1&create",'Alter table'),"Rows"=>array("select",'Select data'),)as$x=>$z)echo($z?"<td align='right'><a href='".h(ME."$z[0]=").urlencode($D)."' id='$x-".h($D)."' title='$z[1]'>?</a>":"<td id='$x-".h($D)."'>&nbsp;");}echo(support("comment")?"<td id='Comment-".h($D)."'>&nbsp;":"");}echo"<tr><td>&nbsp;<th>".sprintf('%d in total',count($ff)),"<td>".nbsp($w=="sql"?$f->result("SELECT @@storage_engine"):""),"<td>".nbsp(db_collation(DB,collations()));foreach(array("Data_length","Index_length","Data_free")as$x)echo"<td align='right' id='sum-$x'>&nbsp;";echo"</table>\n","<script type='text/javascript'>tableCheck();</script>\n";if(!information_schema(DB)){echo"<p>".(ereg('^(sql|sqlite|pgsql)$',$w)?($w!="sqlite"?"<input type='submit' value='".'Analyze'."'> ":"")."<input type='submit' name='optimize' value='".'Optimize'."'> ":"").($w=="sql"?"<input type='submit' name='check' value='".'Check'."'> <input type='submit' name='repair' value='".'Repair'."'> ":"")."<input type='submit' name='truncate' value='".'Truncate'."'".confirm("formChecked(this, /tables/)")."> <input type='submit' name='drop' value='".'Drop'."'".confirm("formChecked(this, /tables|views/)").">\n";$h=(support("scheme")?schemas():$b->databases());if(count($h)!=1&&$w!="sqlite"){$i=(isset($_POST["target"])?$_POST["target"]:(support("scheme")?$_GET["ns"]:DB));echo"<p>".'Move to other database'.": ",($h?html_select("target",$h,$i):'<input name="target" value="'.h($i).'">')," <input type='submit' name='move' value='".'Move'."'>",(support("copy")?" <input type='submit' name='copy' value='".'Copy'."'>":""),"\n";}echo"<input type='hidden' name='token' value='$U'>\n";}echo"</form>\n";}echo'<p><a href="'.h(ME).'create=">'.'Create table'."</a>\n";if(support("view"))echo'<a href="'.h(ME).'view=">'.'Create view'."</a>\n";if(support("routine")){echo"<h3>".'Routines'."</h3>\n";$Ae=routines();if($Ae){echo"<table cellspacing='0'>\n",'<thead><tr><th>'.'Name'.'<td>'.'Type'.'<td>'.'Return type'."<td>&nbsp;</thead>\n";odd('');foreach($Ae
as$K){echo'<tr'.odd().'>','<th><a href="'.h(ME).($K["ROUTINE_TYPE"]!="PROCEDURE"?'callf=':'call=').urlencode($K["ROUTINE_NAME"]).'">'.h($K["ROUTINE_NAME"]).'</a>','<td>'.h($K["ROUTINE_TYPE"]),'<td>'.h($K["DTD_IDENTIFIER"]),'<td><a href="'.h(ME).($K["ROUTINE_TYPE"]!="PROCEDURE"?'function=':'procedure=').urlencode($K["ROUTINE_NAME"]).'">'.'Alter'."</a>";}echo"</table>\n";}echo'<p>'.(support("procedure")?'<a href="'.h(ME).'procedure=">'.'Create procedure'.'</a> ':'').'<a href="'.h(ME).'function=">'.'Create function'."</a>\n";}if(support("event")){echo"<h3>".'Events'."</h3>\n";$L=get_rows("SHOW EVENTS");if($L){echo"<table cellspacing='0'>\n","<thead><tr><th>".'Name'."<td>".'Schedule'."<td>".'Start'."<td>".'End'."</thead>\n";foreach($L
as$K){echo"<tr>",'<th><a href="'.h(ME).'event='.urlencode($K["Name"]).'">'.h($K["Name"])."</a>","<td>".($K["Execute at"]?'At given time'."<td>".$K["Execute at"]:'Every'." ".$K["Interval value"]." ".$K["Interval field"]."<td>$K[Starts]"),"<td>$K[Ends]";}echo"</table>\n";$Jb=$f->result("SELECT @@event_scheduler");if($Jb&&$Jb!="ON")echo"<p class='error'><code class='jush-sqlset'>event_scheduler</code>: ".h($Jb)."\n";}echo'<p><a href="'.h(ME).'event=">'.'Create event'."</a>\n";}if($ff)echo"<script type='text/javascript'>ajaxSetHtml('".js_escape(ME)."script=db');</script>\n";}}}page_footer();
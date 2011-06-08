<?php
/**
 * Copyright (c) STMicroelectronics 2011. All rights reserved
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
require_once('pre.php');

$attachmentArea = $GLOBALS['sys_data_dir'].'/massmail/';

if(!is_dir($attachmentArea)) {
    mkdir($attachmentArea,0777);
}

sleep(2);
if(!empty($_FILES['image'])){
    $ftmp = $_FILES['image']['tmp_name'];
    $oname = $_FILES['image']['name'];
    $osize = $_FILES['image']['size'];

    $extenssion = strstr($oname, ".");
    $fname = $attachmentArea.$_FILES['image']['name'];

    if(move_uploaded_file($ftmp, $fname)){
        ?>
<html>
<head>
<script>
            var par = window.parent.document;
            var images = par.getElementById('images1');
            var imgdiv = images.getElementsByTagName('div')[<?php echo (int) $_POST['imgnum'] ?>];
            var image = imgdiv.getElementsByTagName('img')[0];
            imgdiv.removeChild(image);
            var bre = par.createElement('br');
            imgdiv.appendChild(bre);
            var attachment=par.createElement('span');
            attachment.innerHTML='<?php echo $oname ?> <img src="../themes/common/images/ic/cross.png" onclick="delete_image(\'id\');" > &nbsp; Size : <?php echo $osize ?> bytes &nbsp;  Disposition inline ? <input type=checkbox name="attachmentDisposition" onclick="setAttachmentDisposition()" checked="checked" id="disposition" />';
            imgdiv.appendChild(attachment);
            // TODO : implement delete_image 
</script>
</head>
</html>
<?php
        exit();
}
}
?>
<html><head>
<script language="javascript">
function upload(){
    // hide old iframe
    var par = window.parent.document;
    var num = par.getElementsByTagName('iframe').length - 1;
    var iframe = par.getElementsByTagName('iframe')[num];
    iframe.className = 'hidden';	
    // create new iframe
    var new_iframe = par.createElement('iframe');
    new_iframe.src = 'massmail_upload_attachments.php';
    new_iframe.frameBorder = '0';
    par.getElementById('iframe').appendChild(new_iframe);
    // add image progress
    var images = par.getElementById('images1');
    var new_div = par.createElement('div');
    var new_img = par.createElement('img');
    new_img.src = '/themes/common/images/ic/spinner.gif';
    new_img.className = 'load';
    new_div.appendChild(new_img);

    images.appendChild(new_div);

    var imgnum = images.getElementsByTagName('div').length - 1;
    document.iform.imgnum.value = imgnum;
    setTimeout(document.iform.submit(),5000);
}

function ajaxRequest(){
 var activexmodes=['Msxml2.XMLHTTP', 'Microsoft.XMLHTTP']; //activeX versions to check for in IE
 if (window.ActiveXObject){ //Test for support for ActiveXObject in IE first (as XMLHttpRequest in IE7 is broken)
  for (var i=0; i<activexmodes.length; i++){
   try{
    return new ActiveXObject(activexmodes[i]);
   }
   catch(e){
    //suppress error
   }
  }
 }
 else if (window.XMLHttpRequest) // if Mozilla, Safari etc
  return new XMLHttpRequest();
 else
  return false;
}

function sendPreview() {
    var mypostrequest=new ajaxRequest();
    mypostrequest.onreadystatechange=function() {
        if (mypostrequest.readyState==4){
            document.getElementById('preview_result').innerHTML = '<img src=\"/themes/common/images/ic/spinner.gif\" border=\"0\" />';
            if (mypostrequest.status==200 || window.location.href.indexOf('http')==-1) {
                document.getElementById('preview_result').innerHTML=mypostrequest.responseText;
            } else {
                alert('An error has occured making the request');
            }
        }
    }
    var mailSubject=encodeURIComponent(document.getElementById('mail_subject').value);
    var mailMessage=encodeURIComponent(document.getElementById('mail_message').value);
    var previewDestination=encodeURIComponent(document.getElementById('preview_destination').value);
    for (var i=0; i < document.massmail_form.body_format.length; i++) {
        if (document.massmail_form.body_format[i].checked) {
            var bodyFormat = document.massmail_form.body_format[i].value;
        }
    }
    var parameters='destination=preview&mail_subject='+mailSubject+'&body_format='+bodyFormat+'&mail_message='+mailMessage+'&preview_destination='+previewDestination+'&Submit=Submit';
    mypostrequest.open('POST', '/admin/massmail_execute.php', true);
    mypostrequest.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    mypostrequest.send(parameters);
}

</script>
<style>
#file {
width: 350px;
}
</style>
</head><body><center>
<form name="iform" action="" method="post" enctype="multipart/form-data">
<span> Attach a file :</span>
<input id="file" type="file" name="image" onChange="upload()" />
<input type="hidden" name="imgnum" />
</form>
</center></html>
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

if (!is_dir($attachmentArea)) {
    mkdir($attachmentArea, 0777);
}

sleep(2);
if (!empty($_FILES['image'])) {
    $ftmp = $_FILES['image']['tmp_name'];
    $oname = $_FILES['image']['name'];
    $osize = $_FILES['image']['size'];

    $extenssion = strstr($oname, ".");
    $fname = $attachmentArea.$_FILES['image']['name'];

    if (move_uploaded_file($ftmp, $fname)) {

print " <html>
        <head>
        <script>
            var par = window.parent.document;
            var images = par.getElementById('images1');
            var imgdiv = images.getElementsByTagName('div')[". (int) $_POST['imgnum'] ."];
            var image = imgdiv.getElementsByTagName('img')[0];
            imgdiv.removeChild(image);
            var bre = par.createElement('br');
            imgdiv.appendChild(bre);
            var attachment=par.createElement('span');";
print "  attachment.innerHTML='". $oname ."<img src=\"../themes/common/images/ic/cross.png\" onclick=\"delete_image(\'id\');\" > &nbsp; Size : ".  $osize ." bytes &nbsp;  Disposition inline ? <input type=checkbox name=\"attachmentDisposition\" onclick=\"setAttachmentDisposition()\" checked=\"checked\" id=\"disposition\" />';
            imgdiv.appendChild(attachment);
            // TODO : implement delete_image 
        </script>
        </head>
        </html>";
        exit();
    }
}

print "<html><head>
<script language=\"javascript\">
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
}";

print "</script>
<style>
#file {
width: 350px;
}
</style>";

print '</head><body><center>
<form name="iform" action="" method="post" enctype="multipart/form-data">
<span> Attach a file :</span>
<input id="file" type="file" name="image" onChange="upload()" />
<input type="hidden" name="imgnum" />
</form>
</center></html>';
?>
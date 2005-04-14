<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
require_once('pre.php');
$Language->loadLanguageMsg('file/file');

if (user_isloggedin()) {

  // Must have a group_id and file_id otherwise
  // we cannot do much
  if (!$file_id || !$group_id) {
    exit_missing_param();
  }

    //determine font for this platform
    if (browser_is_windows() && browser_is_ie()) {
    
            //ie needs smaller fonts
            $font_size = 'smaller';
            $font_size_normal = 'small';
    
    } else if (browser_is_windows()) {
    
            //netscape on wintel
            $font_size = 'small';
            $font_size_normal = 'medium';
    
    } else if (browser_is_mac()){
    
            //mac users need bigger fonts
            $font_size = 'medium';
            $font_size_normal = 'medium';
    
    } else {
    
            //linux and other users
            $font_size = 'small';
            $font_size_normal = 'medium';
    
    }
?>
<html>
<head>
   <title><?php echo $Language->getText('file_confirm_download','download_agreement'); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" type="text/css" href="<? echo util_get_css_theme(); ?>">
</head>

<body>
<table width="100%" height="100%" cellpadding="5" class="bg_confirmdownload">
<tr><td>
<span class="small">
<div align="center">
<?php echo $Language->getText('file_confirm_download','download_explain',array($GLOBALS['sys_org_name'],"/docman/display_doc.php?docid=16&group_id=1",$GLOBALS['sys_email_contact'])); ?><br>

<br><br>
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="normal">
  <tr> 
    <td> 
      <div align="center"><a href="javascript:opener.download(<?php echo "$group_id,$file_id,'$filename'"; ?>);"><b><?php echo $Language->getText('file_confirm_download','agree'); ?></b></a></div>
    </td>
    <td> 
      <div align="center"><a href="javascript:window.close();"><b><?php echo $Language->getText('file_confirm_download','decline'); ?></b></a></div>
    </td>
  </tr>
</table>
</span>
</td></tr>
</table>
</body>
</html>
<?

} else {
  /*
    Not logged in
  */
  exit_not_logged_in();
}



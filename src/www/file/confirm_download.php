<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
require_once('pre.php');
require_once('common/frs/FRSFileFactory.class.php');
require_once('common/frs/FRSPackageFactory.class.php');
$Language->loadLanguageMsg('file/file');

if (user_isloggedin()) {
	
  $frsff = new FRSFileFactory();
  $frspf = new FRSPackageFactory();
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

    if (!$filename) {
        # Get it from DB
        $res =& $frsff->getFRSFileFromDb($file_id);

        if (count( $res ) > 0) {
            $filename = $res->getFileName();
        }
    }

    if (!$GLOBALS['sys_frs_license_mandatory']) {
        // Display license popup?
        // This is useful when using a 'file #123' reference, that points to this script
        $res =& $frspf->getFRSPackageByFileIdFromDb($file_id);
        //$sql="SELECT approve_license FROM frs_package,frs_release,frs_file WHERE frs_file.file_id=$file_id and frs_file.release_id=frs_release.release_id and  frs_release.package_id=frs_package.package_id";
        //res = db_query( $sql);
        if (count( $res ) > 0) {
            if ($res->getApproveLicence()==0) {
                // Directly display file
                $location = "Location: /file/download.php/$group_id/$file_id/$filename";
                header($location);
            }
        }
    }

    if ($popup) {
        $dlscript='opener.download';
        $cancelscript='window.close()';
    } else {
        $dlscript='download_local';
        $cancelscript='history.back()';
    }
?>
<html>
<head>
   <title><?php echo $Language->getText('file_confirm_download','download_agreement'); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" type="text/css" href="<? echo util_get_css_theme(); ?>">
</head>

<body>
<SCRIPT language="JavaScript">
<!--

function download_local(group_id,file_id,filename) {
    url = "http://cxtst.xrce.xerox.com/file/download.php/" + group_id + "/" + file_id +"/"+filename;
    self.location = url;
    //history.back();
}
-->
</SCRIPT>
<table width="100%" height="100%" cellpadding="5" class="bg_confirmdownload">
<tr><td>
<span class="small">
<div align="center">
<?php echo $Language->getText('file_confirm_download','download_explain',array($GLOBALS['sys_org_name'],$GLOBALS['sys_email_contact'])); ?><br>

<br><br>
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="normal">
  <tr> 
    <td> 
      <div align="center"><a href="javascript:<?php echo "$dlscript($group_id,$file_id,'$filename'"; ?>);"><b><?php echo $Language->getText('file_confirm_download','agree'); ?></b></a></div>
    </td>
    <td> 
      <div align="center"><a href="javascript:<?php echo "$cancelscript"?>;"><b><?php echo $Language->getText('file_confirm_download','decline'); ?></b></a></div>
    </td>
  </tr>
<?php if (!$popup) echo '<p>  <tr><td colspan="2" class="small"><a href="javascript:history.back();">'.$Language->getText('file_confirm_download','back').'</a></td></tr>'; ?>
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



<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2004. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
//	Originally written by Laurent Julliard 2004, CodeX Team, Xerox
//
$Language->loadLanguageMsg('svn/svn');

$project=project_get_object($group_id);
$gname = $project->getUnixName();

if ($post_changes) {
    $buffer = svn_utils_read_svn_access_file_defaults($gname);
    $buffer .= $form_accessfile;
    $ret = svn_utils_write_svn_access_file($gname,$buffer);
    if ($ret) {
	$GLOBALS['feedback'] .= $Language->getText('svn_admin_access_control','upd_success');
    } else {
	$GLOBALS['feedback'] .= $Language->getText('svn_admin_access_control','upd_fail');
    }
}

// Display the form
svn_header_admin(array ('title'=>$Language->getText('svn_admin_access_control','access_ctrl'),
		      'help' => 'SubversionAdministrationInterface.html#SubversionAccessControl'));

echo '
       <H2>'.$Language->getText('svn_admin_access_control','access_ctrl').'</H2>';

if (svn_utils_svn_repo_exists($gname)) {
    $svn_accessfile = svn_utils_read_svn_access_file($gname);
    echo'
       <FORM ACTION="'. $PHP_SELF .'" METHOD="GET">
       <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
       <INPUT TYPE="HIDDEN" NAME="func" VALUE="access_control">
       <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
      <p>'.$Language->getText('svn_admin_access_control','def_policy',$GLOBALS['sys_name']).' 
      <h3>'.$Language->getText('svn_admin_access_control','access_ctrl_file').' '. help_button('SubversionAdministrationInterface.html#SubversionAccessControl').':</h3> 
       <TEXTAREA cols="70" rows="20" wrap="virtual" name="form_accessfile">'.$svn_accessfile.'</TEXTAREA>
        </p>
        <p><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_submit').'"></p></FORM>';

} else {
      echo '<p>'.$Language->getText('svn_admin_access_control','not_created');
}
svn_footer(array());
?>

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

$project=project_get_object($group_id);
$gname = $project->getUnixName();

if ($post_changes) {
    $buffer = svn_utils_read_svn_access_file_defaults($gname);
    $buffer .= $form_accessfile;
    $ret = svn_utils_write_svn_access_file($gname,$buffer);
    if ($ret) {
	$GLOBALS['feedback'] .= "Access Control updated succesfully";
    } else {
	$GLOBALS['feedback'] .= "Access Control update failed";
    }
}

// Display the form
svn_header_admin(array ('title'=>'Subversion Administration - Access Control',
		      'help' => 'SubversionAdministrationInterface.html#SubversionAccessControl'));

echo '
       <H2>Subversion Administration - Access Control</H2>';

if (svn_utils_svn_repo_exists($gname)) {
    $svn_accessfile = svn_utils_read_svn_access_file($gname);
    echo'
       <FORM ACTION="'. $PHP_SELF .'" METHOD="GET">
       <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
       <INPUT TYPE="HIDDEN" NAME="func" VALUE="access_control">
       <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
      <p>The default CodeX policy is to allow read-write access to all project members
      on the entire repository and read-only access to all other '.$GLOBALS['sys_name'].' users.You can tune or even redefine the access permissions below to suit your needs. 
      <h3>Access Control File '. help_button('SVNWebInterface.html#SVNAdministration').':</h3> 
       <TEXTAREA cols="70" rows="20" wrap="virtual" name="form_accessfile">'.$svn_accessfile.'</TEXTAREA>
        </p>
        <p><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Submit"></p></FORM>';

} else {
      echo '<p>Your subversion repository has not been created yet. Access
                  permissions cannot yet be defined';
}
svn_footer(array());
?>

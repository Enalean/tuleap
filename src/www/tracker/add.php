<?php
//
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
// 
//
//
//  Written for Codendi by Stephane Bouhet
//

//require_once('common/tracker/ArtifactFactory.class.php');

require_once('include/ArtifactRulesManagerHtml.class.php');
// Check if a user can submit a new without loggin
if ( !user_isloggedin() && !$ath->allowsAnon() ) {
	exit_not_logged_in();
	return;
}

// Check if this tracker is valid (not deleted)
if ( !$ath->isValid() ) {
	exit_error($Language->getText('global', 'error'),$Language->getText('tracker_add', 'invalid'));
}

//
//  make sure this person has permission to add artifacts
//
if (!$ath->userCanSubmit()) {
    exit_permission_denied();
}

// Display the menus
$ath->header(array('title'=>$Language->getText('tracker_add', 'add_a')." ".$ath->getCapsItemName(),'titlevals'=>array($ath->getName()),'pagename'=>'tracker_browse',
	'atid'=>$ath->getID(),'sectionvals'=>array($group->getPublicName()),'help' => 'ArtifactSubmission.html'));
echo '<div id="tracker_toolbar_clear"></div>';

// Display the artifact items according to all the parameters
$ah->displayAdd(user_getid());

echo "<script type=\"text/javascript\">\n";
$armh = new ArtifactRulesManagerHtml($ath);
$armh->displayRulesAsJavascript();
echo "new UserAutoCompleter('tracker_cc',
                          '".util_get_dir_image_theme()."',
                          true);\n";
echo "</script>";

// Display footer page
$ath->footer(array());

?>

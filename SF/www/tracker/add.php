<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id$
//
//
//  Written for CodeX by Stephane Bouhet
//

//require_once($DOCUMENT_ROOT.'/../common/tracker/ArtifactFactory.class');

//
//  make sure this person has permission to view artifacts
//
if (!$ath->userCanView()) {
	exit_permission_denied();
}

// Display the menus
$ath->header(array('title'=>'Add a '.$ath->getItemName(),'titlevals'=>array($ath->getName()),'pagename'=>'tracker_browse',
	'atid'=>$ath->getID(),'sectionvals'=>array($group->getPublicName())));

// Display the artifact items according to all the parameters
$ah->displayAdd();

// Display footer page
$ath->footer(array());

?>

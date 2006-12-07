<?php

//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id$
//
//
//  Written for CodeX by Marie-Luise Schneider
//

//require_once('common/include/Error.class.php');
//require_once('common/tracker/ArtifactField.class.php');
//require_once('include/ArtifactFieldHtml.class.php');
//require_once('common/tracker/ArtifactFieldFactory.class.php');

$Language->loadLanguageMsg('tracker/tracker');

// Check if this tracker is valid (not deleted)
if ( !$ath->isValid() ) {
	exit_error($Language->getText('global','error'),$Language->getText('tracker_add','invalid'));
}

// Create factories
$art_field_fact = new ArtifactFieldFactory($ath);

// Printer version ?
if ( !isset($pv) ) {
	$pv = false;
	$ro = false;
} else {
	if ( $pv ) $ro = true;
}

$params=array('title'=>$group->getPublicName().' '.$ath->getName().' #'.$ah->getID(). ' - \'' . $ah->getSummary().'\'',
              'pagename'=>'tracker',
              'atid'=>$ath->getID(),
              'sectionvals'=>array($group->getPublicName()),
              'pv'=>$pv,
              'help' => 'ArtifactUpdate.html');

$ath->header($params);


// artifact object (and field values) initialized in script above (index.php)
$ah->displayCopy($ro,$pv);

// Display footer page
$ath->footer($params);

?>

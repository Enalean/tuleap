<?php
/**
  *
  * SourceForge Generic Tracker facility
  *
  * SourceForge: Breaking Down the Barriers to Open Source Development
  * Copyright 1999-2001 (c) VA Linux Systems
  * http://sourceforge.net
  *
  * @version   $Id$
  *
  */

//require_once('common/include/Error.class');
//require_once('common/tracker/ArtifactField.class');
//require_once('include/ArtifactFieldHtml.class');
//require_once('common/tracker/ArtifactFieldFactory.class');

$Language->loadLanguageMsg('tracker/tracker');

// Check if this tracker is valid (not deleted)
if ( !$ath->isValid() ) {
	exit_error($Language->getText('global','error'),$Language->getText('tracker_add', 'invalid'));
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
              'help' => 'ArtifactSubmission.html' );

$ath->header($params);


// artifact object (and field values) initialized in script above (index.php)
$ah->display($ro,$pv,user_getid());

// Display footer page
$ath->footer($params);

?>

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

//require_once($DOCUMENT_ROOT.'/../common/include/Error.class');
//require_once($DOCUMENT_ROOT.'/../common/tracker/ArtifactField.class');
//require_once('include/ArtifactFieldHtml.class');
//require_once($DOCUMENT_ROOT.'/../common/tracker/ArtifactFieldFactory.class');

// Create factories
$art_field_fact = new ArtifactFieldFactory($ath);

// Printer version ?
if ( !isset($pv) ) {
	$pv = false;
	$ro = false;
} else {
	if ( $pv ) $ro = true;
}

if ($pv) {
    help_header('Artifact detail '.format_date($sys_datefmt,time()),false);	
} else {
	$ath->header(array ('title'=>'Modify: '.$ah->getID(). ' - ' . $ah->getSummary(),'pagename'=>'tracker','atid'=>$ath->getID(),'sectionvals'=>array($group->getPublicName()),'help' => 'HELP_FIXME.html' ));
}

$res = $ah->getFieldsValues();
$ah->display($res,$ro,$pv);

// Display footer page
if ( $pv ) {
     help_footer();
} else {
	$ath->footer(array());
}

?>

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

$ath->header(array ('title'=>'Modify: '.$ah->getID(). ' - ' . $ah->getSummary(),'pagename'=>'tracker','atid'=>$ath->getID(),'sectionvals'=>array($group->getPublicName()) ));

$res = $ah->getFieldsValues();
$ah->display($res,false);

// Display footer page
$ath->footer(array());

?>

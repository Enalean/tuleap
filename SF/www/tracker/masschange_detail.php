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

// Printer version ?
if ( !isset($pv) ) {
	$pv = false;
	$ro = false;
} else {
	if ( $pv ) $ro = true;
}

$params=array('title'=>$group->getPublicName().' '.$ath->getName().' '.$Language->getText('tracker_index','mass_change'),
              'pagename'=>'tracker',
              'atid'=>$ath->getID(),
              'sectionvals'=>array($group->getPublicName()),
              'pv'=>$pv,
              'help' => 'ArtifactMassChange.html');

$ath->header($params);


if (strstr($submit,$Language->getText('tracker_masschange_detail','selected_items'))) {
  if (!$mass_change_ids) {
    $feedback = $Language->getText('tracker_masschange_detail','no_items_selected');
  } else {
    $ath->displayMassChange($mass_change_ids);
  }
} else {
  // If still not defined then force it to system 'Default' report
  if (!$report_id) { $report_id=100; }
  // Create factories
  $report_fact = new ArtifactReportFactory();
  // Create the HTML report object
  $art_report_html = $report_fact->getArtifactReportHtml($report_id,$atid);
  $query = $art_field_fact->extractFieldList();
  $ath->displayMassChange(null,$query,$art_report_html);
}
// Display footer page
$ath->footer($params);

?>

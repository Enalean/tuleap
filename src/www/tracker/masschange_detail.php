<?php
/**
  *
  * SourceForge Generic Tracker facility
  *
  * SourceForge: Breaking Down the Barriers to Open Source Development
  * Copyright 1999-2001 (c) VA Linux Systems
  * http://sourceforge.net
  *
  *
  */

//require_once('common/include/Error.class.php');
//require_once('common/tracker/ArtifactField.class.php');
//require_once('include/ArtifactFieldHtml.class.php');
//require_once('common/tracker/ArtifactFieldFactory.class.php');

$Language->loadLanguageMsg('tracker/tracker');

// Printer version ?
if ( !$request->exist('pv') ) {
	$pv = false;
	$ro = false;
} else {
    $pv = $request->get('pv');
	if ( $pv ) $ro = true;
}

$params=array('title'=>$group->getPublicName().' '.$ath->getName().' '.$Language->getText('tracker_index','mass_change'),
              'pagename'=>'tracker',
              'atid'=>$ath->getID(),
              'sectionvals'=>array($group->getPublicName()),
              'pv'=>$pv,
              'help' => 'ArtifactMassChange.html');

$ath->header($params);

$submit = $request->get('submit');
if (strstr($submit,$Language->getText('tracker_masschange_detail','selected_items'))) {
    $mass_change_ids = $request->get('mass_change_ids');
  if (!$mass_change_ids) {
    $feedback = $Language->getText('tracker_masschange_detail','no_items_selected');
  } else {
    $ath->displayMassChange($ro, $mass_change_ids);
  }
} else {
  // If still not defined then force it to system 'Default' report
  $report_id = $request->get('report_id');
  if (!$report_id) { $report_id=100; }
  // Create factories
  $report_fact = new ArtifactReportFactory();
  // Create the HTML report object
  $art_report_html = $report_fact->getArtifactReportHtml($report_id,$atid);
  $query = $art_field_fact->extractFieldList();
  $ath->displayMassChange($ro, null,$query,$art_report_html);
}
// Display footer page
$ath->footer($params);

?>

<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id: masschange.php 1387 2005-03-08 16:41:17Z guerin $
//
//
//

$Language->loadLanguageMsg('tracker/tracker');

//
//  make sure this person has permission to view artifacts
//
if (!$ath->userCanView()) {
	exit_permission_denied();
}

// Check if this tracker is valid (not deleted)
if ( !$ath->isValid() ) {
	exit_error($Language->getText('global','error'),$Language->getText('tracker_add','invalid'));
}

$constraint = "AND a.artifact_id IN ($export_aids)";
$sql = $ath->buildExportQuery($fields,$col_list,$lbl_list,$dsc_list,$export_select,$export_from,$export_where,$multiple_queries,$all_queries,$constraint);

// Normally these two fields should be part of the artifact_fields.
// For now big hack:
// As we don't know the projects language
$submitted_field = $art_field_fact->getFieldFromName('submitted_by');
//print_r($submitted_field);

if (strstr($submitted_field->getLabel(),"ubmit")) {
  // Assume English
  $lbl_list['follow_ups'] = "Follow-up Comments";
  $lbl_list['is_dependent_on'] = "Depend on";
  
  $dsc_list['follow_ups'] = "All follow-up comments in one chunck of text";
  $dsc_list['is_dependent_on'] = "List of artifacts this artifact depends on";
 } else {
  // Assume French
  $lbl_list['follow_ups'] = "Fil de commentaires";
  $lbl_list['is_dependent_on'] = "Dépend de";
  
  $dsc_list['follow_ups'] = "Tout le fil de commentaires en un seul bloc de texte";
  $dsc_list['is_dependent_on'] = "Liste des artefacts dont celui-ci dépend";
 }

// Add the 2 fields that we build ourselves for user convenience
// - All follow-up comments
// - Dependencies

$col_list[] = 'follow_ups';
$col_list[] = 'is_dependent_on';

$eol = "\n";

// If user asked to export only displayed fields (fields displayed in the current report)
// The export is based on the arrays col_list and lbl_list, that contain the fields to export.
// Basically, these arrays contain all the fields of the tracker,
// so we simply remove the non-displayed fields from these arrays.
if (isset($only_displayed_fields) && $only_displayed_fields == 'on') {
    $artifact_report = new ArtifactReport($report_id, $atid);
    $displayed_fields = $artifact_report->getResultFields();
    // array_intersect_key is a PHP 5 function (implemented here in src/www/include/utils.php)
    $col_list = array_intersect_key($col_list, $displayed_fields);
    $lbl_list = array_intersect_key($lbl_list, $displayed_fields);
}

//$sql = $export_select." ".$export_from." ".$export_where." AND a.artifact_id IN ($export_aids) group by a.artifact_id";

if ($multiple_queries) {
  $all_results = array();
  foreach($all_queries as $q) {
    $result = db_query($q);
    $all_results[] = $result;
    $rows = db_numrows($result);    
  }
} else {
  $result = db_query($sql);
  $rows = db_numrows($result);
}

// Send the result in CSV format
if ($result && $rows > 0) {
  $file_name = str_replace(' ','_','artifact_'.$ath->getItemName());
  header ('Content-Type: text/csv');
  header ('Content-Disposition: filename='.$file_name.'_'.$ath->Group->getUnixName().'.csv');
  
  echo build_csv_header($col_list, $lbl_list).$eol;
  
  if ($multiple_queries) {
    $multiarr = array();
    for ($i = 0; $i < $rows; $i++) {
      foreach ($all_results as $result) {
	$multiarr = array_merge($multiarr,db_fetch_array($result));
      }
      
      prepare_artifact_record($ath,$fields,$atid,$multiarr);
      $curArtifact=new Artifact($ath, $multiarr['artifact_id']);
      if ($curArtifact->userCanView(user_getid())) {
	echo build_csv_record($col_list, $multiarr).$eol;
      }
    }
    
  } else {
    while ($arr = db_fetch_array($result)) {	    
      prepare_artifact_record($ath,$fields,$atid,$arr);
      $curArtifact=new Artifact($ath, $arr['artifact_id']);
      if ($curArtifact->userCanView(user_getid())) {
	echo build_csv_record($col_list, $arr).$eol;
      }
    }
  }
  
 } else {
  $params['group']=$group_id;
  $params['toptab']='tracker';
  $params['pagename']='trackers';
  $params['title']=$Language->getText('tracker_index','trackers_for');
  $params['sectionvals']=array($group->getPublicName());
  $params['help']='TrackerService.html';
  $params['pv']  = isset($pv)?$pv:'';
  site_project_header($params);
  
  echo '<h3>'.$Language->getText('project_export_artifact_export','art_export').'</h3>';
  if ($result) {
    echo '<P>'.$Language->getText('project_export_artifact_export','no_art_found');
  } else {
    echo '<P>'.$Language->getText('project_export_artifact_export','db_access_err',$GLOBALS['sys_name']);
    echo '<br>'.db_error();
  }
  site_project_footer( array() );
}


?>

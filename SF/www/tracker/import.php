<?php

//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id$
//
//
//  Written for CodeX by Marie-Luise Schneider
//

require('./tracker_import_utils.php');
require_once('www/project/export/project_export_utils.php');

$Language->loadLanguageMsg('tracker/tracker');

if($group_id && $atid && $user_id) {

  //   parse the CSV file and show the parse report *****************************************************
  if ($mode == "parse") {
		
    //if (!$file_upload) {
      //if (!$data) {
      //	exit_missing_param();
      //} else {
    //$csv_filename = tempnam("","imp");
    //$csv_file = fopen($csv_filename,'w');
    //fwrite($csv_file,stripslashes($data));
    //fclose($csv_file);
    //$is_tmp = true;
    //}
    //} else {
      if (!file_exists($csv_filename) || !is_readable($csv_filename)) {
	exit_missing_param();
      }
      $is_tmp = false;
      //}

    
    $ok = parse($csv_filename,$group_id,$is_tmp,
		$used_fields,$fields,$artifacts_data,
		$aid_column,$submitted_by_column,$submitted_on_column,
		$number_inserts,$number_updates,
		$errors);

    $ath->header(array ('title'=>$Language->getText('tracker_import','art_import').$ath->getID(). ' - ' . $ath->getName(),'pagename'=>'tracker',
			'atid'=>$ath->getID(),'sectionvals'=>array($group->getPublicName()),
			'help' => 'ArtifactImport.html'));

    echo '<h2>'.$Language->getText('tracker_import','parse_report').'</h2>';
    if (!$ok) {
      show_errors($errors);
    } else {
      echo $Language->getText('tracker_import','ready',array(($number_inserts+$number_updates),$number_inserts, $number_updates))."<br><br>\n";
      show_parse_results($used_fields,$fields,$artifacts_data,$aid_column,$submitted_by_column,$submitted_on_column,$group_id);
    }

    $ath->footer(array());


    //   import the artifacts that the user has accepted from the parse report **********************************
  } else if ($mode == "import") {  
    
    for ($i=0; $i < $count_artifacts; $i++) {
      for ($c=0; $c < count($parsed_labels); $c++) {
	$label = $parsed_labels[$c];
	$var_name = "artifacts_data_".$i."_".$c;
	$data[$label] = $$var_name;
	//echo "insert $label,".$$var_name." into data<br>";
      }
      $artifacts_data[] = $data;
    }
    
    $ok = update_db($parsed_labels,$artifacts_data,$aid_column,$errors);
    
    if ($ok) $feedback = $Language->getText('tracker_import','success_import',$count_artifacts)." ";
    else $feedback = $errors;

    //update group history
    group_add_history($Language->getText('tracker_import_admin','import'),$ath->getName(),$group_id);

    require('./browse.php');
    

    //   screen showing the allowed input format of the CSV files *************************************************
  } else if ($mode == "showformat") {

    // project_export_utils is using $at instead of $ath
    $at = $ath;
    $ath->header(array ('title'=>$Language->getText('tracker_import','art_import').' '.$ath->getID(). ' - ' . $ath->getName(),'pagename'=>'tracker',
			'atid'=>$ath->getID(),'sectionvals'=>array($group->getPublicName()),
			'help' => 'ArtifactImport.html'));
    $sql = $ath->buildExportQuery($fields,$col_list,$lbl_list,$dsc_list);
    
    //we need only one single record
    $sql .= " LIMIT 1";

    //get all mandatory fields
    $mand_list = mandatory_fields($ath);
    
    // Add the 2 fields that we build ourselves for user convenience
    // - All follow-up comments
    // - Dependencies
    
    $col_list[] = 'follow_ups';
    $col_list[] = 'is_dependent_on';
    $col_list[] = 'add_cc';
    $col_list[] = 'cc_comment';
    
    $lbl_list['follow_ups'] = $Language->getText('tracker_import','follow_ups');
    $lbl_list['is_dependent_on'] = $Language->getText('tracker_import','depend_on');
    $lbl_list['add_cc'] = $Language->getText('tracker_import','cc_list');
    $lbl_list['cc_comment'] = $Language->getText('tracker_import','cc_comment');
    
    $dsc_list['follow_ups'] = $Language->getText('tracker_import','follow_ups_desc');
    $dsc_list['is_dependent_on'] = $Language->getText('tracker_import','depend_on_desc');
    $dsc_list['add_cc'] = $Language->getText('tracker_import','cc_list_desc');
    $dsc_list['cc_comment'] = $Language->getText('tracker_import','cc_comment_desc');
    
    $eol = "\n";
    
    $result=db_query($sql);
    $rows = db_numrows($result); 

    echo $Language->getText('tracker_import','format_desc');

    if ($rows > 0) { 
      $record = pick_a_record_at_random($result, $rows, $col_list);
      } else {
      $record = $ath->buildDefaultRecord();
      }
    prepare_artifact_record($at,$fields,$atid,$record);
    display_exported_fields($col_list,$lbl_list,$dsc_list,$record,$mand_list);
    
    echo '<br><br><h4>'.$Language->getText('tracker_import','sample_cvs_file').'</h4>';
    echo build_csv_header($col_list,$lbl_list);
    echo '<br>';
    echo build_csv_record($col_list,$record);
    

    //   screen accepting the CSV file to be parsed **************************************************************
  } else {
    
    $ath->header(array ('title'=>$Language->getText('tracker_import','art_import').' '.$ath->getID(). ' - ' . $ath->getName(),'pagename'=>'tracker',
			'atid'=>$ath->getID(),'sectionvals'=>array($group->getPublicName()),
			'help' => 'ArtifactImport.html'));

    echo '<h3>'.$Language->getText('tracker_import','import_new', array(help_button('ArtifactImport.html'),'/tracker/index.php?group_id='.$group_id.'&atid='.$atid.'&user_id='.$user_id.'&mode=showformat&func=import'));
    if ($user == 100) {
      print $Language->getText('tracker_import','not_logged');
    }
    
    echo '
	    <FORM NAME="importdata" action="'.$PHP_SELF.'" method="POST" enctype="multipart/form-data">
            <INPUT TYPE="hidden" name="group_id" value="'.$group_id.'">            
            <INPUT TYPE="hidden" name="atid" value="'.$atid.'">            
            <INPUT TYPE="hidden" name="func" value="import">
            <INPUT TYPE="hidden" name="mode" value="parse">

			<table border="0" width="75%">
			<tr>
			<th> ';//<input type="checkbox" name="file_upload" value="1"> 
    echo '<B>'.$Language->getText('tracker_import','upload_file').'</B></th>
			<td> <input type="file" name="csv_filename" size="50">
                 <br><span class="smaller"><i>'.$Language->getText('tracker_import','max_upload_size',formatByteToMb($sys_max_size_upload)).'</i></span>
			</td>
			</tr>';

    //<tr>
    //<th>OR Paste Artifact Data (in CSV format):</th>
    //<td><textarea cols="60" rows="10" name="data"></textarea></td>
    //</tr>
    echo '
                        </table>

			<input type="submit" value="'.$Language->getText('tracker_import','submit_info').'">

	    </FORM> '; 
    $ath->footer(array());
    
  } // end else.
  
} else {
  exit_no_group();
}

?>
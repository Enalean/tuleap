<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$

$LANG->loadLanguageMsg('project/project');

//
//	get the Group object
//
$group = group_get_object($group_id);
if (!$group || !is_object($group) || $group->isError()) {
	exit_no_group();
}

if ( $atid ) {
	//	Create the ArtifactType object
	//
	$at = new ArtifactType($group,$atid);
	if (!$at || !is_object($at)) {
		exit_error($LANG->getText('global','error'),$LANG->getText('project_export_artifact_deps_export','at_not_created'));
	}
	if ($at->isError()) {
		exit_error($LANG->getText('global','error'),$at->getErrorMessage());
	}
	// Check if this tracker is valid (not deleted)
	if ( !$at->isValid() ) {
		exit_error($LANG->getText('global','error'),$LANG->getText('project_export_artifact_deps_export','tracker_no_longer_valid'));
	}
	
	// Create field factory
	$art_field_fact = new ArtifactFieldFactory($at);
	if ($art_field_fact->isError()) {
		exit_error($LANG->getText('global','error'),$art_field_fact->getErrorMessage());
	}

}

// This is the SQL query to retrieve all the artifact history for this group
// Note: the text value of the comment_type_id is not fetched by the
// SQL statement because this field can be NULL and the NULL value is
// not a valid comment type in the bug_field_value table. So the join
// doesn't work.
$sql = "SELECT ah.artifact_id,ah.field_name,".
'ah.old_value, ah.new_value, user.user_name AS mod_by, user.email, ah.date, ah.type'.
' FROM artifact_history ah, user, artifact a '.
"WHERE ah.artifact_id = a.artifact_id AND a.group_artifact_id = ".$atid." AND ".
'user.user_id=ah.mod_by ORDER BY ah.artifact_id,ah.date DESC';

$col_list = array('artifact_id','field_name','old_value','new_value','mod_by','email','date','type');
$lbl_list = array('artifact_id' => $LANG->getText('project_export_artifact_history_export','art_id'),
		  'field_name' => $LANG->getText('project_export_artifact_history_export','field_name'),
		  'old_value' => $LANG->getText('project_export_artifact_history_export','old_val'),
		  'new_value' => $LANG->getText('project_export_artifact_history_export','new_val'),
		  'mod_by' => $LANG->getText('project_export_artifact_history_export','mod_by'),
		  'email' => $LANG->getText('project_export_artifact_history_export','email'),
		  'date' => $LANG->getText('project_export_artifact_history_export','mod_on'),
		  'type' => $LANG->getText('project_export_artifact_history_export','comment_type'));

$dsc_list = array('artifact_id' => $LANG->getText('project_export_artifact_history_export','art_id'),
		  'field_name' => $LANG->getText('project_export_artifact_history_export','field_name_desc'),
		  'old_value' => $LANG->getText('project_export_artifact_history_export','old_val_desc'),
		  'new_value' => $LANG->getText('project_export_artifact_history_export','new_val_desc'),
		  'mod_by' => $LANG->getText('project_export_artifact_history_export','mod_by_desc'),
		  'date' => $LANG->getText('project_export_artifact_history_export','mod_on_desc'),
		  'type' => $LANG->getText('project_export_artifact_history_export','comment_type_desc'));

$eol = "\n";

$result=db_query($sql);
$rows = db_numrows($result);       

if ($export == 'artifact_history') {

    // Send the result in CSV format
    if ($result && $rows > 0) {
	
	$tbl_name = str_replace(' ','_','artifact_history_'.$at->getName());
	header ('Content-Type: text/csv');
	header ('Content-Disposition: filename='.$tbl_name.'_'.$dbname.'.csv');	
	echo build_csv_header($col_list, $lbl_list).$eol;

	while ($arr = db_fetch_array($result)) {
	    prepare_artifact_history_record($at,$art_field_fact,$arr);
	    echo build_csv_record($col_list, $arr).$eol;
	}

    } else {

	project_admin_header(array('title'=>$pg_title));

	echo '<h3>'.$LANG->getText('project_export_artifact_history_export','art_hist_export').'</h3>';
	if ($result) {
	    echo '<P>'.$LANG->getText('project_export_artifact_history_export','no_hist_found');
	} else {
	    echo '<P>'.$LANG->getText('project_export_artifact_history_export','db_access_err',$GLOBALS['sys_name']);
	    echo '<br>'.db_error();
	}
	site_project_footer( array() );
    }


} else if ($export == "artifact_history_format") {

    echo $LANG->getText('project_export_artifact_history_export','hist_export_format');
   
    $record = pick_a_record_at_random($result, $rows, $col_list);
    prepare_artifact_history_record($at,$art_field_fact,$record);
    display_exported_fields($col_list,$lbl_list,$dsc_list,$record);


} else if ($export == "project_db") {

    // make sure the database name is not the same as the 
    // CodeX database name !!!!
    if ($dbname != $sys_dbname) {

		// Get the artfact type list
		$at_arr = $atf->getArtifactTypes();
		
		if ($at_arr && count($at_arr) >= 1) {
			for ($j = 0; $j < count($at_arr); $j++) {

				$tbl_name = "artifact_history_".$at_arr[$j]->getName();
				$tbl_name = str_replace(' ','_',$tbl_name);

				$atid = $at_arr[$j]->getID();
				
				//	Create the ArtifactType object
				//
				$at = new ArtifactType($group,$atid);
				if (!$at || !is_object($at)) {
					exit_error($LANG->getText('global','error'),$LANG->getText('project_export_artifact_deps_export','at_not_created'));
				}
				if ($at->isError()) {
					exit_error($LANG->getText('global','error'),$at->getErrorMessage());
				}
				// Check if this tracker is valid (not deleted)
				if ( !$at->isValid() ) {
					break;
				}
				
				// Create field factory
				$art_field_fact = new ArtifactFieldFactory($at);
				if ($art_field_fact->isError()) {
					exit_error($LANG->getText('global','error'),$art_field_fact->getErrorMessage());
				}

				// Let's create the project database if it does not exist
				// Drop the existing table and create a fresh one
				db_project_create($dbname);
				db_project_query($dbname,'DROP TABLE IF EXISTS '. $tbl_name);
		
				$sql_create = "CREATE TABLE $tbl_name (".
				    'artifact_id INTEGER, field_name VARCHAR(255), '.
				    'old_value TEXT, mod_by VARCHAR(255), email TEXT, date DATETIME, '.
				    'type VARCHAR(255))';
			
				$res = db_project_query($dbname, $sql_create);
			
				// extract data from the bug table and insert them into
				// the project database table
				if ($res) {
					$sql = "SELECT ah.artifact_id,ah.field_name,".
					'ah.old_value, user.user_name AS mod_by, ah.email, ah.date, ah.type'.
					' FROM artifact_history ah, user, artifact a '.
					"WHERE ah.artifact_id = a.artifact_id AND a.group_artifact_id = ".$atid." AND ".
					'user.user_id=ah.mod_by';
					$result=db_query($sql);
				    while ($arr = db_fetch_array($result)) {
						prepare_artifact_history_record($at,$art_field_fact,$arr);
						insert_record_in_table($dbname, $tbl_name, $col_list, $arr);
				    }
				} else {
				    $feedback .= $LANG->getText('project_export_artifact_deps_export','create_proj_err',array($tbl_name,db_project_error()));
				}
			} // for
	
		}

    } else {
		$feedback .= $LANG->getText('project_export_artifact_deps_export','security_violation',$dbname);
    }
   
}

?>

<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$

$Language->loadLanguageMsg('project/project');

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
		exit_error($Language->getText('global','error'),$Language->getText('project_export_artifact_deps_export','at_not_created'));
	}
	if ($at->isError()) {
		exit_error($Language->getText('global','error'),$at->getErrorMessage());
	}
	// Check if this tracker is valid (not deleted)
	if ( !$at->isValid() ) {
		exit_error($Language->getText('global','error'),$Language->getText('project_export_artifact_deps_export','tracker_no_longer_valid'));
	}
	
	// Create field factory
	$art_field_fact = new ArtifactFieldFactory($at);
	if ($art_field_fact->isError()) {
		exit_error($Language->getText('global','error'),$art_field_fact->getErrorMessage());
	}

}

// This is the SQL query to retrieve all the bugs which depends on another bug

$sql = 'SELECT ad.artifact_id,'.
'ad.is_dependent_on_artifact_id '.
'FROM artifact_dependencies ad, artifact a '.
'WHERE ad.artifact_id = a.artifact_id AND a.group_artifact_id = '.$atid.' AND '.
'ad.is_dependent_on_artifact_id <> 100';

$col_list = array('artifact_id','is_dependent_on_artifact_id');
$lbl_list = array('artifact_id' => $Language->getText('project_export_artifact_history_export','art_id'),
	     'is_dependent_on_artifact_id' => $Language->getText('project_export_artifact_deps_export','depend_on_art'));
$dsc_list = array('artifact_id' => $Language->getText('project_export_artifact_deps_export','art_id_desc'),
	     'is_dependent_on_artifact_id' => $Language->getText('project_export_artifact_deps_export','depend_on_art'));

$eol = "\n";

//echo "DBG -- $sql<br>";

$result=db_query($sql);
$rows = db_numrows($result);

if ($export == 'artifact_deps') {

    // Send the result in CSV format
    if ($result && $rows > 0) {
	
	        $tbl_name = str_replace(' ','_','artifact_deps_'.$at->getItemName());
		header ('Content-Type: text/csv');
		header ('Content-Disposition: filename='.$tbl_name.'_'.$dbname.'.csv');
		
		echo build_csv_header($col_list, $lbl_list).$eol;
	
		while ($arr = db_fetch_array($result)) {
		    echo build_csv_record($col_list, $arr).$eol;
		}

    } else {

		project_admin_header(array('title'=>$pg_title));
	
		echo '<h3>'.$Language->getText('project_export_artifact_deps_export','art_deps_export').'</h3>';
		if ($result) {
		    echo '<P>'.$Language->getText('project_export_artifact_deps_export','no_deps_found');
		} else {
		    echo '<P>'.$Language->getText('project_export_artifact_deps_export','db_access_err',$GLOBALS['sys_name']);
		    echo '<br>'.db_error();
		}
		site_project_footer( array() );
    }


} else if ($export == "artifact_deps_format") {

    echo $Language->getText('project_export_artifact_deps_export','deps_export_format');
 
    $record = pick_a_record_at_random($result, $rows, $col_list);

    display_exported_fields($col_list,$lbl_list,$dsc_list,$record);

} else if ($export == "project_db") {

    // make sure the database name is not the same as the 
    // CodeX database name !!!!
    if ($dbname != $sys_dbname) {

		// Get the artfact type list
		$at_arr = $atf->getArtifactTypes();
		
		if ($at_arr && count($at_arr) >= 1) {
			for ($j = 0; $j < count($at_arr); $j++) {

				$tbl_name = "artifact_deps_".$at_arr[$j]->getItemName();
				$tbl_name = str_replace(' ','_',$tbl_name);

				$atid = $at_arr[$j]->getID();
				
				//	Create the ArtifactType object
				//
				$at = new ArtifactType($group,$atid);
				if (!$at || !is_object($at)) {
					exit_error($Language->getText('global','error'),$Language->getText('project_export_artifact_deps_export','at_not_created'));
				}
				if ($at->isError()) {
					exit_error($Language->getText('global','error'),$at->getErrorMessage());
				}
				// Check if this tracker is valid (not deleted)
				if ( !$at->isValid() ) {
					break;
				}
				
				// Create field factory
				$art_field_fact = new ArtifactFieldFactory($at);
				if ($art_field_fact->isError()) {
					exit_error($Language->getText('global','error'),$art_field_fact->getErrorMessage());
				}

				// Let's create the project database if it does not exist
				// Drop the existing table and create a fresh one
				db_project_create($dbname);
				db_project_query($dbname,'DROP TABLE IF EXISTS '.$tbl_name);
				
				$sql_create = "CREATE TABLE $tbl_name (".
				    'artifact_id INTEGER, is_dependent_on_artifact_id INTEGER)';
			
				$res = db_project_query($dbname, $sql_create);
			
				// extract data from the bug table and insert them into
				// the project database table. Do it in one shot here.
				if ($res) {	    

					$sql = 'SELECT ad.artifact_id,'.
					'ad.is_dependent_on_artifact_id '.
					'FROM artifact_dependencies ad, artifact a '.
					'WHERE ad.artifact_id = a.artifact_id AND a.group_artifact_id = '.$atid.' AND '.
					'ad.is_dependent_on_artifact_id <> 100';
					$result=db_query($sql);
				    while ($arr = db_fetch_array($result)) {
						insert_record_in_table($dbname, $tbl_name, $col_list, $arr);
				    }
				} else {
				    $feedback .= $Language->getText('project_export_artifact_deps_export','create_proj_err',array($tbl_name,db_project_error()));
				}
			} // for
		}

    } else {
		$feedback .= $Language->getText('project_export_artifact_deps_export','security_violation',$dbname);
    }
   
}

?>

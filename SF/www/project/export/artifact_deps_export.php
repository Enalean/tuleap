<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$


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
		exit_error('Error','ArtifactType could not be created');
	}
	if ($at->isError()) {
		exit_error('Error',$at->getErrorMessage());
	}
	// Check if this tracker is valid (not deleted)
	if ( !$at->isValid() ) {
		exit_error('Error',"This tracker is no longer valid.");
	}
	
	// Create field factory
	$art_field_fact = new ArtifactFieldFactory($at);
	if ($art_field_fact->isError()) {
		exit_error('Error',$art_field_fact->getErrorMessage());
	}

	$tbl_name = 'artifact_'.$at->getName().'_history';
}

// This is the SQL query to retrieve all the bugs which depends on another bug

$sql = 'SELECT ad.artifact_id,'.
'ad.is_dependent_on_artifact_id '.
'FROM artifact_dependencies ad, artifact a '.
'WHERE ad.artifact_id = a.artifact_id AND a.group_artifact_id = '.$atid.' AND '.
'ad.is_dependent_on_artifact_id <> 100';

$col_list = array('artifact_id','is_dependent_on_artifact_id');
$lbl_list = array('artifact_id' => 'Artifact ID',
	     'is_dependent_on_artifact_id' => 'Depend on Artifact');
$dsc_list = array('artifact_id' => 'Unique artifact identifier',
	     'is_dependent_on_artifact_id' => 'Depend on Artifact');

$eol = "\n";

//echo "DBG -- $sql<br>";

$result=db_query($sql);
$rows = db_numrows($result);

if ($export == 'artifact_deps') {

    // Send the result in CSV format
    if ($result && $rows > 0) {
	
		header ('Content-Type: text/csv');
		header ('Content-Disposition: filename='.$tbl_name.'_'.$dbname.'.csv');
		
		echo build_csv_header($col_list, $lbl_list).$eol;
	
		while ($arr = db_fetch_array($result)) {
		    echo build_csv_record($col_list, $arr).$eol;
		}

    } else {

		project_admin_header(array('title'=>$pg_title));
	
		echo '<h3>Artifact Dependencies Export</h3>';
		if ($result) {
		    echo '<P>No artifact depencies  found. Could not generate an export.';
		} else {
		    echo '<P>Error while accessing your artifact dependencies database. Please report the error to the '.$GLOBALS['sys_name'].' Administrator';
		    echo '<br>'.db_error();
		}
		site_project_footer( array() );
    }


} else if ($export == "artifact_deps_format") {

    echo '<h3>Artifact Dependencies Export Format</h3> The Artifact
Dependencies export provides you with the following fields. The sample
values indicate what the field data types are. <p>';
 
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

				$tbl_name = "artifact_deps_".$at_arr[$j]->getName();
				$atid = $at_arr[$j]->getID();
				
				//	Create the ArtifactType object
				//
				$at = new ArtifactType($group,$atid);
				if (!$at || !is_object($at)) {
					exit_error('Error','ArtifactType could not be created');
				}
				if ($at->isError()) {
					exit_error('Error',$at->getErrorMessage());
				}
				// Check if this tracker is valid (not deleted)
				if ( !$at->isValid() ) {
					break;
				}
				
				// Create field factory
				$art_field_fact = new ArtifactFieldFactory($at);
				if ($art_field_fact->isError()) {
					exit_error('Error',$art_field_fact->getErrorMessage());
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
				    $feedback .= 'Error in Create project '.$tbl_name.' table:'.db_project_error();
				}
			} // for
		}

    } else {
		$feedback .= "SECURITY VIOLATION!!! Unauthorized database name: $dbname";
    }
   
}

?>

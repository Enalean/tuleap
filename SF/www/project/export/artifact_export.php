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

        //
        //      Create the ArtifactTypeHtml object - needed in ArtifactField.getFieldPredefinedValues() 
        //
        $ath = new ArtifactTypeHtml($group,$atid);
        if (!$ath || !is_object($ath)) {
            exit_error('Error','ArtifactTypeHtml could not be created');
        }
        if ($ath->isError()) {
            exit_error('Error',$ath->getErrorMessage());
        }

	// Create field factory
	$art_field_fact = new ArtifactFieldFactory($at);
	if ($art_field_fact->isError()) {
		exit_error('Error',$art_field_fact->getErrorMessage());
	}
	
	$sql = $at->buildExportQuery($fields,$col_list,$lbl_list,$dsc_list);
}


// Add the 2 fields that we build ourselves for user convenience
// - All follow-up comments
// - Dependencies

$col_list[] = 'follow_ups';
$col_list[] = 'is_dependent_on';

$lbl_list['follow_ups'] = 'Follow-up Comments';
$lbl_list['is_dependent_on'] = 'Depend on';

$dsc_list['follow_ups'] = 'All follow-up comments in one chunck of text';
$dsc_list['is_dependent_on'] = 'List of artifacts this artifact depends on';

$eol = "\n";
    
//echo "DBG -- $sql<br>";

$result=db_query($sql);
$rows = db_numrows($result);    

if ($export == 'artifact') {

    // Send the result in CSV format
    if ($result && $rows > 0) {
	
	        $tbl_name = str_replace(' ','_','artifact_'.$at->getName());
		header ('Content-Type: text/csv');
		header ('Content-Disposition: filename='.$tbl_name.'_'.$dbname.'.csv');
	
		echo build_csv_header($col_list, $lbl_list).$eol;
		
		while ($arr = db_fetch_array($result)) {	    
		    prepare_artifact_record($at,$fields,$atid,$arr);
		    echo build_csv_record($col_list, $arr).$eol;
		}
	
    } else {

		project_admin_header(array('title'=>$pg_title));
	
		echo '<h3>Artifact export</h3>';
		if ($result) {
		    echo '<P>No artifact found. Could not generate an export.';
		} else {
		    echo '<P>Error while accessing your artifact database. Please report the error to the '.$GLOBALS['sys_name'].' Administrator';
		    echo '<br>'.db_error();
		}
		site_project_footer( array() );
    }


} else if ($export == "artifact_format") {

    echo '<h3>Artifact Export Format</h3>
The artifact export provides you with the following artifact fields. The sample values indicate what the field data types are.<p>';

    $record = pick_a_record_at_random($result, $rows, $col_list);
    prepare_artifact_record($at,$fields,$atid,$record);
    display_exported_fields($col_list,$lbl_list,$dsc_list,$record);


} else if ($export == "project_db") {


    // make sure the database name is not the same as the 
    // CodeX database name !!!!
    if ($dbname != $sys_dbname) {

		// Get the artfact type list
		$at_arr = $atf->getArtifactTypes();
		
		if ($at_arr && count($at_arr) >= 1) {
			for ($j = 0; $j < count($at_arr); $j++) {

				$tbl_name = "artifact_".$at_arr[$j]->getName();
				$tbl_name = str_replace(' ','_',$tbl_name);
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
				
                                //
                                //      Create the ArtifactTypeHtml object - needed in ArtifactField.getFieldPredefinedValues() 
                                //
                                $ath = new ArtifactTypeHtml($group,$atid);
                                if (!$ath || !is_object($ath)) {
                                    exit_error('Error','ArtifactTypeHtml could not be created');
                                }
                                if ($ath->isError()) {
                                    exit_error('Error',$ath->getErrorMessage());
                                }


				// Create field factory
				$art_field_fact = new ArtifactFieldFactory($at);
				if ($art_field_fact->isError()) {
					exit_error('Error',$art_field_fact->getErrorMessage());
				}
				
				$col_list = array();
				$sql = $at->buildExportQuery($fields,$col_list,$lbl_list,$dsc_list);
				$col_list[] = 'follow_ups';
				$col_list[] = 'is_dependent_on';

				// Let's create the project database if it does not exist
				// Drop the existing table and create a fresh one
				db_project_create($dbname);
				db_project_query($dbname,'DROP TABLE IF EXISTS '.$tbl_name);
				
				$sql_create = "";
				reset($col_list);
				while (list(,$col) = each($col_list)) {
					$field = $art_field_fact->getFieldFromName($col);
					if ( !$field ) 
						break;
						
					if ( $field->isSelectBox() || $field->isMultiSelectBox() ) {
						$type = "TEXT";
					} else if ( $field->isTextArea() || ($field->isTextField() && $field->getDataType() == $field->DATATYPE_TEXT) ) {
						$type = "TEXT";
					} else if ( $field->isDateField() ) {
						$type = "DATETIME";
					} else if ( $field->isFloat() ) {
						$type = "FLOAT(10,2)";
					} else {
						$type = "INTEGER";
					}

				    $sql_create .= $field->getName().' '.$type.',';
								
				} // end while
				
				// Add  depend_on and follow ups
			    $sql_create .= " follow_ups TEXT, is_dependent_on TEXT";
				
				$sql_create = 'CREATE TABLE '.$tbl_name.' ('.$sql_create.')';
				$res = db_project_query($dbname, $sql_create);
			
				// extract data from the bug table and insert them into
				// the project database table
				if ($res) {
				    
					$result=db_query($sql);
				    while ($arr = db_fetch_array($result)) {
						prepare_artifact_record($at,$fields,$atid,$arr);
						insert_record_in_table($dbname, $tbl_name, $col_list, $arr);
				    }
			
				} else {
				    $feedback .= 'Error in Create project '.$tbl_name.' table:'.db_project_error();
				}

			} // for
		} // if 

    } else {
		$feedback .= "SECURITY VIOLATION!!! Unauthorized database name: $dbname";
    }

   
}

?>

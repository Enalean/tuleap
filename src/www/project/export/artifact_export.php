<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// 

require_once('common/tracker/ArtifactFieldSetFactory.class.php');

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


        //
        //      Create the ArtifactTypeHtml object - needed in ArtifactField.getFieldPredefinedValues() 
        //
        $ath = new ArtifactTypeHtml($group,$atid);
        if (!$ath || !is_object($ath)) {
            exit_error($Language->getText('global','error'),$Language->getText('project_export_artifact_export','ath_not_created'));
        }
        if ($ath->isError()) {
            exit_error($Language->getText('global','error'),$ath->getErrorMessage());
        }

	// Create field factory
	$art_field_fact = new ArtifactFieldFactory($at);
	if ($art_field_fact->isError()) {
		exit_error($Language->getText('global','error'),$art_field_fact->getErrorMessage());
	}
	$art_fieldset_fact = new ArtifactFieldSetFactory($at);
	if ($art_fieldset_fact->isError()) {
		exit_error($Language->getText('global','error'),$art_fieldset_fact->getErrorMessage());
	}
	
	$sql = $at->buildExportQuery($fields,$col_list,$lbl_list,$dsc_list,$select,$from,$where,$multiple_queries,$all_queries);

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
            $lbl_list['is_dependent_on'] = "Depend de";
            
            $dsc_list['follow_ups'] = "Tout le fil de commentaires en un seul bloc de texte";
            $dsc_list['is_dependent_on'] = "Liste des artefacts dont celui-ci depend";
        }

}


// Add the 2 fields that we build ourselves for user convenience
// - All follow-up comments
// - Dependencies

$col_list[] = 'follow_ups';
$col_list[] = 'is_dependent_on';


$eol = "\n";
    
//echo "DBG -- $sql<br>";

if (isset($multiple_queries) && $multiple_queries) {
  $all_results = array();
  foreach($all_queries as $q) {
    $result = db_query($q);
    $all_results[] = $result;
    $rows = db_numrows($result);    
  }
} else {
  $result=db_query($sql);
  $rows = db_numrows($result);    
}

if ($export == 'artifact') {

    // Send the result in CSV format
    if ($result && $rows > 0) {
	
	        $tbl_name = str_replace(' ','_','artifact_'.$at->getItemName());
		header ('Content-Type: text/csv');
		header ('Content-Disposition: filename='.$tbl_name.'_'.$dbname.'.csv');
	
		echo build_csv_header($col_list, $lbl_list).$eol;
		
		if ($multiple_queries) {
		  $multiarr = array();
		  for ($i = 0; $i < $rows; $i++) {
		    foreach ($all_results as $result) {
		      $multiarr = array_merge($multiarr,db_fetch_array($result));
		    }
		    
		    prepare_artifact_record($ath,$fields,$atid,$multiarr);
		    echo build_csv_record($col_list, $multiarr).$eol;
		  }
		} else {
		  while ($arr = db_fetch_array($result)) {	    
		    prepare_artifact_record($at,$fields,$atid,$arr);
		    echo build_csv_record($col_list, $arr).$eol;
		  }
		}
	
    } else {

		project_admin_header(array('title'=>$pg_title));
	
		echo '<h3>'.$Language->getText('project_export_artifact_export','art_export').'</h3>';
		if ($result) {
		    echo '<P>'.$Language->getText('project_export_artifact_export','no_art_found');
		} else {
		    echo '<P>'.$Language->getText('project_export_artifact_export','db_access_err',$GLOBALS['sys_name']);
		    echo '<br>'.db_error();
		}
		site_project_footer( array() );
    }


} else if ($export == "artifact_format") {

    echo '<h3>'.$Language->getText('project_export_artifact_export','art_exp_format').'</h3>';

    echo '<p>'.$Language->getText('project_export_artifact_export','art_exp_format_msg').'</p>';

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

				$tbl_name = "artifact_".$at_arr[$j]->getItemName();
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
				
                                //
                                //      Create the ArtifactTypeHtml object - needed in ArtifactField.getFieldPredefinedValues() 
                                //
                                $ath = new ArtifactTypeHtml($group,$atid);
                                if (!$ath || !is_object($ath)) {
                                    exit_error($Language->getText('global','error'),$Language->getText('project_export_artifact_export','ath_not_created'));
                                }
                                if ($ath->isError()) {
                                    exit_error($Language->getText('global','error'),$ath->getErrorMessage());
                                }


				// Create field factory
				$art_field_fact = new ArtifactFieldFactory($at);
				if ($art_field_fact->isError()) {
					exit_error($Language->getText('global','error'),$art_field_fact->getErrorMessage());
				}
				$art_fieldset_fact = new ArtifactFieldSetFactory($at);
				if ($art_fieldset_fact->isError()) {
					exit_error($Language->getText('global','error'),$art_fieldset_fact->getErrorMessage());
				}

				$col_list = array();
				$sql = $at->buildExportQuery($fields,$col_list,$lbl_list,$dsc_list,$select,$from,$where,$multiple_queries,$all_queries);
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
				    $feedback .= $Language->getText('project_export_artifact_deps_export','create_proj_err',array($tbl_name,db_project_error()));
				}

				// MV add
				// Export table structure
				
				// Create table
				$tbl_struct_name = "artifact_struct_".$at_arr[$j]->getItemName();
				$tbl_struct_name = str_replace(' ','_', $tbl_struct_name);
				$fieldsList = $art_field_fact->getAllUsedFields();
				db_project_query($dbname,'DROP TABLE IF EXISTS '.$tbl_struct_name);
				$struct_table_create = 'CREATE TABLE '.$tbl_struct_name.'('
				  .' field_name VARCHAR(255), '
				  .' field_label VARCHAR(255)'
				  .')';
				db_project_query($dbname, $struct_table_create);
				// Populate table
				$struct_col_list = array('field_name', 'field_label');
				foreach($fieldsList as $art_field) {
				  $struct_arr['field_name']  = $art_field->getName();
				  $struct_arr['field_label'] = $art_field->getLabel();
				  insert_record_in_table($dbname, $tbl_struct_name, $struct_col_list, $struct_arr);                    
				}
			} // for
		} // if 

    } else {
		$feedback .= $Language->getText('project_export_artifact_deps_export','security_violation',$dbname);
    }

   
}

?>

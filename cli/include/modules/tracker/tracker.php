<?php
/**
 * CodeX Command-line Interface
 *
 * Portion of this file is inspired from the  GForge Command-line Interface
 * contained in GForge.
 * Copyright 2005 GForge, LLC
 * http://gforge.org/
 *
 */
require_once(CODEX_CLI_DIR.'/CLI_Module.class.php');

require_once('CLI_Action_Tracker_Trackerlist.class.php');
require_once('CLI_Action_Tracker_Trackers.class.php');
require_once('CLI_Action_Tracker_Tracker.class.php');
require_once('CLI_Action_Tracker_List.class.php');
require_once('CLI_Action_Tracker_Add.class.php');
require_once('CLI_Action_Tracker_Update.class.php');
require_once('CLI_Action_Tracker_Comments.class.php');
require_once('CLI_Action_Tracker_AddComment.class.php');
require_once('CLI_Action_Tracker_CCList.class.php');
require_once('CLI_Action_Tracker_AddCC.class.php');
require_once('CLI_Action_Tracker_DeleteCC.class.php');
require_once('CLI_Action_Tracker_Reports.class.php');
require_once('CLI_Action_Tracker_Dependencies.class.php');
require_once('CLI_Action_Tracker_InverseDependencies.class.php');
require_once('CLI_Action_Tracker_AddDependencies.class.php');
require_once('CLI_Action_Tracker_DeleteDependency.class.php');
require_once('CLI_Action_Tracker_ArtifactHistory.class.php');

class CLI_Module_Tracker extends CLI_Module {
    // These fields are the standard fields
    // for adding and updating an artifact, we will parse the arguments command line,
    // and all the arguments not present in this array will be considered as "extra_fields"
    var $standard_artifact_fields;
    
    function CLI_Module_Tracker() {
        $this->CLI_Module("tracker", "Manage trackers");
        
        $this->addAction(new CLI_Action_Tracker_Trackerlist());
        $this->addAction(new CLI_Action_Tracker_Trackers());
        $this->addAction(new CLI_Action_Tracker_Tracker());
        $this->addAction(new CLI_Action_Tracker_List());
        $this->addAction(new CLI_Action_Tracker_Add());
        $this->addAction(new CLI_Action_Tracker_Update());
        $this->addAction(new CLI_Action_Tracker_Comments());
        $this->addAction(new CLI_Action_Tracker_AddComment());
        $this->addAction(new CLI_Action_Tracker_CCList());
        $this->addAction(new CLI_Action_Tracker_AddCC());
        $this->addAction(new CLI_Action_Tracker_DeleteCC());
        $this->addAction(new CLI_Action_Tracker_Reports());
        $this->addAction(new CLI_Action_Tracker_Dependencies());
        $this->addAction(new CLI_Action_Tracker_InverseDependencies());
        $this->addAction(new CLI_Action_Tracker_AddDependencies());
        $this->addAction(new CLI_Action_Tracker_DeleteDependency());
        $this->addAction(new CLI_Action_Tracker_ArtifactHistory());
        
        /* TODO: other actions =>
        files
        getfile
        addfile
        technicians
        */
        
        $this->standard_artifact_fields = array(
            'artifact_id',
            'status_id',
            'submitted_by',
            'open_date',
            'close_date',
            'summary',
            'details',
            'severity'
        );
    }
    /**
     * Get the parameters for an artifact from the command line. This function is used when
     * adding/updating an artifact.
     *
     * extracted from GForge Command-line Interface
     * contained in GForge.
     * Copyright 2005 GForge, LLC
     * http://gforge.org/
     *
     * As there are standard and custom fields, we assume that every parameter other than
     * 'tracker', 'group_id', 'project' is a field name.
     * A param which doesn't correspond with a field would be simply ignored.
     * 
     * @param array $PARAMS the command line parameters to parse and analyse
     * @return array the array of params from the command line.
     */
    function getArtifactParams($params) {
    
        // Except the project name, the noask option and the artifact id,
        // the others parameters are fields
        $extra_fields = array();
        foreach($params as $idx => $key_equals_value) {
            $passed_parameter = '';
            $passed_value = '';
            if (preg_match("/^\\-\\-(.+)=(.+)/s",$key_equals_value,$res)) {	// something like "--username=john"
                $passed_parameter = $res[1];
                $passed_value = $res[2];
            }
            if ($passed_parameter) {
                if (in_array($passed_parameter, $this->standard_artifact_fields)) {
                    // this field is a standard field
                    $cmd_params[$passed_parameter] = $passed_value;
                } else {
                    if (!in_array($passed_parameter, array('tracker_id', 'group_id', 'project', 'id'))) {
                        // this field is not a standard field, so we consider it as an extra_filed
                        $extra_field = array();
                        $extra_field["field_name"] = $passed_parameter;
                        $extra_field["field_value"] = $passed_value;
                        $extra_fields[] = $extra_field;
                    }
                }
            }
        }
        // We add the extra_fields
        $cmd_params['extra_fields'] = $extra_fields;
        return $cmd_params;
    }
    
    /**
     * Get the parameters for an artifact from the command line. This function is used when
     * adding/updating an artifact.
     *
     * extracted from GForge Command-line Interface
     * contained in GForge.
     * Copyright 2005 GForge, LLC
     * http://gforge.org/
     *
     * As there are standard and custom fields, we assume that every parameter other than
     * 'tracker', 'group_id', 'project' is a field name.
     * A param which doesn't correspond with a field would be simply ignored.
     * 
     * @param array $PARAMS the command line parameters to parse and analyse
     * @return array the array of params from the command line.
     */
    function getArtifactCriteria($params) {
        // Except the project name and the artifact id,
        // the others parameters are fields
        $criterias = array();
        foreach($params as $idx => $key_operator_value) {
            $passed_parameter = '';
            $passed_operator = '';
            $passed_value = '';
            if (preg_match("/^\\-\\-(.+?)(<=|<>|>=|=|<|>)(.+)/s",$key_operator_value,$res)) {	// something like "--username>john"
                $passed_parameter = $res[1];
                $passed_operator = $res[2];
                $passed_value = $res[3];
                if ($passed_parameter != 'tracker_id' && 
                    $passed_parameter != 'group_id' && 
                    $passed_parameter != 'project' && 
                    $passed_parameter != 'limit' &&
                    $passed_parameter != 'offset') {
                    $criteria['field_name'] = $passed_parameter;
                    $criteria['operator'] = $passed_operator;
                    $criteria['field_value'] = $passed_value;
                    $criterias[] = $criteria;
                }
            }
        }
        return $criterias;
    }
}

/*
function tracker_do_files() {
    global $PARAMS, $SOAP, $LOG;
    
    $group_artifact_id = get_parameter($PARAMS, "type", true);
    if (!$group_artifact_id || !is_numeric($group_artifact_id)) {
        exit_error("You must specify the type ID as a valid number");
    }
    
    $artifact_id = get_parameter($PARAMS, "id", true);
    if (!$artifact_id || !is_numeric($artifact_id)) {
        exit_error("You must specify the artifact ID as a valid number");
    }
    
    $group_id = get_working_group($PARAMS);
    
    $cmd_params = array(
        "group_id"            => $group_id,
        "group_artifact_id"    => $group_artifact_id,
        "artifact_id"        => $artifact_id,
    );

    $res = $SOAP->call("getArtifactFiles", $cmd_params);
    if (($error = $SOAP->getError())) {
        $LOG->add($SOAP->responseData);
        exit_error($error, $SOAP->faultcode);
    }
    
    show_output($res);
}

function tracker_do_getfile() {
    global $PARAMS, $SOAP, $LOG;
    
    $group_artifact_id = get_parameter($PARAMS, "type", true);
    if (!$group_artifact_id || !is_numeric($group_artifact_id)) {
        exit_error("You must specify the type ID as a valid number");
    }
    
    $artifact_id = get_parameter($PARAMS, "id", true);
    if (!$artifact_id || !is_numeric($artifact_id)) {
        exit_error("You must specify the artifact ID as a valid number");
    }
    
    $file_id = get_parameter($PARAMS, "file_id", true);
    if (!$file_id || !is_numeric($file_id)) {
        exit_error("You must specify the file ID as a valid number");
    }
    
    // Should we save the contents to a file?
    $output = get_parameter($PARAMS, "output", true); 
    if ($output) {
        if (file_exists($output)) {
            $sure = get_user_input("File $output already exists. Do you want to overwrite it? (y/n): ");
            if (strtolower($sure) != "y" && strtolower($sure) != "yes") {
                exit_error("Retrieval of file aborted");
            }
        }
    }

    $group_id = get_working_group($PARAMS);
    
    $cmd_params = array(
        "group_id"            => $group_id,
        "group_artifact_id"    => $group_artifact_id,
        "artifact_id"        => $artifact_id,
        "file_id"            => $file_id
    );
    
    $res = $SOAP->call("getArtifactFileData", $cmd_params);
    if (($error = $SOAP->getError())) {
        $LOG->add($SOAP->responseData);
        exit_error($error, $SOAP->faultcode);
    }
    
    $file = base64_decode($res);
    if ($output) {
        while (!($fh = @fopen($output, "wb"))) {
            echo "Couldn't open file ".$output." for writing.\n";
            $output = "";
            while (!$output) {
                $output = get_user_input("Please specify a new file name: ");
            }
        }
        
        fwrite($fh, $file, strlen($file));
        fclose($fh);
        
        echo "File retrieved successfully.\n";
    } else {
        echo $file;        // if not saving to a file, output to screen
    }
}

function tracker_do_addfile() {
    global $PARAMS, $SOAP, $LOG;

    $group_artifact_id = get_parameter($PARAMS, "type", true);
    if (!$group_artifact_id || !is_numeric($group_artifact_id)) {
        exit_error("You must specify the type ID as a valid number");
    }
    
    $artifact_id = get_parameter($PARAMS, "id", true);
    if (!$artifact_id || !is_numeric($artifact_id)) {
        exit_error("You must specify the artifact ID as a valid number");
    }
    
    $description = get_parameter($PARAMS, "description", true);
    if (is_null($description)) $description = "";        // description wasn't specified

    $group_id = get_working_group($PARAMS);
    
    if (!($file = get_parameter($PARAMS, "file", true))) {
        exit_error("You must specify a file for uploading");
    }    
    
    while (!($fh = fopen($file, "rb"))) {
        echo "Couldn't open file ".$file." for reading.\n";
        $file = "";
        while (!$file) {
            $file = get_user_input("Please specify a new file name: ");
        }
    }
    
    $bin_contents = fread($fh, filesize($file));
    $base64_contents = base64_encode($bin_contents);
    $filename = basename($file);
    
    //TODO: Check file type
    $filetype = "";

    $cmd_params = array(
                    "group_id"            => $group_id,
                    "group_artifact_id"    => $group_artifact_id,
                    "artifact_id"        => $artifact_id,
                    "base64_contents"    => $base64_contents,
                    "description"        => $description,
                    "filename"            => $filename,
                    "filetype"            => $filetype 
                );
    
    $res = $SOAP->call("addArtifactFile", $cmd_params);
    if (($error = $SOAP->getError())) {
        $LOG->add($SOAP->responseData);
        exit_error($error, $SOAP->faultcode);
    }
    
    show_output($res);

}

function tracker_do_technicians() {
    global $PARAMS, $SOAP, $LOG;

    $group_artifact_id = get_parameter($PARAMS, "type", true);
    if (!$group_artifact_id || !is_numeric($group_artifact_id)) {
        exit_error("You must specify the type ID as a valid number");
    }
    
    $group_id = get_working_group($PARAMS);
    
    $cmd_params = array(
                    "group_id"            => $group_id,
                    "group_artifact_id"    => $group_artifact_id
                );
    
    $res = $SOAP->call("getArtifactTechnicians", $cmd_params);
    if (($error = $SOAP->getError())) {
        $LOG->add($SOAP->responseData);
        exit_error($error, $SOAP->faultcode);
    }
    
    show_output($res);

}
*/


?>
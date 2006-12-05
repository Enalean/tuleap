<?php
/**
 * CodeX Command-line Interface
 *
 * Portion of this file is inspired from the  GForge Command-line Interface
 * contained in GForge.
 * Copyright 2005 GForge, LLC
 * http://gforge.org/
 *
 * @version   $Id$
 */

/**
* Variables passed by parent script:
* - $SOAP: Soap object to talk to the server
* - $PARAMS: parameters passed to this script
* - $LOG: object for logging of events
*/

// These fields are the standard fields
// for adding and updating an artifact, we will parse the arguments command line,
// and all the arguments not present in this array will be considered as "extra_fields"
$STANDARD_ARTIFACT_FIELDS = array('artifact_id',
                                  'status_id',
                                  'submitted_by',
                                  'open_date',
                                  'close_date',
                                  'summary',
                                  'details',
                                  'severity');

// Here are the operators accepted in the list function, to filter the artifact with criteria.
$ACCEPTED_CRITERIA_OPERATORS = array('=', '<', '>', '<>', '<=', '>=');

// function to execute
// $PARAMS[0] is "tracker" (the name of this module) and $PARAMS[1] is the name of the function
$module_name = array_shift($PARAMS);        // Pop off module name
$function_name = array_shift($PARAMS);        // Pop off function name

switch ($function_name) {
case "trackerlist":
    tracker_do_trackerlist();
    break;
case "list":
    tracker_do_artifactlist();
    break;
case "add":
    tracker_do_add();
    break;
case "update":
    tracker_do_update();
    break;
case "comments":
    tracker_do_comments();
    break;
case "addcomment":
    tracker_do_addcomment();
    break;
/*case "files":
    tracker_do_files();
    break;
case "getfile":
    tracker_do_getfile();
    break;
case "addfile":
    tracker_do_addfile();
    break;
case "technicians":
    tracker_do_technicians();
    break;*/
default:
    exit_error("Unknown function name: ".$function_name);
    break;
}

///////////////////////////////
/**
 * tracker_do_trackerlist - List of tracker
 */
function tracker_do_trackerlist() {
    global $PARAMS, $SOAP, $LOG;

    if (get_parameter($PARAMS, "help")) {
        echo <<<EOF
Returns the list of trackers that belongs to a project.
Parameters:
--project=<name>: Name of the project the returned trackers belong to. If you specified the name of
    the working project when you logged in, this parameter is not needed.
EOF;
        return;
    }

    $group_id = get_working_group($PARAMS);
    $user_id = $SOAP->getSessionUserID();

    $res = $SOAP->call("getArtifactTypes", array("group_id" => $group_id));
    if (($error = $SOAP->getError())) {
        $LOG->add($SOAP->responseData);
        exit_error($error, $SOAP->faultcode);
    }

    show_output($res);
}

/**
 * tracker_do_list - List of artifacts
 */
function tracker_do_artifactlist() {
    global $PARAMS, $SOAP, $LOG;

    if (get_parameter($PARAMS, "help")) {
        echo <<<EOF
Returns a list of artifacts that belongs to a specific tracker.
Parameters:
--project=<name>: Name of the project the returned trackers belong to. If you specified the name of
    the working project when you logged in, this parameter is not needed.
--tracker_id=<tracker_id>: The ID of the tracker the returned artifacts belong to.
--[field_name][operator][value]: a criteria to filter the returned artifacts (e.g: "--open_date>2006-05-21")
--limit=<limit>: The maximum number of returned artifacts.
--offset=<offset>: Number of artifacts that will be skipped (comes with the limit parameter).
EOF;
        return;
    }

    $cmd_params = array();

    $group_artifact_id = get_parameter($PARAMS, "tracker_id", true);
    if (!$group_artifact_id) {
        exit_error("You must specify a tracker ID using the --tracker_id parameter");
    }

    $cmd_params["group_artifact_id"] = $group_artifact_id;

    if ($max_rows = get_parameter($PARAMS, "limit", true)) {
        $cmd_params["max_rows"] = intval($max_rows);
    } else {
        $cmd_params["max_rows"] = 0;
    }
    
    if ($offset = get_parameter($PARAMS, "offset", true)) {
        $cmd_params["offset"] = intval($offset);
    } else {
        $cmd_params["offset"] = 0;
    }
    
    // Manage generic criteria filter on artifact fields.
    $cmd_params["criteria"] = array();    
    $cmd_params["criteria"] = get_artifact_criteria();

    $group_id = get_working_group($PARAMS);    
    $cmd_params["group_id"] = $group_id;
    
    $user_id = $SOAP->getSessionUserID();
    $cmd_params["user_id"] = $user_id;

    $res = $SOAP->call("getArtifacts", $cmd_params);

    if (($error = $SOAP->getError())) {
        $LOG->add($SOAP->responseData);
        exit_error($error, $SOAP->faultcode);
    }
    
    if (!is_array($res) || count($res) == 0) {
        echo "No artifacts were found for this tracker.";
    } else {
        show_output($res);
    }
}

/**
 * tracker_do_add - Add a new tracker
 */
function tracker_do_add() {
    global $PARAMS, $SOAP, $LOG, $STANDARD_ARTIFACT_FIELDS;
    
    if (get_parameter($PARAMS, "help")) {
        echo <<<EOF
Add a new artifact in a tracker.
Parameters:
--project=<name>: Name of the project in which this artifact will be added. If you specified the name of
    the working project when you logged in, this parameter is not needed.
--tracker_id=<tracker_id>: Specify the ID of the tracker the artifact will be added in. The function "typelist" shows a list
    of available trackers and their corresponding IDs.
--<field_name>=<value>: Specify the couple field name/field value the artifact will be composed of. Works as well with standard field 
    than with custom fields. The function "typelist" shows a list of available trackers with its structure (field name and types)
EOF;
        return;
    }
    
    // get the group id giving the project name
    // the project name is optionnal. If it is not filled, the client will search the used project in the sessions values.
    $group_id = get_working_group($PARAMS);
    
    // get the tracker ID
    $group_artifact_id = get_parameter($PARAMS, "tracker_id", true);
    if (!$group_artifact_id) {
        exit_error("You must specify a tracker ID using the --tracker_id parameter");
    }
    
    // We get the other params (supposed to be fields)
    $cmd_params = get_artifact_params();
    
    // Wa add the group_id and tracker ID
    $cmd_params['group_id'] = $group_id;
    $cmd_params['group_artifact_id'] = $group_artifact_id;
    
    // Ask for confirmation
    echo "Confirm you want to add a new tracker with the following information:\n";
    // print the standard fields with their values
    foreach($cmd_params as $field_name => $field_value) {
        if ($field_name != 'extra_fields') {
            echo $field_name.": ".$field_value."\n";
        }
    }
    // print the extra fields with their values in the console
    foreach($cmd_params['extra_fields'] as $extrafield) {
        echo $extrafield['field_name'].": ".$extrafield['field_value']."\n";
    }
    
    // ask for confirmation if the --noask param is not set
    if (!get_parameter($PARAMS, array("n", "noask"))) {
        $input = get_user_input("Is this information correct? (y/n): ");
        $input = strtolower($input);
    } else {
        $input = "y";        // commit changes directly
    }

    if ($input == "yes" || $input == "y") {
        // Everything is OK... add the artifact
        $res = $SOAP->call("addArtifactWithFieldNames", $cmd_params);
        if (($error = $SOAP->getError())) {
            $LOG->add($SOAP->responseData);
            exit_error($error, $SOAP->faultcode);
        }
        show_output($res);
    } else {
        exit_error("Submission aborted");
    }
}

/**
 * tracker_do_update - Update a tracker
 */
function tracker_do_update() {
    global $PARAMS, $SOAP, $LOG, $STANDARD_ARTIFACT_FIELDS;
    
    if (get_parameter($PARAMS, "help")) {
        echo <<<EOF
Update an artifact in a tracker.
Parameters:
--project=<name>: Name of the project in which this artifact will be updated. If you specified the name of
    the working project when you logged in, this parameter is not needed.
--tracker_id*=<tracker_id>: Specify the ID of the tracker the artifact will be updated in. The function "typelist" shows a list
    of available trackers and their corresponding IDs.
--id*=<artifact_id>: ID of the artifact that will be updated. The function "list" shows a list of available artifacts.
--<field_name>=<value>: Specify the couple field name/field value for the fields that will be updated. Works as well with standard field 
    than with custom fields. The function "typelist" shows a list of available trackers with its structure (field name and types)
EOF;
        return;
    }
    
    // get the group id giving the project name
    // the project name is optionnal. If it is not filled, the client will search the used project in the sessions values.
    $group_id = get_working_group($PARAMS);
    
    // get the tracker ID
    $group_artifact_id = get_parameter($PARAMS, "tracker_id", true);
    if (!$group_artifact_id) {
        exit_error("You must specify a tracker ID using the --tracker_id parameter");
    }
    
    // get the artifact id
    if (!($artifact_id = get_parameter($PARAMS, "id", true))) {
        $artifact_id = get_user_input("ID of the artifact: ");
    }
    
    // We get the other params (supposed to be fields)
    $cmd_params = get_artifact_params();
    
    // Wa add the group_id, tracker name and artifact_id
    $cmd_params['group_id'] = $group_id;
    $cmd_params['group_artifact_id'] = $group_artifact_id;
    $cmd_params['artifact_id'] = $artifact_id;
    
    echo "Confirm you want to add a new tracker with the following information:\n";
    // print the standard fields with their values
    foreach($cmd_params as $field_name => $field_value) {
        if ($field_name != 'extra_fields') {
            echo $field_name.": ".$field_value."\n";
        }
    }
    // print the standard fields with their values
    foreach($cmd_params['extra_fields'] as $extrafield) {
        echo $extrafield['field_name'].": ".$extrafield['field_value']."\n";
    }

    // ask for confirmation if the --noask param is not set
    if (!get_parameter($PARAMS, array("n", "noask"))) {
        $input = get_user_input("Is this information correct? (y/n): ");
        $input = strtolower($input);
    } else {
        $input = "y";        // commit changes directly
    }

    if ($input == "yes" || $input == "y") {
        $res = $SOAP->call("updateArtifactWithFieldNames", $cmd_params);
        if (($error = $SOAP->getError())) {
            $LOG->add($SOAP->responseData);
            exit_error($error, $SOAP->faultcode);
        }
        show_output($res);
    } else {
        exit_error("Submission aborted");
    }
}


function tracker_do_comments() {
    global $PARAMS, $SOAP, $LOG;
    
    if (get_parameter($PARAMS, "help")) {
        echo <<<EOF
Returns the list of follow-up comments associated with a specific artifact.
Parameters:
--project=<name>: Name of the project in which this artifact belong to. If you specified the name of
    the working project when you logged in, this parameter is not needed.
--tracker_id*=<tracker_id>: Specify the ID of the tracker the artifact belong to
--id*=<artifact_id>: ID of the artifact.
EOF;
        return;
    }
    
    $group_artifact_id = get_parameter($PARAMS, "tracker_id", true);
    if (!$group_artifact_id || !is_numeric($group_artifact_id)) {
        exit_error("You must specify the tracker ID as a valid number");
    }
    
    $artifact_id = get_parameter($PARAMS, "id", true);
    if (!$artifact_id || !is_numeric($artifact_id)) {
        exit_error("You must specify the artifact ID as a valid number");
    }
    
    $group_id = get_working_group($PARAMS);
    
    $cmd_params = array(
        "group_id"            => $group_id,
        "group_artifact_id"    => $group_artifact_id,
        "artifact_id"        => $artifact_id
    );
    $res = $SOAP->call("getArtifactFollowups", $cmd_params);
    if (($error = $SOAP->getError())) {
        $LOG->add($SOAP->responseData);
        exit_error($error, $SOAP->faultcode);
    }
    
    show_output($res);
}

function tracker_do_addcomment() {
    global $PARAMS, $SOAP, $LOG;
    
    if (get_parameter($PARAMS, "help")) {
        echo <<<EOF
Add a follow-up comment to a specific artifact.
Parameters:
--project=<name>: Name of the project in which this artifact belong to. If you specified the name of
    the working project when you logged in, this parameter is not needed.
--tracker_id*=<tracker_id>: Specify the ID of the tracker the artifact belong to
--id*=<artifact_id>: ID of the artifact the comment will be added to.
--message*=<message>: The body message of the follow-up comment that will be added to the artifact.
EOF;
        return;
    }

    $group_artifact_id = get_parameter($PARAMS, "tracker_id", true);
    if (!$group_artifact_id || !is_numeric($group_artifact_id)) {
        exit_error("You must specify the type ID as a valid number");
    }
    
    $artifact_id = get_parameter($PARAMS, "id", true);
    if (!$artifact_id || !is_numeric($artifact_id)) {
        exit_error("You must specify the artifact ID as a valid number");
    }
    
    $body = get_parameter($PARAMS, "message", true);
    if (strlen($body) == 0) {
        exit_error("You must specify the message");
    }
    
    $group_id = get_working_group($PARAMS);
    
    $cmd_params = array(
        "group_id"            => $group_id,
        "group_artifact_id"    => $group_artifact_id,
        "artifact_id"        => $artifact_id,
        "body"                => $body
    );
    $res = $SOAP->call("addFollowup", $cmd_params);
    if (($error = $SOAP->getError())) {
        $LOG->add($SOAP->responseData);
        exit_error($error, $SOAP->faultcode);
    }
    
    show_output($res);
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

/**
 * Get the parameters for an artifact from the command line. This function is used when
 * adding/updating an artifact.
 * As there are standard and custom fields, we assume that every parameter other than
 * 'tracker', 'group_id', 'project' is a field name.
 * A param which doesn't correspond with a field would be simply ignored.
 * 
 * @global array $PARAMS the command line parameters to parse and analyse
 * @global array $STANDARD_ARTIFACT_FIELDS the list of standard fields
 * @return array the array of params from the command line.
 */
function get_artifact_params() {
    global $PARAMS, $STANDARD_ARTIFACT_FIELDS;

    // Except the project name and the artifact id,
    // the others parameters are fields
    $extra_fields = array();
    foreach($PARAMS as $idx => $key_equals_value) {
        $passed_parameter = '';
        $passed_value = '';
        if (preg_match("/^\\-\\-(.+)=(.+)/",$key_equals_value,$res)) {	// something like "--username=john"
            $passed_parameter = $res[1];
            $passed_value = $res[2];
        }
        if (in_array($passed_parameter, $STANDARD_ARTIFACT_FIELDS)) {
            // this field is a standard field
            $cmd_params[$passed_parameter] = $passed_value;
        } else {
            if ($passed_parameter != 'tracker_id' && $passed_parameter != 'group_id' && $passed_parameter != 'project' && $passed_parameter != 'id') {
                // this field is not a standard field, so we consider it as an extra_filed
                $extra_field = array();
                $extra_field["field_name"] = $passed_parameter;
                $extra_field["field_value"] = $passed_value;
                $extra_fields[] = $extra_field;
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
 * As there are standard and custom fields, we assume that every parameter other than
 * 'tracker', 'group_id', 'project' is a field name.
 * A param which doesn't correspond with a field would be simply ignored.
 * 
 * @global array $PARAMS the command line parameters to parse and analyse
 * @global array $STANDARD_ARTIFACT_FIELDS the list of standard fields
 * @return array the array of params from the command line.
 */
function get_artifact_criteria() {
    global $PARAMS, $ACCEPTED_CRITERIA_OPERATORS;

    // Except the project name and the artifact id,
    // the others parameters are fields
    $criterias = array();
    foreach($PARAMS as $idx => $key_operator_value) {
        $passed_parameter = '';
        $passed_operator = '';
        $passed_value = '';
        if (preg_match("/^\\-\\-(.+?)(<=|<>|>=|=|<|>)(.+)/",$key_operator_value,$res)) {	// something like "--username>john"
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

?>
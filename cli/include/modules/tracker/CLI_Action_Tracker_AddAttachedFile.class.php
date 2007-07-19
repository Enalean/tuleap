<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* 
*/

require_once(CODEX_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Tracker_AddAttachedFile extends CLI_Action {
    function CLI_Action_Tracker_AddAttachedFile() {
        $this->CLI_Action('addAttachedFile', 'Add a file to a specific artifact (as an attached files).');
        
        $this->soapCommand = 'addArtifactAttachedFile';
        
        $this->addParam(array(
            'name'           => 'group_artifact_id',
            'description'    => '--tracker_id=<tracker_id>    The ID of the tracker the returned attached file belong to.',
            'parameters'     => array('tracker_id'),
        ));
        $this->addParam(array(
            'name'           => 'artifact_id',
            'description'    => '--artifact_id=<artifact ID>        The ID of the artifact the attached file belong to.',
            'parameters'     => array('artifact_id'),
        ));
        $this->addParam(array(
            'name'           => 'file',
            'description'    => '--file=<location>          Name of the file to attach',
            'soap'           => false,
        ));
        $this->addParam(array(
            'name'           => 'description',
            'description'    => '--description=<description>          (Optional) Description of the file',
            'parameters'     => array('description'),
        ));
    }
    function validate_group_artifact_id(&$group_artifact_id) {
        if (!$group_artifact_id) {
            exit_error("You must specify a tracker ID using the --tracker_id parameter");
        }
        return true;
    }
    function validate_artifact_id(&$artifact_id) {
        if (!$artifact_id) {
            exit_error("You must specify an artifact ID using the --artifact_id parameter");
        }
        return true;
    }
    function before_soapCall(&$loaded_params) {
        if (!$loaded_params['others']['file']) {
            exit_error("You must specify a file name with --file parameter.");
        } else {
            if (!file_exists($loaded_params['others']['file'])) {
                exit_error("File '". $loaded_params['others']['file'] ."' doesn't exist");
            } else if (!($fh = fopen($loaded_params['others']['file'], "rb"))) {
                exit_error("Could not open '". $loaded_params['others']['file'] ."' for reading");
            } else {
                $contents = fread($fh, filesize($loaded_params['others']['file']));
                $loaded_params['soap']['encoded_data'] = base64_encode($contents);
                $loaded_params['soap']['filename']  = basename($loaded_params['others']['file']);
                $loaded_params['soap']['filetype'] = mime_content_type($loaded_params['others']['file']);   // obsolete function to replace by fileinfo if we install pear one day.
                fclose($fh);
            }
        }
    }}

?>

<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Tracker_AttachedFile extends CLI_Action {
    function __construct() {
        parent::__construct('attachedFile', 'Returns a file attached to a specific artifact.');

        $this->soapCommand = 'getArtifactAttachedFile';

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
            'name'           => 'file_id',
            'description'    => '--file_id=<attached file ID>        The ID of the attached file.',
            'parameters'     => array('file_id'),
        ));
        $this->addParam(array(
            'name'           => 'output',
            'description'    => '--output=<location>          (Optional) Name of the file to write the file to',
            'soap'           => false
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
    function validate_file_id(&$file_id) {
        if (!$file_id) {
            exit_error("You must specify a file ID using the --file_id parameter");
        }
        return true;
    }
    function validate_output(&$output) {
        if ($output) {
            $output = trim($output);
            if ($output && file_exists($output)) {
                if (!$this->user_confirm("File $output already exists. Do you want to overwrite it?")) {
                    exit_error("Retrieval of file aborted");
                }
            }
        }
        return true;
    }
    function soapResult($params, $soap_result, $fieldnames = array(), $loaded_params = array()) {
        $file = $soap_result->bin_data;

        if ($loaded_params['others']['output']) {
            $output = $loaded_params['others']['output'];
            while (!($fh = @fopen($output, "wb"))) {
                echo "Couldn't open file ".$output." for writing.\n";
                $output = "";
                while (!$output) {
                    $output = get_user_input("Please specify a new file name: ");
                }
            }

            fwrite($fh, $file, strlen($file));
            fclose($fh);

            if (!$loaded_params['others']['quiet']) echo "File retrieved successfully.\n";
        } else {
            if (!$loaded_params['others']['quiet']) echo $file;     // if not saving to a file, output to screen
        }
    }
}

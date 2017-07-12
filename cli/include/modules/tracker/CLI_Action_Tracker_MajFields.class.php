<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Tracker_MajFields extends CLI_Action {
    function __construct($name, $description) {
        parent::__construct($name, $description);

        $this->addParam(array(
            'name'           => 'group_artifact_id',
            'description'    => '--tracker_id=<tracker_id>    '. $this->getGroupArtifactIdDescription(),
            'parameters'     => array('tracker_id'),
        ));
        $this->addParamArtifactId();
        $this->addParam(array(
            'name'           => 'noask',
            'description'    => '--noask or -n                Do not ask for confirmation',
            'parameters'     => array('noask', 'n'),
            'value_required' => false,
            'soap'           => false,
        ));
        $this->addParam(array(
            'name'           => 'fields',
            'description'    => '--<field_name>=<value>       Specify the couple field name/field value the artifact.',
            'method'         => array(&$this, 'getArtifactParams'),
            'soap'           => false,
        ));
    }
    function addParamArtifactId() {
    }
    function getArtifactParams($params) {
        return $this->module->getArtifactParams($params);
    }
    function validate_group_artifact_id(&$group_artifact_id) {
        if (!$group_artifact_id) {
            exit_error("You must specify a tracker ID using the --tracker_id parameter");
        }
        return true;
    }
    function after_loadParams(&$loaded_params) {
        //We need to put fields in soap params
        $loaded_params['soap'] = array_merge($loaded_params['others']['fields'], $loaded_params['soap']);
    }
    function confirmation($loaded_params) {
        if (!$loaded_params['others']['noask']) {
            echo "Confirm you want to add a new artifact with the following information:\n";
            // print the standard fields with their values
            foreach($loaded_params['soap'] as $field_name => $field_value) {
                if ($field_name != 'extra_fields') {
                    echo $field_name.": ".$field_value."\n";
                }
            }
            // print the extra fields with their values in the console
            foreach($loaded_params['soap']['extra_fields'] as $extrafield) {
                echo $extrafield['field_name'].": ".$extrafield['field_value']."\n";
            }

            if (!$this->user_confirm("Is this information correct?")) {
                exit_error("Submission aborted");
            }
        }
        return true;
    }
}

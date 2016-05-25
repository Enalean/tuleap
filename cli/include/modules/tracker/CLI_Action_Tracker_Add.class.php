<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once('CLI_Action_Tracker_MajFields.class.php');

class CLI_Action_Tracker_Add extends CLI_Action_Tracker_MajFields {
    function __construct() {
        parent::__construct('add', 'Add a new artifact in a tracker.');
        $this->soapCommand = 'addArtifactWithFieldNames';
    }
    function getGroupArtifactIdDescription() {
        return 'The ID of the tracker the returned artifacts belong to.';
    }

    function before_soapCall(&$loaded_params) {
        // add potential missing parameters : $status_id, $close_date, $summary, $details, $severity
        // and give it the value null : it will take the default value for this field.
    	if ( ! array_key_exists('status_id', $loaded_params['soap'])) $loaded_params['soap']['status_id'] = null;
    	if ( ! array_key_exists('close_date', $loaded_params['soap'])) $loaded_params['soap']['close_date'] = null;
    	if ( ! array_key_exists('summary', $loaded_params['soap'])) $loaded_params['soap']['summary'] = null;
    	if ( ! array_key_exists('details', $loaded_params['soap'])) $loaded_params['soap']['details'] = null;
    	if ( ! array_key_exists('severity', $loaded_params['soap'])) $loaded_params['soap']['severity'] = null;

    	// sort the parameters in the right order
        uksort($loaded_params['soap'], array($this, "sort_parameters"));
    }

	function sort_parameters($p1, $p2) {
        $order = array('group_id', 'group_artifact_id', 'status_id', 'close_date', 'summary', 'details', 'severity', 'extra_fields');
        $order_flip = array_flip($order);
        return $order_flip[$p1] > $order_flip[$p2];
    }
}

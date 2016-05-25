<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Tracker_List extends CLI_Action {
    function __construct() {
        parent::__construct('list', 'Returns a list of artifacts that belongs to a specific tracker.');

        $this->soapCommand = 'getArtifacts';

        $this->addParam(array(
            'name'           => 'group_artifact_id',
            'description'    => '--tracker_id=<tracker_id>    The ID of the tracker the returned artifacts belong to.',
            'parameters'     => array('tracker_id'),
        ));
        $this->addParam(array(
            'name'           => 'max_rows',
            'description'    => '--limit=<limit>        The maximum number of returned artifacts.',
            'parameters'     => array('limit'),
        ));
        $this->addParam(array(
            'name'           => 'offset',
            'description'    => '--offset=<offset>     Number of artifacts that will be skipped (comes with the limit parameter).',
        ));
        $this->addParam(array(
            'name'           => 'criteria',
            'description'    => '--[field_name][operator][value] a criteria to filter the returned artifacts (e.g: "--open_date<2006-05-21")',
            'method'         => array(&$this, 'getArtifactCriteria'),
        ));
    }
    function getArtifactCriteria($params) {
        return $this->module->getArtifactCriteria($params);
    }
    function validate_group_artifact_id(&$group_artifact_id) {
        if (!$group_artifact_id) {
            exit_error("You must specify a tracker ID using the --tracker_id parameter");
        }
        return true;
    }
    function validate_max_rows(&$max_rows) {
        if (!$max_rows) {
            $max_rows = 0;
        } else {
            $max_rows = intval($max_rows);
        }
        return true;
    }
    function validate_offset(&$offset) {
        if (!$offset) {
            $offset = 0;
        } else {
            $offset = intval($offset);
        }
        return true;
    }
    function before_soapCall(&$loaded_params) {
        $loaded_params['user_id'] = $GLOBALS['soap']->getSessionUserID();
        // sort the parameters in the right order
        uksort($loaded_params['soap'], array($this, "sort_parameters"));
    }
    function soapResult($params, $soap_result, $fieldnames = array(), $loaded_params = array()) {
        if (!is_object($soap_result) || $soap_result->total_artifacts_number == 0) {
            if (!$loaded_params['others']['quiet']) echo "No artifacts were found for this tracker.";
        } else {
            if (!$loaded_params['others']['quiet']) $this->show_output($soap_result, $fieldnames);
        }
    }

    function sort_parameters($p1, $p2) {
        $order = array('group_id', 'group_artifact_id', 'criteria', 'offset', 'max_rows');
        $order_flip = array_flip($order);
        return $order_flip[$p1] > $order_flip[$p2];
    }

}

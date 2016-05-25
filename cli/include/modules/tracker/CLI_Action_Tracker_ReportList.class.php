<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Tracker_ReportList extends CLI_Action {
    function __construct() {
        parent::__construct('reportlist', 'Returns a list of artifacts from a specific report that belongs to a specific tracker.');

        $this->soapCommand = 'getArtifactsFromReport';

        $this->addParam(array(
            'name'           => 'group_artifact_id',
            'description'    => '--tracker_id=<tracker_id>    The ID of the tracker the returned artifacts belong to.',
            'parameters'     => array('tracker_id'),
        ));
        $this->addParam(array(
            'name'           => 'report_id',
            'description'    => '--report_id=<report> ID of the report',
        ));
        $this->addParam(array(
            'name'           => 'criteria',
            'description'    => '--[field_name][operator][value] a criteria to filter the returned artifacts (e.g: "--open_date<2006-05-21")',
            'method'         => array(&$this, 'getArtifactCriteria'),
        ));
        $this->addParam(array(
            'name'           => 'offset',
            'description'    => '--offset=<offset>     Number of artifacts that will be skipped (comes with the limit parameter).',
        ));
        $this->addParam(array(
            'name'           => 'max_rows',
            'description'    => '--limit=<limit>        The maximum number of returned artifacts.',
            'parameters'     => array('limit'),
        ));
        $this->addParam(array(
            'name'           => 'sort_criteria',
            'description'    => '--sort="field_name ASC, field_name DESC, ..."',
            'parameters'     => array('sort'),
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
            $max_rows = 100000; // if no max_rows, set to max.
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
    function validate_report_id(&$report_id) {
        if (!$report_id) {
            $report_id = 100;
        } else {
            $report_id = intval($report_id);
        }
        return true;
    }
    function validate_sort_criteria(&$sort_criteria) {
        $sort_criteria = $this->module->getArtifactSortCriteria($sort_criteria);
        return true;
    }
    function before_soapCall(&$loaded_params) {
        $loaded_params['user_id'] = $GLOBALS['soap']->getSessionUserID();
    }
    function soapResult($params, $soap_result, $fieldnames = array(), $loaded_params = array()) {
        if (!is_object($soap_result) || $soap_result->total_artifacts_number == 0) {
            if (!$loaded_params['others']['quiet']) echo "No artifacts were found for this tracker.";
        } else {
            if (!$loaded_params['others']['quiet']) $this->show_output($soap_result, $fieldnames);
        }
    }
}

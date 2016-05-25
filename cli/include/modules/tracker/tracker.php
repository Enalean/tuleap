<?php
/**
 * Codendi Command-line Interface
 *
 * Portion of this file is inspired from the  GForge Command-line Interface
 * contained in GForge.
 * Copyright 2005 GForge, LLC
 * http://gforge.org/
 *
 */
require_once(CODENDI_CLI_DIR.'/CLI_Module.class.php');

require_once('CLI_Action_Tracker_Trackerlist.class.php');
require_once('CLI_Action_Tracker_Trackers.class.php');
require_once('CLI_Action_Tracker_Tracker.class.php');
require_once('CLI_Action_Tracker_List.class.php');
require_once('CLI_Action_Tracker_ReportList.class.php');
require_once('CLI_Action_Tracker_Add.class.php');
require_once('CLI_Action_Tracker_Update.class.php');
require_once('CLI_Action_Tracker_Comments.class.php');
require_once('CLI_Action_Tracker_AddComment.class.php');
require_once('CLI_Action_Tracker_UpdateComment.class.php');
require_once('CLI_Action_Tracker_DeleteComment.class.php');
require_once('CLI_Action_Tracker_CCList.class.php');
require_once('CLI_Action_Tracker_AddCC.class.php');
require_once('CLI_Action_Tracker_DeleteCC.class.php');
require_once('CLI_Action_Tracker_Reports.class.php');
require_once('CLI_Action_Tracker_Dependencies.class.php');
require_once('CLI_Action_Tracker_InverseDependencies.class.php');
require_once('CLI_Action_Tracker_AddDependencies.class.php');
require_once('CLI_Action_Tracker_DeleteDependency.class.php');
require_once('CLI_Action_Tracker_ArtifactHistory.class.php');
require_once('CLI_Action_Tracker_AttachedFiles.class.php');
require_once('CLI_Action_Tracker_AttachedFile.class.php');
require_once('CLI_Action_Tracker_AddAttachedFile.class.php');
require_once('CLI_Action_Tracker_DeleteAttachedFile.class.php');

class CLI_Module_Tracker extends CLI_Module {
    // These fields are the standard fields
    // for adding and updating an artifact, we will parse the arguments command line,
    // and all the arguments not present in this array will be considered as "extra_fields"
    var $standard_artifact_fields;

    function __construct() {
        parent::__construct("tracker", "Manage trackers");

        $this->addAction(new CLI_Action_Tracker_Trackerlist());
        $this->addAction(new CLI_Action_Tracker_Trackers());
        $this->addAction(new CLI_Action_Tracker_Tracker());
        $this->addAction(new CLI_Action_Tracker_List());
        $this->addAction(new CLI_Action_Tracker_ReportList());
        $this->addAction(new CLI_Action_Tracker_Add());
        $this->addAction(new CLI_Action_Tracker_Update());
        $this->addAction(new CLI_Action_Tracker_Comments());
        $this->addAction(new CLI_Action_Tracker_AddComment());
        $this->addAction(new CLI_Action_Tracker_UpdateComment());
        $this->addAction(new CLI_Action_Tracker_DeleteComment());
        $this->addAction(new CLI_Action_Tracker_CCList());
        $this->addAction(new CLI_Action_Tracker_AddCC());
        $this->addAction(new CLI_Action_Tracker_DeleteCC());
        $this->addAction(new CLI_Action_Tracker_Reports());
        $this->addAction(new CLI_Action_Tracker_Dependencies());
        $this->addAction(new CLI_Action_Tracker_InverseDependencies());
        $this->addAction(new CLI_Action_Tracker_AddDependencies());
        $this->addAction(new CLI_Action_Tracker_DeleteDependency());
        $this->addAction(new CLI_Action_Tracker_ArtifactHistory());
        $this->addAction(new CLI_Action_Tracker_AttachedFiles());
        $this->addAction(new CLI_Action_Tracker_AttachedFile());
        $this->addAction(new CLI_Action_Tracker_AddAttachedFile());
        $this->addAction(new CLI_Action_Tracker_DeleteAttachedFile());

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
            'last_update_date',
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
            if (preg_match("/^\\-\\-([^=]+)=(.+)/s",$key_equals_value,$res)) {	// something like "--username=john"
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
                        $extra_field["artifact_id"] = 0;
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
                    $passed_parameter != 'offset' &&
                    $passed_parameter != 'report_id' &&
                    $passed_parameter != 'sort') {
                    $criteria['field_name'] = $passed_parameter;
                    $criteria['operator'] = $passed_operator;
                    $criteria['field_value'] = $passed_value;
                    $criterias[] = $criteria;
                }
            }
        }
        return $criterias;
    }

    function getArtifactSortCriteria($sort_criteria) {
        if (!$sort_criteria) {
            $sort_criteria = array();
        } else {
            $SORT_SEPARATOR = ',';
            $SORT_OPERATOR_SEPARATOR = ' ';
            $array_sort = array();
            $array_sort_string = explode($SORT_SEPARATOR, $sort_criteria);
            foreach($array_sort_string as $sort_string) {
                $array_sort_param = explode($SORT_OPERATOR_SEPARATOR, trim($sort_string));
                $sort_item = array();
                $sort_item['field_name'] = $array_sort_param[0];
                // direction is optionnal, ASC is the default one
                if (isset($array_sort_param[1]) && ($array_sort_param[1] == 'ASC' || $array_sort_param[1] == 'DESC')) {
                    $sort_item['sort_direction'] = $array_sort_param[1];
                } else {
                    $sort_item['sort_direction'] = 'ASC';
                }
                $array_sort[] = $sort_item;
            }
            $sort_criteria = $array_sort;
        }
        return $sort_criteria;
    }

}

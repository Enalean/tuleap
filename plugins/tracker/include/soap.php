<?php

//define fault code constants
define ('get_group_fault', '3000');
define ('get_artifact_type_factory_fault', '3002');
define ('get_artifact_factory_fault', '3003');
define ('get_artifact_field_factory_fault', '3004');
define ('get_artifact_type_fault', '3005');
define ('get_artifact_fault', '3006');
define ('create_artifact_fault', '3007');
define ('invalid_field_dependency_fault', '3009');
define ('update_artifact_fault', '3010');
define ('get_artifact_file_fault', '3011');
define ('add_dependency_fault', '3012');
define ('delete_dependency_fault', '3013');
define ('create_followup_fault', '3014');
define ('get_artifact_field_fault', '3015');
define ('add_cc_fault', '3016');
define ('invalid_field_fault', '3017');
define ('delete_cc_fault', '3018');
define ('get_service_fault', '3020');
define ('get_artifact_report_fault', '3021');
define('update_artifact_followup_fault','3022');
define('delete_artifact_followup_fault','3023');

define('get_tracker_factory_fault','3024');
define('get_tracker_fault','3025');


require_once ('pre.php');
require_once ('session.php');
require_once ('utils_soap.php');

require_once ('Tracker/Tracker.class.php');
require_once ('Tracker/TrackerFactory.class.php');
require_once ('Tracker/Artifact/Tracker_Artifact.class.php');
require_once ('Tracker/Artifact/Tracker_ArtifactFactory.class.php');
require_once ('Tracker/FormElement/Tracker_FormElementFactory.class.php');

/**
 * getArtifacts - returns an ArtifactQueryResult that belongs to the project $group_id, to the tracker $group_artifact_id,
 *                and that match the criteria $criteria. If $offset and $max_rows are filled, the number of returned artifacts
 *                will not exceed $max_rows, beginning at $offset.
 *
 * !!!!!!!!!!!!!!!
 * !!! Warning : If $max_rows is not filled, $offset is not taken into account. !!!
 * !!!!!!!!!!!!!!!
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the array of artifacts
 * @param int $tracker_id the ID of the tracker we want to retrieve the array of artifacts
 * @param array{SOAPCriteria} $criteria the criteria that the set of artifact must match
 * @param int $offset number of artifact skipped. Used in association with $max_rows to limit the number of returned artifact.
 * @param int $max_rows the maximum number of artifacts returned
 * @return the SOAPArtifactQueryResult that match the criteria $criteria and belong to the project $group_id and the tracker $group_artifact_id,
 *          or a soap fault if group_id does not match with a valid project, or if group_artifact_id does not match with a valid tracker.
 */
function getArtifacts($sessionKey,$group_id,$tracker_id, $criteria, $offset, $max_rows) {
    //trigger_error(var_export($criteria, 1), E_USER_NOTICE);
    ob_start();
    print_r($criteria);
    file_put_contents('/tmp/soap.log', ob_get_clean(), FILE_APPEND);
    if (session_continue($sessionKey)) {
        $pm = ProjectManager::instance();
        try {
            $project = $pm->getGroupByIdForSoap($group_id, 'getArtifacts');
        } catch (SoapFault $e) {
            return $e;
        }
        if (!$project->usesService('plugin_tracker')) {
            return new SoapFault(get_service_fault, 'Tracker service is not used for this project.', 'getArtifacts');
        }

        $tf = TrackerFactory::instance();
        if (!$tf) {
            return new SoapFault(get_tracker_factory_fault, 'Could Not Get TrackerFactory', 'getArtifacts');
        }

        $tracker = $tf->getTrackerById($tracker_id);

        if ($tracker == null) {
            return new SoapFault(get_tracker_factory_fault, 'Could Not Get Tracker', 'getArtifacts');
        } else {
            if (! $tracker->userCanView()) {
                return new SoapFault(get_tracker_factory_fault,'Permission Denied: You are not granted sufficient permission to perform this operation.', 'getArtifacts');
            } else {
                $af = Tracker_ArtifactFactory::instance();
                $artifacts = $af->getArtifactsByTrackerId($tracker_id);
            }
        }

        // the function getArtifacts returns all artifacts without whecking if user is allowed to see them
        // => we need to fliter them
        return artifact_query_result_to_soap($artifacts);
    } else {
        return new SoapFault(invalid_session_fault,'Invalid Session ','getArtifacts');
    }
}

function artifacts_to_soap($artifacts) {
    $return = array();
    foreach ($artifacts as $artifact_id => $artifact) {
        $return[] = artifact_to_soap($artifact);
    }
    return $return;
}

function artifact_query_result_to_soap($artifacts) {
    $return = array();
    if ($artifacts == false) {
        $return['artifacts'] = null;
        $return['total_artifacts_number'] = 0;
    } else {
        $return['artifacts'] = artifacts_to_soap($artifacts);
        $return['total_artifacts_number'] = count($return['artifacts']);
    }
    return $return;
}
/**
 * getArtifactHistory - returns the array of ArtifactHistory of the artifact $artifact_id in the tracker $tracker_id of the project $group_id
 *
 * @param string $sessionKey the session hash associated with the session opened by the person who calls the service
 * @param int $group_id the ID of the group we want to retrieve the history
 * @param int $tracker_id the ID of the tracker we want to retrieve the history
 * @param int $artifact_id the ID of the artifact we want to retrieve the history
 * @return array{SOAPArtifactHistory} the array of the history of the artifact,
 *              or a soap fault if :
 *              - group_id does not match with a valid project,
 *              - tracker_id does not match with a valid tracker
 *              - artifact_id does not match with a valid artifact
 */
function getArtifactHistory($sessionKey, $group_id, $tracker_id, $artifact_id) {
    if (session_continue($sessionKey)) {
        $pm = ProjectManager::instance();
        try {
            $grp = $pm->getGroupByIdForSoap($group_id, 'getArtifactHistory');
        } catch (SoapFault $e) {
            return $e;
        }

        if (!$project->usesService('plugin_tracker')) {
            return new SoapFault(get_service_fault, 'Tracker service is not used for this project.', 'getArtifactFollowups');
        }

        $tf = TrackerFactory::instance();
        if (!$tf) {
            return new SoapFault(get_tracker_factory_fault, 'Could Not Get TrackerFactory', 'getArtifactFollowups');
        }

        $tracker = $tf->getTrackerById($tracker_id);

        if ($tracker == null) {
            return new SoapFault(get_tracker_factory_fault, 'Could Not Get Tracker', 'getArtifactFollowups');
        } else {
            if (! $tracker->userCanView()) {
                return new SoapFault(get_tracker_factory_fault,'Permission Denied: You are not granted sufficient permission to perform this operation.', 'getArtifactFollowups');
            } else {
                $af = Tracker_ArtifactFactory::instance();
                $artifact = $af->getArtifactById($artifact_id);
                $changesets = $artifact->getChangesets();
                return history_to_soap($changesets, $group_id);

            }
        }
    } else {
        return new SoapFault(invalid_session_fault, 'Invalid Session', 'getArtifactHistory');
    }
}

function history_to_soap($changesets, $group_id) {
    $return = array();
    foreach ($changesets as $changeset_id => $changeset) {

        if ($previous_changeset = $changeset->getArtifact()->getPreviousChangeset($changeset->getId())) {

            $changes = array();
            $factory = Tracker_FormElementFactory::instance();
            foreach($changeset->getValues() as $field_id => $current_changeset_value) {
                if ($field = $factory->getFieldById($field_id)) {
                    if ($current_changeset_value->hasChanged()) {
                        if ($previous_changeset_value = $previous_changeset->getValue($field)) {
                            if ($diff = $current_changeset_value->diff($previous_changeset_value)) {
                                $changes[] = $field->getLabel() .': ' .$diff;
                            }
                        }
                    }
                }
            }

            $return[] = array(
                            'artifact_id'     => $changeset->artifact->getId(),
                            'changeset_id'    => $changeset_id,
                            'changes'         => $changes,
                            'modification_by' => $changeset->submitted_by,
                            'date'            => $changeset->submitted_on,
                            'comment'         => $changeset->getComment()
                        );
        }
    }
    return $return;
}
?>

<?php




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

?>

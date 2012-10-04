<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once 'common/soap/SOAP_UserManager.class.php';
require_once 'Report/Tracker_Report_SOAP.class.php';

class Tracker_SOAPServer {
    /**
     * @var SOAP_UserManager
     */
    private $soap_user_manager;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var PermissionsManager
     */
    private $permissions_manager;

    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;

    /**
     * @var Tracker_ReportDao
     */
    private $report_dao;

    public function __construct(
            SOAP_UserManager $soap_user_manager,
            ProjectManager $project_manager,
            TrackerFactory $tracker_factory,
            PermissionsManager $permissions_manager,
            Tracker_ReportDao $dao,
            Tracker_FormElementFactory $formelement_factory) {
        $this->soap_user_manager   = $soap_user_manager;
        $this->project_manager     = $project_manager;
        $this->tracker_factory     = $tracker_factory;
        $this->permissions_manager = $permissions_manager;
        $this->report_dao          = $dao;
        $this->formelement_factory = $formelement_factory;
    }

    public function getArtifacts($session_key, $group_id, $tracker_id, $criteria, $offset, $max_rows) {
        $current_user = $this->soap_user_manager->continueSession($session_key);
        $tracker = $this->tracker_factory->getTrackerById($tracker_id);
        $report = new Tracker_Report_SOAP($current_user, $tracker, $this->permissions_manager, $this->report_dao, $this->formelement_factory);
        $report->setSoapCriteria($criteria);
        $matching = $report->getMatchingIds();
        return explode(',', $matching['id']);
    }

    /**
     * getTrackerList - returns an array of Tracker that belongs to the project identified by group_id
     *
     * @param string $session_key the session hash associated with the session opened by the person who calls the service
     * @param int $group_id the ID of the group we want to retrieve the list of trackers
     * @return array the array of SOAPTracker that belongs to the project identified by $group_id, or a soap fault if group_id does not match with a valid project.
     */
    public function getTrackerList($session_key, $group_id) {
        $this->soap_user_manager->continueSession($session_key);
        $this->getProject($group_id, 'getTrackerList');

        // The function getTrackersByGroupId returns all trackers,
        // even those the user is NOT allowed to view -> we will filter in trackerlist_to_soap
        $trackers = $this->tracker_factory->getTrackersByGroupId($group_id);
        return trackerlist_to_soap($trackers);
    }

    /**
    * getTrackerFields - returns an array of TrackerFields used in the tracker tracker_id of the project identified by group_id
    *
    * @param string $session_key the session hash associated with the session opened by the person who calls the service
    * @param int $group_id the ID of the project
    * @param int $tracker_id the ID of the Tracker
    * @return array the array of SOAPTrackerFields used in the tracker $tracker_id in the project identified by $group_id,
    *          or a soap fault if tracker_id or group_id does not match with a valid project/tracker.
    */
   public function getTrackerFields($session_key, $group_id, $tracker_id) {
        $this->soap_user_manager->continueSession($session_key);
        $this->getProject($group_id, 'getTrackerFields');

        $tracker = $this->tracker_factory->getTrackerById($tracker_id);
        if ($tracker == null) {
            return new SoapFault(get_tracker_factory_fault, 'Could Not Get Tracker', 'getTrackerFields');
        } elseif ($tracker->getGroupId() != $group_id) {
            return new SoapFault(get_tracker_fault, 'Could not get Tracker.', 'getTrackerFields');
        }

        // The function getTrackerFields returns all tracker fields,
        // even those the user is NOT allowed to view -> we will filter in trackerlist_to_soap
        $tracker_fields = $this->formelement_factory->getUsedFields($tracker);
        return trackerfields_to_soap($tracker, $tracker_fields);
    }

    /**
     * getArtifact - returns the Artifacts that is identified by the ID $artifact_id
     *
     * @param string $session_key the session hash associated with the session opened by the person who calls the service
     * @param int $group_id the ID of the project. Not used, here for backward compatibility reason. Will be removed in 6.0
     * @param int $tracker_id the ID of the tracker. Not used, here for backward compatibility reason. Will be removed in 6.0
     * @param int $artifact_id the ID of the artifact we are looking for
     * @return array the SOAPArtifact identified by ID $artifact_id,
     *          or a soap fault if artifact_id is not a valid artifact
     */
    function getArtifact($session_key, $group_id, $tracker_id, $artifact_id) {
        $this->soap_user_manager->continueSession($session_key);

        $af = Tracker_ArtifactFactory::instance();
        $artifact = $af->getArtifactById($artifact_id);
        if (!$artifact) {
            return new SoapFault(get_artifact_fault, 'Could Not Get Artifact', 'getArtifact');
        }

        $tracker = $artifact->getTracker();
        if (!$tracker) {
            return new SoapFault(get_tracker_factory_fault, 'Could Not Get Tracker', 'getArtifact');
        }
        $group_id = $tracker->getProject()->getGroupId();
        $this->getProject($group_id, 'getArtifact');

        if (!$tracker->userCanView()) {
            return new SoapFault(get_tracker_factory_fault, 'Permission Denied: You are not granted sufficient permission to perform this operation.', 'getArtifact');
        } else {
            return artifact_to_soap($artifact);
        }
    }

    /**
     * addArtifact - add an artifact in tracker $tracker_id of the project $group_id with given values
     *
     * @param string $session_key the session hash associated with the session opened by the person who calls the service
     * @param int    $group_id   the ID of the group we want to add the artifact
     * @param int    $tracker_id the ID of the tracker we want to add the artifact
     * @param array  $value      The fields values of the artifact (array of {SOAPArtifactFieldValue})
     *
     * @return int the ID of the new created artifact,
     *              or a soap fault if :
     *              - group_id does not match with a valid project,
     *              - tracker_name does not match with a valid tracker,
     *              - the user does not have the permissions to submit an artifact
     *              - the given values are breaking a field dependency rule
     *              - the artifact creation failed.
     */
    public function addArtifact($session_key, $group_id, $tracker_id, $value) {
        $user = $this->soap_user_manager->continueSession($session_key);
        $this->getProject($group_id, 'addArtifact');

        $tracker = $this->tracker_factory->getTrackerById($tracker_id);
        if ($tracker == null) {
            return new SoapFault(get_tracker_fault, 'Could not get Tracker.', 'addArtifact');
        } elseif ($tracker->getGroupId() != $group_id) {
            return new SoapFault(get_tracker_fault, 'Could not get Tracker.', 'addArtifact');
        }

        $fields_data = array();
        foreach ($value as $field_value) {
            // field are identified by name, we need to retrieve the field id
            if ($field_value->field_name) {

                $field = $this->formelement_factory->getUsedFieldByName($tracker_id, $field_value->field_name);
                if ($field) {

                    $field_data = $field->getFieldData($field_value->field_value);
                    if ($field_data != null) {
                        // $field_value is an object: SOAP must cast it in ArtifactFieldValue
                        if (isset($fields_data[$field->getId()])) {
                            if (!is_array($fields_data[$field->getId()])) {
                                $fields_data[$field->getId()] = array($fields_data[$field->getId()]);
                            }
                            $fields_data[$field->getId()][] = $field_data;
                        } else {
                            $fields_data[$field->getId()] = $field_data;
                        }
                    } else {
                        return new SoapFault(update_artifact_fault, 'Unknown value ' . $field_value->field_value . ' for field: ' . $field_value->field_name, 'addArtifact');
                    }
                } else {
                    return new SoapFault(update_artifact_fault, 'Unknown field: ' . $field_value->field_name, 'addArtifact');
                }
            }
        }

        $af = Tracker_ArtifactFactory::instance();
        if ($artifact = $af->createArtifact($tracker, $fields_data, $user, null)) {
            return $artifact->getId();
        } else {
            if ($GLOBALS['Response']->feedbackHasErrors()) {
                return new SoapFault(update_artifact_fault, $GLOBALS['Response']->getRawFeedback(), 'addArtifact');
            } else {
                return new SoapFault(update_artifact_fault, 'Unknown error', 'addArtifact');
            }
        }
   }

    /**
     * updateArtifact - update the artifact $artifact_id in tracker $tracker_id of the project $group_id with given values
     *
     * @param string $session_key  the session hash associated with the session opened by the person who calls the service
     * @param int    $group_id    the ID of the group we want to update the artifact
     * @param int    $tracker_id  the ID of the tracker we want to update the artifact
     * @param int    $artifact_id the ID of the artifact to update
     * @param array{SOAPArtifactFieldValue} $value the fields value to update
     * @param string  $comment     the comment associated with the modification, or null if no follow-up comment.
     * @param string  $comment_format     The comment (follow-up) type ("text" | "html")
     *
     * @return int The artifact id if update was fine,
     *              or a soap fault if :
     *              - group_id does not match with a valid project,
     *              - tracker_id does not match with a valid tracker,
     *              - artifact_id does not match with a valid artifact,
     *              - the given values are breaking a field dependency rule
     *              - the artifact modification failed.
     */
    public function updateArtifact($session_key, $group_id, $tracker_id, $artifact_id, $value, $comment, $comment_format) {
        $user = $this->soap_user_manager->continueSession($session_key);
        $this->getProject($group_id, 'updateArtifact');

        $tracker = $this->tracker_factory->getTrackerById($tracker_id);
        if ($tracker == null) {
            return new SoapFault(get_tracker_fault, 'Could not get Tracker.', 'updateArtifact');
        } elseif ($tracker->getGroupId() != $group_id) {
            return new SoapFault(get_tracker_fault, 'Could not get Tracker.', 'updateArtifact');
        }

        $af = Tracker_ArtifactFactory::instance();
        if ($artifact = $af->getArtifactById($artifact_id)) {
            if ($artifact->getTrackerId() != $tracker_id) {
                return new SoapFault(get_tracker_fault, 'Could not get Artifact.', 'updateArtifact');
            }

            //Check Field Dependencies
            // TODO : implement it
            /* require_once('common/tracker/ArtifactRulesManager.class.php');
              $arm =& new ArtifactRulesManager();
              if (!$arm->validate($ath->getID(), $data, $art_field_fact)) {
              return new SoapFault(invalid_field_dependency_fault, 'Invalid Field Dependency', 'updateArtifact');
              } */

            $fields_data = array();
            foreach ($value as $field_value) {
                // field are identified by name, we need to retrieve the field id
                if ($field_value->field_name) {

                    $field = $this->formelement_factory->getUsedFieldByName($tracker_id, $field_value->field_name);
                    if ($field) {

                        $field_data = $field->getFieldData($field_value->field_value);
                        if ($field_data != null) {
                            // $field_value is an object: SOAP must cast it in ArtifactFieldValue
                            if (isset($fields_data[$field->getId()])) {
                                if (!is_array($fields_data[$field->getId()])) {
                                    $fields_data[$field->getId()] = array($fields_data[$field->getId()]);
                                }
                                $fields_data[$field->getId()][] = $field_data;
                            } else {
                                $fields_data[$field->getId()] = $field_data;
                            }
                        } else {
                            return new SoapFault(update_artifact_fault, 'Unknown value ' . $field_value->field_value . ' for field: ' . $field_value->field_name, 'addArtifact');
                        }
                    } else {
                        return new SoapFault(update_artifact_fault, 'Unknown field: ' . $field_value->field_name, 'addArtifact');
                    }
                }
            }

            if ($artifact->createNewChangeset($fields_data, $comment, $user, null, true, $comment_format)) {
                return $artifact_id;
            } else {
                $response = new Response();
                if ($response->feedbackHasErrors()) {
                    return new SoapFault(update_artifact_fault, $response->getRawFeedback(), 'updateArtifact');
                } else {
                    return new SoapFault(update_artifact_fault, 'Unknown error', 'updateArtifact');
                }
            }
        } else {
            return new SoapFault(get_tracker_fault, 'Could not get Artifact.', 'updateArtifact');
        }
    }

    private function getProject($group_id, $method_name) {
        $project = $this->project_manager->getGroupByIdForSoap($group_id, $method_name);
        if (!$project->usesService('plugin_tracker')) {
            throw new SoapFault(get_service_fault, 'Tracker service is not used for this project.', $method_name);
        }
        return $project;
    }
}

?>

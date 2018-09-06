<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

use Tuleap\SOAP\SOAPRequestValidator;
use Tuleap\Tracker\Report\AdditionalCriteria\CommentCriterionValueRetriever;

require_once 'common/soap/SOAP_RequestValidator.class.php';

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
define('user_is_not_tracker_admin','3026');
define('invalid_report', '3027');
define('invalid_file', '3028');
define('invalid_file_field_format', Tracker_FormElement_Field_File::SOAP_FAULT_INVALID_REQUEST_FORMAT); //'3029'
define('nb_max_temp_files', '3030');
define('temp_file_invalid', '3031');
define('uploaded_file_too_big', '3032');
define('artifact_does_not_exist', '3033');
define('add_selectbox_fields_fault', '3034');
define('check_field_fault', '3035');

class Tracker_SOAPServer {
    /**
     * @var SOAPRequestValidator
     */
    private $soap_request_validator;

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

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var Tracker_ReportFactory
     */
    private $report_factory;

    /**
     * @var Tracker_FileInfoFactory
     */
    private $fileinfo_factory;

    /**
     * @var TrackerManager
     */
    private $tracker_manager;
    /**
     * @var CommentCriterionValueRetriever
     */
    private $comment_criterion_value_retriever;

    public function __construct(
        SOAPRequestValidator $soap_request_validator,
        TrackerFactory $tracker_factory,
        PermissionsManager $permissions_manager,
        Tracker_ReportDao $dao,
        Tracker_FormElementFactory $formelement_factory,
        Tracker_ArtifactFactory $artifact_factory,
        Tracker_ReportFactory $report_factory,
        Tracker_FileInfoFactory $fileinfo_factory,
        TrackerManager $tracker_manager,
        CommentCriterionValueRetriever $comment_criterion_value_retriever
    ) {
        $this->soap_request_validator            = $soap_request_validator;
        $this->tracker_factory                   = $tracker_factory;
        $this->permissions_manager               = $permissions_manager;
        $this->report_dao                        = $dao;
        $this->formelement_factory               = $formelement_factory;
        $this->artifact_factory                  = $artifact_factory;
        $this->report_factory                    = $report_factory;
        $this->fileinfo_factory                  = $fileinfo_factory;
        $this->tracker_manager                   = $tracker_manager;
        $this->comment_criterion_value_retriever = $comment_criterion_value_retriever;
    }

    public function getVersion() {
        return file_get_contents(dirname(__FILE__).'/../../www/soap/VERSION');
    }

    /**
     * Return artifacts according to given criteria
     *
     * @param String  $session_key
     * @param Integer $group_id
     * @param Integer $tracker_id
     * @param Array   $criteria
     * @param Integer $offset
     * @param Integer $max_rows
     *
     * @return Array
     */
    public function getArtifacts($session_key, $group_id, $tracker_id, $criteria, $offset, $max_rows) {
        try {
            $current_user = $this->soap_request_validator->continueSession($session_key);
            $tracker = $this->tracker_factory->getTrackerById($tracker_id);
            $this->checkUserCanViewTracker($tracker, $current_user);

            $report = new Tracker_Report_SOAP(
                $current_user,
                $tracker,
                $this->permissions_manager,
                $this->report_dao,
                $this->formelement_factory,
                $this->comment_criterion_value_retriever
            );

            $report->setSoapCriteria($criteria);
            $matching = $report->getMatchingIds();
            return $this->artifactListToSoap($current_user, $matching['id'], $offset, $max_rows);
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }

    private function artifactListToSoap(PFUser $user, $id_list, $offset, $max_rows) {
        $return = array(
            'artifacts' => array(),
            'total_artifacts_number' => 0
        );
        if ($id_list) {
            $id_list = explode(',', $id_list);
            $return['total_artifacts_number'] = count($id_list);
            foreach (array_slice($id_list, $offset, $max_rows) as $artifact_id) {
                $artifact      = $this->artifact_factory->getArtifactById((int)$artifact_id);
                $soap_artifact = $artifact->getSoapValue($user);
                if (count($soap_artifact)) {
                    $return['artifacts'][] = $soap_artifact;
                }
            }
        }
        return $return;
    }

    /**
     * Returns all artifacts that match criteria defined in reports
     *
     * @param String $session_key
     * @param Integer $report_id
     * @param Integer $offset
     * @param Integer $max_rows
     *
     * @return Array
     * @throws SoapFault
     */
    public function getArtifactsFromReport($session_key, $report_id, $offset, $max_rows) {
        try {
            $current_user = $this->soap_request_validator->continueSession($session_key);
            $report = $this->report_factory->getReportById($report_id, $current_user->getId(), false);
            if ($report) {
                $this->checkUserCanViewTracker($report->getTracker(), $current_user);
                $matching = $report->getMatchingIds(null, true);
                return $this->artifactListToSoap($current_user, $matching['id'], $offset, $max_rows);
            } else {
                return new SoapFault(invalid_report, "You attempt to use a report that doesn't exist or you don't have access to");
            }
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }

    /**
     * getTrackerList - returns an array of Tracker that belongs to the project identified by group_id
     *
     * @param string $session_key the session hash associated with the session opened by the person who calls the service
     * @param int $group_id the ID of the group we want to retrieve the list of trackers
     * @return array the array of SOAPTracker that belongs to the project identified by $group_id, or a soap fault if group_id does not match with a valid project.
     */
    public function getTrackerList($session_key, $group_id) {
        try {
            $current_user = $this->soap_request_validator->continueSession($session_key);
            $project = $this->getProjectById($group_id, 'getTrackerList');
            $this->checkUserCanAccessProject($current_user, $project);

            // The function getTrackersByGroupId returns all trackers,
            // even those the user is NOT allowed to view -> we will filter in trackerlist_to_soap
            $trackers = $this->tracker_factory->getTrackersByGroupId($group_id);
            return $this->trackerlist_to_soap($trackers, $current_user);
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }

    /**
     * trackerlist_to_soap : return the soap ArrayOfTracker structure giving an array of PHP Tracker Object.
     * @access private
     *
     * WARNING : We check the permissions here : only the readable trackers are returned.
     *
     * @param array of Object{Tracker} $tf_arr the array of ArtifactTrackers to convert.
     * @return array the SOAPArrayOfTracker corresponding to the array of Trackers Object
     */
    private function trackerlist_to_soap($tf_arr, PFUser $user) {
        $return = array();
        foreach ($tf_arr as $tracker_id => $tracker) {

            // Check if this tracker is active (not deleted)
            if (!$tracker->isActive()) {
                return new SoapFault(get_tracker_fault, 'This tracker is no longer valid.', 'getTrackerList');
            }

            // Check if the user can view this tracker
            if ($tracker->userCanView($user)) {

                // get the reports description (light desc of reports)
                //$report_fact = new ArtifactReportFactory();
                /* if (!$report_fact || !is_object($report_fact)) {
                  return new SoapFault(get_artifact_type_fault, 'Could Not Get ArtifactReportFactory', 'getArtifactTrackers');
                  }
                  $reports_desc = artifactreportsdesc_to_soap($report_fact->getReports($at_arr[$i]->data_array['group_artifact_id'], $user_id)); */

                $return[] = array(
                    'tracker_id' => $tracker->getId(),
                    'group_id' => $tracker->getGroupID(),
                    'name' =>$tracker->getName(),
                    'description' => $tracker->getDescription(),
                    'item_name' => $tracker->getItemName()
                        /* 'reports_desc' => $reports_desc */
                );
            }
        }
        return $return;
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
        try {
            $current_user = $this->soap_request_validator->continueSession($session_key);
            $project = $this->getProjectById($group_id, 'getTrackerFields');
            $this->checkUserCanAccessProject($current_user, $project);

            $tracker = $this->getTrackerById($group_id, $tracker_id, 'getTrackerFields');

            // The function getTrackerFields returns all tracker fields,
            // even those the user is NOT allowed to view -> we will filter in trackerlist_to_soap
            $tracker_fields = $this->formelement_factory->getUsedFields($tracker);
            return $this->trackerfields_to_soap($current_user, $tracker, $tracker_fields);
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }

    /**
     * trackerfields_to_soap : return the soap ArrayOfTrackerField structure giving an array of PHP Tracker_FormElement_Field Object.
     * @access private
     *
     * WARNING : We check the permissions here : only the readable fields are returned.
     *
     * @param Tracker $tracker the tracker
     * @param array of Object{Field} $tracker_fields the array of TrackerFields to convert.
     * @return array the SOAPArrayOfTrackerField corresponding to the array of Tracker Fields Object
     */
    private function trackerfields_to_soap(PFUser $user, Tracker $tracker, $tracker_fields) {
        $return = array();
        foreach ($tracker_fields as $tracker_field) {
            if ($tracker_field->userCanRead($user) && $tracker_field->isCompatibleWithSoap()) {
                $return[] = array(
                    'tracker_id'  => $tracker->getId(),
                    'field_id'    => $tracker_field->getId(),
                    'short_name'  => $tracker_field->getName(),
                    'label'       => $tracker_field->getLabel(),
                    'type'        => $this->formelement_factory->getType($tracker_field),
                    'values'      => $tracker_field->getSoapAvailableValues(),
                    'binding'     => $tracker_field->getSoapBindingProperties(),
                    'permissions' => $tracker_field->exportCurrentUserPermissionsToSOAP($user)
                );
            }
        }
        return $return;
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
    public function getArtifact($session_key, $group_id, $tracker_id, $artifact_id) {
        try {
            $current_user = $this->soap_request_validator->continueSession($session_key);
            $artifact     = $this->getArtifactById($artifact_id, $current_user, 'getArtifact');
            return $artifact->getSoapValue($current_user);
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }

    private function checkUserCanViewArtifact(Tracker_Artifact $artifact, PFUser $user) {
        if (!$artifact->userCanView($user)) {
            throw new SoapFault(get_artifact_fault, 'Permission Denied: you cannot access this artifact');
        }

        $tracker = $artifact->getTracker();
        if (!$tracker) {
            throw new SoapFault(get_tracker_factory_fault, 'Could Not Get Tracker', 'getArtifact');
        }
        $this->getProjectById($tracker->getProject()->getGroupId(), 'getArtifact');

        $this->checkUserCanViewTracker($tracker, $user);
    }

    /**
     * @throws SoapFault if user can't view the tracker
     */
    private function checkUserCanCreateArtifact(Tracker $tracker, PFUser $user) {
        $this->checkUserCanAccessProject($user, $tracker->getProject());

        $this->checkUserCanViewTracker($tracker, $user);

        if (! $tracker->userCanSubmitArtifact($user)) {
            throw new Exception($GLOBALS['Language']->getText('plugin_tracker', 'submit_at_least_one_field'), (string)get_tracker_factory_fault);
        }
    }

    /**
     * @throws SoapFault if user can't view the tracker
     */
    private function checkUserCanViewTracker(Tracker $tracker, PFUser $user) {
        if (! $tracker->userCanView($user)) {
            throw new Exception('Permission Denied: You are not granted sufficient permission to perform this operation.', (string)get_tracker_factory_fault);
        }
        $this->checkUserCanAccessProject($user, $tracker->getProject());
    }

    /**
     * @throws SoapFault if user can't view the tracker
     */
    private function checkUserCanAdminTracker(PFUser $user, $tracker) {
        $this->checkUserCanViewTracker($tracker, $user);
        if (! $tracker->userIsAdmin($user)) {
            throw new SoapFault(user_is_not_tracker_admin,' Permission Denied: You are not granted sufficient permission to perform this operation.', 'getTrackerSemantic');
        }
    }

    /**
     * @throws SoapFault if user can't access the project
     */
    private function checkUserCanAccessProject(PFUser $user, Project $project) {
        if (! $this->tracker_manager->userCanAdminAllProjectTrackers($user)) {
            $this->soap_request_validator->assertUserCanAccessProject($user, $project);
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
        try {
            $user = $this->soap_request_validator->continueSession($session_key);
            $tracker = $this->getTrackerById($group_id, $tracker_id, 'addArtifact');
            $this->checkUserCanCreateArtifact($tracker, $user);

            $fields_data = $this->getArtifactDataFromSoapRequest($tracker, $value);
            $fields_data = $this->formelement_factory->getUsedFieldsWithDefaultValue($tracker, $fields_data, $user);

            if ($artifact = $this->artifact_factory->createArtifact($tracker, $fields_data, $user, null)) {
                return $artifact->getId();
            } else {
                if ($GLOBALS['Response']->feedbackHasErrors()) {
                    return new SoapFault(update_artifact_fault, $GLOBALS['Response']->getRawFeedback(), 'addArtifact');
                } else {
                    return new SoapFault(update_artifact_fault, 'Unknown error', 'addArtifact');
                }
            }
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }

    private function getArtifactDataFromSoapRequest(Tracker $tracker, $values, Tracker_Artifact $artifact = null) {
        $fields_data = array();
        foreach ($values as $field_value) {
            // field are identified by name, we need to retrieve the field id
            if ($field_value->field_name) {

                $field = $this->formelement_factory->getUsedFieldByName($tracker->getId(), $field_value->field_name);
                if ($field) {
                    $field_data = $field->getFieldDataFromSoapValue($field_value, $artifact);

                    if ($field_data !== null) {
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
                        throw new SoapFault(update_artifact_fault, 'Unknown value ' . print_r($field_value->field_value, true) . ' for field: ' . $field_value->field_name);
                    }
                } else {
                    throw new SoapFault(update_artifact_fault, 'Unknown field: ' . $field_value->field_name);
                }
            }
        }

        return $fields_data;
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
        try {
            $user = $this->soap_request_validator->continueSession($session_key);
            $artifact = $this->getArtifactById($artifact_id, $user, 'updateArtifact');

            try {
                $fields_data = $this->getArtifactDataFromSoapRequest($artifact->getTracker(), $value, $artifact);
            } catch (Exception $e) {
                return new SoapFault(update_artifact_fault, $e->getMessage());
            }

            try {
                $artifact->createNewChangeset($fields_data, $comment, $user,  $send_notification = true, $comment_format);
                return $artifact_id;
            } catch (Tracker_NoChangeException $e) {
                return $artifact_id;
            } catch (Tracker_Exception $e) {
                $GLOBALS['Response']->addFeedback('error', $e->getMessage());
            }

            if ($GLOBALS['Response']->feedbackHasErrors()) {
                return new SoapFault(update_artifact_fault, $GLOBALS['Response']->getRawFeedback(), 'updateArtifact');
            } else {
                return new SoapFault(update_artifact_fault, 'Unknown error', 'updateArtifact');
            }
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }

    /**
     * getTrackerStructure - returns the structure of a tracker specified by $tracker_id in soap format.
     * @param type $session_key the session hash associated with the session opened by the person who calls the service
     * @param type $group_id the ID of the group we want to retrieve the semantic
     * @param type $tracker_id the ID of the tracker we want to retrieve the semantic
     * @return array{SOAPTrackerSemantic} the array of the semantic.
     * @throws SoapFault in case of failure.
     */
    public function getTrackerStructure($session_key, $group_id, $tracker_id) {
        try {
            $user      = $this->soap_request_validator->continueSession($session_key);
            $tracker   = $this->getTrackerById($group_id, $tracker_id, 'getTrackerSemantic');
            $this->checkUserCanViewTracker($tracker, $user);
            return array(
                'semantic' => $this->getTrackerSemantic($user, $tracker),
                'workflow' => $this->getTrackerWorkflow($user, $tracker),
            );
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }

    protected function getTrackerSemantic(PFUser $user, Tracker $tracker) {
        $tracker_semantic_manager = new Tracker_SemanticManager($tracker);
        return $tracker_semantic_manager->exportToSOAP($user);
    }

    protected function getTrackerWorkflow (PFUser $user, Tracker $tracker) {
        return $tracker->getWorkflow()->exportToSOAP($user);
    }

    /**
     * List all reports the user can access/run (both project reports and personnal reports)
     *
     * @param String  $session_key
     * @param Integer $group_id
     * @param Integer $tracker_id
     *
     * @return Array
     *
     * @throws SoapFault
     */
    public function getTrackerReports($session_key, $group_id, $tracker_id) {
        try {
            $current_user      = $this->soap_request_validator->continueSession($session_key);
            $tracker = $this->tracker_factory->getTrackerById($tracker_id);
            $this->checkUserCanViewTracker($tracker, $current_user);

            return $this->report_factory->exportToSoap($tracker, $current_user);
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }

    /**
     * Return a part of the requested attachment, base64 encoded.
     *
     * @param String  $session_key
     * @param Integer $artifact_id
     * @param Integer $attachment_id
     * @param Integer $offset
     * @param Integer $size
     *
     * @return String
     */
    public function getArtifactAttachmentChunk($session_key, $artifact_id, $attachment_id, $offset, $size) {
        try {
            $current_user = $this->soap_request_validator->continueSession($session_key);
            $artifact     = $this->getArtifactById($artifact_id, $current_user, 'getArtifactAttachmentChunk');

            $file_info = $this->fileinfo_factory->getById($attachment_id);
            if ($file_info && $file_info->fileExists()) {
                $field = $file_info->getField();
                if ($field->userCanRead($current_user)) {
                    return $file_info->getContent($offset, $size);
                } else {
                    return new SoapFault(invalid_field_fault, 'Permission denied: you cannot access this field');
                }
            } else {
                return new SoapFault(invalid_field_fault, 'Permission denied: you cannot access this field');
            }
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }

    /**
     * Provision a file name for a future upload
     *
     * @param String $session_key
     *
     * @return String Name (UUID) of the provisioned file
     */
    public function createTemporaryAttachment($session_key) {
        try {
            $current_user = $this->soap_request_validator->continueSession($session_key);
            $temporary    = new Tracker_SOAP_TemporaryFile($current_user);
            return $temporary->getUniqueFileName();
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }

    /**
     * Upload base64 encoded content of file (appened at the end of file)
     *
     * @param String $session_key
     * @param String $attachment_name (generated wuth createTemporaryAttachment)
     * @param String $content
     *
     * @return Integer the length written on disk
     */
    public function appendTemporaryAttachmentChunk($session_key, $attachment_name, $content) {
        try {
            $current_user = $this->soap_request_validator->continueSession($session_key);
            $temporary    = new Tracker_SOAP_TemporaryFile($current_user, $attachment_name);
            return $temporary->appendChunk($content);
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }

    /**
     * Remove existing temporary files for a user
     *
     * @param String $session_key
     *
     * @return Boolean
     */
    public function purgeAllTemporaryAttachments($session_key) {
        try {
            $current_user = $this->soap_request_validator->continueSession($session_key);
            $temporary    = new Tracker_SOAP_TemporaryFile($current_user);
            return $temporary->purgeAllTemporaryFiles();
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }

    /**
     * Returns comments of a given artifact
     *
     * @param String $session_key
     * @param int $artifact_id
     */
    public function getArtifactComments($session_key, $artifact_id) {
        try {
            $current_user = $this->soap_request_validator->continueSession($session_key);
            $artifact     = $this->getArtifactById($artifact_id, $current_user, __FUNCTION__);
            return $artifact->exportCommentsToSOAP();
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }

    /**
     * Returns full history of artifact with values at each changeset
     *
     * @param String $session_key
     * @param int $artifact_id
     */
    public function getArtifactHistory($session_key, $artifact_id) {
        try {
            $current_user = $this->soap_request_validator->continueSession($session_key);
            $artifact     = $this->getArtifactById($artifact_id, $current_user, __FUNCTION__);
            return $artifact->exportHistoryToSOAP($current_user);
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }

    /**
     * Add values to tracker's selectBox field
     *
     * @param String $session_key
     * @param int $tracker_id
     * @param int $field_id
     * @param ArrayOfString $values
     */
    public function addSelectBoxValues($session_key, $tracker_id, $field_id, $values) {
        try {
            $current_user = $this->soap_request_validator->continueSession($session_key);
            $tracker      = $this->tracker_factory->getTrackerById($tracker_id);
            if($tracker == null) {
              throw new SoapFault(add_selectbox_fields_fault, "Invalid tracker Id", "addSelectBoxValues");
            }

            $this->checkUserCanAdminTracker($current_user, $tracker);
            $field = $this->getStaticSelectBoxField($tracker, $field_id);

            return $this->createSelectBoxValues($field, $values);
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }

    /**
     * Get static selectbox field
     *
     * @param Tracker $tracker
     * @param int $field_id
     */
    private function getStaticSelectBoxField(Tracker $tracker, $field_id){
        $usedStaticFields = $this->formelement_factory->getUsedStaticSbFields($tracker);
        foreach($usedStaticFields as $staticField){
            if($staticField->getId() == $field_id){
                return $staticField;
            }
        }
        throw new SoapFault(check_field_fault, "Static selectbox Field not found", "getStaticSelectBoxField");
    }

    /**
     * Create values in selectBox field
     *
     * @param Tracker_FormElement_Field_Selectbox $field
     * @param Array $values
     */
    private function createSelectBoxValues(Tracker_FormElement_Field_Selectbox $field, $values) {
        $request            = new SOAPRequest(array());
        $concatenatedValues = implode("\n", $values);
        $bindValues['add'] = $concatenatedValues;
        $request->set('bind', $bindValues);

        try {
             $field->processSoap($request);
        } catch (Exception $e) {
             return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
        return true;
    }

    private function getProjectById($group_id, $method_name) {
        $project = $this->soap_request_validator->getProjectById($group_id, $method_name);
        if (! $project->usesService('plugin_tracker')) {
            throw new SoapFault(get_service_fault, 'Tracker service is not used for this project.', $method_name);
        }
        return $project;
    }

    private function getTrackerById($group_id, $tracker_id, $method_name) {
        $tracker = $this->tracker_factory->getTrackerById($tracker_id);
        if ($tracker == null) {
            throw new SoapFault(get_tracker_fault, 'Could not get Tracker.', $method_name);
        } elseif ($tracker->getGroupId() != $group_id) {
            throw new SoapFault(get_tracker_fault, 'Could not get Tracker.', $method_name);
        }
        return $tracker;
    }

    private function getArtifactById($artifact_id, $user, $method_name) {
        $artifact = $this->artifact_factory->getArtifactById($artifact_id);
        if (!$artifact) {
            throw new SoapFault(get_artifact_fault, 'Could Not Get Artifact', $method_name);
        }
        $this->checkUserCanViewArtifact($artifact, $user);
        return $artifact;
    }

}

?>

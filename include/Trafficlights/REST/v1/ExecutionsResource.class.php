<?php
/**
 * Copyright (c) Enalean, 2014 - 2016. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Trafficlights\REST\v1;

use Tracker_FormElement_InvalidFieldValueException;
use Tracker_REST_Artifact_ArtifactCreator;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\Header;
use Luracast\Restler\RestException;
use Tracker_ArtifactFactory;
use Tracker_Artifact;
use Tuleap\Tracker\REST\TrackerReference;
use Tuleap\Trafficlights\ArtifactDao;
use Tuleap\Trafficlights\ArtifactFactory;
use UserManager;
use PFUser;
use Tracker_FormElementFactory;
use Tracker_REST_Artifact_ArtifactUpdater;
use Tracker_REST_Artifact_ArtifactValidator;
use Tracker_FormElement_InvalidFieldException;
use Tracker_Exception;
use Tracker_NoChangeException;
use TrackerFactory;
use Tracker_URLVerification;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use Tracker_Artifact_ChangesetValue_Text;
use Tuleap\RealTime\NodeJSClient;
use Tracker_Permission_PermissionsSerializer;
use Tracker_Permission_PermissionRetrieveAssignee;
use Tuleap\RealTime\MessageDataPresenter;
use Tuleap\Trafficlights\TrafficlightsArtifactRightsPresenter;
use Tuleap\Trafficlights\ConfigConformanceValidator;
use Tuleap\Trafficlights\Config;
use Tuleap\Trafficlights\Dao;
use Tuleap\User\REST\UserRepresentation;

class ExecutionsResource {
    const FIELD_RESULTS      = 'results';
    const FIELD_STATUS       = 'status';
    const FIELD_TIME         = 'time';
    const HTTP_CLIENT_UUID   = 'HTTP_X_CLIENT_UUID';

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var ArtifactFactory */
    private $trafficlights_artifact_factory;

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var ExecutionRepresentationBuilder */
    private $execution_representation_builder;

    /** @var AssignedToRepresentationBuilder */
    private $assigned_to_representation_builder;

    /** @var NodeJSClient */
    private $node_js_client;

    /** @var Tracker_Permission_PermissionsSerializer */
    private $permissions_serializer;

    public function __construct() {
        $config                      = new Config(new Dao());
        $conformance_validator       = new ConfigConformanceValidator($config);

        $this->user_manager          = UserManager::instance();
        $this->tracker_factory       = TrackerFactory::instance();
        $this->formelement_factory   = Tracker_FormElementFactory::instance();
        $this->artifact_factory      = Tracker_ArtifactFactory::instance();
        $this->trafficlights_artifact_factory = new ArtifactFactory(
            $config,
            $conformance_validator,
            $this->artifact_factory,
            new ArtifactDao()
        );

        $this->assigned_to_representation_builder = new AssignedToRepresentationBuilder(
            $this->formelement_factory,
            $this->user_manager
        );

        $this->execution_representation_builder = new ExecutionRepresentationBuilder(
            $this->user_manager,
            $this->formelement_factory,
            $conformance_validator,
            $this->assigned_to_representation_builder
        );

        $this->node_js_client         = new NodeJSClient();
        $this->permissions_serializer = new Tracker_Permission_PermissionsSerializer(
            new Tracker_Permission_PermissionRetrieveAssignee(UserManager::instance())
        );
    }

    /**
     * @url OPTIONS
     */
    public function options() {
        Header::allowOptions();
    }

    /**
     * @url OPTIONS {id}/presences
     */
    public function optionsPresences() {
        Header::allowOptionsPatch();
    }

    /**
     * Create a test execution
     *
     * @url POST
     *
     * @param TrackerReference $tracker       Execution tracker of the execution {@from body}
     * @param int              $definition_id Definition of the execution {@from body}
     * @param string           $status        Status of the execution {@from body} {@choice notrun,passed,failed,blocked}
     * @param string           $results       Result of the execution {@from body}
     * @return ExecutionRepresentation
     *
     * @throws 400
     * @throws 404
     * @throws 500
     */
    protected function post(
        TrackerReference $tracker,
        $definition_id,
        $status,
        $time = 0,
        $results = ''
    ) {
        try {
            $user    = UserManager::instance()->getCurrentUser();
            $creator = new Tracker_REST_Artifact_ArtifactCreator(
                new Tracker_REST_Artifact_ArtifactValidator(
                    $this->formelement_factory
                ),
                $this->artifact_factory,
                $this->tracker_factory
            );

            $values = $this->getValuesByFieldsName($user, $tracker->id, $definition_id, $status, $time, $results);

            if (! empty($values)) {
                $artifact_reference = $creator->create($user, $tracker, $values);
            } else {
                throw new RestException(400, "No valid data are provided");
            }
        } catch (Tracker_FormElement_InvalidFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_FormElement_InvalidFieldValueException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_Exception $exception) {
            throw new RestException(500, $exception->getMessage());
        }

        $execution_representation = $this->execution_representation_builder->getExecutionRepresentation($user, $artifact_reference->getArtifact());

        $this->sendAllowHeadersForExecutionPost($artifact_reference->getArtifact());

        return $execution_representation;
    }

    /**
     * Update a test exception
     *
     * @url PUT {id}
     *
     * @param string $id      Id of the artifact
     * @param string $status  Status of the execution {@from body} {@choice notrun,passed,failed,blocked}
     * @param int    $time    Time to pass the execution {@from body}
     * @param string $results Result of the execution {@from body}
     * @return ExecutionRepresentation
     *
     * @throws 400
     * @throws 500
     */
    protected function putId($id, $status, $time = 0, $results = '') {
        $previous_status = '';
        $previous_user   = '';
        try {
            $user            = UserManager::instance()->getCurrentUser();
            $artifact        = $this->getArtifactById($user, $id);
            $changes         = $this->getChanges($status, $time, $results, $artifact, $user);
            $previous_status = $this->getPreviousStatus($artifact);
            $previous_user   = $this->getPreviousSubmittedBy($artifact);

            $updater = new Tracker_REST_Artifact_ArtifactUpdater(
                new Tracker_REST_Artifact_ArtifactValidator(
                    $this->formelement_factory
                )
            );

            $updater->update($user, $artifact, $changes);
        } catch (Tracker_FormElement_InvalidFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_NoChangeException $exception) {
            // Do nothing
        } catch (Tracker_Exception $exception) {
            if ($GLOBALS['Response']->feedbackHasErrors()) {
                throw new RestException(500, $GLOBALS['Response']->getRawFeedback());
            }
            throw new RestException(500, $exception->getMessage());
        }

        $execution_representation = $this->execution_representation_builder->getExecutionRepresentation($user, $artifact);

        if(isset($_SERVER[self::HTTP_CLIENT_UUID]) && $_SERVER[self::HTTP_CLIENT_UUID]) {
            $user_representation = new UserRepresentation();
            $user_representation->build($user);
            $data = array(
                'artifact'        => $execution_representation,
                'previous_status' => $previous_status,
                'user'            => $user_representation,
                'previous_user'   => $previous_user
            );
            $rights   = new TrafficlightsArtifactRightsPresenter($artifact, $this->permissions_serializer);
            $campaign = $this->trafficlights_artifact_factory->getCampaignForExecution($user, $artifact);
            $message  = new MessageDataPresenter(
                $user->getId(),
                $_SERVER[self::HTTP_CLIENT_UUID],
                'trafficlights_' . $campaign->getId(),
                $rights,
                'trafficlights_execution:update',
                $data
            );

            $this->node_js_client->sendMessage($message);
        }

        $this->sendAllowHeadersForExecutionPut($artifact);

        return $execution_representation;
    }

    /**
     * User views a test execution
     *
     * @url PATCH {id}/presences
     *
     * @param string $id           Id of the artifact
     * @param string $uuid         Uuid of current user {@from body}
     * @param string $remove_from  Id of the old artifact {@from body}
     *
     * @throws 404
     */
    protected function presences($id, $uuid, $remove_from = '') {
        $user = UserManager::instance()->getCurrentUser();
        $artifact = $this->getArtifactById($user, $id);

        if(! $artifact) {
            throw new RestException(404);
        }

        if(isset($_SERVER[self::HTTP_CLIENT_UUID]) && $_SERVER[self::HTTP_CLIENT_UUID]) {
            $user_representation = new UserRepresentation();
            $user_representation->build($user);
            $data = array(
                'presence' => array(
                    'execution_id' => $id,
                    'uuid'         => $uuid,
                    'remove_from'  => $remove_from,
                    'user'         => $user_representation
                )
            );
            $rights   = new TrafficlightsArtifactRightsPresenter($artifact, $this->permissions_serializer);
            $campaign = $this->trafficlights_artifact_factory->getCampaignForExecution($user, $artifact);
            $message  = new MessageDataPresenter(
                $user->getId(),
                $_SERVER[self::HTTP_CLIENT_UUID],
                'trafficlights_' . $campaign->getId(),
                $rights,
                'trafficlights_user:presence',
                $data
            );

            $this->node_js_client->sendMessage($message);
        }

        $this->optionsPresences();
    }

    /** @return array */
    private function getChanges(
        $status,
        $time,
        $results,
        Tracker_Artifact $artifact,
        PFUser $user
    ) {
        $changes = array();

        $status_value = $this->getFormattedChangesetValueForFieldList(
            self::FIELD_STATUS,
            $status,
            $artifact,
            $user
        );
        if ($status_value) {
            $changes[] = $status_value;
        }

        if (get_magic_quotes_gpc()) {
            $results = stripslashes($results);
        }
        $result_value = $this->getFormattedChangesetValueForFieldText(
            self::FIELD_RESULTS,
            $results,
            $artifact,
            $user
        );
        if ($result_value) {
            $changes[] = $result_value;
        }

        if ($time !== 0) {
            $time_value = $this->getFormattedChangesetValueForFieldInt(
                self::FIELD_TIME,
                $time,
                $artifact,
                $user
            );
            if ($time_value) {
                $changes[] = $time_value;
            }
        }

        return $changes;
    }

    private function getFormattedChangesetValueForFieldList(
        $field_name,
        $value,
        Tracker_Artifact $artifact,
        PFUser $user
    ) {
        $field = $this->getFieldByName($field_name, $artifact->getTrackerId(), $user);
        if (! $field) {
            return null;
        }

        $binds = $field->getBind()->getValuesByKeyword($value);
        $bind = array_pop($binds);
        if (! $bind) {
            throw new RestException(400, 'Invalid status value');
        }

        $value_representation                 = new ArtifactValuesRepresentation();
        $value_representation->field_id       = (int) $field->getId();
        $value_representation->bind_value_ids = array((int) $bind->getId());

        return $value_representation;
    }

    private function getFormattedChangesetValueForFieldText(
        $field_name,
        $value,
        $artifact,
        $user
    ) {
        $field = $this->getFieldByName($field_name, $artifact->getTrackerId(), $user);
        if (! $field) {
            return null;
        }

        $value_representation           = new ArtifactValuesRepresentation();
        $value_representation->field_id = (int) $field->getId();
        $value_representation->value    = array(
            'format'  => Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT,
            'content' => $value
        );

        return $value_representation;
    }

    private function getFormattedChangesetValueForFieldInt(
        $field_name,
        $value,
        $artifact,
        $user
    ) {
        $field = $this->getFieldByName($field_name, $artifact->getTrackerId(), $user);
        if (! $field) {
            return null;
        }

        $value_representation           = new ArtifactValuesRepresentation();
        $value_representation->field_id = (int) $field->getId();
        $value_representation->value    = $value;

        return $value_representation;
    }

    private function getFieldByName($field_name, $tracker_id, $user) {
        return  $this->formelement_factory->getUsedFieldByNameForUser(
            $tracker_id,
            $field_name,
            $user
        );
    }

    /**
     * @param int $id
     *
     * @return Tracker_Artifact
     */
    private function getArtifactById(PFUser $user, $id) {
        $artifact = $this->trafficlights_artifact_factory->getArtifactByIdUserCanView($user, $id);
        if ($artifact) {
            ProjectAuthorization::userCanAccessProject(
                $user,
                $artifact->getTracker()->getProject(),
                new Tracker_URLVerification()
            );
            return $artifact;
        }
        throw new RestException(404);
    }

    /** @return string */
    private function getPreviousStatus(Tracker_Artifact $artifact) {
        $last_changeset = $artifact->getLastChangeset();
        if (! $last_changeset) {
            return null;
        }

        return $artifact->getStatusForChangeset($last_changeset);
    }

    /** @return UserRepresentation */
    private function getPreviousSubmittedBy(Tracker_Artifact $artifact) {
        $last_changeset = $artifact->getLastChangeset();
        if (! $last_changeset) {
            return null;
        }

        $submitted_by = $this->user_manager->getUserById($last_changeset->getSubmittedBy());
        $user_representation = new UserRepresentation();
        $user_representation->build($submitted_by);

        return $user_representation;
    }

    /** @return array */
    private function getValuesByFieldsName(
        PFUser $user,
        $tracker_id,
        $definition_id,
        $status,
        $time,
        $results
    ) {
        $status_field         = $this->getFieldByName(ExecutionRepresentation::FIELD_STATUS, $tracker_id, $user);
        $time_field           = $this->getFieldByName(ExecutionRepresentation::FIELD_TIME, $tracker_id, $user);
        $results_field        = $this->getFieldByName(ExecutionRepresentation::FIELD_RESULTS, $tracker_id, $user);
        $artifact_links_field = $this->getFieldByName(ExecutionRepresentation::FIELD_ARTIFACT_LINKS, $tracker_id, $user);

        $status_field_binds      = $status_field->getBind()->getValuesByKeyword($status);
        $status_field_bind       = array_pop($status_field_binds);

        $values = array();

        $values[] = $this->createArtifactValuesRepresentation(
            intval($status_field->getId()),
            array(
                (int) $status_field_bind->getId()
            ),
            'bind_value_ids'
        );

        $values[] = $this->createArtifactValuesRepresentation(
            intval($time_field->getId()),
            $time,
            'value'
        );

        $values[] = $this->createArtifactValuesRepresentation(
            intval($results_field->getId()),
            $results,
            'value'
        );

        $values[] = $this->createArtifactValuesRepresentation(
            intval($artifact_links_field->getId()),
            array(
                array('id' => $definition_id)
            ),
            'links'
        );

        return $values;
    }

    private function createArtifactValuesRepresentation($field_id, $value, $key)
    {
        $artifact_values_representation           = new ArtifactValuesRepresentation();
        $artifact_values_representation->field_id = $field_id;
        if ($key == 'value') {
            $artifact_values_representation->value = $value;
        } else if ($key == 'bind_value_ids') {
            $artifact_values_representation->bind_value_ids = $value;
        } else if ($key == 'links') {
            $artifact_values_representation->links = $value;
        }

        return $artifact_values_representation;
    }

    private function sendAllowHeadersForExecutionPut(Tracker_Artifact $artifact)
    {
        $date = $artifact->getLastUpdateDate();
        Header::allowOptionsPut();
        Header::lastModified($date);
    }

    private function sendAllowHeadersForExecutionPost(Tracker_Artifact $artifact)
    {
        $date = $artifact->getLastUpdateDate();
        Header::allowOptionsPost();
        Header::lastModified($date);
    }
}

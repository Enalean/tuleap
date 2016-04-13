<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\Header;
use Luracast\Restler\RestException;
use Tracker_ArtifactFactory;
use Tracker_Artifact;
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

class ExecutionsResource {
    const FIELD_RESULTS      = 'results';
    const FIELD_STATUS       = 'status';
    const HTTP_CLIENT_UUID   = 'HTTP_X_CLIENT_UUID';

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var ExecutionRepresentationBuilder */
    private $execution_representation_builder;

    /** @var AssignedToRepresentationBuilder */
    private $assigned_to_representation_builder;

    /** @var ConfigConformanceValidator */
    private $conformance_validator;

    /** @var NodeJSClient */
    private $node_js_client;

    /** @var Tracker_Permission_PermissionsSerializer */
    private $permissions_serializer;

    public function __construct() {
        $this->user_manager          = UserManager::instance();
        $this->tracker_factory       = TrackerFactory::instance();
        $this->formelement_factory   = Tracker_FormElementFactory::instance();
        $this->artifact_factory      = Tracker_ArtifactFactory::instance();
        $config                      = new Config(new Dao());
        $this->conformance_validator = new ConfigConformanceValidator(
            $config
        );

        $this->assigned_to_representation_builder = new AssignedToRepresentationBuilder(
            $this->formelement_factory,
            $this->user_manager
        );

        $this->execution_representation_builder = new ExecutionRepresentationBuilder(
            $this->user_manager,
            $this->formelement_factory,
            $this->conformance_validator,
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
     * @url OPTIONS {id}
     */
    public function optionsId($id) {
        Header::allowOptionsPut();
    }

    /**
     * @url OPTIONS {id}/presences
     */
    public function optionsPresences() {
        Header::allowOptionsPatch();
    }

    /**
     * Update a test exception
     *
     * @url PUT {id}
     *
     * @param string $id     Id of the artifact
     * @param string $status Status of the execution {@from body} {@choice notrun,passed,failed,blocked}
     * @param string $results Result of the execution {@from body}
     *
     * @throws 400
     * @throws 500
     */
    protected function putId($id, $status, $results = '') {
        try {
            $user     = UserManager::instance()->getCurrentUser();
            $artifact = $this->getArtifactById($user, $id);
            $changes  = $this->getChanges($status, $results, $artifact, $user);

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
            $data = array(
                'artifact' => $execution_representation
            );
            $rights   = new TrafficlightsArtifactRightsPresenter($artifact, $this->permissions_serializer);
            $message  = new MessageDataPresenter(
                $user->getId(),
                $_SERVER[self::HTTP_CLIENT_UUID],
                'trafficlights_' . $artifact->getParent($user)->getId(),
                $rights,
                'trafficlights_execution:update',
                $data
            );

            $this->node_js_client->sendMessage($message);
        }

        $this->sendAllowHeadersForExecution($artifact);

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
            $data = array(
                'presence' => array(
                    'execution_id' => $id,
                    'uuid'         => $uuid,
                    'remove_from'  => $remove_from,
                    'user'         => array(
                        'id'         => $user->getId(),
                        'real_name'  => $user->getRealName(),
                        'avatar_url' => $user->getAvatarUrl()
                    )
                )
            );
            $rights   = new TrafficlightsArtifactRightsPresenter($artifact, $this->permissions_serializer);
            $message  = new MessageDataPresenter(
                $user->getId(),
                $_SERVER[self::HTTP_CLIENT_UUID],
                'trafficlights_' . $artifact->getParent($user)->getId(),
                $rights,
                'trafficlights_user:presence',
                $data
            );

            $this->node_js_client->sendMessage($message);
        }

        Header::allowOptionsPatch();
    }

    /** @return array */
    private function getChanges(
        $status,
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

        return $changes;
    }

    private function getFormattedChangesetValueForFieldList(
        $field_name,
        $value,
        Tracker_Artifact $artifact,
        PFUser $user
    ) {
        $field = $this->getFieldByName($field_name, $artifact, $user);
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
        $field = $this->getFieldByName($field_name, $artifact, $user);
        if (! $field) {
            return null;
        }

        $value_representation                 = new ArtifactValuesRepresentation();
        $value_representation->field_id       = (int) $field->getId();
        $value_representation->value = array(
            'format'  => Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT,
            'content' => $value
        );

        return $value_representation;
    }

    private function getFieldByName($field_name, $artifact, $user) {
        $tracker_id = $artifact->getTrackerId();

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
        $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $id);
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

    private function sendAllowHeadersForExecution(Tracker_Artifact $artifact) {
        $date = $artifact->getLastUpdateDate();
        Header::allowOptionsPut();
        Header::lastModified($date);
    }
}

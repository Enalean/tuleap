<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1;

use BackendLogger;
use Luracast\Restler\RestException;
use PFUser;
use Tracker_Artifact;
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_ArtifactFactory;
use Tracker_Exception;
use Tracker_FormElement_Field_List_Bind;
use Tracker_FormElement_InvalidFieldException;
use Tracker_FormElement_InvalidFieldValueException;
use Tracker_FormElementFactory;
use Tracker_NoChangeException;
use Tracker_Permission_PermissionRetrieveAssignee;
use Tracker_Permission_PermissionsSerializer;
use Tracker_REST_Artifact_ArtifactCreator;
use Tracker_REST_Artifact_ArtifactUpdater;
use Tracker_REST_Artifact_ArtifactValidator;
use Tracker_URLVerification;
use TrackerFactory;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\RealTime\NodeJSClient;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\TestManagement\ArtifactDao;
use Tuleap\TestManagement\ArtifactFactory;
use Tuleap\TestManagement\Campaign\Execution\DefinitionForExecutionRetriever;
use Tuleap\TestManagement\Campaign\Execution\DefinitionNotFoundException;
use Tuleap\TestManagement\Campaign\Execution\ExecutionDao;
use Tuleap\TestManagement\Config;
use Tuleap\TestManagement\ConfigConformanceValidator;
use Tuleap\TestManagement\Dao;
use Tuleap\TestManagement\RealTime\RealTimeMessageSender;
use Tuleap\TestManagement\REST\v1\Execution\StepsResultsFilter;
use Tuleap\TestManagement\REST\v1\Execution\StepsResultsRepresentationBuilder;
use Tuleap\Tracker\RealTime\RealTimeArtifactMessageSender;
use Tuleap\Tracker\REST\ChangesetCommentRepresentation;
use Tuleap\Tracker\REST\TrackerReference;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use UserManager;

class ExecutionsResource
{
    public const FIELD_RESULTS = 'results';
    public const FIELD_STATUS  = 'status';
    public const FIELD_TIME    = 'time';

    /** @var Config */
    private $config;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var ArtifactFactory */
    private $testmanagement_artifact_factory;

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var ExecutionRepresentationBuilder */
    private $execution_representation_builder;

    /** @var RealTimeMessageSender  */
    private $realtime_message_sender;

    /** @var ExecutionDao */
    private $execution_dao;

    /** @var DefinitionForExecutionRetriever */
    private $definition_retriever;

    /** @var Tracker_REST_Artifact_ArtifactUpdater */
    private $artifact_updater;

    /** @var UserManager */
    private $user_manager;

    /** @var ExecutionStatusUpdater */
    private $execution_status_updater;

    /** @var StepsResultsChangesBuilder */
    private $steps_results_changes_builder;

    public function __construct()
    {
        $this->config          = new Config(new Dao(), TrackerFactory::instance());
        $conformance_validator = new ConfigConformanceValidator($this->config);

        $this->user_manager                    = UserManager::instance();
        $this->tracker_factory                 = TrackerFactory::instance();
        $this->formelement_factory             = Tracker_FormElementFactory::instance();
        $this->artifact_factory                = Tracker_ArtifactFactory::instance();
        $artifact_dao                          = new ArtifactDao();

        $this->testmanagement_artifact_factory = new ArtifactFactory(
            $this->config,
            $this->artifact_factory,
            $artifact_dao
        );

        $assigned_to_representation_builder = new AssignedToRepresentationBuilder(
            $this->formelement_factory,
            $this->user_manager
        );

        $requirement_retriever = new RequirementRetriever($this->artifact_factory, $artifact_dao, $this->config);

        $this->definition_retriever             = new DefinitionForExecutionRetriever($conformance_validator);
        $this->execution_dao                    = new ExecutionDao();
        $steps_results_representation_builder   = new StepsResultsRepresentationBuilder(
            $this->formelement_factory,
            new StepsResultsFilter()
        );
        $this->execution_representation_builder = new ExecutionRepresentationBuilder(
            $this->user_manager,
            $this->formelement_factory,
            $conformance_validator,
            $assigned_to_representation_builder,
            new ArtifactDao(),
            $this->artifact_factory,
            $requirement_retriever,
            $this->definition_retriever,
            $this->execution_dao,
            $steps_results_representation_builder,
            \Codendi_HTMLPurifier::instance()
        );

        $node_js_client         = new NodeJSClient(
            HttpClientFactory::createClient(),
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            new BackendLogger()
        );
        $permissions_serializer = new Tracker_Permission_PermissionsSerializer(
            new Tracker_Permission_PermissionRetrieveAssignee(UserManager::instance())
        );
        $artifact_message_sender = new RealTimeArtifactMessageSender(
            $node_js_client,
            $permissions_serializer
        );

        $this->realtime_message_sender = new RealTimeMessageSender(
            $node_js_client,
            $permissions_serializer,
            $artifact_message_sender
        );

        $this->artifact_updater = new Tracker_REST_Artifact_ArtifactUpdater(
            new Tracker_REST_Artifact_ArtifactValidator(
                $this->formelement_factory
            )
        );

        $this->steps_results_changes_builder = new StepsResultsChangesBuilder(
            $this->formelement_factory,
            $this->execution_dao,
            new TestStatusAccordingToStepsStatusChangesBuilder()
        );

        $this->execution_status_updater = new ExecutionStatusUpdater(
            $this->artifact_updater,
            $this->testmanagement_artifact_factory,
            $this->realtime_message_sender,
            $this->user_manager
        );
    }

    /**
     * @url OPTIONS
     *
     */
    public function options(): void
    {
        Header::allowOptions();
    }

    /**
     * @url OPTIONS {id}/presences
     *
     */
    public function optionsPresences(string $id): void
    {
        Header::allowOptionsPatch();
    }

    /**
     * @url OPTIONS {id}/issues
     *
     */
    public function optionsIssues(string $id): void
    {
        Header::allowOptionsPatch();
    }

    /**
     * @url OPTIONS {id}
     *
     */
    public function optionsId(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get execution
     *
     * Get testing execution by its id
     *
     * @url GET {id}
     *
     * @param int $id Id of the execution
     * @return ExecutionRepresentation
     * @throws RestException 400
     * @throws RestException 404
     */
    protected function getId(int $id)
    {
        $this->optionsId($id);

        $user     = $this->getCurrentUser();
        $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $id);
        if (! $artifact) {
            throw new RestException(404);
        }

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $artifact->getTracker()->getProject()
        );

        return $this->getExecutionRepresentation($user, $artifact);
    }

    /**
     * Create a test execution
     *
     * @url POST
     *
     * @param TrackerReference $tracker_reference       Execution tracker of the execution {@from body}
     * @param int              $definition_id Definition of the execution {@from body}
     * @param string           $status        Status of the execution {@from body} {@choice notrun,passed,failed,blocked}
     * @param string           $results       Result of the execution {@from body}
     * @return ExecutionRepresentation
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 500
     */
    protected function post(
        TrackerReference $tracker_reference,
        $definition_id,
        $status,
        int $time = 0,
        $results = ''
    ) {
        $tracker = $this->tracker_factory->getTrackerById($tracker_reference->id);
        if ($tracker === null) {
            throw new RestException(404);
        }
        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $tracker->getProject()
        );

        try {
            $user    = $this->getCurrentUser();
            $creator = new Tracker_REST_Artifact_ArtifactCreator(
                new Tracker_REST_Artifact_ArtifactValidator(
                    $this->formelement_factory
                ),
                $this->artifact_factory,
                $this->tracker_factory
            );

            $values = $this->getValuesByFieldsName($user, $tracker_reference->id, $definition_id, $status, $time, $results);

            if (! empty($values)) {
                $artifact_reference = $creator->create($user, $tracker_reference, $values);
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

        $this->sendAllowHeadersForExecutionPost($artifact_reference->getArtifact());

        return $this->getExecutionRepresentation($user, $artifact_reference->getArtifact());
    }

    /**
     * Update part of a test execution
     *
     * @url PATCH {id}
     *
     * @param string                       $id   Id of the execution artifact
     * @param PATCHExecutionRepresentation $body Actions to performs on the execution {@from body}
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 500
     *
     */
    public function patchId($id, PATCHExecutionRepresentation $body): void
    {
        $user               = $this->getCurrentUser();
        $execution_artifact = $this->getArtifactById($user, (int) $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $execution_artifact->getTracker()->getProject()
        );

        if (! $execution_artifact->userCanUpdate($user)) {
            throw new RestException(403);
        }

        $definition_artifact = $this->getDefinitionOfExecution($user, $execution_artifact);

        $last_changeset = $definition_artifact->getLastChangeset();
        if ($body->force_use_latest_definition_version && $last_changeset) {
            $this->execution_dao->updateExecutionToUseLatestVersionOfDefinition(
                $id,
                $last_changeset->getId()
            );
        }

        if ($body->steps_results) {
            $this->execution_status_updater->update(
                $execution_artifact,
                $this->steps_results_changes_builder->getStepsChanges(
                    $body->steps_results,
                    $execution_artifact,
                    $definition_artifact,
                    $user
                ),
                $user
            );
        }
    }

    /**
     * Update a test execution
     *
     * @url PUT {id}
     *
     * @param string $id      Id of the artifact
     * @param string $status  Status of the execution {@from body} {@choice notrun,passed,failed,blocked}
     * @param int    $time    Time to pass the execution {@from body}
     * @param string $results Result of the execution {@from body}
     * @return ExecutionRepresentation
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 500
     */
    protected function putId($id, $status, $time = 0, $results = '')
    {
        $user     = $this->getCurrentUser();
        $artifact = $this->getArtifactById($user, (int) $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $artifact->getTracker()->getProject()
        );

        $this->execution_status_updater->update(
            $artifact,
            $this->getChanges($status, $time, $results, $artifact, $user),
            $user
        );

        $this->sendAllowHeadersForExecutionPut($artifact);

        return $this->getExecutionRepresentation($user, $artifact);
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
     * @throws RestException 404
     *
     */
    protected function presences($id, $uuid, $remove_from = ''): void
    {
        $user = $this->getCurrentUser();
        $artifact = $this->getArtifactById($user, (int) $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $artifact->getTracker()->getProject()
        );

        $campaign = $this->testmanagement_artifact_factory->getCampaignForExecution($artifact);
        if ($campaign) {
            $this->realtime_message_sender->sendPresences($campaign, $artifact, $user, $uuid, $remove_from);
        }

        $this->optionsPresences($id);
    }

    /**
     * Create an artifact link between an issue and a test execution
     *
     * @url PATCH {id}/issues
     *
     * @param string                         $id        Id of the test execution artifact
     * @param string                         $issue_id  Id of the issue artifact {@from body}
     * @param ChangesetCommentRepresentation $comment   Comment describing the test execution {body, format} {@from body}
     *
     * @throws RestException 400
     * @throws RestException 404
     * @throws RestException 500
     *
     */
    protected function patchIssueLink($id, $issue_id, ?ChangesetCommentRepresentation $comment = null): void
    {
        $user               = $this->getCurrentUser();
        $execution_artifact = $this->getArtifactById($user, (int) $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $execution_artifact->getTracker()->getProject()
        );

        $issue_artifact = $this->getArtifactById($user, (int) $issue_id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $issue_artifact->getTracker()->getProject()
        );

        if (! $execution_artifact || ! $issue_artifact) {
            throw new RestException(404);
        }

        $tracker = $issue_artifact->getTracker();
        if ($tracker->getId() !== $this->config->getIssueTrackerId($tracker->getProject())) {
            throw new RestException(400, 'The given artifact does not belong to issue tracker');
        }

        $is_linked = $execution_artifact->linkArtifact($issue_artifact->getId(), $user);
        if (! $is_linked) {
            throw new RestException(400, 'Could not link the issue artifact to the test execution');
        }

        $campaign = $this->testmanagement_artifact_factory->getCampaignForExecution($execution_artifact);
        if ($campaign) {
            $this->realtime_message_sender->sendArtifactLinkAdded(
                $user,
                $campaign,
                $execution_artifact,
                $issue_artifact
            );
        }

        try {
            $this->artifact_updater->update($user, $issue_artifact, array(), $comment);
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

        $this->optionsIssues($id);
    }

    /**
     * @return array
     */
    private function getChanges(
        string $status,
        int $time,
        string $results,
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
        string $field_name,
        string $value,
        Tracker_Artifact $artifact,
        PFUser $user
    ): ?ArtifactValuesRepresentation {
        $field = $this->getFieldByName($field_name, $artifact->getTrackerId(), $user);
        if (! $field) {
            return null;
        }

        $binds = [];
        assert($field instanceof \Tracker_FormElement_Field_List);
        if ($field->getBind()) {
            $binds = $field->getBind()->getValuesByKeyword($value);
        }
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
        string $field_name,
        string $value,
        Tracker_Artifact $artifact,
        PFUser $user
    ): ?ArtifactValuesRepresentation {
        $field = $this->getFieldByName($field_name, $artifact->getTrackerId(), $user);
        if (! $field) {
            return null;
        }

        $value_representation           = new ArtifactValuesRepresentation();
        $value_representation->field_id = (int) $field->getId();
        $value_representation->value    = array(
            'format'  => Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT,
            'content' => $value
        );

        return $value_representation;
    }

    private function getFormattedChangesetValueForFieldInt(
        string $field_name,
        int $value,
        Tracker_Artifact $artifact,
        PFUser $user
    ): ?ArtifactValuesRepresentation {
        $field = $this->getFieldByName($field_name, $artifact->getTrackerId(), $user);
        if (! $field) {
            return null;
        }

        $value_representation           = new ArtifactValuesRepresentation();
        $value_representation->field_id = (int) $field->getId();
        $value_representation->value    = $value;

        return $value_representation;
    }

    private function getFieldByName(string $field_name, int $tracker_id, PFUser $user): ?\Tracker_FormElement_Field
    {
        return  $this->formelement_factory->getUsedFieldByNameForUser(
            $tracker_id,
            $field_name,
            $user
        );
    }

    private function getArtifactById(PFUser $user, int $id): Tracker_Artifact
    {
        $artifact = $this->testmanagement_artifact_factory->getArtifactByIdUserCanView($user, $id);
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

    /**
     * @return array
     */
    private function getValuesByFieldsName(
        PFUser $user,
        int $tracker_id,
        int $definition_id,
        string $status,
        int $time,
        string $results
    ) {
        $status_field         = $this->getFieldByName(ExecutionRepresentation::FIELD_STATUS, $tracker_id, $user);
        $time_field           = $this->getFieldByName(ExecutionRepresentation::FIELD_TIME, $tracker_id, $user);
        $results_field        = $this->getFieldByName(ExecutionRepresentation::FIELD_RESULTS, $tracker_id, $user);
        $artifact_links_field = $this->getFieldByName(ExecutionRepresentation::FIELD_ARTIFACT_LINKS, $tracker_id, $user);

        $values = array();

        if ($status_field) {
            $status_field_binds = [];
            assert($status_field instanceof \Tracker_FormElement_Field_List);
            $bind               = $status_field->getBind();
            if ($bind) {
                assert($bind instanceof Tracker_FormElement_Field_List_Bind);
                $status_field_binds = $bind->getValuesByKeyword($status);
            }
            $status_field_bind  = array_pop($status_field_binds);

            if ($status_field_bind !== null) {
                $values[] = $this->createArtifactValuesRepresentation(
                    intval($status_field->getId()),
                    [
                        (int) $status_field_bind->getId()
                    ],
                    'bind_value_ids'
                );
            }
        }

        if ($time_field) {
            $values[] = $this->createArtifactValuesRepresentation(
                intval($time_field->getId()),
                $time,
                'value'
            );
        }

        if ($results_field) {
            $values[] = $this->createArtifactValuesRepresentation(
                intval($results_field->getId()),
                $results,
                'value'
            );
        }

        if ($artifact_links_field) {
            $values[] = $this->createArtifactValuesRepresentation(
                intval($artifact_links_field->getId()),
                array(
                    array('id' => $definition_id)
                ),
                'links'
            );
        }

        return $values;
    }

    /**
     * @param mixed $value
     */
    private function createArtifactValuesRepresentation(int $field_id, $value, string $key): ArtifactValuesRepresentation
    {
        $artifact_values_representation           = new ArtifactValuesRepresentation();
        $artifact_values_representation->field_id = $field_id;
        if ($key === 'value') {
            $artifact_values_representation->value = $value;
        } elseif ($key === 'bind_value_ids') {
            $artifact_values_representation->bind_value_ids = $value;
        } elseif ($key === 'links') {
            $artifact_values_representation->links = $value;
        }

        return $artifact_values_representation;
    }

    private function sendAllowHeadersForExecutionPut(Tracker_Artifact $artifact): void
    {
        $date = $artifact->getLastUpdateDate();
        Header::allowOptionsPut();
        Header::lastModified($date);
    }

    private function sendAllowHeadersForExecutionPost(Tracker_Artifact $artifact): void
    {
        $date = $artifact->getLastUpdateDate();
        Header::allowOptionsPost();
        Header::lastModified($date);
    }

    /**
     *
     *
     * @return Tracker_Artifact
     * @throws RestException
     */
    private function getDefinitionOfExecution(PFUser $user, Tracker_Artifact $execution_artifact)
    {
        try {
            return $this->definition_retriever->getDefinitionRepresentationForExecution(
                $user,
                $execution_artifact
            );
        } catch (DefinitionNotFoundException $e) {
            throw new RestException(400, 'The execution is not linked to a definition');
        }
    }

    /**
     * @param PFUser           $user
     * @param Tracker_Artifact $artifact
     *
     * @return ExecutionRepresentation
     * @throws RestException
     */
    private function getExecutionRepresentation($user, $artifact)
    {
        try {
            return $this->execution_representation_builder->getExecutionRepresentation($user, $artifact);
        } catch (DefinitionNotFoundException $e) {
            throw new RestException(400, 'The execution is not linked to a definition');
        }
    }

    /**
     * @throws RestException
     */
    private function getCurrentUser(): PFUser
    {
        $user = $this->user_manager->getCurrentUser();
        if (! $user) {
            throw new RestException(404, "User not found");
        }

        return $user;
    }
}

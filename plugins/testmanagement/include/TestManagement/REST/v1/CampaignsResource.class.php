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
use EventManager;
use Http\Client\Common\Plugin\CookiePlugin;
use Http\Message\CookieJar;
use Jenkins_Client;
use Jenkins_ClientUnableToLaunchBuildException;
use Luracast\Restler\RestException;
use PFUser;
use ProjectManager;
use Tracker_AfterSaveException;
use Tracker_Artifact;
use Tracker_Artifact_PriorityDao;
use Tracker_Artifact_PriorityHistoryDao;
use Tracker_Artifact_PriorityManager;
use Tracker_ArtifactFactory;
use Tracker_ChangesetCommitException;
use Tracker_ChangesetNotCreatedException;
use Tracker_CommentNotStoredException;
use Tracker_Exception;
use Tracker_FormElement_InvalidFieldException;
use Tracker_FormElement_InvalidFieldValueException;
use Tracker_FormElementFactory;
use Tracker_NoChangeException;
use Tracker_Permission_PermissionRetrieveAssignee;
use Tracker_Permission_PermissionsSerializer;
use Tracker_ReportFactory;
use Tracker_REST_Artifact_ArtifactCreator;
use Tracker_REST_Artifact_ArtifactUpdater;
use Tracker_REST_Artifact_ArtifactValidator;
use Tracker_URLVerification;
use TrackerFactory;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Jenkins\JenkinsCSRFCrumbRetriever;
use Tuleap\RealTime\NodeJSClient;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\TestManagement\ArtifactDao;
use Tuleap\TestManagement\ArtifactFactory;
use Tuleap\TestManagement\Campaign\ArtifactNotFoundException;
use Tuleap\TestManagement\Campaign\AutomatedTests\AutomatedTestsTriggerer;
use Tuleap\TestManagement\Campaign\AutomatedTests\NoJobConfiguredForCampaignException;
use Tuleap\TestManagement\Campaign\Campaign;
use Tuleap\TestManagement\Campaign\CampaignDao;
use Tuleap\TestManagement\Campaign\CampaignRetriever;
use Tuleap\TestManagement\Campaign\CampaignSaver;
use Tuleap\TestManagement\Campaign\Execution\DefinitionForExecutionRetriever;
use Tuleap\TestManagement\Campaign\Execution\DefinitionNotFoundException;
use Tuleap\TestManagement\Campaign\Execution\ExecutionDao;
use Tuleap\TestManagement\Campaign\JobConfiguration;
use Tuleap\TestManagement\Config;
use Tuleap\TestManagement\ConfigConformanceValidator;
use Tuleap\TestManagement\Dao;
use Tuleap\TestManagement\LabelFieldNotFoundException;
use Tuleap\TestManagement\MilestoneItemsArtifactFactory;
use Tuleap\TestManagement\RealTime\RealTimeMessageSender;
use Tuleap\TestManagement\REST\v1\Execution\StepsResultsFilter;
use Tuleap\TestManagement\REST\v1\Execution\StepsResultsRepresentationBuilder;
use Tuleap\Tracker\RealTime\RealTimeArtifactMessageSender;
use Tuleap\Tracker\REST\v1\ArtifactLinkUpdater;
use UserManager;

class CampaignsResource
{
    public const MAX_LIMIT = 50;
    /**
     * @var CampaignCreator
     */
    private $campaign_creator;

    /** @var Config */
    private $config;

    /** @var UserManager */
    private $user_manager;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var ExecutionCreator */
    private $execution_creator;

    /** @var ConfigConformanceValidator */
    private $conformance_validator;

    /** @var ExecutionRepresentationBuilder */
    private $execution_representation_builder;

    /** @var CampaignRepresentationBuilder */
    private $campaign_representation_builder;

    /** @var ProjectManager */
    private $project_manager;

    /** @var ArtifactLinkUpdater */
    private $artifactlink_updater;

    /** @var RealTimeMessageSender */
    private $realtime_message_sender;

    /** @var CampaignUpdater */
    private $campaign_updater;

    /** @var AutomatedTestsTriggerer */
    private $automated_triggerer;

    /** @var CampaignRetriever */
    private $campaign_retriever;

    /** @var ExecutionDao */
    private $execution_dao;

    public function __construct()
    {
        $this->project_manager       = ProjectManager::instance();
        $this->user_manager          = UserManager::instance();
        $tracker_factory       = TrackerFactory::instance();
        $this->artifact_factory      = Tracker_ArtifactFactory::instance();
        $formelement_factory   = Tracker_FormElementFactory::instance();
        $this->config                = new Config(new Dao(), $tracker_factory);
        $this->conformance_validator = new ConfigConformanceValidator(
            $this->config
        );
        $artifact_dao                = new ArtifactDao();

        $testmanagement_artifact_factory = new ArtifactFactory(
            $this->config,
            $this->artifact_factory,
            $artifact_dao
        );

        $milestone_items_artifact_factory = new MilestoneItemsArtifactFactory(
            $this->config,
            $artifact_dao,
            $this->artifact_factory,
            EventManager::instance()
        );

        $assigned_to_representation_builder = new AssignedToRepresentationBuilder(
            $formelement_factory,
            $this->user_manager
        );

        $requirement_retriever = new RequirementRetriever($this->artifact_factory, $artifact_dao, $this->config);
        $definition_retriever  = new DefinitionForExecutionRetriever($this->conformance_validator);

        $this->execution_dao                    = new ExecutionDao();
        $steps_results_representation_builder   = new StepsResultsRepresentationBuilder(
            $formelement_factory,
            new StepsResultsFilter()
        );
        $this->execution_representation_builder = new ExecutionRepresentationBuilder(
            $this->user_manager,
            $formelement_factory,
            $this->conformance_validator,
            $assigned_to_representation_builder,
            $artifact_dao,
            $this->artifact_factory,
            $requirement_retriever,
            $definition_retriever,
            $this->execution_dao,
            $steps_results_representation_builder,
            \Codendi_HTMLPurifier::instance()
        );

        $campaign_dao = new CampaignDao();
        $key_factory  = new KeyFactory();

        $this->campaign_retriever = new CampaignRetriever($this->artifact_factory, $campaign_dao, $key_factory);

        $this->campaign_representation_builder = new CampaignRepresentationBuilder(
            $formelement_factory,
            $testmanagement_artifact_factory,
            $this->campaign_retriever
        );

        $artifact_validator = new Tracker_REST_Artifact_ArtifactValidator(
            $formelement_factory
        );

        $artifact_creator = new Tracker_REST_Artifact_ArtifactCreator(
            $artifact_validator,
            $this->artifact_factory,
            $tracker_factory
        );

        $this->execution_creator = new ExecutionCreator(
            $formelement_factory,
            $this->config,
            $this->project_manager,
            $tracker_factory,
            $artifact_creator,
            $this->execution_dao
        );

        $definition_selector = new DefinitionSelector(
            $this->config,
            $testmanagement_artifact_factory,
            new ProjectAuthorization(),
            $this->artifact_factory,
            $milestone_items_artifact_factory,
            Tracker_ReportFactory::instance()
        );

        $this->campaign_creator = new CampaignCreator(
            $this->config,
            $this->project_manager,
            $formelement_factory,
            $tracker_factory,
            $definition_selector,
            $artifact_creator,
            $this->execution_creator
        );

        $artifact_updater = new Tracker_REST_Artifact_ArtifactUpdater(
            $artifact_validator
        );

        $this->campaign_updater = new CampaignUpdater(
            $formelement_factory,
            $artifact_updater,
            new CampaignSaver($campaign_dao, $key_factory)
        );

        $priority_manager = new Tracker_Artifact_PriorityManager(
            new Tracker_Artifact_PriorityDao(),
            new Tracker_Artifact_PriorityHistoryDao(),
            $this->user_manager,
            $this->artifact_factory
        );

        $this->artifactlink_updater = new ArtifactLinkUpdater($priority_manager);

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

        $http_client          = HttpClientFactory::createClient(new CookiePlugin(new CookieJar()));
        $http_request_factory = HTTPFactoryBuilder::requestFactory();

        $this->automated_triggerer = new AutomatedTestsTriggerer(
            new Jenkins_Client(
                $http_client,
                $http_request_factory,
                HTTPFactoryBuilder::streamFactory(),
                new JenkinsCSRFCrumbRetriever($http_client, $http_request_factory)
            )
        );
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
     * Get campaign
     *
     * Get testing campaign by its id
     *
     * @url GET {id}
     *
     * @param int $id Id of the campaign
     *
     * @return CampaignRepresentation
     *
     * @throws RestException 403
     */
    protected function getId($id)
    {
        $this->optionsId($id);

        $user     = $this->getCurrentUser();
        $campaign = $this->getCampaignUserCanRead($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $this->user_manager->getCurrentUser(),
            $campaign->getArtifact()->getTracker()->getProject()
        );

        return $this->campaign_representation_builder->getCampaignRepresentation($user, $campaign);
    }

    /**
     * @url OPTIONS {id}/testmanagement_executions
     *
     */
    public function optionsExecutions(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get executions
     *
     * Get executions of a given campaign
     *
     * @url GET {id}/testmanagement_executions
     *
     * @param int $id     Id of the campaign
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return array {@type ExecutionRepresentation}
     *
     * @throws RestException 400
     * @throws RestException 403
     */
    protected function getExecutions($id, $limit = 10, $offset = 0)
    {
        $this->optionsExecutions($id);

        $user     = $this->getCurrentUser();
        $campaign = $this->getCampaignUserCanRead($user, $id);
        $artifact = $campaign->getArtifact();

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $this->user_manager->getCurrentUser(),
            $artifact->getTracker()->getProject()
        );

        $execution_representations = $this->execution_representation_builder
            ->getPaginatedExecutionsRepresentationsForCampaign(
                $user,
                $artifact,
                $this->config->getTestExecutionTrackerId($artifact->getTracker()->getProject()),
                $limit,
                $offset
            );

        $this->sendPaginationHeaders($limit, $offset, $execution_representations->getTotalSize());

        return $execution_representations->getRepresentations();
    }

    /**
     * PATCH test executions
     *
     * Create new test executions and unlink some test executions for a campaign
     *
     * @url PATCH {id}/testmanagement_executions
     *
     * @param int    $id                      Id of the campaign
     * @param string $uuid                    UUID of current user {@from body}
     * @param array  $definition_ids_to_add   Test definition ids for which test executions should be created {@from body}
     * @param array  $execution_ids_to_remove Test execution ids which should be unlinked from the campaign {@from body}
     *
     * @return array {@type ExecutionRepresentation}
     *
     * @throws RestException 400
     * @throws RestException 403
     */
    protected function patchExecutions($id, $uuid, $definition_ids_to_add, $execution_ids_to_remove)
    {
        $user              = $this->getCurrentUser();
        $campaign          = $this->getCampaignUserCanRead($user, $id);
        $artifact          = $campaign->getArtifact();
        $project           = $artifact->getTracker()->getProject();
        $project_id        = $project->getID();
        $new_execution_ids = [];
        $executions_to_add = [];

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $project
        );

        foreach ($definition_ids_to_add as $definition_id) {
            $definition = $this->artifact_factory->getArtifactById($definition_id);
            if (! $definition) {
                throw new RestException(400, 'Invalid definition id ' . (int) $definition_id);
            }
            $new_execution_ref   = $this->execution_creator->createTestExecution(
                $project_id,
                $user,
                $definition
            );
            $new_execution_ids[] = $new_execution_ref->id;
            $executions_to_add[] = $new_execution_ref->getArtifact();
        }

        $executions_to_remove = $this->artifact_factory->getArtifactsByArtifactIdList($execution_ids_to_remove);

        $this->artifactlink_updater->updateArtifactLinks(
            $user,
            $artifact,
            $new_execution_ids,
            $execution_ids_to_remove,
            \Tracker_FormElement_Field_ArtifactLink::NO_NATURE
        );

        foreach ($executions_to_remove as $execution) {
            $this->execution_dao->removeExecution($execution->getId());
            $this->realtime_message_sender->sendExecutionDeleted($user, $artifact, $execution);
        }

        foreach ($executions_to_add as $execution) {
            $this->realtime_message_sender->sendExecutionCreated($user, $artifact, $execution);
        }

        $this->sendAllowHeadersForExecutionsList($artifact);

        $limit  = 10;
        $offset = 0;
        try {
            $execution_representations =
                $this->execution_representation_builder->getPaginatedExecutionsRepresentationsForCampaign(
                    $user,
                    $artifact,
                    $this->config->getTestExecutionTrackerId($project),
                    $limit,
                    $offset
                );
        } catch (DefinitionNotFoundException $e) {
            $execution_id = $e->getExecutionArtifact()->getId();
            throw new RestException(400, "The execution with id $execution_id is not linked to a definition");
        }

        $this->sendPaginationHeaders($limit, $offset, $execution_representations->getTotalSize());

        return $execution_representations->getRepresentations();
    }

    /**
     * @url OPTIONS
     *
     */
    public function options(): void
    {
        Header::allowOptionsPost();
    }

    /**
     * POST campaign
     *
     * Create a new campaign
     *
     * @url POST
     *
     * @param int $project_id Id of the project the campaign will belong to
     * @param string $label The label of the new campaign
     * @param string $test_selector The method used to set initial test definitions for campaign {@from query} {@choice none,all,milestone,report}
     * @param int $milestone_id Id of the milestone with which the campaign will be linked {@from query}
     * @param int $report_id Id of the report to retrieve test definitions for campaign {@from query}
     *
     * @throws RestException 403
     *
     */
    protected function post($project_id, $label, $test_selector = 'all', $milestone_id = 0, $report_id = 0): \Tuleap\Tracker\REST\Artifact\ArtifactReference
    {
        $this->options();

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $this->project_manager->getProject($project_id)
        );

        return $this->campaign_creator->createCampaign(
            UserManager::instance()->getCurrentUser(),
            $project_id,
            $label,
            $test_selector,
            $milestone_id,
            $report_id
        );
    }

    /**
     * PATCH campaign
     *
     * @url    PATCH {id}
     *
     * @param int                            $id                Id of the campaign
     * @param string                         $label             New label of the campaign {@from body}
     * @param JobConfigurationRepresentation $job_configuration {@from body}
     *
     * @return CampaignRepresentation
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 500
     */
    protected function patch($id, $label = null, ?JobConfigurationRepresentation $job_configuration = null)
    {
        $user     = $this->getCurrentUser();
        $campaign = $this->getUpdatedCampaign($user, $id, $label, $job_configuration);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $campaign->getArtifact()->getTracker()->getProject()
        );

        if (! $campaign->getArtifact()->userCanUpdate($user)) {
            throw new RestException(403, "You don't have the permission to update this campaign");
        }

        try {
            $this->campaign_updater->updateCampaign($user, $campaign);
        } catch (Tracker_ChangesetNotCreatedException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_CommentNotStoredException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_AfterSaveException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_ChangesetCommitException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_FormElement_InvalidFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_FormElement_InvalidFieldValueException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_NoChangeException $exception) {
            // Do nothing
        } catch (LabelFieldNotFoundException $exception) {
            throw new RestException(500, $exception->getMessage());
        } catch (Tracker_Exception $exception) {
            throw new RestException(500, $exception->getMessage());
        }

        $campaign_representation = $this->campaign_representation_builder->getCampaignRepresentation($user, $campaign);

        $this->realtime_message_sender->sendCampaignUpdated($user, $campaign->getArtifact());

        $this->sendAllowHeadersForCampaign($campaign);

        return $campaign_representation;
    }

    /**
     * POST automated tests
     *
     * <pre>/!\ Experimental. DO NOT USE</pre>
     *
     * @url POST {id}/automated_tests
     *
     * @param int $id Id of the campaign
     *
     * @throws RestException 403
     *
     */
    protected function postAutomatedTests($id): void
    {
        $this->options();

        $user     = $this->getCurrentUser();
        $campaign = $this->getCampaignUserCanRead($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $campaign->getArtifact()->getTracker()->getProject()
        );

        try {
            $this->automated_triggerer->triggerAutomatedTests($campaign);
        } catch (Jenkins_ClientUnableToLaunchBuildException $e) {
            throw new RestException(500, $e->getMessage());
        } catch (NoJobConfiguredForCampaignException $e) {
            throw new RestException(400, $e->getMessage());
        }
    }

    private function sendPaginationHeaders(int $limit, int $offset, int $size): void
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function sendAllowHeadersForExecutionsList(Tracker_Artifact $artifact): void
    {
        $date = $artifact->getLastUpdateDate();
        Header::allowOptionsPatch();
        Header::lastModified($date);
    }

    private function sendAllowHeadersForCampaign(Campaign $campaign): void
    {
        $date = $campaign->getArtifact()->getLastUpdateDate();
        Header::allowOptionsPatch();
        Header::lastModified($date);
    }

    /**
     * @param int                                 $id
     * @param string|null                         $label
     *
     * @return Campaign
     * @throws RestException
     */
    private function getUpdatedCampaign(
        PFUser $user,
        $id,
        $label = null,
        ?JobConfigurationRepresentation $job_representation = null
    ) {
        $campaign = $this->getCampaignUserCanRead($user, $id);

        $this->overrideWithSubmittedData($campaign, $label, $job_representation);

        return $campaign;
    }

    /**
     * @param int    $id
     *
     * @return Campaign
     * @throws RestException
     */
    private function getCampaignUserCanRead(PFUser $user, $id)
    {
        try {
            $campaign = $this->campaign_retriever->getById($id);
            $this->checkUserCanReadCampaign($user, $campaign);
        } catch (ArtifactNotFoundException $e) {
            throw new RestException(404);
        }

        return $campaign;
    }

    /**
     * @throws RestException
     *
     */
    private function checkUserCanReadCampaign(PFUser $user, Campaign $campaign): void
    {
        $artifact = $campaign->getArtifact();

        if (! $this->conformance_validator->isArtifactACampaign($artifact)) {
            throw new RestException(404, 'The campaign does not exist');
        }

        if (! $artifact->userCanView($user)) {
            throw new RestException(403);
        }

        ProjectAuthorization::userCanAccessProject(
            $user,
            $artifact->getTracker()->getProject(),
            new Tracker_URLVerification()
        );
    }

    /**
     * @param string|null                         $label
     *
     */
    private function overrideWithSubmittedData(
        Campaign $campaign,
        $label = null,
        ?JobConfigurationRepresentation $job_representation = null
    ): void {
        if ($label) {
            $campaign->setLabel($label);
        }

        if ($job_representation) {
            $uri_validator = new \Valid_HTTPURI();
            $uri_validator->disableFeedback();
            if (! $uri_validator->validate($job_representation->url)) {
                throw new RestException(
                    400,
                    dgettext('tuleap-testmanagement', 'Job URL is invalid')
                );
            }

            $job_configuration = new JobConfiguration(
                $job_representation->url,
                new ConcealedString($job_representation->token)
            );
            $campaign->setJobConfiguration($job_configuration);
        }
    }

    private function getCurrentUser(): PFUser
    {
        $user = $this->user_manager->getCurrentUser();

        if (!$user) {
            throw new RestException(
                400,
                'User not found'
            );
        }

        return $user;
    }
}

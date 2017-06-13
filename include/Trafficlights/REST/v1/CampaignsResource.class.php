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

use Project_AccessException;
use Project_AccessProjectNotFoundException;
use Tracker_Exception;
use Tracker_FormElement_InvalidFieldException;
use Tracker_FormElement_InvalidFieldValueException;
use Tracker_NoChangeException;
use Tracker_Permission_PermissionRetrieveAssignee;
use Tracker_Permission_PermissionsSerializer;
use Tracker_URLVerification;
use Tuleap\RealTime\MessageDataPresenter;
use Tuleap\RealTime\NodeJSClient;
use Tuleap\REST\Header;
use Luracast\Restler\RestException;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use ProjectManager;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\Trafficlights\ArtifactDao;
use Tuleap\Trafficlights\ArtifactFactory;
use Tuleap\Trafficlights\TrafficlightsArtifactRightsPresenter;
use UserManager;
use PFUser;
use Tuleap\Trafficlights\ConfigConformanceValidator;
use Tuleap\Trafficlights\Config;
use Tuleap\Trafficlights\Dao;
use Tracker_Artifact;
use Tracker_Artifact_PriorityDao;
use Tracker_Artifact_PriorityManager;
use Tracker_Artifact_PriorityHistoryDao;
use TrackerFactory;
use Tracker_REST_Artifact_ArtifactCreator;
use Tracker_REST_Artifact_ArtifactValidator;
use Tuleap\AgileDashboard\REST\v1\ArtifactLinkUpdater;

class CampaignsResource {

    const MAX_LIMIT        = 50;
    const HTTP_CLIENT_UUID = 'HTTP_X_CLIENT_UUID';

    /** @var Config */
    private $config;

    /** @var UserManager */
    private $user_manager;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var ArtifactFactory */
    private $trafficlights_artifact_factory;

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var ExecutionCreator */
    private $execution_creator;

    /** @var ConfigConformanceValidator */
    private $conformance_validator;

    /** @var ExecutionRepresentationBuilder */
    private $execution_representation_builder;

    /** @var AssignedToRepresentationBuilder */
    private $assigned_to_representation_builder;

    /** @var CampaignRepresentationBuilder */
    private $campaign_representation_builder;

    /** @var ProjectManager */
    private $project_manager;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var ArtifactLinkUpdater */
    private $artifactlink_updater;

    /** @var NodeJSClient */
    private $node_js_client;
    /** @var Tracker_Permission_PermissionsSerializer */
    private $permissions_serializer;

    public function __construct() {
        $this->project_manager                = ProjectManager::instance();
        $this->user_manager                   = UserManager::instance();
        $this->tracker_factory                = TrackerFactory::instance();
        $this->artifact_factory               = Tracker_ArtifactFactory::instance();
        $this->config                         = new Config(new Dao());
        $this->conformance_validator          = new ConfigConformanceValidator(
            $this->config
        );
        $this->trafficlights_artifact_factory = new ArtifactFactory(
            $this->config,
            $this->conformance_validator,
            $this->artifact_factory,
            new ArtifactDao()
        );
        $this->formelement_factory            = Tracker_FormElementFactory::instance();

        $this->assigned_to_representation_builder = new AssignedToRepresentationBuilder(
            $this->formelement_factory,
            $this->user_manager
        );
        $this->execution_representation_builder   = new ExecutionRepresentationBuilder(
            $this->user_manager,
            $this->formelement_factory,
            $this->conformance_validator,
            $this->assigned_to_representation_builder
        );
        $this->campaign_representation_builder    = new CampaignRepresentationBuilder(
            $this->user_manager,
            $this->formelement_factory,
            $this->trafficlights_artifact_factory
        );

        $artifact_creator = new Tracker_REST_Artifact_ArtifactCreator(
            new Tracker_REST_Artifact_ArtifactValidator(
                $this->formelement_factory
            ),
            $this->artifact_factory,
            $this->tracker_factory
        );

        $this->execution_creator = new ExecutionCreator(
            $this->formelement_factory,
            $this->config,
            $this->project_manager,
            $this->tracker_factory,
            $artifact_creator
        );

        $this->campaign_creator = new CampaignCreator(
            $this->config,
            $this->project_manager,
            $this->formelement_factory,
            $this->tracker_factory,
            $this->trafficlights_artifact_factory,
            $artifact_creator,
            $this->execution_creator
        );

        $priority_manager           = new Tracker_Artifact_PriorityManager(
            new Tracker_Artifact_PriorityDao(),
            new Tracker_Artifact_PriorityHistoryDao(),
            $this->user_manager,
            $this->artifact_factory
        );
        $this->artifactlink_updater = new ArtifactLinkUpdater($priority_manager);

        $this->node_js_client         = new NodeJSClient();
        $this->permissions_serializer = new Tracker_Permission_PermissionsSerializer(
            new Tracker_Permission_PermissionRetrieveAssignee(UserManager::instance())
        );
    }

    /**
     * @url OPTIONS {id}
     */
    public function optionsId($id) {
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
     * @return Tuleap\Trafficlights\REST\v1\CampaignRepresentation
     */
    protected function getId($id) {
        $this->optionsId($id);

        $user     = $this->user_manager->getCurrentUser();
        $campaign = $this->getCampaignFromId($id, $user);

        return $this->campaign_representation_builder->getCampaignRepresentation($user, $campaign);
    }

    /**
     * @url OPTIONS {id}/trafficlights_executions
     */
    public function optionsExecutions($id) {
        Header::allowOptionsGet();
    }

    /**
     * Get executions
     *
     * Get executions of a given campaign
     *
     * @url GET {id}/trafficlights_executions
     *
     * @param int $id Id of the campaign
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return array {@type Tuleap\Trafficlights\REST\v1\ExecutionRepresentation}
     */
    protected function getExecutions($id, $limit = 10, $offset = 0) {
        $this->optionsExecutions($id);

        $user     = $this->user_manager->getCurrentUser();
        $campaign = $this->getCampaignFromId($id, $user);

        $execution_representations = $this->execution_representation_builder->getPaginatedExecutionsRepresentationsForCampaign(
            $user,
            $campaign,
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
     * @url PATCH {id}/trafficlights_executions_list
     *
     * @param int   $id                      Id of the campaign
     * @param array $definition_ids_to_add   Test definition ids for which test executions should be created
     * @param array $execution_ids_to_remove Test execution ids which should be unlinked from the campaign
     *
     * @return array {@type Tuleap\Trafficlights\REST\v1\ExecutionRepresentation}
     */
    protected function patchExecutions($id, $definition_ids_to_add, $execution_ids_to_remove)
    {
        $user               = $this->user_manager->getCurrentUser();
        $campaign           = $this->getCampaignFromId($id, $user);
        $project_id         = $campaign->getTracker()->getProject()->getId();
        $new_executions_ids = array();

        foreach ($definition_ids_to_add as $definition_id) {
            $new_execution_ref    = $this->execution_creator->createTestExecution(
                $project_id,
                $user,
                $definition_id
            );
            $new_executions_ids[] = $new_execution_ref->id;
        }

        $this->artifactlink_updater->updateArtifactLinks(
            $user,
            $campaign,
            $new_executions_ids,
            $execution_ids_to_remove,
            \Tracker_FormElement_Field_ArtifactLink::NO_NATURE
        );

        $this->sendAllowHeadersForExecutionsList($campaign);

        $limit                     = 10;
        $offset                    = 0;
        $execution_representations = $this->execution_representation_builder->getPaginatedExecutionsRepresentationsForCampaign(
            $user,
            $campaign,
            $limit,
            $offset
        );

        $this->sendPaginationHeaders($limit, $offset, $execution_representations->getTotalSize());

        return $execution_representations->getRepresentations();
    }

    /**
     * @url OPTIONS {id}/trafficlights_assignees
     */
    public function optionsAssignees($id) {
        Header::allowOptionsGet();
    }

    /**
     * Get assignees
     *
     * Get all users that are assigned to at least one test execution of the
     * given campaign
     *
     * @url GET {id}/trafficlights_assignees
     *
     * @param int $id Id of the campaign
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return array {@type Tuleap\User\REST\UserRepresentation}
     */
    protected function getAssignees($id, $limit = 10, $offset = 0) {
        $this->optionsAssignees($id);

        $user     = $this->user_manager->getCurrentUser();
        $campaign = $this->getCampaignFromId($id, $user);

        $assignees = $this->getAssigneesForCampaign($user, $campaign);

        $this->sendPaginationHeaders($limit, $offset, count($assignees));

        return array_slice($assignees, $offset, $limit);
    }

    /**
     * @url OPTIONS
     */
    public function options() {
        Header::allowOptionsPost();
    }

    /**
     * POST campaign
     *
     * Create a new campaign
     *
     * @url POST
     *
     * @param int    $project_id   Id of the project the campaign will belong to
     * @param string $label        The label of the new campaign
     * @param int    $milestone_id Id of the milestone with which the campaign will be linked
     */
    protected function post($project_id, $label, $milestone_id = null) {
        $this->options();
        return $this->campaign_creator->createCampaign(
            UserManager::instance()->getCurrentUser(),
            $project_id,
            $label,
            $milestone_id
        );
    }

    /**
     * PATCH campaign
     *
     * @url PATCH {id}
     *
     * @param int    $id            Id of the campaign
     * @param array  $execution_ids Test executions
     * @return array
     *
     * @throws 400
     * @throws 500
     */
    protected function patch($id, $execution_ids)
    {
        $user                       = UserManager::instance()->getCurrentUser();
        $campaign_artifact          = $this->getArtifactById($user, $id);
        $executions                 = array();
        $executions_representations = array();

        try {
            foreach ($execution_ids as $execution_id) {
                $campaign_artifact->linkArtifact($execution_id, $user);
                $execution                    = $this->trafficlights_artifact_factory->getArtifactById($execution_id);
                $executions[]                 = $execution;
                $executions_representations[] = $this->execution_representation_builder->getExecutionRepresentation($user, $execution);
            }
        } catch (Tracker_FormElement_InvalidFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_FormElement_InvalidFieldValueException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_NoChangeException $exception) {
            // Do nothing
        } catch (Tracker_Exception $exception) {
            throw new RestException(500, $exception->getMessage());
        }

        if (isset($_SERVER[self::HTTP_CLIENT_UUID]) && $_SERVER[self::HTTP_CLIENT_UUID]) {
            foreach ($executions as $execution) {
                $execution_representation = $this->execution_representation_builder->getExecutionRepresentation($user, $execution);
                $data = array(
                    'artifact' => $execution_representation
                );
                $rights  = new TrafficlightsArtifactRightsPresenter($execution, $this->permissions_serializer);
                $message = new MessageDataPresenter(
                    $user->getId(),
                    $_SERVER[self::HTTP_CLIENT_UUID],
                    'trafficlights_' . $campaign_artifact->getId(),
                    $rights,
                    'trafficlights_execution:create',
                    $data
                );

                $this->node_js_client->sendMessage($message);
            }
        }

        $this->sendAllowHeadersForCampaign($campaign_artifact);

        return $executions_representations;
    }

    private function getAssigneesForCampaign(PFUser $user, Tracker_Artifact $campaign) {
        $assignees = array();

        $executions = $this->execution_representation_builder->getExecutionsForCampaign($user, $campaign);
        foreach ($executions as $execution) {
            $assigned_to_representation = $this->assigned_to_representation_builder->getAssignedToRepresentationForExecution($user, $execution);

            if (! $assigned_to_representation) {
                continue;
            }

            if (isset($assignees[$assigned_to_representation->id])) {
                continue;
            }

            $assignees[$assigned_to_representation->id] = $assigned_to_representation;
        }

        return $assignees;
    }

    private function getCampaignFromId($id, PFUser $user) {
        $campaign = $this->trafficlights_artifact_factory->getArtifactById($id);

        if (! $this->isACampaign($campaign)) {
            throw new RestException(404, 'The campaign does not exist');
        }

        if (! $campaign->userCanView($user)) {
            throw new RestException(403, 'Access denied to this campaign');
        }

        return $campaign;
    }

    private function sortByCategoryAndId(array &$execution_representations) {
        usort($execution_representations, function ($a, $b) {
            $def_a = $a->definition;
            $def_b = $b->definition;

            $category_cmp = strnatcasecmp($def_a->category, $def_b->category);
            if ($category_cmp !== 0) {
                return $category_cmp;
            }

            return strcmp($def_a->id, $def_b->id);
        });
    }

    private function isACampaign($campaign) {
        return $campaign && $this->conformance_validator->isArtifactACampaign($campaign);
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function sendAllowHeadersForExecutionsList(Tracker_Artifact $artifact)
    {
        $date = $artifact->getLastUpdateDate();
        Header::allowOptionsPatch();
        Header::lastModified($date);
    }


    /**
     * @param int $id
     *
     * @return Tracker_Artifact
     * @throws Project_AccessProjectNotFoundException 404
     * @throws Project_AccessException 403
     * @throws RestException 404
     */
    private function getArtifactById(PFUser $user, $id)
    {
        $artifact = $this->trafficlights_artifact_factory->getArtifactById($id);
        if ($artifact) {
            if (! $artifact->userCanView($user)) {
                throw new RestException(403);
            }

            ProjectAuthorization::userCanAccessProject($user, $artifact->getTracker()->getProject(), new Tracker_URLVerification());
            return $artifact;
        }
        throw new RestException(404);
    }

    private function sendAllowHeadersForCampaign(Tracker_Artifact $artifact)
    {
        $date = $artifact->getLastUpdateDate();
        Header::allowOptionsPatch();
        Header::lastModified($date);
    }
}

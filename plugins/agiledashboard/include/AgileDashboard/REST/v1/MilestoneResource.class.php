<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
namespace Tuleap\AgileDashboard\REST\v1;

use \Tuleap\REST\Header;
use \Luracast\Restler\RestException;
use \PlanningFactory;
use \Tracker_ArtifactFactory;
use \Tracker_FormElementFactory;
use \TrackerFactory;
use \Planning_MilestoneFactory;
use \AgileDashboard_BacklogItemDao;
use \AgileDashboard_Milestone_Backlog_BacklogStrategyFactory;
use \AgileDashboard_Milestone_Backlog_BacklogItemBuilder;
use \UserManager;
use \Planning_Milestone;
use \PFUser;
use \AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory;
use \MilestoneContentUpdater;
use \EventManager;

/**
 * Wrapper for milestone related REST methods
 */
class MilestoneResource {

    const MAX_LIMIT = 100;

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var MilestoneResourceValidator */
    private $milestone_validator;

    /** @var MilestoneContentUpdater */
    private $milestone_content_updater;

    /** @var AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory */
    private $backlog_item_collection_factory;

    /** @var EventManager */
    private $event_manager;

    public function __construct() {
        $planning_factory             = PlanningFactory::build();
        $tracker_artifact_factory     = Tracker_ArtifactFactory::instance();
        $tracker_form_element_factory = Tracker_FormElementFactory::instance();

        $this->milestone_factory = new Planning_MilestoneFactory(
            $planning_factory,
            $tracker_artifact_factory,
            $tracker_form_element_factory,
            TrackerFactory::instance()
        );

        $this->backlog_strategy_factory = new AgileDashboard_Milestone_Backlog_BacklogStrategyFactory(
            new AgileDashboard_BacklogItemDao(),
            $tracker_artifact_factory,
            $planning_factory
        );

        $this->backlog_item_collection_factory = new AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory(
            new AgileDashboard_BacklogItemDao(),
            $tracker_artifact_factory,
            $tracker_form_element_factory,
            $this->milestone_factory,
            $planning_factory,
            new AgileDashboard_Milestone_Backlog_BacklogItemBuilder()
        );

        $this->milestone_validator = new MilestoneResourceValidator(
            $planning_factory,
            $tracker_artifact_factory,
            $tracker_form_element_factory,
            $this->backlog_strategy_factory,
            $this->milestone_factory,
            $this->backlog_item_collection_factory
        );

        $this->milestone_content_updater = new MilestoneContentUpdater($tracker_form_element_factory);

        $this->event_manager = EventManager::instance();
    }

    /**
     * @url OPTIONS
     */
    public function options() {
        Header::allowOptions();
    }

    /**
     * Get milestone
     *
     * Get the definition of a given the milestone
     *
     * @url GET {id}
     *
     * @param int $id Id of the milestone
     *
     * @return Tuleap\AgileDashboard\REST\v1\MilestoneRepresentation
     *
     * @throws 403
     * @throws 404
     */
    protected function getId($id) {
        $user      = $this->getCurrentUser();
        $milestone = $this->getMilestoneById($user, $id);
        $this->sendAllowHeadersForMilestone($milestone);

        $milestone_representation = new MilestoneRepresentation();
        $milestone_representation->build($milestone);

        return $milestone_representation;
    }

    /**
     * Return info about milestone if exists
     *
     * @url OPTIONS {id}
     *
     * @param string $id Id of the milestone
     *
     * @throws 403
     * @throws 404
     */
    protected function optionsId($id) {
        $milestone = $this->getMilestoneById($this->getCurrentUser(), $id);
        $this->sendAllowHeadersForMilestone($milestone);
    }

    /**
     * @url OPTIONS {id}/milestones
     *
     * @param int $id ID of the milestone
     *
     * @throws 403
     * @throws 404
     */
    protected function optionsSubmilestones($id) {
        $this->getMilestoneById($this->getCurrentUser(), $id);
        $this->sendAllowHeaderForSubmilestones();
    }

    /**
     * Get sub-milestones
     *
     * Get the sub-milestones of a given milestone.
     * A sub-milestone is a decomposition of a milestone (for instance a Release has Sprints as submilestones)
     *
     * @url GET {id}/milestones
     *
     * @param int $id Id of the milestone
     *
     * @return Array {@type Tuleap\AgileDashboard\REST\v1\MilestoneRepresentation}
     *
     * @throws 403
     * @throws 404
     */
    protected function getSubmilestones($id) {
        $user      = $this->getCurrentUser();
        $milestone = $this->getMilestoneById($user, $id);
        $this->sendAllowHeaderForSubmilestones();

        return array_map(
            function (Planning_Milestone $milestone) {
                $milestone_representation = new MilestoneRepresentation();
                $milestone_representation->build($milestone);

                return $milestone_representation;
            },
            $this->milestone_factory->getSubMilestones($user, $milestone)
        );
    }

    /**
     * Get content
     *
     * Get the backlog items of a given milestone
     *
     * @url GET {id}/content
     *
     * @param int $id     Id of the milestone
     * @param int $limit  Number of elements displayed per page
     * @param int $offset Position of the first element to display
     *
     * @return array {@type Tuleap\AgileDashboard\REST\v1\BacklogItemRepresentation}
     *
     * @throws 403
     * @throws 404
     */
    protected function getContent($id, $limit = 10, $offset = 0) {
        $this->checkContentLimit($limit);

        $milestone = $this->getMilestoneById($this->getCurrentUser(), $id);
        $this->sendAllowHeaderForContent();

        $backlog_items = $this->getMilestoneContentItems($milestone);
        $backlog_items_representations = array();

        foreach ($backlog_items as $backlog_item) {
            $backlog_item_representation = new BacklogItemRepresentation();
            $backlog_item_representation->build($backlog_item);
            $backlog_items_representations[] = $backlog_item_representation;
        }

        return array_slice($backlog_items_representations, $offset, $limit);
    }

    /**
     * @url OPTIONS {id}/content
     *
     * @param int $id Id of the milestone
     *
     * @throws 403
     * @throws 404
     */
    protected function optionsContent($id) {
        $this->getMilestoneById($this->getCurrentUser(), $id);
        $this->sendAllowHeaderForContent();
    }

    /**
     * Get backlog
     *
     * Get the backlog items of a given milestone that can be planned in a sub-milestone
     *
     * @url GET {id}/backlog
     *
     * @param int $id     Id of the milestone
     * @param int $limit  Number of elements displayed per page
     * @param int $offset Position of the first element to display
     *
     * @return array {@type Tuleap\AgileDashboard\REST\v1\BacklogItemRepresentation}
     *
     * @throws 403
     * @throws 404
     */
    protected function getBacklog($id, $limit = 10, $offset = 0) {
        $this->checkContentLimit($limit);

        $user = $this->getCurrentUser();
        $milestone = $this->getMilestoneById($user, $id);
        $this->sendAllowHeaderForBacklog();

        $backlog_items = $this->getMilestoneBacklogItems($user, $milestone);
        $backlog_items_representation = array();

        foreach ($backlog_items as $backlog_item) {
            $backlog_item_representation = new BacklogItemRepresentation();
            $backlog_item_representation->build($backlog_item);
            $backlog_items_representation[] = $backlog_item_representation;
        }

        return array_slice($backlog_items_representation, $offset, $limit);
    }

    /**
     * @url OPTIONS {id}/backlog
     *
     * @param int $id Id of the milestone
     *
     * @throws 403
     * @throws 404
     */
    protected function optionsBacklog($id) {
        $this->getMilestoneById($this->getCurrentUser(), $id);
        $this->sendAllowHeaderForBacklog();
    }

    /**
     * Put content in a given milestone
     *
     * @url PUT {id}/content
     *
     * @param int $id    Id of the milestone
     * @param array $ids Ids of backlog items {@from body}
     *
     * @throws 500
     */
    protected function putContent($id, array $ids) {
        $current_user = $this->getCurrentUser();
        $milestone    = $this->getMilestoneById($current_user, $id);

        try {
            $this->milestone_validator->validateArtifactsFromBodyContent($ids, $milestone, $current_user);
        } catch (ArtifactDoesNotExistException $exception) {
            throw new RestException(500, $exception->getMessage());
        } catch (ArtifactIsNotInBacklogTrackerException $exception) {
            throw new RestException(500, $exception->getMessage());
        } catch (ArtifactIsClosedOrAlreadyPlannedInAnotherMilestone $exception) {
            throw new RestException(500, $exception->getMessage());
        } catch (IdsFromBodyAreNotUniqueException $exception) {
            throw new RestException(500, $exception->getMessage());
        }

        $this->milestone_content_updater->updateMilestoneContent($ids, $current_user, $milestone->getArtifact());

        $this->sendAllowHeaderForContent();
    }

    /**
     * Order backlog items
     *
     * Order backlog items in milestone
     *
     * @url PUT {id}/backlog
     *
     * @param int $id    Id of the milestone
     * @param array $ids Ids of backlog items {@from body}
     *
     * @throws 500
     */
    protected function putBacklog($id, array $ids) {
        $user      = $this->getCurrentUser();
        $milestone = $this->getMilestoneById($user, $id);

        try {
            $this->milestone_validator->validateArtifactIdsAreInOpenAndUnplannedMilestone($ids, $milestone, $user);
        } catch (ArtifactIsNotInOpenAndUnplannedBacklogItemsException $exception) {
            throw new RestException(500, $exception->getMessage());
        } catch (IdsFromBodyAreNotUniqueException $exception) {
            throw new RestException(500, $exception->getMessage());
        }

        $this->milestone_content_updater->setOrder($ids);

        $this->sendAllowHeaderForContent();
    }

    /**
     * Carwall options
     * 
     * Carwall options
     * 
     * @url OPTIONS {id}/cardwall
     * 
     * @param int $id Id of the milestone
     * 
     * @throws 403
     * @throws 404
     */
    protected function optionsCardwall($id) {
        $this->event_manager->processEvent(
            AGILEDASHBOARD_EVENT_REST_OPTIONS_CARDWALL,
            array(
                'version'   => 'v1',
                'milestone' => $this->getMilestoneById($this->getCurrentUser(), $id)
            )
        );
    }

    private function getMilestoneById(PFUser $user, $id) {
        $milestone = $this->milestone_factory->getBareMilestoneByArtifactId($user, $id);

        if (! $milestone) {
            throw new RestException(404);
        }

        if (! $milestone->getArtifact()->userCanView()) {
            throw new RestException(403);
        }

        return $milestone;
    }

    private function getCurrentUser() {
        return UserManager::instance()->getCurrentUser();
    }

    private function getMilestoneBacklogItems(PFUser $user, $milestone) {
        return $this->backlog_item_collection_factory->getUnplannedOpenCollection(
            $user,
            $milestone,
            $this->backlog_strategy_factory->getBacklogStrategy($milestone),
            false
        );
    }

    private function getMilestoneContentItems($milestone) {
        $strategy_factory = new \AgileDashboard_Milestone_Backlog_BacklogStrategyFactory(
            new \AgileDashboard_BacklogItemDao(),
            \Tracker_ArtifactFactory::instance(),
            \PlanningFactory::build()
        );

        $backlog_strategy = $strategy_factory->getSelfBacklogStrategy($milestone);

        return $this->backlog_item_collection_factory->getAllCollection(
            $this->getCurrentUser(),
            $milestone,
            $backlog_strategy,
            ''
        );
    }

    private function checkContentLimit($limit) {
        if (! $this->limitValueIsAcceptable($limit)) {
             throw new RestException(406, 'Maximum value for limit exceeded');
        }
    }

    private function limitValueIsAcceptable($limit) {
        return $limit <= self::MAX_LIMIT;
    }

    private function sendAllowHeaderForContent() {
        Header::allowOptionsGetPut();
    }

    private function sendAllowHeaderForBacklog() {
        Header::allowOptionsGetPut();
    }

    private function sendAllowHeaderForSubmilestones() {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForMilestone($milestone) {
        $date = $milestone->getLastModifiedDate();
        Header::allowOptionsGet();
        Header::lastModified($date);
    }
}

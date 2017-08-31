<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\TestManagement;

use AgileDashboard_Milestone_Backlog_BacklogItem;
use Project;
use PFUser;
use Tracker_ArtifactDao;
use Tracker_ArtifactFactory;
use Planning_MilestoneFactory;
use AgileDashboard_Milestone_Backlog_BacklogStrategyFactory;
use AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory;
use Tuleap\TestManagement\Nature\NatureCoveredByPresenter;

class MilestoneItemsArtifactFactory
{
    /**
     * @var Config
     */
    private $config;

    /** @var Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogStrategyFactory */
    private $backlog_strategy_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory */
    private $backlog_item_collection_factory;

    /** @var ArtifactDao */
    private $dao;

    /**
     * @var Tracker_ArtifactDao
     */
    private $tracker_artifact_dao;

    public function __construct(
        Config $config,
        ArtifactDao $dao,
        Tracker_ArtifactFactory $tracker_artifact_factory,
        Planning_MilestoneFactory $milestone_factory,
        AgileDashboard_Milestone_Backlog_BacklogStrategyFactory $backlog_strategy_factory,
        AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory $backlog_item_collection_factory,
        Tracker_ArtifactDao $tracker_artifact_dao
    ) {
        $this->config                          = $config;
        $this->dao                             = $dao;
        $this->tracker_artifact_factory        = $tracker_artifact_factory;
        $this->milestone_factory               = $milestone_factory;
        $this->backlog_strategy_factory        = $backlog_strategy_factory;
        $this->backlog_item_collection_factory = $backlog_item_collection_factory;
        $this->tracker_artifact_dao            = $tracker_artifact_dao;
    }

    public function getCoverTestDefinitionsUserCanViewForMilestone(PFUser $user, Project $project, $milestone_id)
    {
        $test_def_tracker_id = $this->config->getTestDefinitionTrackerId($project);
        $test_definitions    = array();
        $items_ids           = array();

        $milestone     = $this->milestone_factory->getValidatedBareMilestoneByArtifactId($user, $milestone_id);
        $strategy      = $this->backlog_strategy_factory->getSelfBacklogStrategy($milestone);
        $backlog_items = $this->backlog_item_collection_factory->getAllCollection(
            $user,
            $milestone,
            $strategy,
            ''
        );

        foreach ($backlog_items as $item) {
            $items_ids[] = $item->id();

            if ($item->hasChildren()) {
                $this->parseChildrenElements($item, $user, $items_ids);
            }
        }

        $results = $this->dao->searchPaginatedLinkedArtifactsByLinkNatureAndTrackerId(
            array_unique($items_ids),
            NatureCoveredByPresenter::NATURE_COVERED_BY,
            $test_def_tracker_id,
            PHP_INT_MAX,
            0
        );

        foreach ($results as $row) {
            $test_def_artifact = $this->tracker_artifact_factory->getInstanceFromRow($row);
            if ($test_def_artifact->userCanView($user)) {
                $test_definitions[] = $test_def_artifact;
            }
        }
        return $test_definitions;
    }

    private function parseChildrenElements(AgileDashboard_Milestone_Backlog_BacklogItem $item, PFUser $user, array &$item_ids)
    {
        $children = $this->tracker_artifact_dao->getChildren($item->getArtifact()->getId())
            ->instanciateWith(array($this->tracker_artifact_factory, 'getInstanceFromRow'));

        foreach ($children as $child) {
            if ($child->userCanView($user)) {
                $item_ids[] = $child->getId();
            }
        }
    }
}

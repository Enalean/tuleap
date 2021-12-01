<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Taskboard\Board;

use AgileDashboard_BacklogItemDao;
use AgileDashboard_MilestonePresenter;
use PFUser;
use Planning_MilestonePaneFactory;
use Tracker_ArtifactFactory;
use Tuleap\Taskboard\Column\ColumnPresenterCollectionRetriever;
use Tuleap\Taskboard\Tracker\TrackerPresenterCollectionBuilder;

class BoardPresenterBuilder
{
    /**
     * @var Planning_MilestonePaneFactory
     */
    private $pane_factory;
    /**
     * @var ColumnPresenterCollectionRetriever
     */
    private $columns_retriever;
    /**
     * @var AgileDashboard_BacklogItemDao
     */
    private $backlog_item_dao;
    /** @var TrackerPresenterCollectionBuilder */
    private $trackers_builder;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(
        Planning_MilestonePaneFactory $pane_factory,
        ColumnPresenterCollectionRetriever $columns_retriever,
        AgileDashboard_BacklogItemDao $backlog_item_dao,
        TrackerPresenterCollectionBuilder $trackers_builder,
        Tracker_ArtifactFactory $artifact_factory,
    ) {
        $this->pane_factory      = $pane_factory;
        $this->columns_retriever = $columns_retriever;
        $this->backlog_item_dao  = $backlog_item_dao;
        $this->trackers_builder  = $trackers_builder;
        $this->artifact_factory  = $artifact_factory;
    }

    public function getPresenter(\Planning_Milestone $milestone, PFUser $user): BoardPresenter
    {
        $presenter_data = $this->pane_factory->getPanePresenterData($milestone);

        $backlog_content_rows = $this->backlog_item_dao->getBacklogArtifacts($milestone->getArtifactId());
        $has_content          = count($backlog_content_rows) > 0;

        $backlog_items_have_children = false;
        foreach ($backlog_content_rows as $row) {
            $artifact = $this->artifact_factory->getInstanceFromRow($row);
            if (count($artifact->getChildrenForUserInSameProject($user)) > 0) {
                $backlog_items_have_children = true;
                break;
            }
        }

        return new BoardPresenter(
            new AgileDashboard_MilestonePresenter($milestone, $presenter_data),
            $user,
            $milestone,
            $this->columns_retriever->getColumns($user, $milestone),
            $this->trackers_builder->buildCollection($milestone, $user),
            $has_content,
            $backlog_items_have_children
        );
    }
}

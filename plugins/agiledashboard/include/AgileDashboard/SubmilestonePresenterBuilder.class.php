<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class AgileDashboard_SubmilestonePresenterBuilder {

    /** @var AgileDashboard_Milestone_Backlog_BacklogRowCollectionFactory */
    private $backlog_row_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogStrategyFactory*/
    private $strategy_factory;

    public function __construct(
            AgileDashboard_Milestone_Backlog_BacklogRowCollectionFactory $backlog_row_factory,
            AgileDashboard_Milestone_Backlog_BacklogStrategyFactory      $strategy_factory) {
        $this->backlog_row_factory = $backlog_row_factory;
        $this->strategy_factory    = $strategy_factory;
    }

    public function getSubmilestonePresenter(PFUser $user, Planning_Milestone $milestone) {
        $backlog_strategy = $this->strategy_factory->getSelfBacklogStrategy($milestone);

        return new AgileDashboard_SubmilestonePresenter(
            $this->backlog_row_factory->getAllCollection(
                $user,
                $milestone,
                $backlog_strategy,
                ''
            )
        );
    }
}

?>

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

class AgileDashboard_Milestone_Pane_Planning_PlanningSubMilestonePresenterFactory {

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var AgileDashboard_PaneIconLinkPresenterCollectionFactory */
    private $icon_factory;

    public function __construct(
        Planning_MilestoneFactory $milestone_factory,
        AgileDashboard_PaneIconLinkPresenterCollectionFactory $icon_factory
    ) {
        $this->milestone_factory = $milestone_factory;
        $this->icon_factory      = $icon_factory;
    }

    /**
     * @return AgileDashboard_Milestone_Pane_Planning_PlanningSubMilestonePresenter
     */
    public function getPlanningSubMilestonePresenter(PFUser $user, Planning_ArtifactMilestone $milestone, $redirect_to_self) {
        $this->milestone_factory->updateMilestoneContextualInfo($user, $milestone);
        return new AgileDashboard_Milestone_Pane_Planning_PlanningSubMilestonePresenter(
            $milestone,
            $redirect_to_self,
            $user,
            $this->icon_factory->getIconLinkPresenterCollection($milestone)
        );
    }
}
?>

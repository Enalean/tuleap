<?php
/**
 * Copyright Enalean (c) 2014 - present. All rights reserved.
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

use Tuleap\AgileDashboard\REST\v1\MilestoneRepresentation;
use Tuleap\REST\JsonCast;

class AgileDashboard_Milestone_Pane_Planning_PlanningV2Presenter
{

    /** @var int */
    public $user_id;

    /** @var int */
    public $project_id;

    /** @var int */
    public $milestone_id;

    /** @var string */
    public $lang;

    /** @var string */
    public $view_mode;

    /** @var MilestoneRepresentation */
    public $milestone_representation;

    /** @var AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentations */
    public $paginated_backlog_items_representations;

    /** @var AgileDashboard_Milestone_PaginatedMilestonesRepresentations */
    public $paginated_milestones_representations;

    /** @var string */
    public $user_accessibility_mode;
    /**
     * @var bool
     */
    public $is_in_explicit_top_backlog;

    public function __construct(
        PFUser $current_user,
        Project $project,
        $milestone_id,
        $milestone_representation,
        $paginated_backlog_items_representations,
        $paginated_milestones_representations,
        bool $is_in_explicit_top_backlog
    ) {
        $this->user_id                                 = $current_user->getId();
        $this->lang                                    = $this->getLanguageAbbreviation($current_user);
        $this->project_id                              = $project->getId();
        $this->milestone_id                            = $milestone_id;
        $this->view_mode                               = $current_user->getPreference('agiledashboard_planning_item_view_mode_' . $this->project_id);
        $this->milestone_representation                = json_encode($milestone_representation);
        $this->paginated_backlog_items_representations = json_encode($paginated_backlog_items_representations);
        $this->paginated_milestones_representations    = json_encode($paginated_milestones_representations);
        $this->user_accessibility_mode                 = json_encode((bool) $current_user->getPreference(PFUser::ACCESSIBILITY_MODE));
        $this->is_in_explicit_top_backlog              = $is_in_explicit_top_backlog;
    }

    private function getLanguageAbbreviation(PFUser $current_user)
    {
        list($lang, $country) = explode('_', $current_user->getLocale());

        return $lang;
    }
}

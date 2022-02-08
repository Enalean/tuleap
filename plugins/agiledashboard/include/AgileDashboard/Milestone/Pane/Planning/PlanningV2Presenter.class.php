<?php
/**
 * Copyright Enalean (c) 2014 - Present. All rights reserved.
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

use Tuleap\Tracker\Artifact\Renderer\ListPickerIncluder;
use Tuleap\Tracker\Modal\FeatureFlagArtifactModalLinksFieldV2;

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

    /** @var string */
    public $user_accessibility_mode;
    /**
     * @var bool
     */
    public $is_in_explicit_top_backlog;
    /**
     * @var string
     */
    public $allowed_additional_panes_to_display;

    /**
     * @var string
     */
    public $is_list_picker_enabled;

    /**
     * @var string
     */
    public $trackers_ids_having_list_picker_disabled;

    public string $has_current_project_parents;

    public string $is_links_field_v2_enabled;

    /**
     * @param string[] $allowed_additional_panes_to_display
     */
    public function __construct(
        PFUser $current_user,
        Project $project,
        $milestone_id,
        bool $is_in_explicit_top_backlog,
        array $allowed_additional_panes_to_display,
        bool $has_current_project_parents,
    ) {
        $this->user_id                                  = $current_user->getId();
        $this->lang                                     = $this->getLanguageAbbreviation($current_user);
        $this->project_id                               = $project->getId();
        $this->milestone_id                             = $milestone_id;
        $this->view_mode                                = (string) $current_user->getPreference('agiledashboard_planning_item_view_mode_' . $this->project_id);
        $this->is_in_explicit_top_backlog               = $is_in_explicit_top_backlog;
        $this->user_accessibility_mode                  = json_encode((bool) $current_user->getPreference(PFUser::ACCESSIBILITY_MODE));
        $this->allowed_additional_panes_to_display      = json_encode($allowed_additional_panes_to_display);
        $this->trackers_ids_having_list_picker_disabled = json_encode(ListPickerIncluder::getTrackersHavingListPickerDisabled());
        $this->is_list_picker_enabled                   = json_encode(ListPickerIncluder::isListPickerEnabledOnPlatform());
        $this->is_links_field_v2_enabled                = json_encode(FeatureFlagArtifactModalLinksFieldV2::isArtifactModalLinksFieldV2Enabled(), JSON_THROW_ON_ERROR);
        $this->has_current_project_parents              = json_encode($has_current_project_parents, JSON_THROW_ON_ERROR);
    }

    private function getLanguageAbbreviation(PFUser $current_user): string
    {
        [$lang, $country] = explode('_', $current_user->getLocale());

        return $lang;
    }
}

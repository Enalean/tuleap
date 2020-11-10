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
    public $create_milestone_allowed;

    /**
     * @var string
     */
    public $backlog_add_item_allowed;

    /**
     * @var string
     */
    public $is_list_picker_enabled;

    /**
     * @param string[] $allowed_additional_panes_to_display
     */
    public function __construct(
        PFUser $current_user,
        Project $project,
        $milestone_id,
        bool $is_in_explicit_top_backlog,
        array $allowed_additional_panes_to_display,
        bool $create_milestone_allowed,
        bool $backlog_add_item_allowed
    ) {
        $this->user_id                             = $current_user->getId();
        $this->lang                                = $this->getLanguageAbbreviation($current_user);
        $this->project_id                          = $project->getId();
        $this->milestone_id                        = $milestone_id;
        $this->view_mode                           = (string) $current_user->getPreference('agiledashboard_planning_item_view_mode_' . $this->project_id);
        $this->user_accessibility_mode             = json_encode((bool) $current_user->getPreference(PFUser::ACCESSIBILITY_MODE));
        $this->is_in_explicit_top_backlog          = $is_in_explicit_top_backlog;
        $this->allowed_additional_panes_to_display = json_encode($allowed_additional_panes_to_display);
        $this->create_milestone_allowed            = json_encode($create_milestone_allowed);
        $this->backlog_add_item_allowed            = json_encode($backlog_add_item_allowed);
        $this->is_list_picker_enabled              = json_encode((bool) ListPickerIncluder::isListPickerEnabledAndBrowserNotIE11());
    }

    private function getLanguageAbbreviation(PFUser $current_user)
    {
        [$lang, $country] = explode('_', $current_user->getLocale());

        return $lang;
    }
}

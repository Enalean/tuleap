<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Pane\Planning;

use PFUser;
use Project;

final class PlanningV2Presenter
{
    public int $user_id;
    public int $project_id;
    public string $milestone_id;
    public string $lang;
    public string $view_mode;
    public bool $user_accessibility_mode;
    public bool $is_in_explicit_top_backlog;
    public string $allowed_additional_panes_to_display;

    /**
     * @param string[] $allowed_additional_panes_to_display
     */
    public function __construct(
        PFUser $current_user,
        Project $project,
        string $milestone_id,
        bool $is_in_explicit_top_backlog,
        array $allowed_additional_panes_to_display,
    ) {
        $this->user_id                             = (int) $current_user->getId();
        $this->lang                                = $this->getLanguageAbbreviation($current_user);
        $this->project_id                          = (int) $project->getId();
        $this->milestone_id                        = $milestone_id;
        $this->view_mode                           = (string) $current_user->getPreference(
            'agiledashboard_planning_item_view_mode_' . $this->project_id
        );
        $this->is_in_explicit_top_backlog          = $is_in_explicit_top_backlog;
        $this->user_accessibility_mode             = (bool) $current_user->getPreference(PFUser::ACCESSIBILITY_MODE);
        $this->allowed_additional_panes_to_display = json_encode(
            $allowed_additional_panes_to_display,
            JSON_THROW_ON_ERROR
        );
    }

    private function getLanguageAbbreviation(PFUser $current_user): string
    {
        [$lang, $country] = explode('_', $current_user->getLocale());

        return $lang;
    }
}

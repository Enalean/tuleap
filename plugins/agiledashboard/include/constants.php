<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

define('AGILEDASHBOARD_BASE_URL', '/plugins/agiledashboard');
define('AGILEDASHBOARD_BASE_DIR', dirname(__FILE__));

/**
 * Get the additional panes to display next to a milestone in the agiledashboard
 *
 * Parameters:
 * 'milestone'         => The current Planning_Milestone
 * 'user'              => The current user
 * 'request'           => The current HTTP request
 * 'milestone_factory' => Planning_MilestoneFactory
 *
 * Expected results
 * 'panes'       => Input/Output parameter, array of type AgileDashboard_PaneInfo
 * 'active_pane' => Input/Output parameter, the current active pane (type AgileDashboard_Pane)
 */
define('AGILEDASHBOARD_EVENT_ADDITIONAL_PANES_ON_MILESTONE', 'agiledashboard_event_additional_panes_on_milestone');

/**
 * Get the pane to display on agile dashboard index page
 *
 * Parameters:
 * 'milestone'         => The current Planning_Milestone
 * 'user'              => The current user
 * 'milestone_factory' => Planning_MilestoneFactory
 *
 * Expected results
 * 'pane'        => Input/Output parameter, and AgileDashboard_Pane
 */
define('AGILEDASHBOARD_EVENT_INDEX_PAGE', 'agiledashboard_event_index_page');

/**
 * Modify the redirect parameters when attempt to display a planning without specific Milestone selected
 *
 * Parameters:
 * 'milestone' => The most recent Planning_Milestone on which we are about to be redirected
 *
 * Expected results
 * 'redirect_parameters' => Input/Output parameter, array of 'key' => 'value'
 */
define('AGILEDASHBOARD_EVENT_MILESTONE_SELECTOR_REDIRECT', 'agiledashboard_event_milestone_selector_redirect');

/**
 * Toggle and redirect when user toggle avatars or usernames on the cardwall of the agiledashboard
 *
 * Parameters:
 * 'request' => Codendi_Request
 *
 * Expected results
 * 'redirect parameters' => Input/Output parameter, return to the current agiledashboard cardwall pane
 */
define('AGILEDASHBOARD_EVENT_CARDWALL_TOGGLE_AVATAR_DISPLAY', 'agiledashboard_event_cardwall_toggle_avatar_display');
?>

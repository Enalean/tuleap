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
define('AGILEDASHBOARD_TEMPLATE_DIR', dirname(__FILE__).'/../templates');

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
 * Get the additional panes *info* for a milestone
 *
 * Parameters:
 * 'milestone'         => The current Planning_Milestone
 * 'user'              => The current user
 *
 * Expected results
 * 'pane_info_list' => Input/Output parameter, array of type AgileDashboard_PaneInfo
 */
define('AGILEDASHBOARD_EVENT_ADDITIONAL_PANES_INFO_ON_MILESTONE', 'agiledashboard_event_additional_panes_info_on_milestone');

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
 * Fetch the cardwall configuration html
 *
 * Parameters:
 * 'tracker' => The Planning Tracker of the planning that is being configured
 * 'view'    => The HTML to be fetched
 */
define('AGILEDASHBOARD_EVENT_PLANNING_CONFIG', 'agiledashboard_event_planning_config');

/**
 * Update a planning
 *
 * Parameters:
 * 'tracker' => The Planning Tracker of the planning that is being configured
 * 'request' => The standard request object
 */
define('AGILEDASHBOARD_EVENT_PLANNING_CONFIG_UPDATE', 'agiledashboard_event_planning_config_update');


/**
 * RESt call for cardwall options
 *
 * Parameters:
 * 'version'   => API version
 * 'milestone' => Milestone on which cardwall is requested
 */
define('AGILEDASHBOARD_EVENT_REST_OPTIONS_CARDWALL', 'agiledashboard_event_rest_options_cardwall');

?>

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
define('AGILEDASHBOARD_TEMPLATE_DIR', dirname(__FILE__) . '/../templates');

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
 * RESt call for cardwall GET
 *
 * Parameters:
 * 'version'   => API version
 * 'milestone' => Milestone on which cardwall is requested
 */
define('AGILEDASHBOARD_EVENT_REST_GET_CARDWALL', 'agiledashboard_event_rest_get_cardwall');

/**
 * RESt call for burndown GET
 *
 * Parameters:
 * 'version'   => API version
 * 'user'      => The user who resquest the burndown
 * 'milestone' => Milestone on which burndown is requested
 * 'burndown'  => OUT the \Tuleap\Tracker\REST\Artifact\BurndownRepresentation
 */
define('AGILEDASHBOARD_EVENT_REST_GET_BURNDOWN', 'agiledashboard_event_rest_get_burndown');

/**
 * RESt call for burndown OPTIONS
 *
 * Parameters:
 * 'version'   => API version
 * 'user'      => The user who resquest the burndown
 * 'milestone' => Milestone on which burndown is requested
 */
define('AGILEDASHBOARD_EVENT_REST_OPTIONS_BURNDOWN', 'agiledashboard_event_rest_options_burndown');


/**
 * Allow plugins to modify the milestone requested by a RESt call
 *
 * Parameters:
 * 'user'                     => The user who requested
 * 'milestone'                => The Milestone object
 * 'milestone_representation' => The RESt representation of the milestone
 */
define('AGILEDASHBOARD_EVENT_REST_GET_MILESTONE', 'agiledashboard_event_rest_get_milestone');

/**
 * Checks if cardwall is enabled
 *
 * Parameters:
 * 'tracker' => The Planning Tracker of the planning that is being configured
 * 'enabled' => boolean
 */
define('AGILEDASHBOARD_EVENT_IS_CARDWALL_ENABLED', 'agiledashboard_event_is_cardwall_enabled');

/**
 * Get backlog item tracker card fields semantic
 *
 * Parameters:
 * 'tracker'              => The Tracker of the backlog item
 * 'card_fields_semantic' => Tracker card fields semantic
 */
define('AGILEDASHBOARD_EVENT_GET_CARD_FIELDS', 'agiledashboard_event_get_card_fields');

/**
 * Get Cardwall REST resources only if agiledashboard is activated
 */
define('AGILEDASHBOARD_EVENT_REST_RESOURCES', 'agiledashboard_event_rest_resources');

/**
 * Export AD structure in XML
 */
define('AGILEDASHBOARD_EXPORT_XML', 'agiledashboard_export_xml');

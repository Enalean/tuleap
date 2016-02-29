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

define('GIT_BASE_URL', '/plugins/git');
define('GIT_BASE_DIR', dirname(__FILE__));
define('GIT_TEMPLATE_DIR', GIT_BASE_DIR . '/../templates');

/**
 * Check if platform can use gerrit
 *
 * Parameters:
 *     'platform_can_use_gerrit' => boolean
 */
define('GIT_EVENT_PLATFORM_CAN_USE_GERRIT', 'git_event_platform_can_use_gerrit');
define('REST_GIT_PULL_REQUEST_ENDPOINTS', 'rest_git_pull_request_endpoints');
define('REST_GIT_PULL_REQUEST_GET_FOR_REPOSITORY', 'rest_git_pull_request_get_for_repository');

/**
 * Allow a plugin to add additional info beside the repository name
 *
 * Parameters:
 *   'repository' => (Input)  GitRepository Git repository
 *   'info'       => (Output) String        Html string of the info to append
 */
define('GIT_ADDITIONAL_INFO', 'git_additional_info');

/**
 * Allow a plugin to display his own view instead of the default repository view
 *
 * Parameters:
 *   'repository' => (Input)  GitRepository Git repository
 *   'user'       => (Input)  PFUser        Current user
 *   'view'       => (Output) String        Rendered template of the view
 */
define('GIT_VIEW', 'git_view');


/**
 * Allow a plugin to add additional actions beside the clone bar
 *
 * Parameters:
 *   'repository' => (Input)  GitRepository Git repository
 *   'actions'    => (Output) String        Rendered template of the actions
 */
define('GIT_ADDITIONAL_ACTIONS', 'git_additional_actions');

/**
 * Allow a plugin to append his own classes to the body DOM element in git views
 *
 * Parameters:
 *   'request' => (Input)  Codendi_Request Request
 *   'classes' => (Output) String[]        Additional classnames
 */
define('GIT_ADDITIONAL_BODY_CLASSES', 'git_additional_body_classes');

/**
 * Allow a plugin to add permitted git actions
 *
 * Parameters:
 *   'repository'        => (Input)  GitRepository Git repository
 *   'user'              => (Input)  PFUser        Current user
 *   'permitted_actions' => (Output) String[]      Permitted actions
 */
define('GIT_ADDITIONAL_PERMITTED_ACTIONS', 'git_additional_permitted_actions');

/**
 * Allow a plugin to handle a permitted git action
 *
 * Parameters:
 *   'git_controller' => (Input)  Git           Git controller
 *   'repository'     => (Input)  GitRepository Git repository
 *   'action'         => (Input)  String        Git action
 *   'handled'        => (Output) Boolean       Has the action been handled?
 */
define('GIT_HANDLE_ADDITIONAL_ACTION', 'git_handle_additional_action');

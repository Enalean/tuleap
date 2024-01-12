<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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
define('GIT_SITE_ADMIN_BASE_URL', '/admin/git/');
define('GIT_BASE_DIR', dirname(__FILE__));
define('GIT_TEMPLATE_DIR', GIT_BASE_DIR . '/../templates');
define('GITOLITE3_LOGS_PATH', '/var/lib/gitolite/.gitolite/logs/');

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
 * Allow plugins to add additional notifications setup for git
 *
 * Parameters:
 *   'repository' => (Input) GitRepository Git repository currently modified
 *   'request'    => (Input) HTTPRequest   Current request
 *   'output'     => (Output) String       The HTML to present
 */
define('GIT_ADDITIONAL_NOTIFICATIONS', 'git_additional_notifications');

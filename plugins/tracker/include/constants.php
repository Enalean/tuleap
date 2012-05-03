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

define('TRACKER_BASE_URL', '/plugins/tracker');
define('TRACKER_BASE_DIR', dirname(__FILE__));
define('TRACKER_EVENT_INCLUDE_CSS_FILE', 'tracker_event_include_css_file');

/**
  * The trackers from a project have been duplicated in another project
  *
  * Parameters:
  * 'tracker_mapping' => The mapping between source and target project trackers
  * 'group_id'        => The id of the target project
  *
  * No expected results
  */
define('TRACKER_EVENT_TRACKERS_DUPLICATED', 'tracker_event_trackers_duplicated');

/**
  * An artifact has just been created/updated. Redirect to a plugin specific url if needed.
  *
  * Parameters:
  * 'request'  => The initial request
  *
  * Either a redirection has been done or nothing has been done 
  * (in this case plugins/tracker will commpute the redirection)
  */
define('TRACKER_EVENT_REDIRECT_AFTER_ARTIFACT_CREATION_OR_UPDATE', 'tracker_event_redirect_after_artifact_creation_or_update');

/**
  * We build the form action for a new artifact. Let the plugin inject its own variable.
  *
  * Parameters:
  * 'request'          => The initial request
  * 'query_parameters' => The actual form action parameters
  *
  * No expected results than the query_parameters modified if needed 
  */
define('TRACKER_EVENT_BUILD_ARTIFACT_FORM_ACTION', 'tracker_event_build_artifact_form_action');

?>

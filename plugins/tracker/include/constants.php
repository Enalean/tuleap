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
 * 'artifact' => The involved artifact
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

/**
 * Get the admin items to display in the admin menu of a tracker
 *
 * Parameters:
 * 'tracker' => The current tracker object
 * 'items'   => The items to display. Inject your own items in this array to 
 *            display them in the menu. Each item is an associated array. Eg:
 *            'item_key' => array(
 *              'url',          => // The url to the admin subpage
 *              'short_title',  => // The short title of the item (in toolbar)
 *              'title',        => // The title (on the main admin page)
 *              'description'   => // Displayed in main admin page
 *              'img'           => // The src of the icon. As of today, 48x48px
 *            )
 *
 * No expected results than the query_parameters modified if needed 
 */
define('TRACKER_EVENT_ADMIN_ITEMS', 'tracker_event_admin_items');

/**
 * Let someone process the request on a given tracker.
 *
 * Parameters:
 * 'tracker'               => The current tracker object
 * 'func'                  => The action requested by the user
 * 'layout'                => Tracker_IDisplayTrackerLayout
 * 'request'               => The request object
 * 'user'                  => The user who made the request
 * 'nothing_has_been_done' => flag default to true
 *
 * Expected results:
 *  The action is handled by the listener and the flag nothing_has_been_done 
 *  is set to false.
 *  Or nothing has been done (flag untouched) and the core continue with the 
 *  default processing (eg: display the tracker)
 */
define('TRACKER_EVENT_PROCESS', 'tracker_event_process');

/**
 * An artifact has just been (un)associated to another one
 *
 * Parameters:
 * 'artifact'             => The artifact which receive the (un)association
 * 'linked-artifact-id'   => The (previously) linked artifact id
 * 'request'              => The request
 * 'user'                 => The user who made the request
 * 'form_element_factory' => The FormElementFactory
 *
 * Expected results:
 *  No expected results
 */
define('TRACKER_EVENT_ARTIFACT_ASSOCIATION_EDITED', 'tracker_event_artifact_association_edited');

/**
 * Should we display a selector to choose the parent of an item during creation?
 * If so, which artifacts are possible parents for the created item?
 *
 * By default, we display the selector with open artifacts parents
 *
 * Parameters:
 * 'user'             => User    The current user
 * 'parent_tracker'   => Tracker The parent tracker
 *
 * Re
 * 'possible_parents' => array of Tracker_Artifact
 * 'label'            => string the label of the possible parents list
 * 'display_selector' => bool true if we can display the selector
 */
define('TRACKER_EVENT_ARTIFACT_PARENTS_SELECTOR', 'tracker_event_artifact_parents_selector');

/**
 * Fetch the semantics used by other plugins
 *
 * Parameters:
 * 'semantics' => @var Tracker_SemanticCollection A collection of semantics that needs adding to.
 * 'tracker'   => @var Tracker                    The Tracker the semantics are defined upon
 *
 * Expected results
 * The semantics parameter is populated with additional semantic fields
 */
define('TRACKER_EVENT_MANAGE_SEMANTICS', 'tracker_event_manage_semantics');

/**
 * Create a semantic from xml in other plugins
 *
 * Parameters:
 * 'xml'           => @var SimpleXMLElement
 * 'xml_mapping'   => @var array
 * 'tracker'       => @var Tracker
 * 'semantic'      => @var array
 * 'type'          => @var string
 *
 * Expected results
 * The semantic parameter is populated with a Tracker_Semantic object if it exists for the given type
 */
define('TRACKER_EVENT_SEMANTIC_FROM_XML', 'tracker_event_semantic_from_xml');

/**
 * Fetches all the semantic names
 *
 * Parameters:
 * 'semantic' => @var array of semantic name strings
 */
define('TRACKER_EVENT_SOAP_SEMANTICS', 'tracker_event_soap_semantics');

/**
 * Get the various factories that can retrieve semantics
 *
 * Parameters:
 *  'factories' => All semantic factories
 */
define('TRACKER_EVENT_GET_SEMANTIC_FACTORIES', 'tracker_event_get_semantic_factories');

/**
 * Get the various criteria that may enhance a report
 *
 * Parameters:
 *  'array_of_html_criteria' => (OUT) html code to be included in the criteria list
 *  'tracker'                => (IN)  the current tracker
 */
define('TRACKER_EVENT_REPORT_DISPLAY_ADDITIONAL_CRITERIA', 'tracker_event_report_display_additional_criteria');
?>

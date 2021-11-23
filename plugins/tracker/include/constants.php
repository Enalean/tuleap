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
define('TRACKER_BASE_URL', '/plugins/tracker');
define('TRACKER_BASE_DIR', dirname(__FILE__));
define('TRACKER_TEMPLATE_DIR', realpath(dirname(__FILE__) . '/../templates'));

define('TRACKER_EVENT_INCLUDE_CSS_FILE', 'tracker_event_include_css_file');

/**
 * The trackers from a project have been duplicated in another project
 *
 * Parameters:
 * 'tracker_mapping'   => The mapping between source and target project trackers
 * 'field_mapping'     => The mapping between source and target fields
 * 'group_id'          => The id of the target project
 * 'ugroups_mapping'   => The mapping between source and target ugroups
 * 'source_project_id' => The id of the source project
 *
 * No expected results
 */
define('TRACKER_EVENT_TRACKERS_DUPLICATED', 'tracker_event_trackers_duplicated');

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
 * Request a custom type from other plugins for a new artifact link
 *
 * Parameters:
 * 'project_id'      => The id of the target project
 * 'to_artifact'     => The artifact linked to
 * 'submitted_value' => Values from the artifact form
 *
 * Expected results:
 * 'nature'          => string the type proposed by the plugin
 */
define('TRACKER_EVENT_ARTIFACT_LINK_TYPE_REQUESTED', 'tracker_event_artifact_link_type_requested');

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
define('TRACKER_EVENT_GET_SEMANTICS_NAMES', 'tracker_event_get_semantics_names');

/**
 * Get the various duplicators that can duplicate semantics
 *
 * Parameters:
 *  'duplicators' => \Tuleap\Tracker\Semantic\IDuplicateSemantic[]
 */
define('TRACKER_EVENT_GET_SEMANTIC_DUPLICATORS', 'tracker_event_get_semantic_duplicators');

/**
 * Get the various criteria that may enhance a report
 *
 * Parameters:
 *  'array_of_html_criteria' string[]                (OUT) html code to be included in the criteria list
 *  'tracker'                Tracker                 (IN)  the current tracker
 *  'additional_criteria'    Tracker_Report_AdditionalCriteria[]  (IN)
 *  'user'                   PFUser                  (IN)  the current user
 */
define('TRACKER_EVENT_REPORT_DISPLAY_ADDITIONAL_CRITERIA', 'tracker_event_report_display_additional_criteria');

/**
 * We want to save in database additional criteria
 *
 * Parameters:
 * 'additional_criteria'    Tracker_Report_AdditionalCriteria[]  (IN)
 * 'report'                 Tracker_Report                       (IN)
 */
define('TRACKER_EVENT_REPORT_SAVE_ADDITIONAL_CRITERIA', 'tracker_event_report_save_additional_criteria');

/**
 * We want to save in database additional criteria
 *
 * Parameters:
 * 'additional_criteria_values'    array($key => $value) (OUT)
 * 'report'                        Tracker_Report        (IN)
 */
define('TRACKER_EVENT_REPORT_LOAD_ADDITIONAL_CRITERIA', 'tracker_event_report_load_additional_criteria');

/**
 * Event emitted when a field data can be augmented by plugins
 *
 * Parameters:
 *   'additional_criteria'    Tracker_Report_AdditionalCriteria[]  (IN)
 *   'result'                 String (OUT)
 *   'artifact_id'            Int (IN)
 *   'field'                  Tracker_FormElement_Field (IN)
 */
define('TRACKER_EVENT_FIELD_AUGMENT_DATA_FOR_REPORT', 'tracker_event_field_augment_data_for_report');

/**
 * Event emitted to check if a tracker can be deleted
 *
 * Parameters:
 *   'tracker'                Tracker (IN)
 *   'result'                 Array (OUT)
 */
define('TRACKER_USAGE', 'tracker_usage');

/**
 * Event emitted to check if a user can change the priority of an artifact
 *
 * Parameters:
 *  'user_is_authorized'    BOOl    (OUT)
 *  'group_id'              INT     (IN)
 *  'milestone_id'          IN      (IN)
 *  'user'                  PFUser  (IN)
 */
define('ITEM_PRIORITY_CHANGE', 'item_priority_change');

/**
 * Event emitted to delete tracker
 *
 * Parameters:
 *  'tracker_id'      int (IN)
 *  'key'             string  (IN)
 */
define('TRACKER_EVENT_DELETE_TRACKER', 'tracker_event_delete_tracker');

/**
 * Event emitted to display tracker admin buttons
 *
 * Parameters:
 *  'tracker_id'      int (IN)
 */
define('TRACKER_EVENT_FETCH_ADMIN_BUTTONS', 'tracker_event_fetch_admin_buttons');

/**
 * Getting informations from agile dashboard plugin about the tracker affilitaion to an agile dashboard
 *
 * Parameters:
 *  'cannot_configure_instantiate_for_new_projects' Boolean
 *  'tracker'                                       Tracker
 */
define('TRACKER_EVENT_GENERAL_SETTINGS', 'tracker_event_general_settings');

/**
 * Get the trackers required by agile dashboard
 *
 * Parameters:
 *  'project_id'        project_id
 *  'tracker_ids_list'  array containing tracker ids
*/
define('TRACKER_EVENT_PROJECT_CREATION_TRACKERS_REQUIRED', 'tracker_event_project_creation_trackers_required');

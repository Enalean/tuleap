<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
class Event {
    
    /**
     * Periodical system check event.
     * 
     * No Parameters.
     * No expected results
     */
    const SYSTEM_CHECK = 'system_check';

    /**
     * The user has just changed his ssh authorized keys.
     * 
     * Parameters:
     *  'user' => User
     * 
     * No expected results
     */
    const EDIT_SSH_KEYS = 'edit_ssh_keys';

    /**
     * Dump all ssh keys.
     * 
     * No parameters
     * No expected results
     */
    const DUMP_SSH_KEYS = 'dump_ssh_keys';

    /**
     * The user is accessing a list of their keys
     *
     * Parameters:
     *   'user' => PFUser
     *   'html' => string An emty string of html output- passed by reference
     */
    const LIST_SSH_KEYS = 'list_ssh_keys';

    /**
     * The user has just changed his email address.
     * 
     * Parameters:
     *  'user_id' => User ID
     * 
     * No expected results
     */
    const USER_EMAIL_CHANGED = 'user_email_changed';
    
    /**
     * We are retrieving an instance of Backend. 
     * Shortcut for BACKEND_FACTORY_GET_PREFIX . 'Backend'
     *
     * @see BACKEND_FACTORY_GET_PREFIX
     */
    const BACKEND_FACTORY_GET_BACKEND = 'backend_factory_get_backend';
    
    /**
     * We are retrieving an instance of BackendSystem. 
     * Shortcut for BACKEND_FACTORY_GET_PREFIX . 'system'
     *
     * @see BACKEND_FACTORY_GET_PREFIX
     */
    const BACKEND_FACTORY_GET_SYSTEM = 'backend_factory_get_system';
    
    /**
     * We are retrieving an instance of BackendAliases. 
     * Shortcut for BACKEND_FACTORY_GET_PREFIX . 'Aliases'
     *
     * @see BACKEND_FACTORY_GET_PREFIX
     */
    const BACKEND_FACTORY_GET_ALIASES = 'backend_factory_get_aliases';
    
    /**
     * We are retrieving an instance of BackendMailingList. 
     * Shortcut for BACKEND_FACTORY_GET_PREFIX . 'MailingList'
     *
     * @see BACKEND_FACTORY_GET_PREFIX
     */
    const BACKEND_FACTORY_GET_MAILINGLIST = 'backend_factory_get_mailinglist';
    
    /**
     * We are retrieving an instance of BackendCVS. 
     * Shortcut for BACKEND_FACTORY_GET_PREFIX . 'CVS'
     *
     * @see BACKEND_FACTORY_GET_PREFIX
     */
    const BACKEND_FACTORY_GET_CVS = 'backend_factory_get_cvs';
    
    /**
     * We are retrieving an instance of BackendSVN. 
     * Shortcut for BACKEND_FACTORY_GET_PREFIX . 'SVN'
     *
     * @see BACKEND_FACTORY_GET_PREFIX
     */
    const BACKEND_FACTORY_GET_SVN = 'backend_factory_get_svn';
    
    /**
     * Use this prefix to override plugin's backend.
     * eg: If docman uses its backend the event:
     *   BACKEND_FACTORY_GET_PREFIX . 'plugin_docman' 
     * will be launch to allow overriding.
     *
     * /!\ Please use this syntax only for non-core backends.
     * /!\ For core backends, use BACKEND_FACTORY_GET_SYSTEM & co
     * 
     * Listeners can override the backend by providing a subclass.
     *
     * Parameters:
     * 'base' => null
     * 
     * A backend class name in the 'base' parameter if needed.
     * The subclass must inherit from the wanted backend.
     */
    const BACKEND_FACTORY_GET_PREFIX = 'backend_factory_get_';

    /**
     * Use this event to get the class name of an external event type (plugins)
     * see git plugin for implementation example
     *
     * Parameters:
     *   'type'  =>
     *
     * Expected result:
     *   'class'        => (string) SystemEvent_Class_Name
     *   'dependencies' => (array) OPTIONAL parameters of injectDependencies method of SystemEvent (if any)
     *
     * Example:
     *    'type'         => 'EVENT_NAME'
     *    'class'        => SystemEvent_EVENT_NAME
     *    'dependencies' => array(UserManager::instance(), ProjectManager::instance())
     *
     * With:
     * class SystemEvent_EVENT_NAME {
     *     function injectDependencies(UserManager $user_manager, ProjectManager $project_manager) {
     *         ...
     *     }
     * }
     */
     const GET_SYSTEM_EVENT_CLASS = 'get_system_event_class';

     /**
      * This event is used to get all reserved keywords provided by plugins for reference
      */
     const GET_PLUGINS_AVAILABLE_KEYWORDS_REFERENCES = 'get_plugins_available_keywords_references';

     /**
      * Allow to define specific references natures provided by a plugin
      * 
      * Parameters:
      *   'natures' => array of references natures
      * 
      * Expected result:
      *   A new nature added into $params['nature']
      *   array('keyword' => 'awsome', label => 'Really kick ass')
      */
     const GET_AVAILABLE_REFERENCE_NATURE = 'get_available_reference_natures';

     /**
      * Allow to override default behaviour when managing reference
      *
      * Parameters:
      *  'reference_manager' => ReferenceManager
      *  'project'           => Project
      *  'keyword'           => String
      *  'value'             => String
      *  'group_id'          => Integer
      *
      * Expected results:
      *  'reference'         => Reference | false
      */
     const GET_REFERENCE = 'get_reference';

     /**
      * Allow to define the group_id of an artifact reference
      *
      * Parameters
      *     'artifact_id' => Id of an artifact
      *
      * Expected results:
      *     'group_id'    => Id of the project the artifact belongs to
      */
     const GET_ARTIFACT_REFERENCE_GROUP_ID = 'get_artifact_reference_group_id';

     /**
      * Build a reference for given entry in database
      *
      * Parameters:
      *     'row'    => array, a row of "reference" database table
      *     'ref_id' => ??? reference id ?
      *
      * Expected result IN/OUT:
      *     'ref' => a Reference object
      */
     const BUILD_REFERENCE = 'build_reference';

    /**
     * Project unix name changed
     *
     * Parameters:
     *  'group_id' => Project ID
     *  'new_name' => The new unix name
     *
     * No expected results
     */
    const PROJECT_RENAME = 'project_rename';

    /**
     * User name changed
     *
     * Parameters:
     *  'user_id' => User ID
     *  'new_name' => The new user name
     *
     * No expected results
     */
    const USER_RENAME = 'user_rename';

    /**
     * Instanciate a new PFUser object from a row (probably DB)
     *
     * Parameters:
     *     'row' => DB row
     *
     * Expected results:
     *     'user' => a PFUser object
     */
    const USER_MANAGER_GET_USER_INSTANCE = 'user_manager_get_user_instance';

    const COMPUTE_MD5SUM = 'compute_md5sum';
    
    /**
     * List of lab features
     * 
     * Parameters:
     *   'lab_features' => array of lab features
     * 
     * Expected results
     *   array of array('title' => ..., 'description' => ..., 'cssclass' => ...)
     */
    const LAB_FEATURES_DEFINITION_LIST = 'lab_features_definition_list';
    
    /**
     * Get the additionnal types of system events
     *
     * Parameters:
     *  'types' => array of system event types
     *
     * Expected results
     *  array of string
     */
    const SYSTEM_EVENT_GET_TYPES = 'system_event_get_types';

    /**
     * Display javascript snippets in the page footer (just before </body>)
     *
     * No Parameters.
     * 
     * Expected result:
     *   Javascript snippets are directly output to the browser
     */
    const JAVASCRIPT_FOOTER = 'javascript_footer';
    
    /**
     * Get an instance of service object corresponding to $row
     * 
     * Parameters:
     *  'classnames' => array of Service child class names indexed by service short name
     *  
     * Example (in tracker plugin):
     * $params['classnames']['plugin_tracker'] = 'ServiceTracker'; 
     */
    const SERVICE_CLASSNAMES = 'service_classnames';
    
    /**
     * Get combined scripts
     * 
     * Parameters:
     *   'scripts' => array of scripts to combined
     *   
     * Examples:
     * $params['scripts'][] = '/path/to/script.js';
     */
    const COMBINED_SCRIPTS = 'combined_scripts';
    
    /**
     * Display javascript snippets in the page header (<head>)
     *
     * No Parameters.
     * 
     * Expected result:
     *   Javascript snippets are directly output to the browser
     */
    const JAVASCRIPT = 'javascript';
    
    /**
     * Manage the toggle of an element
     *
     * Parameter
     *  'id'   => the string identifier for the element
     *  'user' => the current user
     *
     * Expected result:
     *  'done' => set to true if the element has been toggled
     */
    const TOGGLE = 'toggle';
    
    /**
     * Display stuff in the widget public_areas (displays entries for the project services)
     *
     * Parameters:
     *   'project' => The project
     *
     * Expected result:
     *   'areas'   => array of string(html)
     */
    const SERVICE_PUBLIC_AREAS = 'service_public_areas';
    
    /**
     * Let display a sparkline next to a cross reference
     *
     * Parameters:
     *   'reference' => the Reference
     *   'keyword'   => the keyword used
     *   'group_id'  => the group_id
     *   'val'       => the val of the cross ref
     *
     * Expected result:
     *   'sparkline' => The url to the sparkline image
     */
    const AJAX_REFERENCE_SPARKLINE = 'ajax_reference_sparkline';
    
    /**
     * Say if we can display a [remove] button on a given wiki page
     *  
     * Parameters:
     *   'group_id'  => The project id
     *   'wiki_page' => The wiki page
     *   
     * Expected result:
     *   'display_remove_button' => boolean, true if ok false otherwise
     */
    const WIKI_DISPLAY_REMOVE_BUTTON = 'wiki_display_remove_button';

    /**
     * Allow to replace the default SVN_Apache_Auth object to be used for
     * generation of project svn apache authentication
     * 
     * Parameters:
     *     'project_info'    => A row of Projects DB table
     * 
     * Expected result:
     *     'svn_apache_auth' => SVN_Apache_Auth, object to generate the conf if relevant
     */
    const SVN_APACHE_AUTH = 'svn_apache_auth';
    
    /**
     * Extends doc to soap types.
     *
     * Parameters:
     *     'doc2soap_types' => The already defined map of doc -> soap types
     *
     * Expected results
     *     'doc2soap_types' => The extended map of doc -> soap types
     */
    const WSDL_DOC2SOAP_TYPES = 'wsdl_doc2soap_types';

    /**
     * Check that the update of members of an ugroup is allowed or not.
     *
     * Parameters:
     *     'ugroup_id' => Id of the ugroup
     *
     * Expected results
     *     'allowed' => Boolean indicating that the update of members of the ugroup is allowed
     */
    const  UGROUP_UPDATE_USERS_ALLOWED = 'ugroup_update_users_allowed';

    /**
     * Raised when an ugroup is bound to another one
     *
     * Parameters
     *     'ugroup' => UGroup The modified ugroup
     *     'source' => UGroup The new ugroup we bind with
     *
     * Expected results:
     *     void
     */
    const UGROUP_MANAGER_UPDATE_UGROUP_BINDING_ADD = 'ugroup_manager_update_ugroup_binding_add';

    /**
     * Raised when an ugroup binding is removed
     *
     * Parameters
     *     'ugroup' => UGroup The modified ugroup (no longer bound)
     *
     * Expected results:
     *     void
     */
    const UGROUP_MANAGER_UPDATE_UGROUP_BINDING_REMOVE = 'ugroup_manager_update_ugroup_binding_remove';

    /**
     * Display information about SOAP end points
     *
     * Parameters:
     *    None
     * Expected results
     *    'end_points' => array of array(
     *        'title'       => '',
     *        'wsdl'        => '',
     *        'wsdl_viewer' => '',
     *        'description' => '',
     *    );
     */
    const SOAP_DESCRIPTION = 'soap_description';

    /**
     * Get ldap login for a given user
     *
     * Parameters:
     *    'user'  => User object
     *
     * Expected results:
     *    'login' => String, ldap username
     */
    const GET_LDAP_LOGIN_NAME_FOR_USER = 'get_ldap_login_name_for_user';

    /**
     * Get the content of the sprint
     *
     * Parameters:
     *    'user'  => The User who made the request
     *    'links' => The Systray_LinksCollection
     *
     * Expected results:
     *    The links are added in the collection
     */
    const SYSTRAY = 'systray';

    /**
     * Event launched during the system check event
     *
     * Parameters:
     *   'logger' => Logger
     *
     * Expected results:
     *    An exception is raised if the system check is in error
     */
    const PROCCESS_SYSTEM_CHECK = 'proccess_system_check';

    /**
     * Event launched during the project creation
     * when we have to rewrite some service URLs
     *
     * Parameters:
     *    'link'  => The service link to modify
     *    'template' => The project used as a template
     *    'project' => The project newly created
     *
     * Expected results:
     *  The link contains the right project information
     */
    const SERVICE_REPLACE_TEMPLATE_NAME_IN_LINK = 'service_replace_template_name_in_link';

    /**
     * Event launched while exporting a project into xml format
     *
     * Parameters:
     *   'project'  => The given project
     *   'into_xml' => The SimpleXMLElement to fill in
     *
     * Expected Results:
     *   The various plugins inject stuff in the given xml element
     */
    const EXPORT_XML_PROJECT = 'export_xml_project';

    /**
     * Event launched while importing a project from a xml content
     *
     * Parameters:
     *   'project_id'  => The id of the project where trackers, cardwall and AD must be created
     *   'xml_content' => The xml content in string to check in
     *
     * Expected Results:
     *   The various plugins create objects from the xml content
     */
    const IMPORT_XML_PROJECT = 'import_xml_project';

    /**
     * Event launched while importing a project from a xml content
     *
     * Parameters:
     *   'project_id'  => The id of the project where trackers, cardwall and AD must be created
     *   'xml_content' => The SimpleXMLElement to check in
     *   'mapping'     => An array with a mapping between xml ids and new ids for trackers
     *
     * Expected Results:
     *   The various plugins create objects from the xml content
     */
    const IMPORT_XML_PROJECT_TRACKER_DONE = 'import_xml_project_tracker_done';

    /**
     * Event launched while importing a cardwall from a xml content
     *
     * Parameters:
     *   'project_id'  => The id of the project where trackers, cardwall and AD must be created
     *   'xml_content' => The SimpleXMLElement to check in
     *   'mapping'     => An array with a mapping between xml ids and new ids for trackers
     *
     * Expected Results:
     *   The various plugins create objects from the xml content
     */
    const IMPORT_XML_PROJECT_CARDWALL_DONE = 'import_xml_project_cardwall_done';

    /**
     * Event raised when svn hooks are updated
     *
     * Paramters:
     *     'group_id' => The id of the project
     *
     * Expected results:
     *     Void
     */
    const SVN_UPDATE_HOOKS = 'svn_update_hooks';

    /**
     * Event raised when a project has a new parent
     *
     * Parameters:
     *     'group_id' => The id of the child project
     *     'parent_group_id' => the id of the parent project
     *
     * Expected results:
     *     Void
     */
    const PROJECT_SET_PARENT_PROJECT= 'project_set_parent_project';

    /**
     *  Event raised when project parent is removed
     *
     * Parameters:
     *     'group_id' => The id of the child project
     *
     * Expected results:
     *     Void
     */
    const PROJECT_UNSET_PARENT_PROJECT= 'project_unset_parent_project';

    /**
     * Build search entries in Layout
     *
     * Parameters:
     *     'type_of_search' => String type of search (wiki, snippet, etc)
     *     'search_entries' => Array (OUT) where to add entries
     *     'hidden_fields'  => Array (OUT) add extra info on search
     */
    const LAYOUT_SEARCH_ENTRY = 'layout_search_entry';

    /**
     * Register REST resources
     *
     * Parameters:
     *  'restler' => \Luracast\Restler\Restler
     */
    const REST_RESOURCES = 'rest_resources';

    /**
     * Event raised to get plannings from a project with REST
     *
     * Parameters:
     *     'version' => String
     *     'project' => Project
     *     'result'  => String
     */
    const REST_GET_PROJECT_PLANNINGS = 'rest_get_project_plannings';

    /**
     * Event raised to get plannings options from a project with REST
     *
     * Parameters:
     *     'version' => String
     *     'project' => Project
     *     'result'  => String
     */
    const REST_OPTIONS_PROJECT_PLANNINGS = 'rest_options_project_plannings';
}
?>

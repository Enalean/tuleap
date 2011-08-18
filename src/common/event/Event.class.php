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
     * The trackers from a project have been duplicated in another project
     *
     * Parameters:
     * 'reportMapping'  => The mapping between source and target project trackers reports
     * 'trackerMapping' => The mapping between source and target project trackers
     * 'ugroupsMapping' => The mapping between source and target project ugroups
     * 'group_id        => The id of the target project
     *
     * No expected results
     */
    const TRACKERS_DUPLICATED = 'trackers_duplicated';

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
     * Display javascript snippets in the page footer (just before </body>)
     * No Parameters.
     * No expected results
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
     * Display javascript snippets
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
}
?>
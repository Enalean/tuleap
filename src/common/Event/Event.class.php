<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

class Event // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /**
     * Periodical system check event.
     *
     * No Parameters.
     * No expected results
     */
    public const SYSTEM_CHECK = 'system_check';

    /**
     * The user has just changed his ssh authorized keys.
     *
     * Parameters:
     *  'user' => User
     *
     * No expected results
     */
    public const EDIT_SSH_KEYS = 'edit_ssh_keys';

    /**
     * Dump all ssh keys.
     *
     * No parameters
     * No expected results
     */
    public const DUMP_SSH_KEYS = 'dump_ssh_keys';

    /**
     * Force aliases database update.
     *
     * No Parameters.
     * No expected results
     */
    public const UPDATE_ALIASES = 'update_aliases';

    /**
     * The user is updated
     *
     * Parameters:
     *   'old_user' => PFUser, the user object prior to modifications
     *   'new_user' => PFUser, the user object, modified
     *
     * No expected results
     */
    public const USER_MANAGER_UPDATE_DB = 'user_manager_update_db';

    /**
     * We are retrieving an instance of Backend.
     * Shortcut for BACKEND_FACTORY_GET_PREFIX . 'Backend'
     *
     * @see BACKEND_FACTORY_GET_PREFIX
     */
    public const BACKEND_FACTORY_GET_BACKEND = 'backend_factory_get_backend';

    /**
     * We are retrieving an instance of BackendSystem.
     * Shortcut for BACKEND_FACTORY_GET_PREFIX . 'system'
     *
     * @see BACKEND_FACTORY_GET_PREFIX
     */
    public const BACKEND_FACTORY_GET_SYSTEM = 'backend_factory_get_system';

    /**
     * We are retrieving an instance of BackendAliases.
     * Shortcut for BACKEND_FACTORY_GET_PREFIX . 'Aliases'
     *
     * @see BACKEND_FACTORY_GET_PREFIX
     */
    public const BACKEND_FACTORY_GET_ALIASES = 'backend_factory_get_aliases';

    /**
     * We are retrieving an instance of BackendMailingList.
     * Shortcut for BACKEND_FACTORY_GET_PREFIX . 'MailingList'
     *
     * @see BACKEND_FACTORY_GET_PREFIX
     */
    public const BACKEND_FACTORY_GET_MAILINGLIST = 'backend_factory_get_mailinglist';

    /**
     * We are retrieving an instance of BackendCVS.
     * Shortcut for BACKEND_FACTORY_GET_PREFIX . 'CVS'
     *
     * @see BACKEND_FACTORY_GET_PREFIX
     */
    public const BACKEND_FACTORY_GET_CVS = 'backend_factory_get_cvs';

    /**
     * We are retrieving an instance of BackendSVN.
     * Shortcut for BACKEND_FACTORY_GET_PREFIX . 'SVN'
     *
     * @see BACKEND_FACTORY_GET_PREFIX
     */
    public const BACKEND_FACTORY_GET_SVN = 'backend_factory_get_svn';

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
    public const BACKEND_FACTORY_GET_PREFIX = 'backend_factory_get_';

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
    public const GET_SYSTEM_EVENT_CLASS = 'get_system_event_class';

     /**
      * This event is used to get all reserved keywords provided by plugins for reference
      */
    public const GET_PLUGINS_AVAILABLE_KEYWORDS_REFERENCES = 'get_plugins_available_keywords_references';

     /**
      * Allow to define the group_id of an artifact reference
      *
      * Parameters
      *     'artifact_id' => Id of an artifact
      *
      * Expected results:
      *     'group_id'    => Id of the project the artifact belongs to
      */
    public const GET_ARTIFACT_REFERENCE_GROUP_ID = 'get_artifact_reference_group_id';

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
    public const BUILD_REFERENCE = 'build_reference';

    /**
     * Project unix name changed
     *
     * Parameters:
     *  'group_id' => Project ID
     *  'new_name' => The new unix name
     *
     * No expected results
     */
    public const PROJECT_RENAME = 'project_rename';

    /**
     * User name changed
     *
     * Parameters:
     *  'user_id'  => User ID
     *  'new_name' => The new user name
     *  'old_user' => The old user
     *
     * No expected results
     */
    public const USER_RENAME = 'user_rename';

    /**
     * Instanciate a new PFUser object from a row (probably DB)
     *
     * Parameters:
     *     'row' => DB row
     *
     * Expected results:
     *     'user' => a PFUser object
     */
    public const USER_MANAGER_GET_USER_INSTANCE = 'user_manager_get_user_instance';

    public const COMPUTE_MD5SUM = 'compute_md5sum';

    /**
     * Get the additionnal types of system events for default queue
     *
     * Parameters:
     *  'types' => array of system event types
     *
     * Expected results
     *  array of string
     */
    public const SYSTEM_EVENT_GET_TYPES_FOR_DEFAULT_QUEUE = 'system_event_get_types_for_default_queue';

    /**
     * Get the types of system events that are used in a custom queue
     *
     * Parameters:
     *  'queue' => the name of the queue
     *  'types' => array of system event types
     *
     * Expected results
     *  array of string
     */
    public const SYSTEM_EVENT_GET_TYPES_FOR_CUSTOM_QUEUE = 'system_event_get_types_for_custom_queue';

    /**
     * Get an instance of service object corresponding to $row
     *
     * Parameters:
     *  'classnames' => array of Service child class names indexed by service short name
     *
     * Example (in tracker plugin):
     * $params['classnames']['plugin_tracker'] = 'ServiceTracker';
     */
    public const SERVICE_CLASSNAMES = 'service_classnames';

    /**
     * Display javascript snippets in the page header (<head>)
     *
     * No Parameters.
     *
     * Expected result:
     *   Javascript snippets are directly output to the browser
     */
    public const JAVASCRIPT = 'javascript';

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
    public const TOGGLE = 'toggle';

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
    public const AJAX_REFERENCE_SPARKLINE = 'ajax_reference_sparkline';

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
    public const WIKI_DISPLAY_REMOVE_BUTTON = 'wiki_display_remove_button';

    /**
     * Allow to replace the default SVN_Apache_Auth object to be used for
     * generation of project svn apache authentication
     *
     * Parameters:
     *     'project_info'              => A row of Projects DB table
     *     'cache_parameters'          => Parameters concerning SVN authentication cache
     *     'svn_apache_auth'           => SVN_Apache_Auth, object to generate the conf if relevant
     */
    public const SVN_APACHE_AUTH = 'svn_apache_auth';

    /**
     * Extends doc to soap types.
     *
     * Parameters:
     *     'doc2soap_types' => The already defined map of doc -> soap types
     *
     * Expected results
     *     'doc2soap_types' => The extended map of doc -> soap types
     */
    public const WSDL_DOC2SOAP_TYPES = 'wsdl_doc2soap_types';

    /**
     * Check that the update of members of an ugroup is allowed or not.
     *
     * Parameters:
     *     'ugroup_id' => Id of the ugroup
     *
     * Expected results
     *     'allowed' => Boolean indicating that the update of members of the ugroup is allowed
     */
    public const  UGROUP_UPDATE_USERS_ALLOWED = 'ugroup_update_users_allowed';

    /**
     * Raised when an ugroup is bound to another one
     *
     * Parameters
     *     'ugroup' => ProjectUGroup The modified ugroup
     *     'source' => ProjectUGroup The new ugroup we bind with
     *
     * Expected results:
     *     void
     */
    public const UGROUP_MANAGER_UPDATE_UGROUP_BINDING_ADD = 'ugroup_manager_update_ugroup_binding_add';

    /**
     * Raised when an ugroup binding is removed
     *
     * Parameters
     *     'ugroup' => ProjectUGroup The modified ugroup (no longer bound)
     *
     * Expected results:
     *     void
     */
    public const UGROUP_MANAGER_UPDATE_UGROUP_BINDING_REMOVE = 'ugroup_manager_update_ugroup_binding_remove';

    /**
     * Get ldap login for a given user
     *
     * Parameters:
     *    'user'  => User object
     *
     * Expected results:
     *    'login' => String, ldap username
     */
    public const GET_LDAP_LOGIN_NAME_FOR_USER = 'get_ldap_login_name_for_user';

    /**
     * Event launched during the system check event
     *
     * Parameters:
     *   'logger' => Logger
     *
     * Expected results:
     *    An exception is raised if the system check is in error
     */
    public const PROCCESS_SYSTEM_CHECK = 'proccess_system_check';

    /**
     * Event launched while importing a project from a xml content
     *
     * Parameters:
     *   'project'             => The project where trackers, cardwall and AD must be created
     *   'xml_content'         => The xml content in string to check in
     *   'extraction_path'     => Path where archive has been extracted
     *   'user_finder'         => IFindUserFromXMLReference
     *   'mappings_registery'  => The XML mappings
     *   'configuration' => ImportConfig configuration of import
     *
     * Expected Results:
     *   The various plugins create objects from the xml content
     */
    public const IMPORT_XML_PROJECT = 'import_xml_project';

    /**
     * Event launched to check if a project can be imported from XML content
     *
     * Parameters:
     *   'project'         => The project where trackers, cardwall and AD must be created
     *   'xml_content'     => The xml content in string to check in
     *   'user_finder'     => IFindUserFromXMLReference
     *
     * Expected Results:
     *   'errors' => All errors messages have been collected
     */
    public const COLLECT_ERRORS_WITHOUT_IMPORTING_XML_PROJECT = 'collect_errors_without_importing_xml_project';

    /**
     * Event launched when compat cross ref needs to be imported from XML content
     *
     * Parameters:
     *   'created_refs'  => An array mapping array(object_name => array( original_id_in_xml => imported_id_in_tuleap ))
     *   'service_name'  => Service name
     *   'xml_content'   => SimpleXMLElement node containing references (<reference source="..." target="..."/>)
     *   'logger'        => Import logger
     *   'project'       => The project being imported
     *   'configuration' => ImportConfig configuration of import
     */
    public const IMPORT_COMPAT_REF_XML = 'import_compat_ref_xml';

    /**
     * Event raised to display the list of deleted Trackers
     *
     * Expected results:
     *     Void
     */
    public const LIST_DELETED_TRACKERS = 'display_deleted_trackers';

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
    public const IMPORT_XML_PROJECT_CARDWALL_DONE = 'import_xml_project_cardwall_done';

    /**
     * Event raised when svn hooks are updated
     *
     * Paramters:
     *     'group_id' => The id of the project
     *
     * Expected results:
     *     Void
     */
    public const SVN_UPDATE_HOOKS = 'svn_update_hooks';

    /**
     * Event raised when an admin changes the SVN authentication cache parameters
     */
    public const SVN_AUTH_CACHE_CHANGE = 'svn_auth_cache_change';

    /**
     * Event raised to see if additional info must be displayed in SVN homepage
     *
     * Paramters:
     *     'group_id'            => The id of the project
     *     'user_id'             => The id of the user
     *     'svn_intro_in_plugin' => boolean
     *     'svn_intro_info'      => mixed
     *
     */
    public const SVN_INTRO = 'svn_intro';

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
    public const PROJECT_SET_PARENT_PROJECT = 'project_set_parent_project';

    /**
     *  Event raised when project parent is removed
     *
     * Parameters:
     *     'group_id' => The id of the child project
     *
     * Expected results:
     *     Void
     */
    public const PROJECT_UNSET_PARENT_PROJECT = 'project_unset_parent_project';

    /**
     * Build search entries in Layout
     *
     * Parameters:
     *     'type_of_search' => String type of search (wiki, snippet, etc)
     *     'search_entries' => Array (OUT) where to add entries
     *     'hidden_fields'  => Array (OUT) add extra info on search
     */
    public const LAYOUT_SEARCH_ENTRY = 'layout_search_entry';

    /**
     * Fetches the sidebar options for searching on the serach homepgae. This is
     * only for display; it does not execute any search query whilst fetching
     * the search sidebar options.
     */
    public const SEARCH_TYPES_PRESENTERS = 'search_types_presenters';

    /**
     * Sends-out a search a query
     *
     * Parameters:
     *  'query'   => Search_SearchQuery - object representing query details
     *  'results' => Search_SearchResults - search results object
     */
    public const SEARCH_TYPE = 'search_type';

    /**
     * Register REST resources
     *
     * Parameters:
     *  'restler' => \Luracast\Restler\Restler
     */
    public const REST_RESOURCES = 'rest_resources';

    /**
     * Register REST Additional informations for project
     *
     * Parameters:
     *  'project'      => Project
     *  'current_user' => PFUser
     *  'informations  => array
     */
    public const REST_PROJECT_ADDITIONAL_INFORMATIONS = 'rest_project_additional_informations';

    /**
     * Allow plugin to add another ways to log in
     *
     * Parameters:
     * 'login_additional_connector' => string
     */
    public const LOGIN_ADDITIONAL_CONNECTOR = 'login_additional_connector';

    /**
     * Get the login, can be used to redirect a non logged user for example
     *
     * Parameters:
     *   'return_to' => string (IN)
     *   'login_url' => string (OUT)
     */
    public const GET_LOGIN_URL = 'get_login_url';

    /**
     * Event raised to get the list of resources associated with a project
     *
     * Parameters:
     *     'version'   => String
     *     'project'   => Project
     *     'resources' => array
     */
    public const REST_PROJECT_RESOURCES = 'rest_project_resources';

    /**
     * Detect the project id in a url
     *
     * Parameters:
     *  'url'        => string
     *
     * Expected result:
     *  'project_id' => int
     *
     * @deprecated
     * @see \Tuleap\Request\DispatchableWithProject
     */
    public const GET_PROJECTID_FROM_URL = 'get_projectid_from_url';

    /**
     * Sends-out an event to see if the user can access a page.
     * E.g. a mediawiki page in a private project and the user has
     * been delegated mediawiki admin rights across the forge
     *
     * Parameters:
     *    'can_access' => boolean
     *    'user'       => PFUser
     *
     * Expected result:
     *    'can_access' => boolean
     */
    public const HAS_USER_BEEN_DELEGATED_ACCESS = 'has_user_been_delegated_access';

    /**
     * Sends-out an event to get all services that handle independently restricted users
     *
     * Parameters:
     *    'allowed_services' => array
     *
     * Expected result:
     *    'allowed_services' => array
     */
    public const GET_SERVICES_ALLOWED_FOR_RESTRICTED = 'get_services_allowed_for_restricted';

    /**
     * We are writing aliases, so if you have any it is time to give them.
     *
     * Expected result:
     *      'aliases' => System_Alias[]
     */
    public const BACKEND_ALIAS_GET_ALIASES = 'backend_alias_get_aliases';

    /**
     *  Parameters:
     *      'project'     => Project
     *      'success'     => boolean (true by default)
     *      'new_name'    => string
     */
    public const RENAME_PROJECT = 'rename_project';

    /**
     *  Parameters:
     *      'executed_events_ids' => array
     *      'queue_name'          => string
     */
    public const POST_SYSTEM_EVENTS_ACTIONS = 'post_system_events_actions';

    /**
     * Event raised to get project's Git repositories information with REST
     *
     * Parameters:
     *     'version'         => String
     *     'project'         => Project
     *     'result'          => array
     *     'limit'           => int
     *     'offset'          => int
     *     'fields'          => String
     *     'query'           => String
     *     'order_by'        => String push_date|path
     *     'total_git_repo'  => int
     */
    public const REST_PROJECT_GET_GIT = 'rest_project_get_git';

    /**
     * When access level of project changes
     *
     * Parameters:
     *      'project_id' => int,
     *      'access'     => string
     *      'old_access' => string (previous access)
     *
     */
    public const PROJECT_ACCESS_CHANGE = 'project_access_change';

    /**
     * When access level of platform changes
     */
    public const SITE_ACCESS_CHANGE = 'site_access_change';

    /**
     * When a user account is created
     *
     * Parameter:
     *     'can_access' => bool,
     *     'user'       => PFUser
     *     'project'    => Project
     */
    public const CAN_USER_ACCESS_UGROUP_INFO = 'can_user_access_ugroup_info';

    /**
     * Gather the services allowed for a given project
     *
     * Parameters:
     *     'project'  => Project (IN)
     *     'services' => array of allowed services (OUT)
     */
    public const SERVICES_ALLOWED_FOR_PROJECT = 'services_allowed_for_project';

    /**
     * Gather the services who can send truncated emails
     *
     * Parameters:
     *     'project'  => Project
     *     'services' => array
     */
    public const SERVICES_TRUNCATED_EMAILS = 'services_truncated_emails';

    /**
     * When a user group is modified
     *
     * Parameters:
     *   'project'         => Project
     *   'new_ugroup_name' => string
     *   'old_ugroup_name' => string
     */
    public const UGROUP_RENAME = 'ugroup_rename';

    /**
     * Retrieve project_id for an artifact reference
     *
     * Parameters:
     *   artifact_id => int
     *   reference   => Reference
     */
    public const SET_ARTIFACT_REFERENCE_GROUP_ID = 'set_artifact_reference_group_id';

    /**
     * Toggle usage of a service
     *
     * Parameters
     *   shortname => string
     *   is_used   => boolean
     *   group_id  => int
     */
    public const SERVICE_IS_USED = 'service_is_used';

    /**
     * When an SVN repository is created
     *
     * Parameters:
     *  - project_id => (input) int
     *
     */
    public const SVN_REPOSITORY_CREATED = 'svn_repository_created';

    /**
     * When a project is created, ask plugins if some actions must be made for ugroup duplication
     *
     * Parameters:
     *  - source_ugroup => (input) Ugroup
     *  - new_ugroup_id => (input) int
     *
     */
    public const UGROUP_DUPLICATION = 'ugroup_duplication';

    /**
     * CSS stylesheets that will be added to the page's <head>
     *
     * Parameters:
     *    - stylesheets: String[] css paths to be added
     *    - theme_variation: \Tuleap\Layout\ThemeVariation
     *
     * @deprecated
     */
    public const BURNING_PARROT_GET_STYLESHEETS = 'burning_parrot_get_stylesheets';

    /**
     * JavaScript files that will be added to the page's <footer>
     *
     * Parameters:
     *    - javascript_files: String[] js paths to be added
     *
     * @deprecated
     */
    public const BURNING_PARROT_GET_JAVASCRIPT_FILES = 'burning_parrot_get_javascript_files';

    /**
     * Event raised when site admin wants to send email to the platform
     *
     * Parameters:
     *    'destination' => 'comm' | 'sf' | 'all' | 'admin' | 'sfadmin' | 'devel',
     *    'message'     => <string>,
     *    'subject'     => <string>
     */
    public const MASSMAIL = 'massmail';


    /**
     * Event sent to know where redirect after sending massmail
     *
     * Parameters:
     */
    public const AFTER_MASSMAIL_TO_PROJECT_ADMINS = 'after_massmail_to_project_admins';

    /**
     * Event sent to get whitelisted external scripts that can be loaded
     *
     * Parameters:
     * 'whitelist_scripts' => array (OUT)
     */
    public const CONTENT_SECURITY_POLICY_SCRIPT_WHITELIST = 'content_security_policy_script_whitelist';

    /**
     * Event sent at the beginning of display the homepage
     */
    public const DISPLAYING_HOMEPAGE = 'displaying_homepage';

    /**
     * Event raised while displaying the user statistics widget on site admin homepage
     *
     * Parameters:
     *  - nb_users_by_status    => (in)  Tuleap\Admin\Homepage\NbUsersByStatus
     *  - additional_statistics => (out) Tuleap\Admin\Homepage\StatisticsPresenter[]
     */
    public const GET_SITEADMIN_HOMEPAGE_USER_STATISTICS = 'get_siteadmin_homepage_user_statistics';

    /**
     * Event raised to collect warnings to be displayed to the site admins on their personal page
     * and siteadmin homepage
     *
     * Parameters:
     *  - nb_users_by_status => (in)  Tuleap\Admin\Homepage\NbUsersByStatus
     *  - warnings           => (out) string[] (html snippets)
     */
    public const GET_SITEADMIN_WARNINGS = 'get_siteadmin_warnings';

    /**
     * Event raised when references are extracted from a text (commit message, follow-up comments, …).
     * It's up to the listener of this event to do custom behavior for this reference.
     *
     * Parameters:
     *  - cross_reference            => CrossReference (IN)
     *  - reverse_reference_instance => Tuleap\Reference\ReferenceInstance (IN)
     */
    public const POST_REFERENCE_EXTRACTED = 'post_reference_extracted';

    /**
     * Event raised to remove a cross reference in the db.
     * See @DOES_CROSS_REFERENCE_EXIST
     *
     * Parameters
     *  - cross_reference      => CrossReference (IN)
     *  - is_reference_removed => bool (OUT)
     */
    public const REMOVE_CROSS_REFERENCE = 'remove_cross_reference';

    /**
     * Event raised to know if a reference can be edited/deleted
     *
     * Parameters
     *  - reference      => Reference (IN)
     *  - can_be_deleted => bool (OUT)
     *  - can_be_edited  => bool (OUT)
     */
    public const GET_REFERENCE_ADMIN_CAPABILITIES = 'get_reference_admin_capabilities';

    /**
     * Clear the user's history
     *
     * Parameters
     *  - user    => PFUser (IN)
     */
    public const USER_HISTORY_CLEAR = 'user_history_clear';
}

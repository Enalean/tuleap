<?php
/**
 * API for role-based access control
 * Defined at Planetforge.org
 *
 * Copyright 2010, Roland Mas
 *
 * This file is part of FusionForge. FusionForge is free software;
 * you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or (at your option)
 * any later version.
 *
 * FusionForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with FusionForge; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

// See http://wiki.planetforge.org/index.php/RBAC_API#Interfaces

// Constants to identify role classes
define ("PFO_ROLE_EXPLICIT",  1) ;
define ("PFO_ROLE_ANONYMOUS", 2) ;
define ("PFO_ROLE_LOGGEDIN",  3) ;
define ("PFO_ROLE_UNION",     4) ;

/**
 * Interface for the RBAC engine
 * @author Roland Mas
 *
 * This interface is meant to be implemented with a singleton pattern. 
 * Its methods use the session management to decide what roles are available within the current session (if any), 
 * and to provide the answer to the question “Does the current client have the permission for this action?”. 
 * Other interesting questions that this interface is meant to answer include “does another account have the permission for that action?” 
 * and, more generically, “who is allowed that action?”. 
 */
interface PFO_RBACEngine {
	/**
	 * singleton creator
	 */
	public static function getInstance() ;
	/**
	 * returns roles available to the user in the current session
	 */
	public function getAvailableRoles() ; // From session
	/**
	 * TODO Enter description here ...
	 * @param string $section
	 * @param unknown_type $reference group_id, ...
	 * @param string $action
	 */
	public function isActionAllowed($section, $reference, $action = NULL) ;
	public function isGlobalActionAllowed($section, $action = NULL) ;
	public function isActionAllowedForUser($user, $section, $reference, $action = NULL) ;
	public function isGlobalActionAllowedForUser($user, $section, $action = NULL) ;
	public function getRolesByAllowedAction($section, $reference, $action = NULL) ;
	public function getUsersByAllowedAction($section, $reference, $action = NULL) ;
}

/**
 * Interfaces for the capabilities
 * @author Roland Mas
 *
 * Abstract interface, not meant to be implemented directly. 
 */
interface PFO_Role {
	public function getName() ;
	public function setName($name) ;
	public function getID() ;

	public function isPublic() ;
	public function setPublic($flag) ;
	/**
	 * TODO: Enter description here ...
	 * NULL if role is “floating” 
	 */
	public function getHomeProject() ;
	public function getLinkedProjects() ;
	public function linkProject($project) ;
	public function unlinkProject($project) ;

	public function getUsers() ;
	public function hasUser($user) ;
	public function hasPermission($section, $reference, $action = NULL) ;
	public function hasGlobalPermission($section, $action = NULL) ;
	public function normalizeData() ;
	public function getSettings() ;
	public function getSettingsForProject($project) ;
	public function setSettings($data) ;
}

/**
 * Standard, explicit membership role (members are list of usernames). 
 * @author Roland Mas
 *
 */
interface PFO_RoleExplicit extends PFO_Role {
	const roleclass = PFO_ROLE_EXPLICIT ;
	public function addUsers($users) ;
	public function removeUsers($users) ;
}

/**
 * Union of roles.
 * @author Roland Mas
 *
 */
interface PFO_RoleUnion extends PFO_Role {
	const roleclass = PFO_ROLE_UNION ;
	public function addRole($role) ;
	public function removeRole($role) ;
}

/**
 * Implicit membership role : always applying
 * 
 * Global scope (public, no home project), always available (even when logged in). hasUser() always returns true. 
 * @author Roland Mas
 *
 */
interface PFO_RoleAnonymous extends PFO_Role {
	const roleclass = PFO_ROLE_ANONYMOUS ;
}

/**
 * Implicit membership role : the client has opened a session
 * 
 * Global scope (public, no home project), available whenever a valid session is opened. hasUser() always returns true.
 * @author Roland Mas
 *
 */
interface PFO_RoleLoggedin extends PFO_Role {
	const roleclass = PFO_ROLE_LOGGEDIN ;
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:

?>

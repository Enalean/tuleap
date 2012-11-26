<?php
/**
 * FusionForge RBAC engine
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

require_once $gfcommon.'include/RBAC.php' ;

/**
 * TODO: Enter description here ...
 *
 */
class RBACEngine extends Error implements PFO_RBACEngine {
	private static $_instance ;
	private $_cached_roles = array () ;
	private $_cached_available_roles = NULL ;
	private $_cached_global_roles = NULL ;
	private $_cached_public_roles = NULL ;

	// singleton constructor
	public static function getInstance() {
		if (!isset(self::$_instance)) {
			$c = __CLASS__;
			self::$_instance = new $c;
		}
		
		return self::$_instance;
	}

	/* (non-PHPdoc)
	 * @see PFO_RBACEngine::getAvailableRoles()
	 */
	public function getAvailableRoles() {
		if ($this->_cached_available_roles != NULL) {
			return $this->_cached_available_roles ;
		}

		$this->_cached_available_roles = array () ;

		$this->_cached_available_roles[] = RoleAnonymous::getInstance() ;
		
		if (session_loggedin()) {
			$this->_cached_available_roles[] = RoleLoggedIn::getInstance() ;
			$user = session_get_user() ;

			if (USE_PFO_RBAC) {
				$res = db_query_params ('SELECT role_id FROM pfo_user_role WHERE user_id=$1',
						array ($user->getID()));
				while ($arr = db_fetch_array($res)) {
					$this->_cached_available_roles[] = $this->getRoleById ($arr['role_id']) ;
				}
			} else {
				$groups = $user->getGroups() ;
				foreach ($groups as $g) {
					$this->_cached_available_roles[] = $user->getRole($g) ;
				}
			}
		}
		
		$params = array();
		$params['current_roles'] = $this->_cached_available_roles;
		$params['new_roles'] = array();
		plugin_hook_by_reference('get_extra_roles', $params);
		foreach ($params['new_roles'] as $r) {
			$this->addAvailableRole($r);
		}
		
		$params = array();
		$params['current_roles'] = $this->_cached_available_roles;
		$params['dropped_roles'] = array();
		plugin_hook_by_reference('restrict_roles', $params);
		foreach ($params['dropped_roles'] as $r) {
			$this->dropAvailableRole($r);
		}
		
		return $this->_cached_available_roles ;
	}

	private function addAvailableRole($role) {
		$seen = false;
		foreach ($this->_cached_available_roles as $r) {
			if ($r->getID() == $role->getID()) {
				$seen = true;
			}
		}
		if (!$seen) {
			$this->_cached_available_roles[] = $role;
		}
	}

	private function dropAvailableRole($role) {
		$new_roles = array();
		foreach ($this->_cached_available_roles as $r) {
			if ($r->getID() != $role->getID()) {
				$new_roles[] = $r;
			}
		}
		$this->_cached_available_roles = $new_roles;
	}

	public function getGlobalRoles() {
		if ($this->_cached_global_roles != NULL) {
			return $this->_cached_global_roles ;
		}

		$this->_cached_global_roles = array () ;

		if (USE_PFO_RBAC) {
			$res = db_query_params ('SELECT role_id FROM pfo_role WHERE home_group_id IS NULL',
						array ());
			while ($arr = db_fetch_array($res)) {
				$this->_cached_global_roles[] = $this->getRoleById ($arr['role_id']) ;
			}
		}
		
		return $this->_cached_global_roles ;
	}

	public function getPublicRoles() {
		if ($this->_cached_public_roles != NULL) {
			return $this->_cached_public_roles ;
		}

		$this->_cached_public_roles = array () ;

		if (USE_PFO_RBAC) {
			$res = db_query_params ('SELECT role_id FROM pfo_role WHERE is_public=$1',
						array ('true'));
			while ($arr = db_fetch_array($res)) {
				$this->_cached_public_roles[] = $this->getRoleById ($arr['role_id']) ;
			}
		}
		
		return $this->_cached_public_roles ;
	}

	public function invalidateRoleCaches () {
		$this->_cached_available_roles = NULL ;
		$this->_cached_global_roles = NULL ;
		$this->_cached_public_roles = NULL ;
	}

	public function getAvailableRolesForUser($user) {
		$result = array () ;

		$result[] = RoleAnonymous::getInstance() ;
		$result[] = RoleLoggedIn::getInstance() ;
		
		if (USE_PFO_RBAC) {
			$res = db_query_params ('SELECT role_id FROM pfo_user_role WHERE user_id=$1',
						array ($user->getID()));
			while ($arr = db_fetch_array($res)) {
				$result[] = $this->getRoleById ($arr['role_id']) ;
			}
		} else {
			$res = db_query_params ('SELECT role_id FROM user_group WHERE user_id=$1',
						array ($user->getID()));
			while ($arr = db_fetch_array($res)) {
				$result[] = $this->getRoleById ($arr['role_id']) ;
			}
		}
		
		return $result ;
	}

	/* (non-PHPdoc)
	 * @see PFO_RBACEngine::isActionAllowed()
	 */
	public function isActionAllowed ($section, $reference, $action = NULL) {
		$rlist = $this->getAvailableRoles () ;
		foreach ($rlist as $r) {
			if ($r->hasPermission ($section, $reference, $action)) {
				return true ;
			}
		}
		return false ;
	}

	public function isGlobalActionAllowed ($section, $action = NULL) {
		return $this->isActionAllowed ($section, -1, $action) ;
	}

	public function isActionAllowedForUser ($user, $section, $reference, $action = NULL) {
		$rlist = $this->getAvailableRolesForUser ($user) ;
		foreach ($rlist as $r) {
			if ($r->hasPermission ($section, $reference, $action)) {
				return true ;
			}
		}
		return false ;
	}

	public function isGlobalActionAllowedForUser ($user, $section, $action = NULL) {
		return $this->isActionAllowedForUser ($user, $section, -1, $action) ;
	}

	public function getRoleById ($role_id) {
		if (array_key_exists ($role_id, $this->_cached_roles)) {
			return $this->_cached_roles[$role_id] ;
		}
		if (USE_PFO_RBAC) {
			$res = db_query_params ('SELECT c.class_name, r.home_group_id FROM pfo_role r, pfo_role_class c WHERE r.role_class = c.class_id AND r.role_id = $1',
						array ($role_id)) ;
			if (!$res || !db_numrows($res)) {
				return NULL ;
			}
			
			$class_id = db_result ($res, 0, 'class_name') ;
			switch ($class_id) {
			case 'PFO_RoleExplicit':
				$group_id = db_result ($res, 0, 'home_group_id') ;
				$group = group_get_object ($group_id) ;
				$this->_cached_roles[$role_id] = new Role ($group, $role_id) ;
				return $this->_cached_roles[$role_id] ;
			case 'PFO_RoleAnonymous':
				$this->_cached_roles[$role_id] = RoleAnonymous::getInstance() ;
				return $this->_cached_roles[$role_id] ;
			case 'PFO_RoleLoggedIn':
				$this->_cached_roles[$role_id] = RoleLoggedIn::getInstance() ;
				return $this->_cached_roles[$role_id] ;
			default:
				throw new Exception ("Not implemented") ;
			}
		} else {
			$res = db_query_params ('SELECT group_id FROM role r WHERE role_id = $1',
						array ($role_id)) ;
			if (!$res || !db_numrows($res)) {
				return NULL ;
			}
			$group_id = db_result ($res, 0, 'group_id') ;
			$group = group_get_object ($group_id) ;
			return new Role ($group, $role_id) ;
		}
	}

	public function getRolesByAllowedAction ($section, $reference, $action = NULL) {
		$ids = $this->_getRolesIdByAllowedAction ($section, $reference, $action) ;
		$roles = array () ;
		foreach ($ids as $role_id) {
			$roles[] = $this->getRoleById ($role_id) ;
		}

		return $roles ;
	}

	public function getUsersByAllowedAction ($section, $reference, $action = NULL) {
		$roles = $this->getRolesByAllowedAction ($section, $reference, $action) ;
		$user_ids = array () ;
		foreach ($roles as $role) {
			foreach ($role->getUsers() as $user) {
				$user_ids[] = $user->getID() ;
			}
		}

		$user_ids = array_unique ($user_ids) ;

		return user_get_objects ($user_ids) ;
	}

	private function _getRolesIdByAllowedAction ($section, $reference, $action = NULL) {
		$result = array () ;
		$qpa = db_construct_qpa () ;
		$qpa = db_construct_qpa ($qpa,
					 'SELECT role_id FROM pfo_role_setting WHERE section_name=$1 AND ref_id=$2 ',
					 array ($section,
						$reference)) ;

		// Look for roles that are directly allowed to perform action

		switch ($section) {
		case 'forge_admin':
		case 'forge_read':
		case 'approve_projects':
		case 'approve_news':
		case 'project_admin':
		case 'project_read':
		case 'tracker_admin':
		case 'pm_admin':
		case 'forum_admin':
			$qpa = db_construct_qpa ($qpa, 'AND perm_val = 1') ;
			break ;
		case 'forge_stats':
			switch ($action) {
			case 'ANY':
				$qpa = db_construct_qpa ($qpa, 'AND perm_val != 0') ;
				break ;
			case 'read':
				$qpa = db_construct_qpa ($qpa, 'AND perm_val >= 1') ;
				break ;
			case 'admin':
				$qpa = db_construct_qpa ($qpa, 'AND perm_val >= 2') ;
				break ;
			}
			break ;
		case 'scm':
			switch ($action) {
			case 'ANY':
				$qpa = db_construct_qpa ($qpa, 'AND perm_val != 0') ;
				break ;
			case 'read':
				$qpa = db_construct_qpa ($qpa, 'AND perm_val >= 1') ;
				break ;
			case 'write':
				$qpa = db_construct_qpa ($qpa, 'AND perm_val >= 2') ;
				break ;
			}
			break ;
		case 'docman':
			switch ($action) {
			case 'ANY':
				$qpa = db_construct_qpa ($qpa, 'AND perm_val != 0') ;
				break ;
			case 'read':
				$qpa = db_construct_qpa ($qpa, 'AND perm_val >= 1') ;
				break ;
			case 'submit':
				$qpa = db_construct_qpa ($qpa, 'AND perm_val >= 2') ;
				break ;
			case 'approve':
				$qpa = db_construct_qpa ($qpa, 'AND perm_val >= 3') ;
				break ;
			case 'admin':
				$qpa = db_construct_qpa ($qpa, 'AND perm_val >= 4') ;
				break ;
			}
			break ;
		case 'frs':
			switch ($action) {
			case 'ANY':
				$qpa = db_construct_qpa ($qpa, 'AND perm_val != 0') ;
				break ;
			case 'read_public':
				$qpa = db_construct_qpa ($qpa, 'AND perm_val >= 1') ;
				break ;
			case 'read_private':
				$qpa = db_construct_qpa ($qpa, 'AND perm_val >= 2') ;
				break ;
			case 'write':
				$qpa = db_construct_qpa ($qpa, 'AND perm_val >= 3') ;
				break ;
			}
			break ;
		case 'forum':
			switch ($action) {
			case 'ANY':
				$qpa = db_construct_qpa ($qpa, 'AND perm_val != 0') ;
				break ;
			case 'read':
				$qpa = db_construct_qpa ($qpa, 'AND perm_val >= 1') ;
				break ;
			case 'post':
				$qpa = db_construct_qpa ($qpa, 'AND perm_val >= 2') ;
				break ;
			case 'unmoderated_post':
				$qpa = db_construct_qpa ($qpa, 'AND perm_val >= 3') ;
				break ;
			case 'moderate':
				$qpa = db_construct_qpa ($qpa, 'AND perm_val >= 4') ;
				break ;
			}
			break ;
		case 'tracker':
		case 'pm':
			switch ($action) {
			case 'ANY':
				$qpa = db_construct_qpa ($qpa, 'AND perm_val != 0') ;
				break ;
			case 'read':
				$qpa = db_construct_qpa ($qpa, 'AND (perm_val & 1) = 1') ;
				break ;
			case 'tech':
				$qpa = db_construct_qpa ($qpa, 'AND (perm_val & 2) = 2') ;
				break ;
			case 'manager':
				$qpa = db_construct_qpa ($qpa, 'AND (perm_val & 4) = 4') ;
				break ;
			}
			break ;
		default:
			$hook_params = array ();
			$hook_params['section'] = $section ;
			$hook_params['reference'] = $reference ;
			$hook_params['action'] = $action ;
			$hook_params['qpa'] = $qpa ;
			$hook_params['result'] = $result ;
			plugin_hook_by_reference ("list_roles_by_permission", $hook_params);
			$qpa = $hook_params['qpa'] ;
			break ;
		}

		$res = db_query_qpa ($qpa) ;
		if (!$res) {
			$this->setError('RBACEngine::getRolesByAllowedAction()::'.db_error());
			return false;
		}
		while ($arr = db_fetch_array($res)) {
			$result[] = $arr['role_id'] ;
		}

		// Also look for roles that can perform the action because they're more powerful

		switch ($section) {
		case 'forge_read':
		case 'approve_projects':
		case 'approve_news':
		case 'forge_stats':
		case 'project_admin':
			$result = array_merge ($result, $this->_getRolesIdByAllowedAction ('forge_admin', -1)) ;
			break ;
		case 'project_read':
		case 'tracker_admin':
		case 'pm_admin':
		case 'forum_admin':
		case 'scm':
		case 'docman':
		case 'frs':
			$result = array_merge ($result, $this->_getRolesIdByAllowedAction ('project_admin', $reference)) ;
			break ;
		case 'tracker':
			if ($action != 'tech') {
				$t = artifactType_get_object ($reference) ;
				$result = array_merge ($result, $this->_getRolesIdByAllowedAction ('tracker_admin', $t->Group->getID())) ;
			}
			break ;			
		case 'pm':
			if ($action != 'tech') {
				$t = projectgroup_get_object ($reference) ;
				$result = array_merge ($result, $this->_getRolesIdByAllowedAction ('pm_admin', $t->Group->getID())) ;
			}
			break ;			
		case 'forum':
			$t = forum_get_object ($reference) ;
			$result = array_merge ($result, $this->_getRolesIdByAllowedAction ('forum_admin', $t->Group->getID())) ;
			break ;			
		case 'new_tracker':
			if ($action != 'tech') {
				$result = array_merge ($result, $this->_getRolesIdByAllowedAction ('tracker_admin', $reference)) ;
			}
			break ;			
		case 'new_pm':
			if ($action != 'tech') {
				$result = array_merge ($result, $this->_getRolesIdByAllowedAction ('pm_admin', $reference)) ;
			}
			break ;			
		case 'new_forum':
			$t = forum_get_object ($reference) ;
			$result = array_merge ($result, $this->_getRolesIdByAllowedAction ('forum_admin', $reference)) ;
			break ;			
		}

		return array_unique ($result) ;
	}
}

/**
 * Check if permission is allowed for an action on a reference in the context of a section
 * @param string $section
 * @param unknown_type $reference (group_id, ...)
 * @param string $action
 */
function forge_check_perm ($section, $reference, $action = NULL) {
	$engine = RBACEngine::getInstance() ;

	return $engine->isActionAllowed($section, $reference, $action) ;
}

/**
 * TODO: Enter description here ...
 * @param unknown_type $section
 * @param unknown_type $action
 */
function forge_check_global_perm ($section, $action = NULL) {
	$engine = RBACEngine::getInstance() ;

	return $engine->isGlobalActionAllowed($section, $action) ;
}

function forge_check_perm_for_user ($user, $section, $reference, $action = NULL) {
	$engine = RBACEngine::getInstance() ;

	return $engine->isActionAllowedForUser($user, $section, $reference, $action) ;
}

function forge_check_global_perm_for_user ($user, $section, $action = NULL) {
	$engine = RBACEngine::getInstance() ;

	return $engine->isGlobalActionAllowedForUser($user, $section, $action) ;
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:

?>

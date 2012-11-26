<?php
/**
 * FusionForge role-based access control
 *
 * Copyright 2004, GForge, LLC
 * Copyright 2009-2010, Roland Mas
 * http://fusionforge.org
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

require "PFO-RBAC.interface.php";

// TODO : remove this which is security issue ?
if (true || file_exists ('/tmp/fusionforge-use-pfo-rbac')) {
	define ('USE_PFO_RBAC', true);
} else {
	define ('USE_PFO_RBAC', false);
}

// Code shared between classes

/**
 * TODO: RBAC::BaseRole Enter description here ...
 *
 */
abstract class BaseRole extends Error {
	/**
	 * TODO: Enter description here ...
	 * @var unknown_type
	 */
	var $role_values;
	/**
	 * TODO: Enter description here ...
	 * @var unknown_type
	 */
	var $defaults;
	/**
	 * TODO: Enter description here ...
	 * @var unknown_type
	 */
	var $global_settings;

	// var $perms_array;
	// var $setting_array;
	
	public function BaseRole() {
		if (USE_PFO_RBAC) {
			// TODO: document these tables
			// $gfcommon.'include/rbac_texts.php' may provide some hints...
			$this->role_values = array(
				'forge_admin' => array(0, 1),
				'approve_projects' => array(0, 1),
				'approve_news' => array(0, 1),
				'forge_stats' => array(0, 1, 2),

				'project_read' => array(0, 1),
				'project_admin' => array(0, 1),

				'tracker_admin' => array(0, 1),
				'pm_admin' => array(0, 1),
				'forum_admin' => array(0, 1),
				
				'tracker' => array(0, 1, 3, 5, 7),
				'pm' => array(0, 1, 3, 5, 7),
				'forum' => array(0, 1, 2, 3, 4),

				'new_tracker' => array(0, 1, 3, 5, 7),
				'new_pm' => array(0, 1, 3, 5, 7),
				'new_forum' => array(0, 1, 2, 3, 4),

				'scm' => array (0, 1, 2),
				'docman' => array (0, 1, 2, 3, 4),
				'frs' => array (0, 1, 2, 3),

				'webcal' => array(0, 1, 2),
				);

			// Global permissions
			$this->global_settings = array(
				'forge_admin', // “God mode”: all actions allowed 
				'approve_projects', // Ability to approve pending projects 
				'approve_news', // Ability to approve news bits to the forge front page 
				'forge_stats'
				);

			// TODO: document these	(Project-related permissions ?)
			$this->defaults = array(
				'Admin' => array(            'project_admin'=> 1,
							     'project_read' => 1,
							     'frs' => 2,
							     'scm' => 2,
							     'docman' => 3,
							     'forum_admin' => 1,
							     'new_forum' => 3,
							     'tracker_admin' => 1,
							     'new_tracker' => 7,
							     'pm_admin' => 1,
							     'new_pm' => 7,
							     'webcal' => 2,
					),
				'Senior Developer' => array( 'project_read' => 1,
							     'frs' => 2,
							     'scm' => 2,
							     'docman' => 3,
							     'forum_admin' => 1,
							     'new_forum' => 3,
							     'tracker_admin' => 1,
							     'new_tracker' => 7,
							     'pm_admin' => 1,
							     'new_pm' => 7,
							     'webcal' => 2,
					),
				'Junior Developer' => array( 'project_read' => 1,
							     'frs' => 2,
							     'scm' => 2,
							     'docman' => 2,
							     'new_forum' => 3,
							     'new_tracker' => 3,
							     'new_pm' => 3,
							     'webcal' => 2,
					),
				'Doc Writer' => array(       'project_read' => 1,
							     'frs' => 2,
						       	     'docman' => 4,
						       	     'new_forum' => 3,
						       	     'new_tracker' => 1,
						       	     'new_pm' => 1,
						       	     'webcal' => 2,
					),
				'Support Tech' => array(     'project_read' => 1,
							     'frs' => 2,
							     'docman' => 1,
							     'new_forum' => 3,
							     'tracker_admin' => 1,
							     'new_tracker' => 3,
							     'pm_admin' => 1,
							     'new_pm' => 7,
							     'webcal' => 2,
					),
				);
		} else {
			$this->role_values = array(
				'projectadmin'	=> array ('0','A'),
				'frs'		=> array ('0','1'),
				'scm'		=> array ('-1','0','1'),
				'docman'	=> array ('0','1'),
				'forumadmin'	=> array ('0','2'),
				'forum'		=> array ('-1','0','1','2'),
				'newforum'	=> array ('-1','0','1','2'),
				'trackeradmin'	=> array ('0','2'),
				'tracker'	=> array ('-1','0','1','2','3'),
				'newtracker'	=> array ('-1','0','1','2','3'),
				'pmadmin'	=> array ('0','2'),
				'pm'		=> array ('-1','0','1','2','3'),
				'newpm'		=> array ('-1','0','1','2','3'),
				'webcal'	=> array ('0','1','2'));
			
			$this->defaults = array(
				'Admin'		  => array( 'projectadmin'=>'A',
							    'frs'=>'1',
							    'scm'=>'1',
							    'docman'=>'1',
							    'forumadmin'=>'2',
							    'forum'=>'2',
							    'newforum'=>'2',
							    'trackeradmin'=>'2',
							    'tracker'=>'2',
							    'newtracker'=>'2',
							    'pmadmin'=>'2',
							    'pm'=>'2',
							    'newpm'=>'2',
							    'webcal'=>'1' ),
				'Senior Developer'=> array( 'projectadmin'=>'0',
							    'frs'=>'1',
							    'scm'=>'1',
							    'docman'=>'1',
							    'forumadmin'=>'2',
							    'forum'=>'2',
							    'newforum'=>'2',
							    'trackeradmin'=>'2',
							    'tracker'=>'2',
							    'newtracker'=>'2',
							    'pmadmin'=>'2',
							    'pm'=>'2',
							    'newpm'=>'2',
							    'webcal'=>'2' ),
				'Junior Developer'=> array( 'projectadmin'=>'0',
							    'frs'=>'0',
							    'scm'=>'1',
							    'docman'=>'0',
							    'forumadmin'=>'0',
							    'forum'=>'1',
							    'newforum'=>'1',
							    'trackeradmin'=>'0',
							    'tracker'=>'1',
							    'newtracker'=>'1',
							    'pmadmin'=>'0',
							    'pm'=>'1',
							    'newpm'=>'1',
							    'webcal'=>'2' ),
				'Doc Writer'	  => array( 'projectadmin'=>'0',
							    'frs'=>'0',
							    'scm'=>'0',
							    'docman'=>'1',
							    'forumadmin'=>'0',
							    'forum'=>'1',
							    'newforum'=>'1',
							    'trackeradmin'=>'0',
							    'tracker'=>'0',
							    'newtracker'=>'0',
							    'pmadmin'=>'0',
							    'pm'=>'0' ,
							    'newpm'=>'0' ,
							    'webcal'=>'2'),
				'Support Tech'	  => array( 'projectadmin'=>'0',
							    'frs'=>'0',
							    'scm'=>'0',
							    'docman'=>'1',
							    'forumadmin'=>'0',
							    'forum'=>'1',
							    'newforum'=>'1',
							    'trackeradmin'=>'0',
							    'tracker'=>'2',
							    'newtracker'=>'2',
							    'pmadmin'=>'0',
							    'pm'=>'0' ,
							    'newpm'=>'0' ,
							    'webcal'=>'2')
				);

		}
	}

	public function getUsers() {
		return array () ;
	}
	public function hasUser($user) {
		throw new Exception ("Not implemented") ;
	}
	function hasGlobalPermission($section, $action = NULL) {
		return $this->hasPermission ($section, -1, $action) ;
	}
	public function getSettings() {
		throw new Exception ("Not implemented") ;
	}
	public function setSettings($data) {
		throw new Exception ("Not implemented") ;
	}
	public function delete () {
		throw new Exception ("Not implemented") ;
	}

	/**
	 * getLinkedProjects - List of projects referencing that role
	 *
	 * Includes the home project (for roles that have one)
	 *
	 * @return	array	Array of Group objects
	 */
	public function getLinkedProjects() {
		$ids = array();

		$hp = $this->getHomeProject();
		if ($hp != NULL) {
			$ids[] = $hp->getID();
		}

		$res = db_query_params('SELECT group_id FROM role_project_refs WHERE role_id=$1',
					array($this->getID()));
		if ($res) {
			while ($arr = db_fetch_array ($res)) {
				$ids[] = $arr['group_id'];
			}
		}

		return group_get_objects(array_unique($ids));
	}

	function linkProject ($project) { // From the PFO spec
		$hp = $this->getHomeProject();
		if ($hp != NULL && $hp->getID() == $project->getID()) {
			$this->setError(_("Can't link to home project"));
			return false;
		}

		$res = db_query_params('SELECT group_id FROM role_project_refs WHERE role_id=$1 AND group_id=$2',
				       array($this->getID(),
					     $project->getID()));

		if (db_numrows($res)) {
			return true ;
		}
		$res = db_query_params('INSERT INTO role_project_refs (role_id, group_id) VALUES ($1, $2)',
				       array($this->getID(),
					     $project->getID()));
		if (!$res || db_affected_rows($res) < 1) {
			$this->setError('linkProject('.$project->getID().') '.db_error());
			return false;
		}

		return true;
	}

	function unlinkProject($project) { // From the PFO spec
		$hp = $this->getHomeProject();
		if ($hp != NULL && $hp->getID() == $project->getID()) {
			$this->setError (_("Can't unlink from home project"));
			return false;
		}

		$res = db_query_params('DELETE FROM role_project_refs WHERE role_id=$1 AND group_id=$2',
				       array($this->getID(),
					     $project->getID()));
		if (!$res) {
			$this->setError('unlinkProject('.$project->getID().') '.db_error());
			return false;
		}

		$this->removeObsoleteSettings ();

		return true ;
	}

	/**
	 * fetchData - May need to refresh database fields.
	 *
	 * If an update occurred and you need to access the updated info.
	 *
	 * @return	boolean	success;
	 */
	function fetchData($role_id) {
		unset($this->data_array);
		unset($this->setting_array);
		unset($this->perms_array);

		if (USE_PFO_RBAC) {
			$res = db_query_params('SELECT * FROM pfo_role WHERE role_id=$1',
						array ($role_id)) ;
			if (!$res || db_numrows($res) < 1) {
				$this->setError('BaseRole::fetchData()::'.db_error());
				return false;
			}
			$this->data_array = db_fetch_array($res);
			if ($this->data_array['is_public'] == 't') {
				$this->data_array['is_public'] = true;
			} else {
				$this->data_array['is_public'] = false;
			}
			$res = db_query_params('SELECT section_name, ref_id, perm_val FROM pfo_role_setting WHERE role_id=$1',
						array($role_id));
			if (!$res) {
				$this->setError('BaseRole::fetchData()::'.db_error());
				return false;
			}
			// TODO: document perms_array
			$this->perms_array=array();
			while ($arr = db_fetch_array($res)) {
				$this->perms_array[$arr['section_name']][$arr['ref_id']] = $arr['perm_val'];
			}
		} else {
			if ($this instanceof RoleAnonymous) {
				$res = db_query_params ('SELECT group_id, enable_anonscm FROM groups WHERE is_public=1',
							array ()) ;
				while ($arr = db_fetch_array($res)) {
					$this->perms_array['project_read'][$arr['group_id']] = 1 ;
					$this->perms_array['frs'][$arr['group_id']] = 1 ;
					$this->perms_array['scm'][$arr['group_id']] = $arr['enable_anonscm'] ;
				}

				$res = db_query_params ('SELECT t.group_artifact_id FROM artifact_group_list t, groups g WHERE t.is_public=1 AND t.allow_anon=1 AND g.is_public=1 AND t.group_id = g.group_id',
							array ()) ;
				while ($arr = db_fetch_array($res)) {
					$this->perms_array['tracker'][$arr['group_artifact_id']] = 1 ;
				}

				$res = db_query_params ('SELECT p.group_project_id FROM project_group_list p, groups g WHERE p.is_public=1 AND g.is_public=1 AND p.group_id = g.group_id',
							array ()) ;
				while ($arr = db_fetch_array($res)) {
					$this->perms_array['pm'][$arr['group_project_id']] = 1 ;
				}

				$res = db_query_params ('SELECT f.group_forum_id, f.allow_anonymous, f.moderation_level FROM forum_group_list f, groups g WHERE f.is_public=1 AND g.is_public=1 AND f.group_id = g.group_id',
							array ()) ;
				while ($arr = db_fetch_array($res)) {
					if ($arr['allow_anonymous'] == 1) {
						if ($arr['moderation_level'] == 0) {
							$this->perms_array['forum'][$arr['group_forum_id']] = 3 ;
						} else {
							$this->perms_array['forum'][$arr['group_forum_id']] = 2 ;
						}
					} else {
						$this->perms_array['forum'][$arr['group_forum_id']] = 1 ;
					}
				}
			} elseif ($this instanceof RoleLoggedIn) {
				$res = db_query_params ('SELECT group_id, enable_anonscm FROM groups WHERE is_public=1',
							array ()) ;
				while ($arr = db_fetch_array($res)) {
					$this->perms_array['project_read'][$arr['group_id']] = 1 ;
					$this->perms_array['frs'][$arr['group_id']] = 1 ;
					$this->perms_array['scm'][$arr['group_id']] = $arr['enable_anonscm'] ;
				}

				$res = db_query_params ('SELECT t.group_artifact_id FROM artifact_group_list t, groups g WHERE t.is_public=1 AND g.is_public=1 AND t.group_id = g.group_id',
							array ()) ;
				while ($arr = db_fetch_array($res)) {
					$this->perms_array['tracker'][$arr['group_artifact_id']] = 1 ;
				}

				$res = db_query_params ('SELECT p.group_project_id FROM project_group_list p, groups g WHERE p.is_public=1 AND g.is_public=1 AND p.group_id = g.group_id',
							array ()) ;
				while ($arr = db_fetch_array($res)) {
					$this->perms_array['pm'][$arr['group_project_id']] = 1 ;
				}

				$res = db_query_params ('SELECT f.group_forum_id, f.moderation_level FROM forum_group_list f, groups g WHERE f.is_public=1 AND g.is_public=1 AND f.group_id = g.group_id',
							array ()) ;
				while ($arr = db_fetch_array($res)) {
					if ($arr['moderation_level'] == 0) {
						$this->perms_array['forum'][$arr['group_forum_id']] = 3 ;
					} else {
						$this->perms_array['forum'][$arr['group_forum_id']] = 2 ;
					}
				}
			} else {
			$res = db_query_params ('SELECT * FROM role WHERE role_id=$1',
						array ($role_id)) ;
			if (!$res || db_numrows($res) < 1) {
				$this->setError('BaseRole::fetchData()::'.db_error());
				return false;
			}
			$this->data_array = db_fetch_array($res);

			// Load pre-PFO RBAC settings...
			$res = db_query_params ('SELECT * FROM role_setting WHERE role_id=$1',
						array ($role_id)) ;
			if (!$res) {
				$this->setError('BaseRole::fetchData()::'.db_error());
				return false;
			}
			$this->setting_array=array();
			while ($arr = db_fetch_array($res)) {
				$this->setting_array[$arr['section_name']][$arr['ref_id']] = $arr['value'];
			}

			// ...and map section names and values to the new values

			if ($this->data_array['group_id'] == forge_get_config ('stats_group')) {
				$this->perms_array['forge_stats'][-1] = 2 ;
			}

			$this->perms_array=array();
			$tohandle = array () ;
			$gid = $this->data_array['group_id'] ;
        		if ($gid == 1 && count ($this->setting_array) == 0) {
				$tohandle[] = array ('forge_admin', -1) ;
			}
			foreach ($this->setting_array as $oldsection => $t) {
				switch ($oldsection) {
				case 'projectadmin':
					$tohandle[] = array ('project_admin', $gid) ;
					if ($this->data_array['group_id'] == 1 && $t[0] == 'A') {
						$tohandle[] = array ('forge_admin', -1) ;
					}
					if ($this->data_array['group_id'] == forge_get_config ('news_group') && $t[0] == 'A') {
						$tohandle[] = array ('approve_news', -1) ;
					}
					if ($this->data_array['group_id'] == forge_get_config ('stats_group') && $t[0] == 'A') {
						$tohandle[] = array ('forge_stats', -1) ;
					}
					break ;
				case 'trackeradmin':
					$tohandle[] = array ('tracker_admin', $gid) ;
					break ;
				case 'pmadmin':
					$tohandle[] = array ('pm_admin', $gid) ;
					break ;
				case 'forumadmin':
					$tohandle[] = array ('forum_admin', $gid) ;
					break ;

				case 'newtracker':
					$tohandle[] = array ('new_tracker', $gid) ;
					break ;
				case 'newpm':
					$tohandle[] = array ('new_pm', $gid) ;
					break ;
				case 'newforum':
					$tohandle[] = array ('new_forum', $gid) ;
					break ;
					
				default:
					foreach ($t as $oldreference => $oldvalue) {
						$tohandle[] = array ($oldsection, $oldreference) ;
						break ;
					}
				}
			}

			foreach ($tohandle as $t) {
				$nsec = $t[0] ;
				$nref = $t[1] ;

				$res = db_query_params ('SELECT pfo_rbac_permissions_from_old($1,$2,$3)',
							array ($role_id, $nsec, $nref)) ;
				if ($res) {
					$arr = db_fetch_array($res) ;
					$this->perms_array[$nsec][$nref] = $arr[0] ;
				}
			}
			} // Explicit role (not Anonymous or LoggedIn)
		}

		return true;
	}

	function setSetting ($section, $reference, $value) {
		$role_id = $this->getID () ;

		$res = db_query_params ('DELETE FROM pfo_role_setting WHERE role_id=$1 AND section_name=$2 AND ref_id=$3',
					array ($role_id,
					       $section,
					       $reference)) ;

		$res = db_query_params ('INSERT INTO pfo_role_setting (role_id, section_name, ref_id, perm_val) VALUES ($1, $2, $3, $4)',
						array ($role_id,
						       $section,
						       $reference,
						       $value)) ;
	}

	function getSettingsForProject ($project) {
		$result = array () ;
		$group_id = $project->getID() ;

		if (USE_PFO_RBAC) {
			$sections = array ('project_read', 'project_admin', 'frs', 'scm', 'docman', 'tracker_admin', 'new_tracker', 'forum_admin', 'new_forum', 'pm_admin', 'new_pm') ;
			foreach ($sections as $section) {
				$result[$section][$group_id] = $this->getVal ($section, $group_id) ;
			}
		} else {
			$sections = array ('projectadmin', 'frs', 'scm', 'docman', 'trackeradmin', 'newtracker', 'forumadmin', 'newforum', 'pmadmin', 'newpm', 'webcal') ;
			foreach ($sections as $section) {
				$result[$section][0] = $this->getVal ($section, 0) ;
			}
		}

		$atf = new ArtifactTypeFactory ($project) ;
		$tids = $atf->getAllArtifactTypeIds () ;
		foreach ($tids as $tid) {
			$result['tracker'][$tid] = $this->getVal ('tracker', $tid) ;
		}
		$sections[] = 'tracker' ;

		$ff = new ForumFactory ($project) ;
		$fids = $ff->getAllForumIds () ;
		foreach ($fids as $fid) {
			$result['forum'][$fid] = $this->getVal ('forum', $fid) ;
		}
		$sections[] = 'forum' ;

		$pgf = new ProjectGroupFactory ($project) ;
		$pgids = $pgf->getAllProjectGroupIds () ;
		foreach ($pgids as $pgid) {
			$result['pm'][$pgid] = $this->getVal ('pm', $pgid) ;
		}
		$sections[] = 'pm' ;


		if (USE_PFO_RBAC) {
			// Add settings not yet listed so far (probably plugins)
			// Currently handled:
			// - global settings (ignored here)
			// - project-wide settings (core and plugins)
			// - settings for multiple-instance tools coming from the core (trackers/pm/forums)
			// TODO:
			// - settings for multiple-instance tools from plugins
			foreach (array_keys ($this->perms_array) as $section) {
				if (!in_array ($section, $sections)) {
					if (!in_array ($section, $this->global_settings)) {
						$result[$section][$group_id] = $this->getVal ($section, $group_id) ;
					}
				}
			}
		}

		return $result ;
	}

	/**
	 * TODO: Enter description here ...
	 * @return multitype:
	 */
	function getGlobalSettings () {
		$result = array () ;

		$sections = array ('forge_admin', 'forge_stats', 'approve_projects', 'approve_news') ;
		foreach ($sections as $section) {
			$result[$section][-1] = $this->getVal($section, -1) ;
		}
		// Add settings not yet listed so far (probably plugins)
		foreach (array_keys ($this->perms_array) as $section) {
			if (!in_array ($section, $sections)) {
				if (in_array ($section, $this->global_settings)) {
					$result[$section][-1] = $this->getVal ($section, -1) ;
				}
			}
		}

		return $result ;
	}

    /**
     * TODO: Enter description here ...
     * @param unknown_type $section
     * @param unknown_type $reference
     * @return number|boolean
     */
    function getSetting($section, $reference) {
        if (isset ($this->perms_array[$section][$reference])) {
			$value = $this->perms_array[$section][$reference] ;
		} else {
			$value = 0 ;
		}
		$min = PHP_INT_MAX ;
		$mask = 0 ;
		
		switch ($section) {
		case 'forge_admin':
			return $value ;
			break ;
			
		case 'forge_read':
		case 'approve_projects':
		case 'approve_news':
			if ($this->hasGlobalPermission('forge_admin')) {
				return 1 ;
			}
			return $value ;
			break ;
		
		case 'forge_stats':
			if ($this->hasGlobalPermission('forge_admin')) {
				return 2 ;
			}
			return $value ;
			break ;
		
		case 'project_admin':
			if ($this->hasGlobalPermission('forge_admin')) {
				return 1 ;
			}
			return $value ;
			break ;
			
		case 'project_read':
		case 'tracker_admin':
		case 'pm_admin':
		case 'forum_admin':
			if ($this->hasPermission('project_admin', $reference)) {
				return 1 ;
			}
			return $value ;
			break ;
		
		case 'scm':
			if ($this->hasPermission('project_admin', $reference)) {
				return 2 ;
			}
			return $value ;
			break ;
			
		case 'docman':
			if ($this->hasPermission('project_admin', $reference)) {
				return 4 ;
			}
			return $value ;
			break ;
			
		case 'frs':
			if ($this->hasPermission('project_admin', $reference)) {
				return 3 ;
			}
			return $value ;
			break ;
			
		case 'forum':
			if ($this->hasPermission('forum_admin', forum_get_groupid($reference))) {
				return 4 ;
			}
			return $value ;
			break ;
		case 'new_forum':
			if ($this->hasPermission('forum_admin', $reference)) {
				return 4 ;
			}
			return $value ;
			break ;
			
		case 'tracker':
			if ($this->hasPermission('tracker_admin', artifacttype_get_groupid($reference))) {
				return 5 | $value ;
			}
			return $value ;
			break ;
		case 'new_tracker':
			if ($this->hasPermission('tracker_admin', $reference)) {
				return 5 | $value ;
			}
			return $value ;
			break ;

		case 'pm':
			if ($this->hasPermission('pm_admin', projectgroup_get_groupid($reference))) {
				return 5 | $value ;
			}
			return $value ;
			break ;
		case 'new_pm':
			if ($this->hasPermission('pm_admin', $reference)) {
				return 5 | $value ;
			}
			return $value ;
			break ;
		default:
			$hook_params = array ();
			$hook_params['role'] = $this ;
			$hook_params['section'] = $section ;
			$hook_params['reference'] = $reference ;
			$hook_params['value'] = $value ;
			$hook_params['result'] = 0 ;
			plugin_hook_by_reference ("role_get_setting", $hook_params);
			return $hook_params['result'] ;
			break ;
		}
	}

	/**
	 *	getVal - get a value out of the array of settings for this role.
	 *
	 *	@param	string	The name of the role.
	 *	@param	integer	The ref_id (ex: group_artifact_id, group_forum_id) for this item.
	 *	@return integer	The value of this item.
	 */
	function getVal($section, $ref_id) {
		global $role_default_array;
		if (!$ref_id) {
			$ref_id=0;
		}
		if (USE_PFO_RBAC) {
			return $this->getSetting($section, $ref_id) ;
		} else {
			if (array_key_exists($section, $this->setting_array)) {
				return $this->setting_array[$section][$ref_id];
			} else {
				return 0 ;
			}
		}
	}

	/**
	 *  &getRoleVals - get all the values and language text strings for this section.
	 *
	 *  @return array	Assoc array of values for this section.
	 */
	function &getRoleVals($section) {
		global $role_vals, $rbac_permission_names;
		setup_rbac_strings () ;

		//
		//	Optimization - save array so it is only built once per page view
		//
		if (!isset($role_vals[$section])) {

			for ($i=0; $i<count($this->role_values[$section]); $i++) {
				//
				//	Build an associative array of these key values + localized description
				//
				$role_vals[$section][$this->role_values[$section][$i]]=$rbac_permission_names["$section".$this->role_values[$section][$i]];
			}
		}
		return $role_vals[$section];
	}

        function hasPermission($section, $reference, $action = NULL) {
		$result = false ;
		
		$value = $this->getSetting ($section, $reference) ;
		$min = PHP_INT_MAX ;
		$mask = 0 ;
		
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
			return ($value >= 1) ;
			break ;
		
		case 'forge_stats':
			switch ($action) {
			case 'read':
				return ($value >= 1) ;
				break ;
			case 'admin':
				return ($value >= 2) ;
				break ;
			}
			break ;
			
		case 'scm':
			switch ($action) {
			case 'read':
				return ($value >= 1) ;
				break ;
			case 'write':
				return ($value >= 2) ;
				break ;
			}
			break ;
			
		case 'docman':
			switch ($action) {
			case 'read':
				return ($value >= 1) ;
				break ;
			case 'submit':
				return ($value >= 2) ;
				break ;
			case 'approve':
				return ($value >= 3) ;
				break ;
			case 'admin':
				return ($value >= 4) ;
				break ;
			}
			break ;
			
		case 'frs':
			switch ($action) {
			case 'read_public':
				return ($value >= 1) ;
				break ;
			case 'read_private':
				return ($value >= 2) ;
				break ;
			case 'write':
				return ($value >= 3) ;
				break ;
			}
			break ;
			
		case 'forum':
		case 'new_forum':
			switch ($action) {
			case 'read':
				return ($value >= 1) ;
				break ;
			case 'post':
				return ($value >= 2) ;
				break ;
			case 'unmoderated_post':
				return ($value >= 3) ;
				break ;
			case 'moderate':
				return ($value >= 4) ;
				break ;
			}
			break ;
			
		case 'tracker':
		case 'new_tracker':
			switch ($action) {
			case 'read':
				return (($value & 1) != 0) ;
				break ;
			case 'tech':
				return (($value & 2) != 0) ;
				break ;
			case 'manager':
				return (($value & 4) != 0) ;
				break ;
			}
			break ;

		case 'pm':
		case 'new_pm':
			switch ($action) {
			case 'read':
				return (($value & 1) != 0) ;
				break ;
			case 'tech':
				return (($value & 2) != 0) ;
				break ;
			case 'manager':
				return (($value & 4) != 0) ;
				break ;
			}
			break ;
		default:
			$hook_params = array ();
			$hook_params['section'] = $section ;
			$hook_params['reference'] = $reference ;
			$hook_params['action'] = $action ;
			$hook_params['value'] = $value ;
			$hook_params['result'] = false ;
			plugin_hook_by_reference ("role_has_permission", $hook_params);
			return $hook_params['result'] ;
			break ;
		}
	}

	/**
	 *	update - update a role in the database.
	 *
	 *	@param	string	The name of the role.
	 *	@param	array	A multi-dimensional array of data in this format: $data['section_name']['ref_id']=$val
	 *      @param  boolean Perform permission checking
	 *	@return	boolean	True on success or false on failure.
	 */
	function update($role_name,$data,$check_perms=true) {
		global $SYS;
		if (USE_PFO_RBAC) {
			if ($check_perms) {
				if ($this->getHomeProject() == NULL) {
					if (!forge_check_global_perm ('forge_admin')) {
						$this->setPermissionDeniedError();
						return false;
					}
				} elseif (!forge_check_perm ('project_admin', $this->getHomeProject()->getID())) {
					$this->setPermissionDeniedError();
					return false;
				}
			}
		} else {
			$perm =& $this->Group->getPermission ();
			if (!$perm || !is_object($perm) || $perm->isError() || !$perm->isAdmin()) {
				$this->setPermissionDeniedError();
				return false;
			}
			//
			//	Cannot update role_id=1
			//
			if ($this->getID() == 1) {
				$this->setError('Cannot Update Default Role');
				return false;
			}
		}

		db_begin();


		if (USE_PFO_RBAC) {
			if ($role_name != $this->getName()) {
				$this->setName($role_name) ;
			}

			foreach ($data as $sect => $refs) {
				foreach ($refs as $refid => $value) {
					$this->setSetting ($sect, $refid, $value) ;
				}
				if ($sect == 'scm') {
					foreach ($this->getUsers() as $u) {
						if (!$SYS->sysGroupCheckUser($refid,$u->getID())) {
							$this->setError($SYS->getErrorMessage());
							db_rollback();
							return false;
						}
					}
				}
			}
		} else {
			if (! $this->setName($role_name)) {
				db_rollback();
				return false;
			}

		// Delete extra settings
		db_query_params ('DELETE FROM role_setting WHERE role_id=$1 AND section_name <> ALL ($2)',
				 array ($this->getID(),
					db_string_array_to_any_clause (array_keys ($this->role_values)))) ;
		db_query_params ('DELETE FROM role_setting WHERE role_id=$1 AND section_name = $2 AND ref_id <> ALL ($3)',
				 array ($this->getID(),
					'tracker',
					db_int_array_to_any_clause (array_keys ($data['tracker'])))) ;
		db_query_params ('DELETE FROM role_setting WHERE role_id=$1 AND section_name = $2 AND ref_id <> ALL ($3)',
				 array ($this->getID(),
					'forum',
					db_int_array_to_any_clause (array_keys ($data['forum'])))) ;
		db_query_params ('DELETE FROM role_setting WHERE role_id=$1 AND section_name = $2 AND ref_id <> ALL ($3)',
				 array ($this->getID(),
					'pm',
					db_int_array_to_any_clause (array_keys ($data['pm'])))) ;
		









////$data['section_name']['ref_id']=$val
		$arr1 = array_keys($data);
		for ($i=0; $i<count($arr1); $i++) {	
		//	array_values($Report->adjust_days)
			$arr2 = array_keys($data[$arr1[$i]]);
			for ($j=0; $j<count($arr2); $j++) {
				$usection_name=$arr1[$i];
				$uref_id=$arr2[$j];
				$uvalue=$data[$usection_name][$uref_id];
				if (!$uref_id) {
					$uref_id=0;
				}
				if (!$uvalue) {
					$uvalue=0;
				}
				//
				//	See if this setting changed. If so, then update it
				//
//				if ($this->getVal($usection_name,$uref_id) != $uvalue) {
					$res = db_query_params ('UPDATE role_setting SET value=$1 WHERE role_id=$2 AND section_name=$3 AND ref_id=$4',
								array ($uvalue,
								       $this->getID(),
								       $usection_name,
								       $uref_id)) ;
					if (!$res || db_affected_rows($res) < 1) {
						$res = db_query_params ('INSERT INTO role_setting (role_id, section_name, ref_id, value) VALUES ($1, $2, $3, $4)',
									array ($this->getID(),
									       $usection_name,
									       $uref_id,
									       $uvalue)) ;
						if (!$res) {
							$this->setError('update::rolesettinginsert::'.db_error());
							db_rollback();
							return false;
						}
					}
					if ($usection_name == 'frs') {
						$update_usergroup=true;
					} elseif ($usection_name == 'scm') {
						//$update_usergroup=true;

						//iterate all users with this role
						$res = db_query_params ('SELECT user_id	FROM user_group WHERE role_id=$1',
									array ($this->getID())) ;
						for ($z=0; $z<db_numrows($res); $z++) {

							//TODO - Shell should be separate flag
							//  If user acquired admin access to CVS,
							//  one to be given normal shell on CVS machine,
							//  else - restricted.
							//
							$cvs_flags=$data['scm'][0];
							$res2 = db_query_params ('UPDATE user_group SET cvs_flags=$1 WHERE user_id=$2',
										 array ($cvs_flags,
											db_result($res,$z,'user_id')));
							if (!$res2) {
								$this->setError('update::scm::'.db_error());
								db_rollback();
								return false;
							}
							// I have doubt the following is usefull
							// This is probably buggy if used
							if ($cvs_flags>1) {
								if (!$SYS->sysUserSetAttribute(db_result($res,$z,'user_id'),"debGforgeCvsShell","/bin/bash")) {
									$this->setError($SYS->getErrorMessage());
									db_rollback();
									return false;
								}
							} else {
								if (!$SYS->sysUserSetAttribute(db_result($res,$z,'user_id'),"debGforgeCvsShell","/bin/cvssh")) {
									$this->setError($SYS->getErrorMessage());
									db_rollback();
									return false;
								}
							}

							//
							//  If user acquired at least commit access to CVS,
							//  one to be promoted to CVS group, else, demoted.
							//
							if ($uvalue>0) {
								if (!$SYS->sysGroupAddUser($this->Group->getID(),db_result($res,$z,'user_id'),1)) {
									$this->setError($SYS->getErrorMessage());
									db_rollback();
									return false;
								}
							} else {
								if (!$SYS->sysGroupRemoveUser($this->Group->getID(),db_result($res,$z,'user_id'),1)) {
									$this->setError($SYS->getErrorMessage());
									db_rollback();
									return false;
								}
							}


						}
					} elseif ($usection_name == 'docman') {
						$update_usergroup=true;
					} elseif ($usection_name == 'forumadmin') {
						$update_usergroup=true;
					} elseif ($usection_name == 'trackeradmin') {
						$update_usergroup=true;
					} elseif ($usection_name == 'projectadmin') {
						$update_usergroup=true;
					} elseif ($usection_name == 'pmadmin') {
						$update_usergroup=true;
					}
	//			}
			}
		}
//		if ($update_usergroup) {
			$keys = array ('forumadmin', 'pmadmin', 'trackeradmin', 'docman', 'scm', 'frs', 'projectadmin') ;
			foreach ($keys as $k) {
				if (!array_key_exists ($k, $data)) {
					$data[$k] = array(0);
				}
			}
			$res = db_query_params ('UPDATE user_group
                               SET admin_flags=$1,
   				   forum_flags=$2,
   				   project_flags=$3,
   				   doc_flags=$4,
   				   cvs_flags=$5,
   				   release_flags=$6,
   				   artifact_flags=$7
   				WHERE role_id=$8',
   						array ($data['projectadmin'][0],
						       $data['forumadmin'][0],
						       $data['pmadmin'][0],
						       $data['docman'][0],
						       $data['scm'][0],
						       $data['frs'][0],
						       $data['trackeradmin'][0],
						       $this->getID())) ;
			if (!$res) {
				$this->setError('::update::usergroup::'.db_error());
				db_rollback();
				return false;
			}

//		}

		} // USE_PFO_RBAC

		$hook_params = array ();
		$hook_params['role'] =& $this;
		$hook_params['role_id'] = $this->getID();
		$hook_params['data'] = $data;
		plugin_hook ("role_update", $hook_params);


		db_commit();
		$this->fetchData($this->getID());
		return true;
	}

	function getDisplayableName($group = NULL) {
		if ($this->getHomeProject() == NULL) {
			return sprintf (_('%s (global role)'),
					$this->getName ()) ;
		} elseif ($group == NULL
			  || $this->getHomeProject()->getID() != $group->getID()) {
			return sprintf (_('%s (in project %s)'),
					$this->getName (),
					$this->getHomeProject()->getPublicName()) ;
		} else {
			return $this->getName () ;
		}
	}

	function removeObsoleteSettings () {
		db_begin () ;

		// Remove obsolete project-wide settings
		$sections = array ('project_read', 'project_admin', 'frs', 'scm', 'docman', 'tracker_admin', 'new_tracker', 'forum_admin', 'new_forum', 'pm_admin', 'new_pm', 'webcal') ;
		db_query_params ('DELETE FROM pfo_role_setting where role_id=$1 AND section_name=ANY($2) and ref_id NOT IN (SELECT home_group_id FROM pfo_role WHERE role_id=$1 AND home_group_id IS NOT NULL UNION SELECT group_id from role_project_refs WHERE role_id=$1)',
				 array ($this->getID(),
					db_string_array_to_any_clause($sections))) ;


		// Remove obsolete settings for multiple-instance tools
		db_query_params ('DELETE FROM pfo_role_setting where role_id=$1 AND section_name=$2 and ref_id NOT IN (SELECT group_artifact_id FROM artifact_group_list WHERE group_id IN (SELECT home_group_id FROM pfo_role WHERE role_id=$1 AND home_group_id IS NOT NULL UNION SELECT group_id from role_project_refs WHERE role_id=$1))',
				 array ($this->getID(),
					'tracker')) ;
		db_query_params ('DELETE FROM pfo_role_setting where role_id=$1 AND section_name=$2 and ref_id NOT IN (SELECT group_project_id FROM project_group_list WHERE group_id IN (SELECT home_group_id FROM pfo_role WHERE role_id=$1 AND home_group_id IS NOT NULL UNION SELECT group_id from role_project_refs WHERE role_id=$1))',
				 array ($this->getID(),
					'pm')) ;
		db_query_params ('DELETE FROM pfo_role_setting where role_id=$1 AND section_name=$2 and ref_id NOT IN (SELECT group_forum_id FROM forum_group_list WHERE group_id IN (SELECT home_group_id FROM pfo_role WHERE role_id=$1 AND home_group_id IS NOT NULL UNION SELECT group_id from role_project_refs WHERE role_id=$1))',
				 array ($this->getID(),
					'forum')) ;

		db_commit () ;
		return true ;
	}

	function normalizeDataForSection (&$new_sa, $section) {
		if (array_key_exists ($section, $this->setting_array)) {
			$new_sa[$section][0] = $this->setting_array[$section][0] ;
		} elseif (array_key_exists ($this->data_array['role_name'], $this->defaults)
			  && array_key_exists ($section, $this->defaults[$this->data_array['role_name']])) {
			$new_sa[$section][0] = $this->defaults[$this->data_array['role_name']][$section] ;
		} else {
			$new_sa[$section][0] = 0 ;
		}
		return $new_sa ;
	}

	function normalizePermsForSection (&$new_pa, $section, $refid) {
		if (array_key_exists ($section, $this->perms_array)
		    && array_key_exists ($refid, $this->perms_array[$section])) {
			$new_pa[$section][$refid] = $this->perms_array[$section][$refid] ;
		} elseif (array_key_exists ($this->data_array['role_name'], $this->defaults)
			  && array_key_exists ($section, $this->defaults[$this->data_array['role_name']])) {
			$new_pa[$section][$refid] = $this->defaults[$this->data_array['role_name']][$section] ;
		} else {
			$new_pa[$section][$refid] = 0 ;
		}
		return $new_pa ;
	}

	function normalizeData () { // From the PFO spec
		$this->removeObsoleteSettings () ;

		$this->fetchData ($this->getID()) ;

		$projects = $this->getLinkedProjects() ;		
		$new_sa = array () ;
		$new_pa = array () ;
		
		// Add missing settings
		// ...project-wide settings
		if (USE_PFO_RBAC) {
			$arr = array ('project_read', 'project_admin', 'frs', 'scm', 'docman', 'tracker_admin', 'new_tracker', 'forum_admin', 'new_forum', 'pm_admin', 'new_pm', 'webcal') ;
			foreach ($projects as $p) {
				foreach ($arr as $section) {
					$this->normalizePermsForSection ($new_pa, $section, $p->getID()) ;
				}
			}
			$this->normalizePermsForSection ($new_pa, 'forge_admin', -1) ;
			$this->normalizePermsForSection ($new_pa, 'approve_projects', -1) ;
			$this->normalizePermsForSection ($new_pa, 'approve_news', -1) ;
			$this->normalizePermsForSection ($new_pa, 'forge_stats', -1) ;
		} else {
			$arr = array ('projectadmin', 'frs', 'scm', 'docman', 'forumadmin', 'trackeradmin', 'newtracker', 'pmadmin', 'newpm', 'webcal') ;
			foreach ($arr as $section) {
				$this->normalizeDataForSection ($new_sa, $section) ;
			}
		}

		$hook_params = array ();
		$hook_params['role'] =& $this;
		$hook_params['new_sa'] =& $new_sa ; 
		$hook_params['new_pa'] =& $new_pa ; 
		plugin_hook ("role_normalize", $hook_params);

		// ...tracker-related settings
		$new_sa['tracker'] = array () ;
		$new_pa['tracker'] = array () ;
		foreach ($projects as $p) {
			$atf = new ArtifactTypeFactory ($p) ;
			$trackerids = $atf->getAllArtifactTypeIds () ;
			foreach ($trackerids as $tid) {
				if (USE_PFO_RBAC) {
					if (array_key_exists ('tracker', $this->perms_array)
					    && array_key_exists ($tid, $this->perms_array['tracker']) ) {
						$new_pa['tracker'][$tid] = $this->perms_array['tracker'][$tid] ;
					} elseif (array_key_exists ('new_tracker', $this->perms_array)
					    && array_key_exists ($p->getID(), $this->perms_array['new_tracker']) ) {
						$new_pa['tracker'][$tid] = $new_pa['new_tracker'][$p->getID()] ;
					}
				} else {
					if (array_key_exists ('tracker', $this->setting_array)
					    && array_key_exists ($tid, $this->setting_array['tracker']) ) {
						$new_sa['tracker'][$tid] = $this->setting_array['tracker'][$tid] ;
					} else {
						$new_sa['tracker'][$tid] = $new_sa['newtracker'][0] ;
					}
				}
			}
		}
		
		// ...forum-related settings
		$new_sa['forum'] = array () ;
		$new_pa['forum'] = array () ;
		foreach ($projects as $p) {
			$ff = new ForumFactory ($p) ;
			$fids = $ff->getAllForumIds () ;
			foreach ($fids as $fid) {
				if (USE_PFO_RBAC) {
					if (array_key_exists ('forum', $this->perms_array)
					    && array_key_exists ($fid, $this->perms_array['forum']) ) {
						$new_pa['forum'][$fid] = $this->perms_array['forum'][$fid] ;
					} elseif (array_key_exists ('new_forum', $this->perms_array)
					    && array_key_exists ($p->getID(), $this->perms_array['new_forum']) ) {
						$new_pa['forum'][$fid] = $new_pa['new_forum'][$p->getID()] ;
					}
				} else {
					if (array_key_exists ('forum', $this->setting_array)
					    && array_key_exists ($fid, $this->setting_array['forum']) ) {
						$new_sa['forum'][$fid] = $this->setting_array['forum'][$fid] ;
					} else {
						$new_sa['forum'][$fid] = $new_sa['newforum'][0] ;
					}
				}
			}
		}
		
		// ...pm-related settings
		$new_sa['pm'] = array () ;
		$new_pa['pm'] = array () ;
		foreach ($projects as $p) {
			$pgf = new ProjectGroupFactory ($p) ;
			$pgids = $pgf->getAllProjectGroupIds () ;
			foreach ($pgids as $gid) {
				if (USE_PFO_RBAC) {
					if (array_key_exists ('pm', $this->perms_array)
					    && array_key_exists ($gid, $this->perms_array['pm']) ) {
						$new_pa['pm'][$gid] = $this->perms_array['pm'][$gid] ;
					} elseif (array_key_exists ('new_pm', $this->perms_array)
					    && array_key_exists ($p->getID(), $this->perms_array['new_pm']) ) {
						$new_pa['pm'][$gid] = $new_pa['new_pm'][$p->getID()] ;
					}
				} else {
					if (array_key_exists ('pm', $this->setting_array)
					    && array_key_exists ($gid, $this->setting_array['pm']) ) {
						$new_sa['pm'][$gid] = $this->setting_array['pm'][$gid] ;
					} else {
						$new_sa['pm'][$gid] = $new_sa['newpm'][0] ;
					}
				}
			}
		}
		
		// Save
		if (USE_PFO_RBAC) {
			$this->update ($this->getName(), $new_pa, false) ;
		} else {
			$this->update ($this->getName(), $new_sa) ;
		}
		return true;
	}
}

// Actual classes

/**
 * TODO: RBAC::RoleExplicit Enter description here ...
 *
 */
abstract class RoleExplicit extends BaseRole implements PFO_RoleExplicit {
	public function addUsers($users) {
		global $SYS;

		$ids = array () ;
		foreach ($users as $user) {
			$ids[] = $user->getID();
		}

		$already_there = array();
		$res = db_query_params('SELECT user_id FROM pfo_user_role WHERE user_id=ANY($1) AND role_id=$2',
					array(db_int_array_to_any_clause($ids), $this->getID()));
		while ($arr = db_fetch_array($res)) {
			$already_there[] = $arr['user_id'] ;
		}

		foreach ($ids as $id) {
			if (!in_array ($id, $already_there)) {
			db_query_params ('INSERT INTO pfo_user_role (user_id, role_id) VALUES ($1, $2)',
					 array ($id,
						$this->getID())) ;
			}
		}	

		foreach ($this->getLinkedProjects() as $p) {
			foreach ($ids as $uid) {
				$SYS->sysGroupCheckUser($p->getID(),$uid) ;
			}
		}
	}

	public function addUser ($user) {
		return $this->addUsers (array ($user)) ;
	}

	public function removeUsers($users) {
		global $SYS;

		$ids = array () ;
		foreach ($users as $user) {
			$ids[] = $user->getID() ;
		}

		$already_there = array () ;
		$res = db_query_params ('DELETE FROM pfo_user_role WHERE user_id=ANY($1) AND role_id=$2',
					array (db_int_array_to_any_clause($ids), $this->getID())) ;

		foreach ($this->getLinkedProjects() as $p) {
			foreach ($ids as $uid) {
				$SYS->sysGroupCheckUser($p->getID(),$uid) ;
			}
		}

		return true ;
	}

	public function removeUser ($user) {
		return $this->removeUsers (array ($user)) ;
	}

	public function getUsers() {
		$result = array () ;
		$res = db_query_params ('SELECT user_id FROM pfo_user_role WHERE role_id=$1',
					array ($this->getID())) ;
		while ($arr = db_fetch_array($res)) {
			$result[] = user_get_object ($arr['user_id']) ;
		}

		return $result ;
	}

	public function hasUser($user) {
		$res = db_query_params ('SELECT user_id FROM pfo_user_role WHERE user_id=$1 AND role_id=$2',
					array (db_int_array_to_any_clause($user->getID()), $this->getID())) ;
		if ($res && $db_numrows($res)) {
			return true ;
		} else {
			return false ;
		}
	}

	function getID() {	// From the PFO spec
		return $this->data_array['role_id'];
	}

	function getName() {	// From the PFO spec
		return $this->data_array['role_name'];
	}
}

class RoleAnonymous extends BaseRole implements PFO_RoleAnonymous {
	// This role is implemented as a singleton
	private static $_instance ;
	private $_role_id ;
	public static function getInstance() {
		if (isset(self::$_instance)) {
			return self::$_instance ;
		}

		$c = __CLASS__ ;
		self::$_instance = new $c ;
		
		$res = db_query_params ('SELECT r.role_id FROM pfo_role r, pfo_role_class c WHERE r.role_class = c.class_id AND c.class_name = "$1"',
					array ('PFO_RoleAnonymous')) ;
		if (!$res || !db_numrows($res)) {
			throw new Exception ("No PFO_RoleAnonymous role in the database") ;
		}
		self::$_instance->_role_id = db_result ($res, 0, 'role_id') ;

		$hook_params = array ();
		$hook_params['role'] =& self::$_instance;
		plugin_hook ("role_get", $hook_params);

		self::$_instance->fetchData (self::$_instance->_role_id) ;

		return self::$_instance ;
	}

	public function getID () {
		return $this->_role_id ;
	}
	public function isPublic () {
		return true ;
	}
	public function setPublic ($flag) {
		throw new Exception ("Can't setPublic() on RoleAnonymous") ;
	}
	public function getHomeProject () {
		return NULL ;
	}
	public function getName () {
		return _('Anonymous/not logged in') ;
	}
	public function setName ($name) {
		throw new Exception ("Can't setName() on RoleAnonymous") ;
	}
}

class RoleLoggedIn extends BaseRole implements PFO_RoleLoggedIn {
	// This role is implemented as a singleton
	private static $_instance ;
	private $_role_id ;
	public static function getInstance() {
		if (isset(self::$_instance)) {
			return self::$_instance ;
		}

		$c = __CLASS__ ;
		self::$_instance = new $c ;
		
		$res = db_query_params ('SELECT r.role_id FROM pfo_role r, pfo_role_class c WHERE r.role_class = c.class_id AND c.class_name = "$1"',
					array ('PFO_RoleLoggedIn')) ;
		if (!$res || !db_numrows($res)) {
			throw new Exception ("No PFO_RoleLoggedIn role in the database") ;
		}
		self::$_instance->_role_id = db_result ($res, 0, 'role_id') ;

		$hook_params = array ();
		$hook_params['role'] =& self::$_instance;
		plugin_hook ("role_get", $hook_params);

		self::$_instance->fetchData (self::$_instance->_role_id) ;

		return self::$_instance ;
	}

	public function getID () {
		return $this->_role_id ;
	}
	public function isPublic () {
		return true ;
	}
	public function setPublic ($flag) {
		throw new Exception ("Can't setPublic() on RoleLoggedIn") ;
	}
	public function getHomeProject () {
		return NULL ;
	}
	public function getName () {
		return _('Any user logged in') ;
	}
	public function setName ($name) {
		throw new Exception ("Can't setName() on RoleLoggedIn") ;
	}
}

abstract class RoleUnion extends BaseRole implements PFO_RoleUnion {
	public function addRole ($role) {
		throw new Exception ("Not implemented") ;
	}
	public function removeRole ($role) {
		throw new Exception ("Not implemented") ;
	}
}

/**
 * TODO: Enter description here ...
 *
 */
class RoleComparator {
	var $criterion = 'composite' ;
	var $reference_project = NULL ;

	function Compare ($a, $b) {
		switch ($this->criterion) {
		case 'name':
			return strcoll ($a->getName(), $b->getName()) ;
			break ;
		case 'id':
			$aid = $a->getID() ;
			$bid = $b->getID() ;
			if ($a == $b) {
				return 0;
			}
			return ($a < $b) ? -1 : 1;
			break ;
		case 'composite':
		default:
			if ($this->reference_project == NULL) {
				return $this->CompareNoRef ($a, $b) ;
			}
			$rpid = $this->reference_project->getID () ;
			$ap = $a->getHomeProject() ;
			$bp = $b->getHomeProject() ;
			$a_is_local = ($ap != NULL && $ap->getID() == $rpid) ; // Local
			$b_is_local = ($bp != NULL && $bp->getID() == $rpid) ;

			if ($a_is_local && !$b_is_local) {
				return -1 ;
			} elseif (!$a_is_local && $b_is_local) {
				return 1 ;
			}
			return $this->CompareNoRef ($a, $b) ;
		}
	}

	/**
	 * TODO: Enter description here ...
	 * @param Role $a
	 * @param Role $b
	 * @return number
	 */
	function CompareNoRef ($a, $b) {
		$ap = $a->getHomeProject() ;
		$bp = $b->getHomeProject() ;
		if ($ap == NULL && $bp != NULL) {
			return 1 ;
		} elseif ($ap != NULL && $bp == NULL) {
			return -1 ;
		} elseif ($ap == NULL && $bp == NULL) {
			$tmp = strcoll ($a->getName(), $b->getName()) ;
			return $tmp ;
		} else {
			$projcmp = new ProjectComparator () ;
			$projcmp->criterion = 'name' ;
			$tmp = $projcmp->Compare ($ap, $bp) ;
			if ($tmp) { /* Different projects, sort accordingly */
				return $tmp ;
			} 
			return strcoll ($a->getName(), $b->getName()) ;
		}
	}
}

function sortRoleList (&$list, $relative_to = NULL, $criterion='composite') {
	$cmp = new RoleComparator () ;
	$cmp->criterion = $criterion ;
	$cmp->reference_project = $relative_to ;

	return usort ($list, array ($cmp, 'Compare')) ;
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:

?>

<?php
/**
 * MediaWikiPlugin Class
 *
 * Copyright 2000-2011, Fusionforge Team 
 * Copyright 2012, Franck Villaume - TrivialDev
 * http://fusionforge.org
 *
 * This file is part of FusionForge.
 *
 * FusionForge is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * FusionForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

require_once 'plugins_utils.php';
if(is_dir("/usr/share/mediawiki")){
forge_define_config_item('src_path','mediawiki', "/usr/share/mediawiki");
forge_define_config_item('mwdata_path', 'mediawiki', '$core/data_path/plugins/mediawiki');
forge_define_config_item('projects_path', 'mediawiki', '$mediawiki/mwdata_path/projects');
forge_define_config_item('master_path', 'mediawiki', '$mediawiki/mwdata_path/master');
forge_define_config_item('enable_uploads', 'mediawiki', false);
forge_set_config_item_bool('enable_uploads', 'mediawiki');
}

class MediaWikiPlugin extends Plugin {
	function __construct ($id=0) {
		$this->Plugin($id) ;
		$this->name = "mediawiki" ;
		$this->text = "Mediawiki" ; // To show in the tabs, use...
		$this->_addHook("groupmenu") ;	// To put into the project tabs
		$this->_addHook("groupisactivecheckbox") ; // The "use ..." checkbox in editgroupinfo
		$this->_addHook("groupisactivecheckboxpost") ; //
		$this->_addHook("project_public_area");
		$this->_addHook("role_get");
		$this->_addHook("role_normalize");
		$this->_addHook("role_translate_strings");
		$this->_addHook("role_has_permission");
		$this->_addHook("role_get_setting");
		$this->_addHook("list_roles_by_permission");
		$this->_addHook("project_admin_plugins"); // to show up in the admin page for group
		$this->_addHook("clone_project_from_template") ;
		$this->_addHook('group_delete');
	}

        function process() {
		echo '<h1>Mediawiki</h1>';
		echo $this->getPluginInfo()->getpropVal('answer');
        }

        function &getPluginInfo() {
		if (!is_a($this->pluginInfo, 'MediaWikiPluginInfo')) {
			require_once 'MediaWikiPluginInfo.class.php';
			$this->pluginInfo = new MediaWikiPluginInfo($this);
		}
		return $this->pluginInfo;
	}

	function CallHook ($hookname, &$params) {
		if (isset($params['group_id'])) {
			$group_id=$params['group_id'];
		} elseif (isset($params['group'])) {
			$group_id=$params['group'];
		} else {
			$group_id=null;
		}
		if ($hookname == "groupmenu") {
			$project = group_get_object($group_id);
			if (!$project || !is_object($project)) {
				return;
			}
			if ($project->isError()) {
				return;
			}
			if (!$project->isProject()) {
				return;
			}
			if ( $project->usesPlugin ( $this->name ) ) {
				$params['TITLES'][]=$this->text;
				$params['DIRS'][]=util_make_url('/plugins/mediawiki/wiki/'.$project->getUnixName().'/index.php');
				$params['ADMIN'][]='';
				$params['TOOLTIPS'][] = _('Mediawiki Space');
			}
			(($params['toptab'] == $this->name) ? $params['selected']=(count($params['TITLES'])-1) : '' );
		} elseif ($hookname == "groupisactivecheckbox") {
			//Check if the group is active
			// this code creates the checkbox in the project edit public info page to activate/deactivate the plugin
			$group = group_get_object($group_id);
			echo "<tr>";
			echo "<td>";
			echo ' <input type="checkbox" name="use_mediawikiplugin" value="1" ';
			// checked or unchecked?
			if ( $group->usesPlugin ( $this->name ) ) {
				echo "checked";
			}
			echo " /><br/>";
			echo "</td>";
			echo "<td>";
			echo "<strong>Use ".$this->text." Plugin</strong>";
			echo "</td>";
			echo "</tr>";
		} elseif ($hookname == "groupisactivecheckboxpost") {
			// this code actually activates/deactivates the plugin after the form was submitted in the project edit public info page
			$group = group_get_object($group_id);
			$use_mediawikiplugin = getStringFromRequest('use_mediawikiplugin');
			if ( $use_mediawikiplugin == 1 ) {
				$group->setPluginUse ( $this->name );
			} else {
				$group->setPluginUse ( $this->name, false );
			}
		} elseif ($hookname == "project_public_area") {
			$project = group_get_object($group_id);
			if (!$project || !is_object($project)) {
				return;
			}
			if ($project->isError()) {
				return;
			}
			if (!$project->isProject()) {
				return;
			}
			if ( $project->usesPlugin ( $this->name ) ) {
				echo '<div class="public-area-box">';
				print '<a href="'. util_make_url ('/plugins/mediawiki/wiki/'.$project->getUnixName().'/index.php').'">';
				print html_abs_image(util_make_url ('/plugins/mediawiki/wiki/'.$project->getUnixName().'/skins/fusionforge/wiki.png'),'20','20',array('alt'=>'Mediawiki'));
				print ' Mediawiki';
				print '</a>';
				echo '</div>';
			}
		} elseif ($hookname == "role_get") {
			$role =& $params['role'] ;

			// Read access
			$right = new PluginSpecificRoleSetting ($role,
								'plugin_mediawiki_read') ;
			$right->SetAllowedValues (array ('0', '1')) ;
			$right->SetDefaultValues (array ('Admin' => '1',
							 'Senior Developer' => '1',
							 'Junior Developer' => '1',
							 'Doc Writer' => '1',
							 'Support Tech' => '1')) ;

			// Edit privileges
			$right = new PluginSpecificRoleSetting ($role,
								'plugin_mediawiki_edit') ;
			$right->SetAllowedValues (array ('0', '1', '2', '3')) ;
			$right->SetDefaultValues (array ('Admin' => '3',
							 'Senior Developer' => '2',
							 'Junior Developer' => '1',
							 'Doc Writer' => '3',
							 'Support Tech' => '0')) ;

			// File upload privileges
			$right = new PluginSpecificRoleSetting ($role,
								'plugin_mediawiki_upload') ;
			$right->SetAllowedValues (array ('0', '1', '2')) ;
			$right->SetDefaultValues (array ('Admin' => '2',
							 'Senior Developer' => '2',
							 'Junior Developer' => '1',
							 'Doc Writer' => '2',
							 'Support Tech' => '0')) ;

			// Administrative tasks
			$right = new PluginSpecificRoleSetting ($role,
								'plugin_mediawiki_admin') ;
			$right->SetAllowedValues (array ('0', '1')) ;
			$right->SetDefaultValues (array ('Admin' => '1',
							 'Senior Developer' => '0',
							 'Junior Developer' => '0',
							 'Doc Writer' => '0',
							 'Support Tech' => '0')) ;

		} elseif ($hookname == "role_normalize") {
			$role =& $params['role'] ;
			$new_sa =& $params['new_sa'] ;
			$new_pa =& $params['new_pa'] ;

			$projects = $role->getLinkedProjects() ;
			foreach ($projects as $p) {
				$role->normalizePermsForSection ($new_pa, 'plugin_mediawiki_read', $p->getID()) ;
				$role->normalizePermsForSection ($new_pa, 'plugin_mediawiki_edit', $p->getID()) ;
				$role->normalizePermsForSection ($new_pa, 'plugin_mediawiki_upload', $p->getID()) ;
				$role->normalizePermsForSection ($new_pa, 'plugin_mediawiki_admin', $p->getID()) ;
			}
		} elseif ($hookname == "role_translate_strings") {
			$right = new PluginSpecificRoleSetting ($role,
							       'plugin_mediawiki_read') ;
			$right->setDescription (_('Mediawiki read access')) ;
			$right->setValueDescriptions (array ('0' => _('No reading'),
							     '1' => _('Read access'))) ;

			$right = new PluginSpecificRoleSetting ($role,
							       'plugin_mediawiki_edit') ;
			$right->setDescription (_('Mediawiki write access')) ;
			$right->setValueDescriptions (array ('0' => _('No editing'),
							     '1' => _('Edit existing pages only'),
							     '2' => _('Edit and create pages'),
							     '3' => _('Edit, create, move, delete pages'))) ;

			$right = new PluginSpecificRoleSetting ($role,
							       'plugin_mediawiki_upload') ;
			$right->setDescription (_('Mediawiki file upload')) ;
			$right->setValueDescriptions (array ('0' => _('No uploading'),
							     '1' => _('Upload permitted'),
							     '2' => _('Upload and re-upload'))) ;

			$right = new PluginSpecificRoleSetting ($role,
							       'plugin_mediawiki_admin') ;
			$right->setDescription (_('Mediawiki administrative tasks')) ;
			$right->setValueDescriptions (array ('0' => _('No administrative access'),
							     '1' => _('Edit interface, import XML dumps'))) ;
		} elseif ($hookname == "role_get_setting") {
			$role = $params['role'] ;
			$reference = $params['reference'] ;
			$value = $params['value'] ;

			switch ($params['section']) {
			case 'plugin_mediawiki_read':
				if ($role->hasPermission('project_admin', $reference)) {
					$params['result'] = 1 ;
				} else {
					$params['result'] =  $value ;
				}
				break ;
			case 'plugin_mediawiki_edit':
				if ($role->hasPermission('project_admin', $reference)) {
					$params['result'] = 3 ;
				} else {
					$params['result'] =  $value ;
				}
				break ;
			case 'plugin_mediawiki_upload':
				if ($role->hasPermission('project_admin', $reference)) {
					$params['result'] = 2 ;
				} else {
					$params['result'] =  $value ;
				}
				break ;
			case 'plugin_mediawiki_admin':
				if ($role->hasPermission('project_admin', $reference)) {
					$params['result'] = 1 ;
				} else {
					$params['result'] =  $value ;
				}
				break ;
			}
		} elseif ($hookname == "role_has_permission") {
			$value = $params['value'];
			switch ($params['section']) {
			case 'plugin_mediawiki_read':
				switch ($params['action']) {
				case 'read':
				default:
					$params['result'] |= ($value >= 1) ;
					break ;
				}
				break ;
			case 'plugin_mediawiki_edit':
				switch ($params['action']) {
				case 'editexisting':
					$params['result'] |= ($value >= 1) ;
					break ;
				case 'editnew':
					$params['result'] |= ($value >= 2) ;
					break ;
				case 'editmove':
					$params['result'] |= ($value >= 3) ;
					break ;
				}
				break ;
			case 'plugin_mediawiki_upload':
				switch ($params['action']) {
				case 'upload':
					$params['result'] |= ($value >= 1) ;
					break ;
				case 'reupload':
					$params['result'] |= ($value >= 2) ;
					break ;
				}
				break ;
			case 'plugin_mediawiki_admin':
				switch ($params['action']) {
				case 'admin':
				default:
					$params['result'] |= ($value >= 1) ;
					break ;
				}
				break ;
			}
		} elseif ($hookname == "list_roles_by_permission") {
			switch ($params['section']) {
			case 'plugin_mediawiki_read':
				switch ($params['action']) {
				case 'read':
				default:
					$params['qpa'] = db_construct_qpa ($params['qpa'], ' AND perm_val >= 1') ;
					break ;
				}
				break ;
			case 'plugin_mediawiki_edit':
				switch ($params['action']) {
				case 'editexisting':
					$params['qpa'] = db_construct_qpa ($params['qpa'], ' AND perm_val >= 1') ;
					break ;
				case 'editnew':
					$params['qpa'] = db_construct_qpa ($params['qpa'], ' AND perm_val >= 2') ;
					break ;
				case 'editmove':
					$params['qpa'] = db_construct_qpa ($params['qpa'], ' AND perm_val >= 3') ;
					break ;
				}
				break ;
			case 'plugin_mediawiki_upload':
				switch ($params['action']) {
				case 'upload':
					$params['qpa'] = db_construct_qpa ($params['qpa'], ' AND perm_val >= 1') ;
					break ;
				case 'reupload':
					$params['qpa'] = db_construct_qpa ($params['qpa'], ' AND perm_val >= 2') ;
					break ;
				}
				break ;
			case 'plugin_mediawiki_admin':
				switch ($params['action']) {
				case 'admin':
				default:
					$params['qpa'] = db_construct_qpa ($params['qpa'], ' AND perm_val >= 1') ;
					break ;
				}
				break ;
			}
		} elseif ($hookname == "project_admin_plugins") {
			$group_id = $params['group_id'];
			$group = group_get_object($group_id);
			if ($group->usesPlugin($this->name))
				echo util_make_link(
				    "/plugins/mediawiki/plugin_admin.php?group_id=" .
				    $group->getID(), _("MediaWiki Plugin admin")) .
				    "<br />";
		} elseif ($hookname == "clone_project_from_template") {
			$template = $params['template'] ;
			$project = $params['project'] ;
			$id_mappings = $params['id_mappings'] ;

			$sections = array ('plugin_mediawiki_read', 'plugin_mediawiki_edit', 'plugin_mediawiki_upload', 'plugin_mediawiki_admin') ;

			foreach ($template->getRoles() as $oldrole) {
				$newrole = RBACEngine::getInstance()->getRoleById ($id_mappings['role'][$oldrole->getID()]) ;
				$oldsettings = $oldrole->getSettingsForProject ($template) ;

				foreach ($sections as $section) {
					if (isset ($oldsettings[$section][$template->getID()])) {
						$newrole->setSetting ($section, $project->getID(), $oldsettings[$section][$template->getID()]) ;
					}
				}
			}
		} elseif ($hookname == 'group_delete') {
			$projectId = $params['group_id'];
			$projectObject = group_get_object($projectId);
			if ($projectObject->usesPlugin($this->name)) {
				//delete the files and db schema
				$schema = 'plugin_mediawiki_'.$projectObject->getUnixName();
				// Sanitize schema name
				$schema = strtr($schema, "-", "_");
				db_query_params('drop schema $1 cascade', array($schema));
				exec('/bin/rm -rf '.forge_get_config('projects_path', 'mediawiki').'/'.$projectObject->getUnixName());
			}
		}
	}
  }

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:

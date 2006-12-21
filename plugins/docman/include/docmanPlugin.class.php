<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * $Id$
 */
require_once('common/plugin/Plugin.class.php');

class DocmanPlugin extends Plugin {
	
	function DocmanPlugin($id) {
		$this->Plugin($id);
        $this->_addHook('cssfile',                           'cssFile',                           false);
        $this->_addHook('javascript_file',                   'jsFile',                            false);
        $this->_addHook('plugin_load_language_file',         'loadPluginLanguageFile',            false);
        $this->_addHook('logs_daily',                        'logsDaily',                         false);
        $this->_addHook('permission_get_name',               'permission_get_name',               false);
        $this->_addHook('permission_get_object_type',        'permission_get_object_type',        false);
        $this->_addHook('permission_get_object_name',        'permission_get_object_name',        false);
        $this->_addHook('permission_get_object_fullname',    'permission_get_object_fullname',    false);
        $this->_addHook('permission_user_allowed_to_change', 'permission_user_allowed_to_change', false);
        $this->_addHook('service_public_areas',              'service_public_areas',              false);
        $this->_addHook('service_admin_pages',               'service_admin_pages',               false);
        $this->_addHook('permissions_for_ugroup',            'permissions_for_ugroup',            false);
        $this->_addHook('register_project_creation',         'installNewDocman',                  false);
        $this->_addHook('service_is_used',                   'service_is_used',                   false);
	}
	function permission_get_name($params) {
        $this->loadPluginLanguageFile($params);
        if (!$params['name']) {
            switch($params['permission_type']) {
                case 'PLUGIN_DOCMAN_READ':
                    $params['name'] = $GLOBALS['Language']->getText('plugin_docman', 'permission_read');
                    break;
                case 'PLUGIN_DOCMAN_WRITE':
                    $params['name'] = $GLOBALS['Language']->getText('plugin_docman', 'permission_write');
                    break;
                case 'PLUGIN_DOCMAN_MANAGE':
                    $params['name'] = $GLOBALS['Language']->getText('plugin_docman', 'permission_manage');
                    break;
                case 'PLUGIN_DOCMAN_ADMIN':
                    $params['name'] = $GLOBALS['Language']->getText('plugin_docman', 'permission_admin');
                    break;
                default:
                    break;
            }
        }
    }
    function permission_get_object_type($params) {
        if (!$params['object_type']) {
            if (in_array($params['permission_type'], array('PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE', 'PLUGIN_DOCMAN_ADMIN'))) {
                require_once('Docman_ItemFactory.class.php');
                $if =& new Docman_ItemFactory();
                $item =& $if->getItemFromDb($params['object_id']);
                if ($item) {
                    $params['object_type'] = is_a($item, 'Docman_Folder') ? 'folder' : 'document';
                }
            }
        }
    }
    function permission_get_object_name($params) {
        if (!$params['object_name']) {
            if (in_array($params['permission_type'], array('PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE', 'PLUGIN_DOCMAN_ADMIN'))) {
                require_once('Docman_ItemFactory.class.php');
                $if =& new Docman_ItemFactory();
                $item =& $if->getItemFromDb($params['object_id']);
                if ($item) {
                    $params['object_name'] = $item->getTitle();
                }
            }
        }
    }
    function permission_get_object_fullname($params) {
        if (!$params['object_fullname']) {
            if (in_array($params['permission_type'], array('PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE', 'PLUGIN_DOCMAN_ADMIN'))) {
                require_once('Docman_ItemFactory.class.php');
                $if =& new Docman_ItemFactory();
                $item =& $if->getItemFromDb($params['object_id']);
                if ($item) {
                    $type = is_a($item, 'Docman_Folder') ? 'folder' : 'document';
                    $name = $item->getTitle();
                    $params['object_fullname'] = $name .' '. $title; //TODO i18n
                }
            }
        }
    }
    function permissions_for_ugroup($params) {
        if (!$params['results']) {
            if (in_array($params['permission_type'], array('PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE', 'PLUGIN_DOCMAN_ADMIN'))) {
                require_once('Docman_ItemFactory.class.php');
                $if =& new Docman_ItemFactory();
                $item =& $if->getItemFromDb($params['object_id']);
                if ($item) {
                    $type = is_a($item, 'Docman_Folder') ? 'folder' : 'document';
                    $params['results']  = $GLOBALS['Language']->getText('plugin_docman', 'resource_name_'.$type, array(
                            '/plugins/docman/?group_id='. $params['group_id'] .
                              '&amp;action=details&amp;section=permissions' .
                              '&amp;id='. $item->getId()
                            , $item->getTitle()
                        )
                    );
                }
            }
        }
    }
    var $_cached_permission_user_allowed_to_change;
    function permission_user_allowed_to_change($params) {
        if (!$params['allowed']) {
            if (!$this->_cached_permission_user_allowed_to_change) {
                if (in_array($params['permission_type'], array('PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE', 'PLUGIN_DOCMAN_ADMIN'))) {
                    require_once('Docman.class.php');
                    $docman =& new Docman($this, $this->_getPluginPath(), $this->_getThemePath());
                    switch($params['permission_type']) {
                        case 'PLUGIN_DOCMAN_READ':
                        case 'PLUGIN_DOCMAN_WRITE':
                        case 'PLUGIN_DOCMAN_MANAGE':
                            $this->_cached_permission_user_allowed_to_change = $docman->userCanManage($params['object_id']);
                            break;
                        default:
                            $this->_cached_permission_user_allowed_to_change = $docman->userCanAdmin();
                            break;
                    }
                }
            }
            $params['allowed'] = $this->_cached_permission_user_allowed_to_change;
        }
    }
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'DocmanPluginInfo')) {
            require_once('DocmanPluginInfo.class.php');
            $this->pluginInfo =& new DocmanPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    function cssFile($params) {
        // Only show the stylesheet if we're actually in the Docman pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->_getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->_getThemePath().'/css/style.css" />'."\n";
        }
    }
    
    function jsFile($params) {
        // Only show the stylesheet if we're actually in the Docman pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->_getPluginPath()) === 0) {
            echo '<script type="text/javascript" src="/scripts/prototype/prototype.js"></script>'."\n";
            echo '<script type="text/javascript" src="/scripts/behaviour/behaviour.js"></script>'."\n";
            echo '<script type="text/javascript" src="/scripts/scriptaculous/scriptaculous.js"></script>'."\n";
            echo '<script type="text/javascript" src="/scripts/calendar.js"></script>'."\n";
            echo '<script type="text/javascript" src="'.$this->_getPluginPath().'/scripts/docman.js"></script>'."\n";
        }
    }

    function loadPluginLanguageFile($params) {
        $GLOBALS['Language']->loadLanguageMsg('docman', 'docman');
    }

    function logsDaily($params) {
        require_once('Docman.class.php');
        $controler =& new Docman($this, $this->_getPluginPath(), $this->_getThemePath());
        $controler->logsDaily($params);
    }
    
    function service_public_areas($params) {
        if ($params['project']->usesService('docman')) {
            $params['areas'][] = '<a href="/plugins/docman/?group_id='. $params['project']->getId() .'">' .
                '<img src="'. $this->_getThemePath() .'/images/ic/text.png" />&nbsp;' .
                $GLOBALS['Language']->getText('plugin_docman', 'descriptor_name') .': '.
                $GLOBALS['Language']->getText('plugin_docman', 'title') .
                '</a>';
        }
    }
    function service_admin_pages($params) {
        if ($params['project']->usesService('docman')) {
            $params['admin_pages'][] = '<a href="/plugins/docman/?action=admin&amp;group_id='. $params['project']->getId() .'">' .
                $GLOBALS['Language']->getText('plugin_docman', 'service_lbl_key') .' - '. 
                $GLOBALS['Language']->getText('plugin_docman', 'admin_title') .
                '</a>';
        }
    }
    function installNewDocman($params) {
        require_once('Docman.class.php');
        $controler =& new Docman($this, $this->_getPluginPath(), $this->_getThemePath());
        $controler->installDocman($params['ugroupsMapping']);
    }
    function service_is_used($params) {
        if (isset($params['shortname']) && $params['shortname'] == 'docman') {
            if (isset($params['is_used']) && $params['is_used']) {
                $this->installNewDocman(array('ugroupsMapping' => false));
            }
        }
    }
    function process() {
        require_once('Docman.class.php');
        $controler =& new Docman($this, $this->_getPluginPath(), $this->_getThemePath());
        $controler->process();
    }
}

?>

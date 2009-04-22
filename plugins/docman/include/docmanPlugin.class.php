<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */
require_once('common/plugin/Plugin.class.php');

class DocmanPlugin extends Plugin {
	
	function DocmanPlugin($id) {
		$this->Plugin($id);
        $this->_addHook('cssfile',                           'cssFile',                           false);
        $this->_addHook('javascript_file',                   'jsFile',                            false);
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
        $this->_addHook('soap',                              'soap',                              false);
        $this->_addHook('widget_instance',                   'myPageBox',                         false);
        $this->_addHook('widgets',                           'widgets',                           false);
        $this->_addHook('codendi',                           'codendiDaily',                      false);
        $this->_addHook('default_widgets_for_new_owner',     'default_widgets_for_new_owner',     false);
        $this->_addHook('wiki_page_updated',                 'wiki_page_updated',                 false);
        $this->_addHook('wiki_before_content',               'wiki_before_content',               false);
        $this->_addHook('isWikiPageReferenced',              'isWikiPageReferenced',              false);
        $this->_addHook('userCanAccessWikiDocument',         'userCanAccessWikiDocument',         false);
        $this->_addHook('getPermsLabelForWiki',              'getPermsLabelForWiki',              false);
        $this->_addHook('ajax_reference_tooltip',            'ajax_reference_tooltip',            false);
	}
	function permission_get_name($params) {
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
                    $params['object_fullname'] = $type .' '. $name; //TODO i18n
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
                    require_once('Docman_HTTPController.class.php');
                    $docman =& new Docman_HTTPController($this, $this->getPluginPath(), $this->getThemePath());
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
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />'."\n";
        }
    }
    
    function jsFile($params) {
        // Only show the stylesheet if we're actually in the Docman pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<script type="text/javascript" src="/scripts/behaviour/behaviour.js"></script>'."\n";
            echo '<script type="text/javascript" src="/scripts/scriptaculous/scriptaculous.js"></script>'."\n";
            echo '<script type="text/javascript" src="'.$this->getPluginPath().'/scripts/docman.js"></script>'."\n";
        }
    }

    function logsDaily($params) {
        require_once('Docman_HTTPController.class.php');
        $controler =& new Docman_HTTPController($this, $this->getPluginPath(), $this->getThemePath());
        $controler->logsDaily($params);
    }
    
    function service_public_areas($params) {
        if ($params['project']->usesService('docman')) {
            $params['areas'][] = '<a href="/plugins/docman/?group_id='. $params['project']->getId() .'">' .
                '<img src="'. $this->getThemePath() .'/images/ic/text.png" />&nbsp;' .
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
        require_once('Docman_HTTPController.class.php');
        $controler =& new Docman_HTTPController($this, $this->getPluginPath(), $this->getThemePath());
        $controler->installDocman($params['ugroupsMapping'], $params['group_id']);
    }
    function service_is_used($params) {
        if (isset($params['shortname']) && $params['shortname'] == 'docman') {
            if (isset($params['is_used']) && $params['is_used']) {
                $this->installNewDocman(array('ugroupsMapping' => false));
            }
        }
    }
    function soap($arams) {
        require_once('soap.php');
    }

    function myPageBox($params) {
        switch ($params['widget']) {
            case 'plugin_docman_mydocman':
                require_once('Docman_Widget_MyDocman.class.php');
                $params['instance'] = new Docman_Widget_MyDocman($this->getPluginPath());
                break;
            case 'plugin_docman_my_embedded':
                require_once('Docman_Widget_MyEmbedded.class.php');
                $params['instance'] = new Docman_Widget_MyEmbedded($this->getPluginPath());
                break;
            case 'plugin_docman_project_embedded':
                require_once('Docman_Widget_ProjectEmbedded.class.php');
                $params['instance'] = new Docman_Widget_ProjectEmbedded($this->getPluginPath());
                break;
            default:
                break;
        }
    }
    function widgets($params) {
        require_once('common/widget/WidgetLayoutManager.class.php');
        if ($params['owner_type'] == WidgetLayoutManager::OWNER_TYPE_USER) {
            $params['codendi_widgets'][] = 'plugin_docman_mydocman';
            $params['codendi_widgets'][] = 'plugin_docman_my_embedded';
        }
        if ($params['owner_type'] == WidgetLayoutManager::OWNER_TYPE_GROUP) {
            $params['codendi_widgets'][] = 'plugin_docman_project_embedded';
        }
    }
    function default_widgets_for_new_owner($params) {
        require_once('common/widget/WidgetLayoutManager.class.php');
        if ($params['owner_type'] == WidgetLayoutManager::OWNER_TYPE_USER) {
            $params['widgets'][] = array(
                'name' => 'plugin_docman_mydocman',
                'column' => 1,
                'rank' => 2,
            );
        }
    }
    /**
     * Hook: called by daily codendi script.
     */
    function codendiDaily() {
        require_once('Docman_HTTPController.class.php');
        $controler =& new Docman_HTTPController($this, $this->getPluginPath(), $this->getThemePath());
        $controler->notifyFuturObsoleteDocuments();
    }

    function process() {
        require_once('Docman_HTTPController.class.php');
        $controler =& new Docman_HTTPController($this, $this->getPluginPath(), $this->getThemePath());
        $controler->process();
    }
    
    protected $soapControler;
    public function processSOAP(&$request) {
        require_once('Docman_SOAPController.class.php');
        if ($this->soapControler) {
            $this->soapControler->setRequest($request);
        } else {
            $this->soapControler = new Docman_SOAPController($this, $this->getPluginPath(), $this->getThemePath(), $request);
        }
        return $this->soapControler->process();
    }
     
    function wiki_page_updated($params) {
        require_once('Docman_WikiRequest.class.php');
        $request = new Docman_WikiRequest(array('action' => 'wiki_page_updated',
                                                'wiki_page' => $params['wiki_page'],
                                                'diff_link' => $params['diff_link'],
                                                'group_id'  => $params['group_id'],
                                                'user'      => $params['user'],
                                                'version'   => $params['version']));
        $this->_getWikiController($request)->process(); 
    }

    function wiki_before_content($params) {
        require_once('Docman_WikiRequest.class.php');
        $params['action'] = 'wiki_before_content';
        $request = new Docman_WikiRequest($params);
        $this->_getWikiController($request)->process(); 
    }

    function isWikiPageReferenced($params) {
        require_once('Docman_WikiRequest.class.php');
        $params['action'] = 'check_whether_wiki_page_is_referenced';
        $request = new Docman_WikiRequest($params);
        $this->_getWikiController($request)->process(); 
    }

    function userCanAccessWikiDocument($params) {
        require_once('Docman_WikiRequest.class.php');
        $params['action'] = 'check_whether_user_can_access';
        $request = new Docman_WikiRequest($params);
        $this->_getWikiController($request)->process(); 
    }

    function getPermsLabelForWiki($params) {
        require_once('Docman_WikiRequest.class.php');
        $params['action'] = 'getPermsLabelForWiki';
        $request = new Docman_WikiRequest($params);
        $this->_getWikiController($request)->process(); 
    }
    
    protected $_wiki_controller;
    protected function _getWikiController($request) {
        if (!$this->_wiki_controller) {
            require_once('Docman_WikiController.class.php');
            $this->_wiki_controller = new Docman_WikiController($this, $this->getPluginPath(), $this->getThemePath(), $request);
            
        } else {
            $this->_wiki_controller->setRequest($request);
        }
        return $this->_wiki_controller;
    }
    
    function ajax_reference_tooltip($params) {
        if ($params['reference']->getServiceShortName() == 'docman') {
            require_once('Docman_CoreController.class.php');
            $request = new Codendi_Request(array(
                'id'       => $params['val'],
                'group_id' => $params['group_id'],
                'action'   => 'ajax_reference_tooltip'
            ));
            $controler =& new Docman_CoreController($this, $this->getPluginPath(), $this->getThemePath(), $request);
            $controler->process();
        }
    }
}

?>

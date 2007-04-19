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
 * $Id: DocmanController.class.php 5849 2007-04-16 14:18:26Z nterray $
 */
require_once('DocmanConstants.class.php');
require_once('Group.class.php');
require_once('common/mvc/Controler.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('common/include/UserManager.class.php');

require_once('view/Docman_View_GetShowViewVisitor.class.php');
require_once('view/Docman_View_GetFieldsVisitor.class.php');


require_once('Docman_Token.class.php');
//require_once('DocmanOneFolderIsWriteable.class.php');

require_once('common/include/Feedback.class.php');

require_once('Docman_NotificationsManager.class.php');
require_once('Docman_NotificationsManager_Add.class.php');
require_once('Docman_NotificationsManager_Delete.class.php');
require_once('Docman_NotificationsManager_Move.class.php');

require_once('Docman_Log.class.php');
require_once('common/event/EventManager.class.php');

require_once('Docman_PermissionsManager.class.php');

require_once('Docman_ReportBo.class.php');
require_once('Docman_MetadataFactory.class.php');

$GLOBALS['Language']->loadLanguageMsg('docman', 'docman');

class DocmanController extends Controler {
    // variables
    var $request;
    var $user;
    var $groupId;
    var $themePath;
    var $plugin;
    var $logger;
    var $feedback;
    var $user_can_admin;

    function DocmanController(&$plugin, $pluginPath, $themePath, &$request) {
        $this->request        =& $request;
        $this->user           = null;
        $this->groupId        = null;
        $this->user_can_admin = null;
        $this->pluginPath     = $pluginPath;
        $this->themePath      = $themePath;
        $this->plugin         = $plugin;

        $flash = user_get_preference('plugin_docman_flash');
        if ($flash) {
            user_del_preference('plugin_docman_flash');
            $this->feedback = unserialize($flash);
        } else {
            $this->feedback =& $GLOBALS['Response']->_feedback;
        }
        
        $event_manager =& $this->_getEventManager();
        
        $this->logger  =& new Docman_Log();
        $event_manager->addListener(PLUGIN_DOCMAN_EVENT_ADD,              $this->logger, 'log', true, 0);
        $event_manager->addListener(PLUGIN_DOCMAN_EVENT_EDIT,             $this->logger, 'log', true, 0);
        $event_manager->addListener(PLUGIN_DOCMAN_EVENT_MOVE,             $this->logger, 'log', true, 0);
        $event_manager->addListener(PLUGIN_DOCMAN_EVENT_DEL,              $this->logger, 'log', true, 0);
        $event_manager->addListener(PLUGIN_DOCMAN_EVENT_ACCESS,           $this->logger, 'log', true, 0);
        $event_manager->addListener(PLUGIN_DOCMAN_EVENT_NEW_VERSION,      $this->logger, 'log', true, 0);
        $event_manager->addListener(PLUGIN_DOCMAN_EVENT_METADATA_UPDATE,  $this->logger, 'log', true, 0);
        
        $this->notificationsManager =& new Docman_NotificationsManager($this->getGroupId(), get_server_url().$this->getDefaultUrl(), $this->feedback);
        $event_manager->addListener(PLUGIN_DOCMAN_EVENT_EDIT,            $this->notificationsManager, 'somethingHappen', true, 0);
        $event_manager->addListener(PLUGIN_DOCMAN_EVENT_NEW_VERSION,     $this->notificationsManager, 'somethingHappen', true, 0);
        $event_manager->addListener(PLUGIN_DOCMAN_EVENT_METADATA_UPDATE, $this->notificationsManager, 'somethingHappen', true, 0);
        $event_manager->addListener('send_notifications',    $this->notificationsManager, 'sendNotifications', true, 0);
        $this->notificationsManager_Add =& new Docman_NotificationsManager_Add($this->getGroupId(), get_server_url().$this->getDefaultUrl(), $this->feedback);
        $event_manager->addListener(PLUGIN_DOCMAN_EVENT_ADD, $this->notificationsManager_Add, 'somethingHappen', true, 0);
        $event_manager->addListener('send_notifications',    $this->notificationsManager_Add, 'sendNotifications', true, 0);
        $this->notificationsManager_Delete =& new Docman_NotificationsManager_Delete($this->getGroupId(), get_server_url().$this->getDefaultUrl(), $this->feedback);
        $event_manager->addListener(PLUGIN_DOCMAN_EVENT_DEL, $this->notificationsManager_Delete, 'somethingHappen', true, 0);
        $event_manager->addListener('send_notifications',    $this->notificationsManager_Delete, 'sendNotifications', true, 0);
        $this->notificationsManager_Move =& new Docman_NotificationsManager_Move($this->getGroupId(), get_server_url().$this->getDefaultUrl(), $this->feedback);
        $event_manager->addListener(PLUGIN_DOCMAN_EVENT_MOVE, $this->notificationsManager_Move, 'somethingHappen', true, 0);
        $event_manager->addListener('send_notifications',     $this->notificationsManager_Move, 'sendNotifications', true, 0);
    }

    /**
     * Wrapper to i18n string call for docman.
     * static
     */
    function txt($key, $vars = array()) {
        return $GLOBALS['Language']->getText('plugin_docman', $key, $vars);
    }

    // Franlky, this is not at all the best place to do this.
    function installDocman($ugroupsMapping, $group_id = false) {
        $_gid = $group_id ? $group_id : (int) $this->request->get('group_id');

        $item_factory =& $this->_getItemFactory();
        $root =& $item_factory->getRoot($_gid);
        if ($root) {
            // Docman already install for this project.
            return false;
        } else {
            $project = group_get_object($_gid);
            $tmplGroupId = (int) $project->getTemplate();
            $this->_cloneDocman($tmplGroupId, $_gid, $ugroupsMapping);
        }
    }

    function _cloneDocman($srcGroupId, $dstGroupId, $ugroupsMapping) {
        // Clone Docman permissions
        $dPm =& Docman_PermissionsManager::instance($this->getGroupId());
        if($ugroupsMapping === false) {
            $dPm->setDefaultDocmanPermissions($dstGroupId);
        }
        else {
            $dPm->cloneDocmanPermissions($srcGroupId, $dstGroupId);
        }

        // Clone Metadata definitions
        $metadataMapping = array();
        $mdFactory = new Docman_MetadataFactory($srcGroupId);
        $mdFactory->cloneMetadata($dstGroupId, $metadataMapping);

        // Clone Items, Item's permissions and metadata values
        $user = $this->getUser();
        $itemFactory = $this->_getItemFactory();
        $dataRoot = $this->getProperty('docman_root');
        $itemFactory->cloneItems($srcGroupId, $dstGroupId, $user, $metadataMapping, $ugroupsMapping, $dataRoot);

        //@todo: verify that key for title for root is copied instead of
        //       string
    }

    function getLogger() {
        return $this->logger;
    }
    function logsDaily($params) {
        $this->logger->logsDaily($params);
    }
    
    function &_getEventManager() {
        return EventManager::instance();
    }
    
    function &getUser() {
        if($this->user === null) {
            $um =& UserManager::instance();
            $this->user = $um->getCurrentUser();
        }
        return $this->user;
    }
    
    /***************** PERMISSIONS ************************/
    function userCanRead($item_id) {
        $dPm  =& Docman_PermissionsManager::instance($this->getGroupId());
        $user =& $this->getUser();
        return $dPm->userCanRead($user, $item_id);
    }
    function userCanWrite($item_id) {
        $dPm  =& Docman_PermissionsManager::instance($this->getGroupId());
        $user =& $this->getUser();
        return $dPm->userCanWrite($user, $item_id);
    }
    function userCanManage($item_id) {
        $dPm  =& Docman_PermissionsManager::instance($this->getGroupId());
        $user =& $this->getUser();
        return $dPm->userCanManage($user, $item_id);
    }
    function userCanAdmin() {
        $dPm  =& Docman_PermissionsManager::instance($this->getGroupId());
        $user =& $this->getUser();
        return $dPm->userCanAdmin($user);
    }
    /******************************************************/
    
    function getGroupId() {
        if($this->groupId === null) {
            $_gid = (int) $this->request->get('group_id');
            if($_gid > 0) {
                $this->groupId = $_gid;
            }
        }
        return $this->groupId;
    }

    function getDefaultUrl() {
        $_gid = $this->getGroupId();
        return $this->pluginPath.'/?group_id='.$_gid;
    }

    function getAdminUrl() {
        $_gid = $this->getGroupId();
        return $this->pluginPath.'/admin/?group_id='.$_gid;
    }
    
    function getThemePath() {
        return $this->themePath;
    }
    
    function _initFilters($view) {        
        $reportBo = new Docman_ReportBo($this->getGroupId());

        $report =& $reportBo->get($view);
        if($report !== null) {
            $reportBo->setup($report, $this->request, $this->getGroupId(), $this->feedback);
        }

        $this->_viewParams['filter'] =& $report;            
    }


    /*private*/ function _checkBrowserCompliance() {
        if($this->request_type == 'http' && $this->request->browserIsNetscape4()) {
            $this->feedback->log('warning', $GLOBALS['Language']->getText('plugin_docman', 'docman_browserns4'));
        }
    }

    function getValueInArrays($key, $array1, $array2) {
        $value = null;
        if(isset($array1[$key])) {
            $value = $array1[$key];
        }
        elseif(isset($array2[$key])) {
            $value = $array2[$key];
        }
        return $value;
    }

    function setMetadataValuesFromUserInput(&$item, $itemArray, $metadataArray) {
        $mdvFactory = new Docman_MetadataValueFactory($this->groupId);
        
        $mdIter =& $item->getMetadataIterator();
        $mdIter->rewind();
        while($mdIter->valid()) {
            $md =& $mdIter->current();
            
            $value = $this->getValueInArrays($md->getLabel(), $itemArray, $metadataArray);
            if($value !== null) {
                $mdv = $mdvFactory->newMetadataValue($item->getId(), $md->getId(), $md->getType(), $value);
                $val = $mdv->getValue();
                $mdvFactory->validateInput($md, $val);
                $md->setValue($val);
            }
            $mdIter->next();
        }
    }

    function createItemFromUserInput() {
        $new_item = null;
        if($this->request->exist('item')) {
            $item_factory =& $this->_getItemFactory();
            $mdFactory = new Docman_MetadataFactory($this->_viewParams['group_id']);

            $i = $this->request->get('item');
            $new_item = $item_factory->getItemFromRow($i);
            // Build metadata list (from db) ...
            $mdFactory->appendItemMetadataList($new_item);
            // ... and set the value (from user input)
            $this->setMetadataValuesFromUserInput($new_item,
                                                  $i,
                                                  $this->request->get('metadata'));
        }
        return $new_item;
    }
    
    function updateMetadataFromUserInput(&$item) {
        $this->setMetadataValuesFromUserInput($item, 
                                             $this->request->get('item'), 
                                             $this->request->get('metadata'));
    }

    function updateItemFromUserInput(&$item) {
        if($this->request->exist('item')) {
            $i = $this->request->get('item');
            $itemFactory =& $this->_getItemFactory();
            switch($itemFactory->getItemTypeForItem($item)) {
            case PLUGIN_DOCMAN_ITEM_TYPE_WIKI:
                $item->setPagename($i['wiki_page']);
                break;
            case PLUGIN_DOCMAN_ITEM_TYPE_LINK:
                $item->setUrl($i['link_url']);
                break;
            }
        }
    }

    function request() {
        if (!$this->request->exist('group_id')) {
            $this->feedback->log('error', 'Project is missing.');
            $this->_setView('Error');
        } else {
            $_groupId = (int) $this->request->get('group_id');
            $project = group_get_object($_groupId);
            if($project == false) {
                $this->feedback->log('error', 'Project is missing.');
                $this->_setView('Error');
                return;
            }
            
            // Browser alert
            $this->_checkBrowserCompliance();
            
            //token for redirection
            $tok =& new Docman_Token();
            
            $this->_viewParams['docman']         =& $this;
            $this->_viewParams['user']           =& $this->getUser();
            $this->_viewParams['token']          =  $tok->getToken();
            $this->_viewParams['default_url']    =  $this->getDefaultUrl();
            $this->_viewParams['theme_path']     =  $this->getThemePath();
            $this->_viewParams['group_id']       = (int) $this->request->get('group_id');                
            if($this->request->exist('version_number')) {
                $this->_viewParams['version_number'] = (int) $this->request->get('version_number');
            }

            if ($this->request->exist('section')) {
                $this->_viewParams['section'] = $this->request->get('section');
            } else if ($this->request->get('action') == 'permissions') {
                $this->_viewParams['section'] = 'permissions';
            }
            $view = $this->request->exist('action') ? $this->request->get('action') : 'show';
            $this->_viewParams['action'] = $view;

            // Start is used by Table view (like LIMIT start,offset)
            if($this->request->exist('start')) {
                $this->_viewParams['start']       = (int) $this->request->get('start');
            }
            
            if($this->request->exist('pv')) {
                $this->_viewParams['pv']       = (int) $this->request->get('pv');
            }

            if($this->request->exist('report')) {
                $this->_viewParams['report'] = $this->request->get('report');
            }

            $item_factory =& $this->_getItemFactory();
            $root =& $item_factory->getRoot($this->request->get('group_id'));
            if (!$root) {
                // Install
                $_gid = (int) $this->request->get('group_id');
                
                $project = group_get_object($_gid);
                $tmplGroupId = (int) $project->getTemplate();
                $this->_cloneDocman($tmplGroupId, $_gid, false);

                $this->_viewParams['redirect_to'] = $_SERVER['REQUEST_URI'];
                $this->view = 'Redirect';
            } else {
                $id = $this->request->get('id');
                if (!$id && $this->request->exist('item')) {
                    $i = $this->request->get('item');
                    if (isset($i['id'])) {
                        $id = $i['id'];
                    }
                }
                if ($id) {
                    $item =& $item_factory->getItemFromDb($id);
                    
                    if (!$item) {
                        $this->feedback->log('error', 'Unable to retrieve item. Perhaps it was removed.');
                        $this->_setView('DocmanError');
                    }

                } else {
                    $item =& $root;
                }
                if ($item) {
                    if ($item->getGroupId() != $this->request->get('group_id')) {
                        $g =& group_get_object($this->request->get('group_id'));
                        $g2 =& group_get_object($item->getGroupId());
                        $this->feedback->log('warning', $GLOBALS['Language']->getText('plugin_docman', 'item_does_not_belong', array($item->getTitle(), $g->getPublicName(), $g2->getPublicName())));
                        $this->_viewParams['redirect_to'] = str_replace('group_id='. $this->request->get('group_id'), 'group_id='. $item->getGroupId(), $_SERVER['REQUEST_URI']);
                        $this->view = 'Redirect';
                    } else {
                        $user = $this->getUser();
                        $dpm =& Docman_PermissionsManager::instance($this->getGroupId());
                        $can_read = $dpm->userCanAccess($user, $item->getId());
                        $folder_or_document = is_a($item, 'Docman_Folder') ? 'folder' : 'document';
                        if (!$can_read) {
                            $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_view'));
                            $this->_setView('ProjectError');
                        } else {
                            $mdFactory = new Docman_MetadataFactory($this->_viewParams['group_id']);
                            $mdFactory->appendItemMetadataList($item);
                            
                            $itemBo                         = new Docman_ItemBo($this->request->get('group_id'));
                            $hierarchy                      =& $itemBo->getItemTree($root->getId(), array('ignore_collapse' => true, 'user' => $this->getUser()));
                            $this->_viewParams['hierarchy'] =& $hierarchy;
                            $get_show_view =& new Docman_View_GetShowViewVisitor();
                            $this->_viewParams['item'] =& $item;
                            if (strpos($view, 'admin') === 0 && !$this->userCanAdmin()) {
                                $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_admin'));
                                $this->view = $item->accept($get_show_view, $this->request->get('report'));
                            } else {
                                switch ($view) {
                                    case 'show':
                                        $this->view = $item->accept($get_show_view, $this->request->get('report'));
                                        if ($item->getId() == $root->getid()) {
                                            $preferrences_exist   = $item_factory->preferrencesExist($this->request->get('group_id'), $this->_viewParams['user']->getId());
                                            $max_items_is_reached = $this->getProperty('docman_max_items') >= $this->_count($item, $hierarchy, $item->getId() == $hierarchy->getId());
                                            if (!$preferrences_exist && $max_items_is_reached) {
                                                $this->_actionParams['hierarchy'] =& $hierarchy;
                                                $this->action = 'expandAll';
                                            }
                                        }
                                        $this->_initFilters($this->view);
                                        break;
                                    case 'expandFolder':
                                        $this->action = 'expandFolder';
                                        if ($this->request->get('view') == 'ulsubfolder') {
                                            $this->view = 'RawTree';
                                        } else {
                                            $this->_viewParams['item'] =& $root;
                                            $this->view = 'Tree';
                                        }
                                        break;
                                    case 'collapseFolder':
                                        $this->action = 'collapseFolder';
                                        $this->_viewParams['item'] =& $root;
                                        $this->view = 'Tree';
                                        break;
                                    case 'admin_set_permissions':
                                        $this->action = $view;
                                        $this->view   = 'Admin_Permissions';
                                        break;
                                    case 'admin_change_view':
                                        $this->action = $view;
                                        $this->_viewParams['default_url_params'] = array(
                                            'action'  => 'admin_view',
                                            'id'      => $item->getParentId()
                                        );
                                        $this->view = 'RedirectAfterCrud';
                                        break;
                                    case 'admin':
                                    case 'details':
                                        $this->view = ucfirst($view);
                                        break;
                                    case 'admin_view':
                                        $this->view = 'Admin_View';
                                        break;
                                    case 'admin_permissions':
                                        $this->view = 'Admin_Permissions';
                                        break;
                                    case 'admin_metadata':
                                        $this->view = 'Admin_Metadata';
                                        $mdFactory = new Docman_MetadataFactory($this->_viewParams['group_id']);
                                        $mdIter =& $mdFactory->getMetadataForGroup();
                                        $this->_viewParams['mdIter'] =& $mdIter;
                                        break;
                                    case 'admin_md_details':
                                        // Sanitize
                                        $_mdLabel = $this->request->get('md');
    
                                        // Valid
                                        $valid = false;
                                        $md = null;
                                        $mdFactory = new Docman_MetadataFactory($this->_viewParams['group_id']);
                                        $valid = $this->validateMetadata($_mdLabel, $md);
    
                                        if(!$valid) {
                                            $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 
                                                                                                        'error_invalid_md'));
                                            $this->view = 'RedirectAfterCrud';
                                            $this->_viewParams['default_url_params'] = array('action' => 'admin_metadata');
                                        }
                                        else {
                                            $this->view = 'Admin_MetadataDetails';
                                            $mdFactory->appendMetadataValueList($md, false);
                                            $this->_viewParams['md'] =& $md;
                                        }
                                        break;
                                    case 'admin_md_details_update':
                                        $this->action = $view;
                                        $_label = $this->request->get('label');
                                        $this->_viewParams['default_url_params'] = array('action'  => 'admin_md_details',
                                                                                         'md' => $_label);
                                        $this->view = 'RedirectAfterCrud';
                                        break;
                                    case 'admin_create_metadata':
                                        $this->action = $view;
                                        $this->_viewParams['default_url_params'] = array('action'  => 'admin_metadata');
                                        $this->view = 'RedirectAfterCrud';
                                        break;
                                    case 'admin_delete_metadata':
                                        $valid = false;
                                        // md
                                        // Sanitize
                                        $_mdLabel = $this->request->get('md');
                                        
                                        // Valid
                                        $logmsg = '';
                                        $mdFactory = new Docman_MetadataFactory($this->_viewParams['group_id']);
                                        $md = null;
                                        $vld = $this->validateMetadata($_mdLabel, $md);
                                        if($vld) {
                                            if(!$mdFactory->isHardCodedMetadata($md->getLabel())) {
                                                $valid = true;
                                            }
                                            else {
                                                $logmsg = $GLOBALS['Language']->getText('plugin_docman', 
                                                                                        'error_cannot_delete_hc_md');
                                            }
                                        }
                                        else {
                                            $logmsg = $GLOBALS['Language']->getText('plugin_docman', 
                                                                                    'error_invalid_md');
                                        }
    
                                        if(!$valid) {
                                            if($logmsg != '') {
                                                $this->feedback->log('error', $logmsg);
                                            }
                                            $this->view = 'RedirectAfterCrud';
                                            $this->_viewParams['default_url_params'] = array('action' => 'admin_metadata');
                                        }
                                        else {
                                           $this->action = $view;
                                           $this->_actionParams['md'] = $md;
                                        }
                                        
                                        break;
                                    case 'admin_create_love':
                                        $this->action = $view;
                                        $this->_viewParams['default_url_params'] = array('action'  => 'admin_md_details',
                                                                                         'md' => $this->request->get('md'));
                                        $this->view = 'RedirectAfterCrud';
                                        break;
                                    case 'admin_delete_love':
                                        $this->action = $view;
                                        $this->_viewParams['default_url_params'] = array('action'  => 'admin_md_details',
                                                                                         'md' => $this->request->get('md'));
                                        $this->view = 'RedirectAfterCrud';
                                        break;
                                    case 'admin_display_love':
                                        $valid = false;
                                        // Required params:
                                        // md (string [a-z_]+)
                                        // loveid (int)
    
                                        // Sanitize
                                        $_mdLabel = $this->request->get('md');
                                        $_loveId = (int) $this->request->get('loveid');
    
                                        // Valid
                                        $md = null;
                                        $love = null;
                                        $this->validateMetadata($_mdLabel, $md);
                                        if($md !== null && $md->getLabel() !== 'status') {
                                            $valid = $this->validateLove($_loveId, $md, $love);
                                        }
    
                                        if(!$valid) {
                                            $this->view = 'RedirectAfterCrud';
                                            $this->_viewParams['default_url_params'] = array('action' => 'admin_md_details',
                                                                                             'md'     => $_mdLabel);
                                        }
                                        else {
                                            $mdFactory = new Docman_MetadataFactory($this->groupId);
                                            $mdFactory->appendMetadataValueList($md, false);
    
                                            $this->view = 'Admin_MetadataDetailsUpdateLove';
                                            $this->_viewParams['md'] = $md;
                                            $this->_viewParams['love'] = $love;
                                        }
                                        break;
                                    case 'admin_update_love':
                                        $valid = false;
                                        // Required params:
                                        // md (string [a-z_]+)
                                        // loveid (int)
                                        //
                                        // rank (beg, end, [0-9]+)
                                        // name
                                        // descr
    
                                        // Sanitize
                                        /// @todo sanitize md, rank, name, descr
                                        $_mdLabel = $this->request->get('md');
                                        $_loveId = (int) $this->request->get('loveid');
                                        $_rank = $this->request->get('rank');
                                        $_name = $this->request->get('name');
                                        $_descr = $this->request->get('descr');
    
                                        // Valid
                                        $md = null;
                                        $love = null;
                                        $this->validateMetadata($_mdLabel, $md);
                                        if($md !== null && $md->getLabel() !== 'status') {
                                            $valid = $this->validateLove($_loveId, $md, $love);
                                        }
    
                                        if(!$valid) {
                                            $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_md_or_love'));
                                            $this->view = 'RedirectAfterCrud';
                                            $this->_viewParams['default_url_params'] = array('action' => 'admin_display_love',
                                                                                             'md'     => $_mdLabel,
                                                                                             'loveid' => $_loveId);
                                        }
                                        else {
                                            // Set parameters
                                            $love->setRank($_rank);
                                            $love->setName($_name);
                                            $love->setDescription($_descr);
    
                                            // define action
                                            $this->action = $view;
                                            $this->_actionParams['md'] = $md;
                                            $this->_actionParams['love'] = $love;
                                        }
                                        break;
                                    case 'move':
                                        if (!$this->userCanWrite($item->getId()) || !$this->userCanWrite($item->getParentId())) {
                                            $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_move'));
                                            $this->view = 'Details';
                                        } else {
                                            if ($this->request->exist('quick_move')) {
                                                $this->action = 'move';
                                                $this->view = null;
                                            } else {
                                                $this->view = ucfirst($view);
                                            }
                                        }
                                        break;
                                    case 'newGlobalDocument':
                                        if ($dpm->oneFolderIsWritable($user)) {
                                            $this->view = 'NewDocument';
                                        } else {
                                            $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_create'));
                                            $this->view = $item->accept($get_show_view, $this->request->get('report'));
                                        }
                                        break;
                                    case 'newDocument':
                                    case 'newFolder':
                                        if (!$this->userCanWrite($item->getId())) {
                                            $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_create'));
                                            $this->view = 'Details';
                                        } else {
                                            $this->view = ucfirst($view);
                                        }
                                        break;
                                    case 'monitor':
                                        if ($this->request->exist('monitor')) {
                                            $this->_actionParams['monitor'] =  $this->request->get('monitor');
                                            if ($this->request->exist('cascade')) {
                                                $this->_actionParams['cascade'] = $this->request->get('cascade');
                                            }
                                            $this->_actionParams['item']    =& $item;
                                            $this->action = 'monitor';
                                        }
                                        $this->_setView('Details');
                                        break;
                                    case 'move_here':
                                        if (!$this->request->exist('item_to_move')) {
                                            $this->feedback->log('error', 'Missing parameter.');
                                            $this->view = 'DocmanError';
                                        } else {
                                            $item_to_move =& $item_factory->getItemFromDb($this->request->get('item_to_move'));
                                            $this->view = null;
                                            if ($this->request->exist('confirm')) {
                                                if (!$item_to_move || !($this->userCanWrite($item->getId()) && $this->userCanWrite($item_to_move->getId()) && $this->userCanWrite($item_to_move->getParentId()))) {
                                                    $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_move'));
                                                    $this->_set_moveView_errorPerms();
                                                } else {
                                                    $this->action = 'move';
                                                }
                                            } 
                                            if (!$this->view) {
                                                $this->_set_redirectView();
                                            }
                                        }
                                        break;
                                    case 'permissions':
                                        if (!$this->userCanManage($item->getId())) {
                                           $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_perms'));
                                           $this->view = 'Details';
                                        } else {
                                           $this->action = 'permissions';
                                           $this->view   = 'Details';
                                        }
                                        break;
                                    case 'confirmDelete':
                                        if (!$this->userCanWrite($item->getId()) || !$this->userCanWrite($item->getParentId())) {
                                            $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_delete'));
                                            $this->view = 'Details';
                                        } else {
                                            $this->view   = 'Delete';
                                        }
                                        break;
                                    case 'action_new_version':
                                        if (!$this->userCanWrite($item->getId())) {
                                            $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_edit'));
                                            $this->view = 'Details';
                                        } else {
                                            $this->view   = 'NewVersion';
                                        }
                                        break;
                                    case 'action_update':
                                        if (!$this->userCanWrite($item->getId())) {
                                            $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_edit'));
                                            $this->view = 'Details';
                                        } else {
                                            $this->view   = 'Update';
                                        }
                                        break;

                                    case 'action_copy':
                                        $_action = $this->request->get('orig_action');
                                        $_id     = $this->request->get('orig_id');
                                        $this->_actionParams['item'] = $item;

                                        $this->action = $view;

                                        $this->_viewParams['default_url_params'] = array(
                                                    'action'  => $_action,
                                                    'id'      => $_id
                                                );

                                        $this->view = 'RedirectAfterCrud';
                                        break;

                                    case 'action_paste':
                                        $itemToPaste = null;
                                        $allowed = $this->checkPasteIsAllowed($item, &$itemToPaste);
                                        if(!$allowed) {
                                            $this->view = 'Details';
                                        }
                                        else {
                                            $this->_viewParams['itemToPaste'] = $itemToPaste;
                                            $this->view = 'Paste';
                                        }
                                        break;

                                    case 'paste':
                                        $itemToPaste = null;
                                        $allowed = $this->checkPasteIsAllowed($item, &$itemToPaste);
                                        if(!$allowed) {
                                            $this->view = 'Details';
                                        }
                                        else {
                                            $this->_actionParams['item'] = $item;
                                            $this->_actionParams['rank'] = $this->request->get('rank');
                                            $this->_actionParams['itemToPaste'] = $itemToPaste;
                                            $this->action = $view;

                                            $this->_viewParams['default_url_params'] = array(
                                                    'action'  => 'show',
                                                    'id'      => $item->getId()
                                                    );
                                            $this->view = 'RedirectAfterCrud';
                                        }
                                        break;

                                    case 'approval_create':
                                        if (!$this->userCanWrite($item->getId())) {
                                            $this->feedback->log('error', $this->txt('error_perms_edit'));
                                            $this->view = 'Details';
                                        } else {
                                            $this->view = 'ApprovalCreate';
                                        }
                                        break;
                                        
                                    case 'approval_delete':
                                        if (!$this->userCanWrite($item->getId())) {
                                            $this->feedback->log('error', $this->txt('error_perms_edit'));
                                            $this->view = 'Details';
                                        } else {
                                            $this->action = $view;
                                            $this->_actionParams['item']   = $item;

                                            $this->_viewParams['default_url_params'] = array(
                                                    'action'  => 'approval_create',
                                                    'id'      => $item->getId()
                                                    );
                                            $this->view = 'RedirectAfterCrud';
                                        }
                                        break;
                                        
                                    case 'approval_update':
                                        if (!$this->userCanWrite($item->getId())) {
                                            $this->feedback->log('error', $this->txt('error_perms_edit'));
                                            $this->view = 'Details';
                                        } else {
                                            $this->_actionParams['item']   = $item;
                                            $this->_actionParams['status'] = (int) $this->request->get('status');
                                            $this->_actionParams['description'] = $this->request->get('description');
                                            $this->_actionParams['notification'] = (int) $this->request->get('notification');
                                            $this->action = $view;

                                            $this->_viewParams['default_url_params'] = array(
                                                    'action'  => 'approval_create',
                                                    'id'      => $item->getId()
                                                    );
                                            $this->view = 'RedirectAfterCrud';
                                        }
                                        break;

                                    case 'approval_add_user':
                                        if (!$this->userCanWrite($item->getId())) {
                                            $this->feedback->log('error', $this->txt('error_perms_edit'));
                                            $this->view = 'Details';
                                        } else {
                                            $this->_actionParams['item'] = $item;
                                            $this->_actionParams['user_list'] = $this->request->get('user_list');
                                            $this->_actionParams['ugroup']    = (int) $this->request->get('ugroup');
                                            $this->action = $view;

                                            $this->_viewParams['default_url_params'] = array(
                                                    'action'  => 'approval_create',
                                                    'id'      => $item->getId()
                                                    );
                                            $this->view = 'RedirectAfterCrud';
                                        }
                                        break;

                                    case 'approval_upd_user':
                                        if (!$this->userCanWrite($item->getId())) {
                                            $this->feedback->log('error', $this->txt('error_perms_edit'));
                                            $this->view = 'Details';
                                        } else {
                                            $this->_actionParams['item'] = $item;
                                            $this->_actionParams['user_id'] = (int) $this->request->get('user_id');
                                            $this->_actionParams['rank']    = $this->request->get('rank');
                                            $this->action = $view;

                                            $this->_viewParams['default_url_params'] = array(
                                                    'action'  => 'approval_create',
                                                    'id'      => $item->getId()
                                                    );
                                            $this->view = 'RedirectAfterCrud';
                                        }
                                        break;

                                    case 'approval_del_user':
                                        if (!$this->userCanWrite($item->getId())) {
                                            $this->feedback->log('error', $this->txt('error_perms_edit'));
                                            $this->view = 'Details';
                                        } else {
                                            $this->_actionParams['item'] = $item;
                                            $this->_actionParams['user_id'] = (int) $this->request->get('user_id');
                                            $this->action = $view;

                                            $this->_viewParams['default_url_params'] = array(
                                                    'action'  => 'approval_create',
                                                    'id'      => $item->getId()
                                                    );
                                            $this->view = 'RedirectAfterCrud';
                                        }
                                        break;

                                    case 'approval_user_commit':
                                        $atf = new Docman_ApprovalTableFactory($item->getId());
                                        $table = $atf->getTable();
                                        if (!$this->userCanRead($item->getId())
                                            || !$atf->isReviewer($user->getId())
                                            || !$table->isEnabled()) {
                                            $this->feedback->log('error', $this->txt('error_perms_edit'));
                                            $this->view = 'Details';
                                        }
                                        else {
                                            $this->_actionParams['item'] = $item;

                                            $svState = 0;
                                            $sState = (int) $this->request->get('state');
                                            if($sState >= 0 && $sState < 3) {
                                                $svState = $sState;
                                            }
                                            $this->_actionParams['svState'] = $svState;

                                            $this->_actionParams['sVersion'] = null;
                                            if($this->request->exist('version')) {
                                                $sVersion = (int) $this->request->get('version');
                                                switch($item_factory->getItemTypeForItem($item)) {
                                                case PLUGIN_DOCMAN_ITEM_TYPE_WIKI:
                                                    if($sVersion <= 0) {
                                                        $sVersion = null;
                                                    }
                                                case PLUGIN_DOCMAN_ITEM_TYPE_FILE:
                                                case PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE:
                                                    // assume ok: do nothing.
                                                    break;
                                                default:
                                                    $sVersion = null;
                                                }
                                                $this->_actionParams['sVersion'] = $sVersion;
                                            }
                                            $this->_actionParams['usComment'] = $this->request->get('comment');
                                            $this->_actionParams['monitor'] = (int) $this->request->get('monitor');

                                            $this->action = $view;

                                            $this->_viewParams['default_url_params'] = array(
                                                    'action'  => 'details',
                                                    'section' => 'approval',
                                                    'id'      => $item->getId()
                                                    );
                                            $this->view = 'RedirectAfterCrud';
                                        }
                                        break;

                                    case 'approval_notif_resend':
                                        if (!$this->userCanWrite($item->getId())) {
                                            $this->feedback->log('error', $this->txt('error_perms_edit'));
                                            $this->view = 'Details';
                                        } else {
                                            $this->action = $view;
                                            $this->_actionParams['item'] = $item;
                                            
                                            $this->_viewParams['default_url_params'] = array(
                                                    'action'  => 'approval_create',
                                                    'id'      => $item->getId()
                                                    );
                                            $this->view = 'RedirectAfterCrud';
                                        }
                                        break;

                                    case 'edit':
                                        if (!$this->userCanWrite($item->getId())) {
                                            $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_edit'));
                                            $this->view = 'Details';
                                        } else {
                                            $mdFactory = new Docman_MetadataFactory($this->_viewParams['group_id']);
                                            $mdFactory->appendAllListOfValuesToItem($item);
                                            $this->view   = 'Edit';
                                        }
                                        break;
                                    case 'delete':
                                        if (!($this->userCanWrite($item->getId()) && $this->userCanWrite($item->getParentId()))) {
                                            $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_delete'));
                                            $this->_set_deleteView_errorPerms();
                                        } else if ($this->request->exist('confirm')) {
                                            $this->action = $view;
                                            $this->_set_redirectView();
                                        } else {
                                            $this->view = 'Details';
                                        }
                                        break;
                                    case 'createFolder':
                                    case 'createDocument':
                                        if ($this->request->exist('cancel')) {
                                            $this->_set_redirectView();
                                        } else {
                                            $i = $this->request->get('item');
                                            if (!$i || !isset($i['parent_id'])) {
                                                $this->feedback->log('error', 'Missing parameter.');
                                                $this->view = 'DocmanError';
                                            } else {
                                                $parent =& $item_factory->getItemFromDb($i['parent_id']);
                                                if (!$parent || !$this->userCanWrite($parent->getId())) {
                                                    $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_create'));
                                                    $this->_set_createItemView_errorParentDoesNotExist($item, $get_show_view);
                                                } else {
                                                    //Validations
                                                    $new_item = $this->createItemFromUserInput();
        
                                                    $valid = $this->_validateRequest(array_merge(
                                                        $new_item->accept(new Docman_View_GetFieldsVisitor()), 
                                                        $new_item->accept(new Docman_View_GetSpecificFieldsVisitor(), array('request' => &$this->request))
                                                    ));
                                                    
                                                    if ($user->isMember($this->getGroupId(), 'A')) {
                                                        $news = $this->request->get('news');
                                                        if ($news) {
                                                            $is_news_details = isset($news['details']) && trim($news['details']);
                                                            $is_news_summary = isset($news['summary']) && trim($news['summary']);
                                                            if ($is_news_details && !$is_news_summary) {
                                                                $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_create_news_summary'));
                                                                $valid = false;
                                                            }
                                                            if (!$is_news_details && $is_news_summary) {
                                                                $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_create_news_details'));
                                                                $valid = false;
                                                            }
                                                        }
                                                    }
                                                    //Actions
                                                    if ($valid) {
                                                        $this->action = $view;
                                                    }
                                                    //Views
                                                    if ($valid) {
                                                        $this->_set_redirectView();
                                                    } else {
                                                        $this->_viewParams['force_item']          = $new_item;
                                                        $this->_viewParams['force_news']          = $this->request->get('news');
                                                        $this->_viewParams['force_permissions']   = $this->request->get('permissions');
                                                        $this->_viewParams['force_ordering']      = $this->request->get('ordering');
                                                        $this->_viewParams['display_permissions'] = $this->request->exist('user_has_displayed_permissions');
                                                        $this->_viewParams['display_news']        = $this->request->exist('user_has_displayed_news');
                                                        $this->_set_createItemView_afterCreate($view);
                                                    }
                                                }
                                            }
                                        }
                                        break;
                                    case 'update':
                                    case 'update_wl':
                                    case 'new_version':
                                        if (!$this->userCanWrite($item->getId())) {
                                            $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_edit'));
                                            $this->view = 'Details';
                                        } else {
                                            $valid = true;
                                            if ($this->request->exist('confirm')) {
                                                //Validations
                                                if ($view == 'update') {
                                                    $this->updateMetadataFromUserInput($item);
    
                                                    $valid = $this->_validateRequest($item->accept(new Docman_View_GetFieldsVisitor()));
                                                } else {
                                                    $this->updateItemFromUserInput($item);
                                                    $valid = $this->_validateRequest($item->accept(new Docman_View_GetSpecificFieldsVisitor(), array('request' => &$this->request)));
                                                }
                                                //Actions
                                                if ($valid) {
                                                    if ($view == 'update_wl') {
                                                        $this->action = 'update';
                                                    } else {
                                                        $this->action = $view;
                                                    }
                                                }
                                            }
                                            //Views
                                            if ($valid) {
                                                if ($redirect_to = Docman_Token::retrieveUrl($this->request->get('token'))) {
                                                    $this->_viewParams['redirect_to'] = $redirect_to;
                                                }
                                                $this->view = 'RedirectAfterCrud';
                                            } else {
                                                if ($view == 'update_wl') {
                                                    $this->view = 'Update';
                                                } else if ($view == 'new_version') {
                                                    $this->view = 'NewVersion';
                                                } else {
                                                    $mdFactory = new Docman_MetadataFactory($this->_viewParams['group_id']);
                                                    $mdFactory->appendAllListOfValuesToItem($item);
                                                    $this->view = 'Edit';
                                                }
                                            }
                                        }
                                        break;
                                    case 'change_view':
                                        $this->action = $view;
                                        $this->view   = $item->accept($get_show_view, $this->request->get('report'));
                                        $this->_initFilters($this->request->get('selected_view'));
                                        break;
                                    case 'install':
                                        $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_alreadyinstalled'));
                                        $this->view = 'DocmanError';
                                        break;
                                    case 'search':
                                        $this->view = 'Table';
                                        $this->_initFilters('Table');
                                        break;
                                    case 'positionWithinFolder':
                                        $this->_viewParams['force_ordering'] = $this->request->get('default_position');
                                        $this->_viewParams['exclude'] = $this->request->get('exclude');
                                        $this->view = ucfirst($view);
                                        break;
                                    case 'permissionsForItem':
                                        $this->_viewParams['user_can_manage'] = $this->userCanManage($item->getId());
                                        $this->view = ucfirst($view);
                                        break;
                                    default:
                                        die(htmlspecialchars($view) .' is not supported');
                                        break;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    function getProperty($name) {
        $info =& $this->plugin->getPluginInfo();
        return $info->getPropertyValueForName($name);
    }
    var $item_factory;
    function &_getItemFactory() {
        if (!$this->item_factory) {
            $this->item_factory =& new Docman_ItemFactory();
        }
        return $this->item_factory;
    }
    
    function forceView($view) {
        $this->view = $view;
    }
    
    function _validateRequest($fields) {
        $valid = true;
        foreach($fields as $field) {
            $validatorList = null;
            if(is_a($field, 'Docman_MetadataHtml')) {
                $fv = $field->getValidator($this->request);
                if($fv !== null) {
                    if(!is_array($fv)) {
                        $validatorList = array($fv);
                    }
                    else {
                        $validatorList =& $fv;
                    }
                }
            }
            else {
                if (isset($field['validator'])) {
                    if (!is_array($field['validator'])) {
                        $validatorList = array($field['validator']);
                    }
                    else {
                        $validatorList = $field['validator'];
                    }
                }
            }
            
            if($validatorList !== null) {
                foreach($validatorList as $v) {
                    if (!$v->isValid()) {
                        $valid = false;
                        foreach($v->getErrors() as $error) {
                            $this->feedback->log('error', $error);
                        }
                    }
                }
            }            
        }
        return $valid;
    }
    
    function validateMetadata($label, &$md) {
        $valid = false;
        
        $mdFactory = new Docman_MetadataFactory($this->groupId);
        if($mdFactory->isValidLabel($label)) {
            $_md =& $mdFactory->getFromLabel($label);
            if($_md !== null 
               && $_md->getGroupId() == $this->groupId) {
                $valid = true;
                $md = $_md;
            }
        }
        
        return $valid;
    }
    
    function validateLove($loveId, $md, &$love) {
        $valid = false;
        
        $loveFactory = new Docman_MetadataListOfValuesElementFactory($md->getId());
        $_love =& $loveFactory->getByElementId($loveId, $md->getLabel());
        if($_love !== null) {
            // Still Need to verify that $love belong to $md
            $valid = true;
            $love = $_love;
        }
        
        return $valid;
    }
    
    function checkPasteIsAllowed($item, &$itemToPaste) {
        $isAllowed = false;
        
        $itemFactory =& $this->_getItemFactory();
        $user        =& $this->getUser();
        
        $type = $itemFactory->getItemTypeForItem($item);
        if(PLUGIN_DOCMAN_ITEM_TYPE_FOLDER != $type) {
            $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_paste_in_document'));
        }
        elseif (!$this->userCanWrite($item->getId())) {
            $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_edit'));
        } 
        else {
            $itemIdToPaste = $itemFactory->getCopyPreference($user);
            $itemToPaste = $itemFactory->getItemFromDb($itemIdToPaste);
            
            if($itemToPaste == null) {
                $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_paste_no_valid_item'));
            }
            else {
                $isAllowed = true;
            }
        }
        
        return $isAllowed;
    }
    
    function viewsManagement() {
        if ($this->view) {
            $className = $this->_includeView();
            if (class_exists($className)) {
                $wv = new $className($this);
                return $wv->display($this->_viewParams);
            } else {
                die($className .' does not exist.');
            }
        }
    }
    function _count(&$item, &$hierarchy, $go = false) {
        $nb = $go ? 1 : 0;
        if (is_a($hierarchy, 'Docman_Folder')) {
            $list =& $hierarchy->getAllItems();
            $iter =& $list->iterator();
            while($iter->valid()) {
                $o =& $iter->current();
                $n = $this->_count($item, $o, $go ? $go : $o->getId() == $item->getId());
                if ($n) {
                    $nb += $n;
                }
                $iter->next();
            }
        }
        return $nb;
    }
    
    function displayMyPageBox() {
        require_once('www/my/my_utils.php');

        $html = '';

        $html .= $GLOBALS['HTML']->box1_top($this->txt('my_reviews'), 0);

        $user =& $this->getUser();
        $atf = new Docman_ApprovalTableFactory(null);
        $reviewsArray = $atf-> getAllPendingReviewsForUser($user->getId(), PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET);

        if(count($reviewsArray) > 0) {
            // Get hide arguments
            $request =& HTTPRequest::instance();
            $hideItemId = (int) $request->get('hide_item_id');
            $hideApproval = null;
            if($request->exist('hide_plugin_docman_approval')) {
                $hideApproval = (int) $request->get('hide_plugin_docman_approval');
            }

            $prevGroupId = -1;
            $hideNow = false;
            $i = 0;

            //$html .= '<TR><TD colspan="2">Reviewer - Requester</TD></TR>';
            foreach($reviewsArray as $review) {
                if($review['group_id'] != $prevGroupId) {
                    list($hideNow,$count_diff,$hideUrl) = 
                        my_hide_url('plugin_docman_approval',$review['group_id'], $hideItemId, 1, $hideApproval);
                    $docmanUrl = $this->pluginPath.'/?group_id='.$review['group_id'];
                    $docmanHref = '<a href="'.$docmanUrl.'">'.$review['group'].'</a>';
                    if($prevGroupId != -1) {
                        $html .= '<tr class="boxitem"><td colspan="2">';
                    }
                    $html .= '<strong>'.$hideUrl.$docmanHref.'</strong></td></tr>';
                    $i = 0;
                }

                if(!$hideNow) {
                    $html .= '<tr class="'. util_get_alt_row_color($i++).'">';
                    // Document
                    $html .= '<td align="left">';
                    $html .= '<a href="'.$review['url'].'">'.$review['title'].'</a>';
                    $html .= '</td>';
                
                    // Date
                    $html .= '<td align="right">';
                    $html .= util_timestamp_to_userdateformat($review['date'], true);
                    $html .= '</td>';
                
                    $html .= '</tr>';
                }

                $prevGroupId = $review['group_id'];
            }
        } else {
            $html .= $this->txt('my_no_doc').'</td></tr>';
        }

        $html .= '<td><td colspan="2">&nbsp;</td></tr>';
        $html .= $GLOBALS['HTML']->box1_bottom(0);
        
        echo $html;
    }
}

?>
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
require_once('service.php');

require_once('common/mvc/Actions.class.php');
require_once('common/include/HTTPRequest.class.php');

require_once('DocmanActionsDeleteVisitor.class.php');
require_once('Docman_Folder.class.php');
require_once('Docman_FolderBo.class.php');
require_once('Docman_ItemFactory.class.php');
require_once('Docman_VersionFactory.class.php');
require_once('Docman_FileStorage.class.php');
require_once('Docman_MetadataValueFactory.class.php');
require_once('Docman_ExpandAllHierarchyVisitor.class.php');
require_once('Docman_ApprovalTableFactory.class.php');

require_once('view/Docman_View_Browse.class.php');

require_once('common/permission/PermissionsManager.class.php');

require_once('www/project/admin/permissions.php');
require_once('www/news/news_utils.php');

class DocmanActions extends Actions {
    
    var $event_manager;
    
    function DocmanActions(&$controler, $view=null) {
        parent::Actions($controler);
        $this->event_manager =& $this->_getEventManager();
    }
    
    function &_getEventManager() {
        return EventManager::instance();
    }
    
    function expandFolder() {
        $folderBo = new Docman_FolderBo();
        $folderBo->expand($this->_getFolderFromRequest());
    }
    function expandAll($params) {
        $params['hierarchy']->accept(new Docman_ExpandAllHierarchyVisitor(), array('folder_bo' => new Docman_FolderBo()));
    }
    function collapseFolder() {
        $folderBo = new Docman_FolderBo();
        $folderBo->collapse($this->_getFolderFromRequest());
    }
    function &_getFolderFromRequest() {
        $request =& HTTPRequest::instance();
        $folder = new Docman_Folder();
        $folder->setId($request->get('id'));
        $folder->setGroupId($request->get('group_id'));
        return $folder;
    }

    //@todo need to check owner rights on parent    
    function _checkOwnerChange($owner, &$user) {
        $ret_id = null;

        $_owner = util_user_finder($owner);
        if($_owner == "") {
            $this->_controler->feedback->log('warning', $GLOBALS['Language']->getText('plugin_docman', 'warning_missingowner'));
            $ret_id = $user->getId();
        }
        else {
            $dbresults = user_get_result_set_from_unix($_owner);
            $_owner_id     = db_result($dbresults, 0, 'user_id');
            $_owner_status = db_result($dbresults, 0, 'status');
            
            if($_owner_id === false || $_owner_id < 1 || !($_owner_status == 'A' || $_owner_status = 'R')) {
                $this->_controler->feedback->log('warning', $GLOBALS['Language']->getText('plugin_docman', 'warning_invalidowner'));
                $ret_id = $user->getId();
            }
            else {
                $ret_id = $_owner_id;
            }
        }
        return $ret_id;
    }

    function _raiseOwnerChangeEvent(&$user, &$item, $group_id, $old, $new) {
        $logEventParam = array('group_id' => $group_id,
                               'item'     => &$item,
                               'user'     => &$user,
                               'old_value' => $old,
                               'new_value' => $new,
                               'field'     => 'owner');
        $this->event_manager->processEvent(PLUGIN_DOCMAN_EVENT_METADATA_UPDATE,
                                           $logEventParam);
    }

    function _updateOwnerOnItemCreation($id, $group_id, $owner, &$user) {
        if($owner != "") {
            $_owner_id = $this->_checkOwnerChange($owner, $user);
                                
            if($_owner_id != $user->getId()) {
                $_update_row['id']      = $id;
                $_update_row['user_id'] = $_owner_id;

                $item_factory =& $this->_getItemFactory();
                $item_factory->update($_update_row);

                $_oldowner = user_getname($user->getId());
                $_newowner = user_getname($_owner_id);
                $this->_raiseOwnerChangeEvent($user, $user, $group_id,
                                              $_oldowner, $_newowner);
                
                return $_owner_id;
            }
            else {
                return $user->getId();
            }
        }
        else {
            return $user->getId();
        }
    }

    /**
     * This function handle file storage regarding user parameters.
     *
     * @access: private
     */
    function _storeFile($item) {
        $fs       =& $this->_getFileStorage();
        $user     =& $this->_controler->getUser();
        $request  =& $this->_controler->request;
        $iFactory =& $this->_getItemFactory();

        $uploadSucceded = false;

        $number = 0;
        $version = $item->getCurrentVersion();
        if($version) {
            $number = $version->getNumber() + 1;
        }

        $_action_type = 'initversion';
        if($number > 0) {
            $_action_type = 'newversion';
        }

        //
        // Prepare label and changelog from user input
        $_label = '';
        $_changelog = '';
        $data_version = $request->get('version');
        if ($data_version) {
            if (isset($data_version['label'])) {
                $_label = $data_version['label'];
            }
            if (isset($data_version['changelog'])) {
                $_changelog = $data_version['changelog'];
            }
        }
        else {
            if($number == 0) {
                $_changelog = 'Initial version';
            }
        }

        switch ($iFactory->getItemTypeForItem($item)) {
        case PLUGIN_DOCMAN_ITEM_TYPE_FILE:
            if ($request->exist('upload_content')) {
                $path = $fs->store($request->get('upload_content'), $request->get('group_id'), $item->getId(), 0);
                if ($path) {
                    $uploadSucceded = true;
                    $_filename = basename($path);
                    $_filesize = filesize($path);
                    $_filetype = mime_content_type($path); //be careful with false detection
                }
            } else {
                $path = $fs->upload($_FILES['file'], $item->getGroupId(), $item->getId(), $number);
                if ($path) {
                    $uploadSucceded = true;
                    $_filename = $_FILES['file']['name'];
                    $_filesize = $_FILES['file']['size'];
                    $_filetype = $_FILES['file']['type']; //TODO detect mime type server side
                }
            }
            break;
        case PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE:
            if ($path = $fs->store($request->get('content'), $item->getGroupId(), $item->getId(), $number)) {
                $uploadSucceded = true;

                //TODO take mimetype once the file has been written ?
                $_filename = basename($path);
                $_filesize = filesize($path);
                $_filetype = 'text/html';
            }
            break;
        default:
            break;
        }

        if($uploadSucceded) {
            $vFactory =& $this->_getVersionFactory();

            $vArray = array('item_id'   => $item->getId(),
                            'number'    => $number,
                            'user_id'   => $user->getId(),
                            'label'     => $_label,
                            'changelog' => $_changelog,
                            'filename'  => $_filename,
                            'filesize'  => $_filesize,
                            'filetype'  => $_filetype, 
                            'path'      => $path);
            $vId = $vFactory->create($vArray);
            
            $eArray = array('group_id' => $item->getGroupId(),
                            'item'     => &$item, 
                            'version'  => $vId,
                            'user'     => &$user);
            $this->event_manager->processEvent(PLUGIN_DOCMAN_EVENT_NEW_VERSION,
                                               $eArray);
            $this->_controler->feedback->log('info', $GLOBALS['Language']->getText('plugin_docman', 'info_create_'.$_action_type));
        }
        else {
            //TODO What should we do if upload failed ?
            //Maybe cancel item ?
            $this->_controler->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_create_'.$_action_type));
        }
    }

    function createFolder() {
        $request =& $this->_controler->request;
        $item_factory =& $this->_getItemFactory();
        if ($request->exist('item')) {
            $item = $request->get('item');
            $item['item_type'] = PLUGIN_DOCMAN_ITEM_TYPE_FOLDER;
            $user =& $this->_controler->getUser();
            $item['user_id']  = $user->getId();
            $item['group_id'] = $request->get('group_id');
            $id = $item_factory->create($this->sanitizeItemData($item), $request->get('ordering'));
            
            if ($id) {
                $new_item =& $item_factory->getItemFromDb($id);
                $parent   =& $item_factory->getItemFromDb($item['parent_id']);
                if ($request->exist('permissions') && $this->_controler->userCanManage($parent->getId())) {
                    $this->permissions(array('id' => $id, 'force' => true));
                } else {
                    $pm =& PermissionsManager::instance();
                    $pm->clonePermissions($item['parent_id'], $id, array('PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE'));
                }
                $this->event_manager->processEvent(PLUGIN_DOCMAN_EVENT_ADD, array(
                    'group_id' => $request->get('group_id'),
                    'parent'   => &$parent,
                    'item'     => &$new_item,
                    'user'     => &$user)
                );
                
                $this->_controler->feedback->log('info', $GLOBALS['Language']->getText('plugin_docman', 'info_folder_created'));

            }
        }
        $this->event_manager->processEvent('send_notifications', array());
    }
    
    function createDocument() {
        $request =& $this->_controler->request;
        $item_factory =& $this->_getItemFactory();
        if ($request->exist('item')) {
            $item = $request->get('item');
            $fs =& $this->_getFileStorage();
            if (
                    $item['item_type'] != PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE 
                    || 
                    (
                        $this->_controler->getProperty('embedded_are_allowed') 
                        && 
                        $request->exist('content')
                    )
                )
            {

                // Special handeling of obsolescence date
                if(isset($item['obsolescence_date']) 
                   && preg_match('/^([0-9]+)-([0-9]+)-([0-9]+)$/', 
                                 $item['obsolescence_date'], $d)) {
                    $item['obsolescence_date'] = mktime(0, 0, 0,
                                                        $d[2], $d[3], $d[1]);
                }
                else {
                    $item['obsolescence_date'] = 0;
                }

                $user =& $this->_controler->getUser();
                $item['user_id']  = $user->getId();
                $item['group_id'] = $request->get('group_id');
                $id = $item_factory->create($item, $request->get('ordering'));
                if ($id) {
                    $new_item =& $item_factory->getItemFromDb($id);
                    $parent   =& $item_factory->getItemFromDb($item['parent_id']);
                    if ($request->exist('permissions') && $this->_controler->userCanManage($parent->getId())) {
                        $this->permissions(array('id' => $id, 'force' => true));
                    } else {
                        $pm =& PermissionsManager::instance();
                        $pm->clonePermissions($item['parent_id'], $id, array('PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE'));
                    }
                    $this->event_manager->processEvent(PLUGIN_DOCMAN_EVENT_ADD, array(
                        'group_id' => $request->get('group_id'),
                        'parent'   => &$parent,
                        'item'     => &$new_item,
                        'user'     => &$user)
                    );
                    $this->_controler->feedback->log('info', $GLOBALS['Language']->getText('plugin_docman', 'info_document_created'));
                    
                    if($item['item_type'] == PLUGIN_DOCMAN_ITEM_TYPE_FILE ||
                       $item['item_type'] == PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE) {
                        $this->_storeFile($new_item);
                    }
                    
                    // Create metatata
                    if($request->exist('metadata')) {
                        $metadata_array = $request->get('metadata');
                        $mdvFactory = new Docman_MetadataValueFactory($request->get('group_id'));
                        $mdvFactory->createFromRow($id, $metadata_array);
                        if($mdvFactory->isError()) {
                            $this->_controler->feedback->log('error', $mdvFactory->getErrorMessage());
                        }
                    }

                    // Change owner if needed
                    //$this->_updateOwnerOnItemCreation($id, $item['group_id'], $item['owner'], $user);
                    
                    //Submit News about this document
                    if ($request->exist('news')) {
                        if ($user->isMember($request->get('group_id'), 'A') || $user->isMember($request->get('group_id'), 'N1') || $user->isMember($request->get('group_id'), 'N2')) { //only for allowed people
                            $news = $request->get('news');
                            if (isset($news['summary']) && trim($news['summary']) && isset($news['details']) && trim($news['details']) && isset($news['is_private'])) {
                                news_submit($request->get('group_id'), $news['summary'], $news['details'], $news['is_private']);
                                $this->_controler->feedback->log('info', $GLOBALS['Language']->getText('plugin_docman', 'info_news_created'));
                            }
                        } else {
                            $this->_controler->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_create_news'));
                        }
                    }
                }
            }
        }
        $this->event_manager->processEvent('send_notifications', array());
    }
    function update() {
        $request =& HTTPRequest::instance();
        if ($request->exist('item')) {
            $user =& $this->_controler->getUser();
            
            $data = $request->get('item');

            $item_factory =& $this->_getItemFactory();
            $item =& $item_factory->getItemFromDb($data['id']);
            
            // Update Owner
            $ownerChanged = false;
            if(array_key_exists('owner', $data)) {
                $_owner_id = $this->_checkOwnerChange($data['owner'], $user);
                if($_owner_id != $item->getOwnerId()) {
                    $ownerChanged = true;
                    $_oldowner = user_getname($item->getOwnerId());
                    $_newowner = user_getname($_owner_id);
                    $data['user_id'] = $_owner_id;
                }
                unset($data['owner']);
            }
            
            // Special handeling of obsolescence date
            if(isset($data['obsolescence_date']) && $data['obsolescence_date'] != '') {
                    $d = explode('-',$data['obsolescence_date']);
                    $data['obsolescence_date'] = mktime(0,0,0,
                                                        $d[1], $d[2], $d[0]);
            }
            else {
                $data['obsolescence_date'] = 0;
            }

            // Check is status change
            $statusChanged = false;
            if(array_key_exists('status', $data)) {
                $old_st = $item->getStatus();
                if($old_st != $data['status']) {
                    $statusChanged = true;
                }
            }

            // For empty document, check if type changed
            $createFile = false;
            $itemType = $item_factory->getItemTypeForItem($item);
            if($itemType == PLUGIN_DOCMAN_ITEM_TYPE_EMPTY
                && isset($data['item_type'])
               && $itemType != $data['item_type']
               && ($data['item_type'] != PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
                   || $this->_controler->getProperty('embedded_are_allowed'))) {
                
                if($data['item_type'] == PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
                   || $data['item_type'] == PLUGIN_DOCMAN_ITEM_TYPE_FILE) {
                    $createFile = true;
                }
            }
            else {
                $data['item_type'] =  $itemType;
            }

            $item_factory->update($this->sanitizeItemData($data));
            if(!$ownerChanged && !$statusChanged && !$request->exist('metadata')) {
                $this->event_manager->processEvent(PLUGIN_DOCMAN_EVENT_EDIT, array(
                    'group_id' => $request->get('group_id'),
                    'item'     => &$item,
                    'user'     => &$user)
                );
            }

            if($ownerChanged) {
                $this->_raiseOwnerChangeEvent($user,
                                              $item,
                                              $request->get('group_id'), 
                                              $_oldowner, 
                                              $_newowner);
            }

            if($statusChanged) {
                $logEventParam = array('group_id' => $request->get('group_id'),
                                       'item'     => &$item,
                                       'user'     => &$user,
                                       'old_value' => $old_st,
                                       'new_value' => $data['status'],
                                       'field'     => 'status');
                $this->event_manager->processEvent(PLUGIN_DOCMAN_EVENT_METADATA_UPDATE,
                                                   $logEventParam);
            }

            if($createFile) {
                // Re-create from DB (because of type changed)
                $item = $item_factory->getItemFromDb($data['id']);
                $this->_storeFile($item);
            }

            // Update real metatata
            if($request->exist('metadata')) {
                $metadata_array = $request->get('metadata');
                $mdvFactory = new Docman_MetadataValueFactory($request->get('group_id'));
                $mdvFactory->updateFromRow($data['id'], $metadata_array);
                if($mdvFactory->isError()) {
                    $this->_controler->feedback->log('error', $mdvFactory->getErrorMessage());
                }
            }

            $this->_controler->feedback->log('info', $GLOBALS['Language']->getText('plugin_docman', 'info_item_updated'));
        }
        $this->event_manager->processEvent('send_notifications', array());
    }

    function new_version() {
        $request =& HTTPRequest::instance();
        if ($request->exist('id')) {
            $user =& $this->_controler->getUser();
            $item_factory =& $this->_getItemFactory();
            $item =& $item_factory->getItemFromDb($request->get('id'));
            $item_type = $item_factory->getItemTypeForItem($item);
            if ($item_type == PLUGIN_DOCMAN_ITEM_TYPE_FILE || $item_type == PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE) {
                $this->_storeFile($item);
            }
        }
        $this->event_manager->processEvent('send_notifications', array());
    }
    var $filestorage;
    function &_getFileStorage() {
        if (!$this->filestorage) {
            $this->filestorage =& new Docman_FileStorage($this->_controler->getProperty('docman_root'));
        }
        return $this->filestorage;
    }
    var $item_factory;
    function &_getItemFactory() {
        if (!$this->item_factory) {
            $this->item_factory =& new Docman_ItemFactory();
        }
        return $this->item_factory;
    }
    var $version_factory;
    function &_getVersionFactory() {
        if (!$this->version_factory) {
            $this->version_factory =& new Docman_VersionFactory();
        }
        return $this->version_factory;
    }
    function sanitizeItemData($data) {
        $sanitized_data = $data;
        
        $must_be_stripped = array('title', 'wiki_page', 'link_url', 'description');
        foreach($must_be_stripped as $property) {
            if (isset($sanitized_data[$property])) {
                $sanitized_data[$property] = strip_tags($sanitized_data[$property]);
            }
        }
        return $sanitized_data;
    }
    function move() {
        $request =& $this->_controler->request;
        if ($request->exist('id')) {
            $user =& $this->_controler->getUser();
            
            $item_factory =& $this->_getItemFactory();
            //Move in a specific folder (maybe the same)
            if ($request->exist('item_to_move')) {
                $item          =& $item_factory->getItemFromDb($request->get('item_to_move'));
                $new_parent_id = $request->get('id');
                $ordering      = $request->get('ordering');
            } else {
                //Move in the same folder
                if ($request->exist('quick_move')) {
                    $item          =& $item_factory->getItemFromDb($request->get('id'));
                    $new_parent_id = $item->getParentId();
                    switch($request->get('quick_move')) {
                        case 'move-up':
                        case 'move-down':
                        case 'move-beginning':
                        case 'move-end':
                            $ordering = substr($request->get('quick_move'), 5);
                            break;
                        default:
                            $ordering = 'beginning';
                            break;
                    }
                }
            }
            if ($item && $new_parent_id) {
                $old_parent =& $item_factory->getItemFromDb($item->getParentId());
                if ($item_factory->setNewParent($item->getId(), $new_parent_id, $ordering)) {
                    $new_parent =& $item_factory->getItemFromDb($new_parent_id);
                    $this->event_manager->processEvent(PLUGIN_DOCMAN_EVENT_MOVE, array(
                        'group_id' => $request->get('group_id'),
                        'item'    => &$item, 
                        'parent'  => &$new_parent,
                        'user'    => &$user)
                    );
                    $this->_controler->feedback->log('info', $GLOBALS['Language']->getText('plugin_docman', 'info_item_moved', array(
                        $item->getGroupId(),
                        $old_parent->getId(), 
                        $old_parent->getTitle(),
                        $new_parent->getId(), 
                        $new_parent->getTitle()
                    )));
                } else {
                    $this->_controler->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_item_not_moved'));
                }
            }
        }
        $this->event_manager->processEvent('send_notifications', array());
    }
    function _get_definition_index_for_permission($p) {
        switch ($p) {
            case 'PLUGIN_DOCMAN_READ':
                return 1;
                break;
            case 'PLUGIN_DOCMAN_WRITE':
                return 2;
                break;
            case 'PLUGIN_DOCMAN_MANAGE':
                return 3;
                break;
            default:
                return 100;
                break;
        }
    }
    function permissions($params) {
        $request =& HTTPRequest::instance();
        $id = isset($params['id']) ? $params['id'] : $request->get('id');
        $force = isset($params['force']) ? $params['force'] : false;
        if ($id && $request->exist('permissions')) {
            $user =& $this->_controler->getUser();
            
            $item_factory =& $this->_getItemFactory();
            $item =& $item_factory->getItemFromDb($id);
            if ($item) {
                $permission_definition = array(
                    100 => array(
                        'order' => 0, 
                        'type'  => null,
                        'label' => null,
                        'previous' => null
                    ),
                    1 => array(
                        'order' => 1, 
                        'type'  => 'PLUGIN_DOCMAN_READ',
                        'label' => permission_get_name('PLUGIN_DOCMAN_READ'),
                        'previous' => 0
                    ),
                    2 => array(
                        'order' => 2, 
                        'type'  => 'PLUGIN_DOCMAN_WRITE',
                        'label' => permission_get_name('PLUGIN_DOCMAN_WRITE'),
                        'previous' => 1
                    ),
                    3 => array(
                        'order' => 3, 
                        'type'  => 'PLUGIN_DOCMAN_MANAGE',
                        'label' => permission_get_name('PLUGIN_DOCMAN_MANAGE'),
                        'previous' => 2
                    )
                );
                $permissions = $request->get('permissions');
                $old_permissions = permission_get_ugroups_permissions($item->getGroupId(), $item->getId(), array('PLUGIN_DOCMAN_READ','PLUGIN_DOCMAN_WRITE','PLUGIN_DOCMAN_MANAGE'), false);
                $done_permissions = array();
                $history = array(
                    'PLUGIN_DOCMAN_READ'  => false,
                    'PLUGIN_DOCMAN_WRITE' => false,
                    'PLUGIN_DOCMAN_MANAGE' => false
                );
                foreach($permissions as $ugroup_id => $wanted_permission) {
                    $this->_setPermission($item->getGroupId(), $item->getId(), $permission_definition, $old_permissions, $done_permissions, $ugroup_id, $permissions, $history, $force);
                }
                
                $updated = false;
                foreach($history as $perm => $put_in_history) {
                    if ($put_in_history) {
                        permission_add_history($item->getGroupId(), $perm, $item->getId());
                        $updated = true;
                    }
                }
                $this->_controler->feedback->log('info', $GLOBALS['Language']->getText('plugin_docman', 'info_perms_updated'));
                if ($this->_controler->request->get('recursive')) {
                    $item_factory->breathFirst($item->getId(), array(&$this, 'recursivePermissions'), array('id' => $item->getId()));
                    $this->_controler->feedback->log('info', $GLOBALS['Language']->getText('plugin_docman', 'info_perms_recursive_updated'));
                }
            }
        }
    }
    function recursivePermissions($data, $params) {
        if ($this->_controler->userCanManage($data["item_id"])) {
            $pm =& PermissionsManager::instance();
            $pm->clonePermissions($params['id'], $data["item_id"], array('PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE'));
        } else {
            $this->_controler->feedback->log('warning', $GLOBALS['Language']->getText('plugin_docman', 'warning_recursive_perms', $data['title']));
        }
    }
    function _setPermission($group_id, $item_id, $permission_definition, $old_permissions, &$done_permissions, $ugroup_id, $wanted_permissions, &$history, $force = false) {
        if (!isset($done_permissions[$ugroup_id])) {
            if (($parent = ugroup_get_parent($ugroup_id)) !== false) {
                $this->_setPermission($group_id, $item_id, $permission_definition, $old_permissions, $done_permissions, $parent, $wanted_permissions, $history, $force);
                if ($parent = $this->_getBiggerOrEqualParent($permission_definition, $done_permissions, $parent, $wanted_permissions[$ugroup_id])) {
                    $this->_controler->feedback->log('warning', $GLOBALS['Language']->getText('plugin_docman', 'warning_perms', array($old_permissions[$ugroup_id]['ugroup']['name'],$old_permissions[$parent]['ugroup']['name'],$permission_definition[$done_permissions[$parent]]['label'])));
                    if (count($old_permissions[$ugroup_id]['permissions'])) {
                        foreach($old_permissions[$ugroup_id]['permissions'] as $permission => $nop) {
                            permission_clear_ugroup_object($group_id, $permission, $ugroup_id, $item_id);
                            $history[$permission] = true;
                        }
                    }
                    $done_permissions[$ugroup_id] = 100;
                }
            }
            if (!isset($done_permissions[$ugroup_id])) {
                //We clear if needed
                $perms_cleared = false;
                if (count($old_permissions[$ugroup_id]['permissions'])) {
                    foreach($old_permissions[$ugroup_id]['permissions'] as $permission => $nop) {
                        if ($permission != $permission_definition[$wanted_permissions[$ugroup_id]]['type']) {
                            permission_clear_ugroup_object($group_id, $permission, $ugroup_id, $item_id);
                            $history[$permission] = true;
                            $perms_cleared = true;
                            $done_permissions[$ugroup_id] = 100;
                        } else {
                            $done_permissions[$ugroup_id] = $this->_get_definition_index_for_permission($permission);
                        }
                    }
                }
                if ($wanted_permissions[$ugroup_id] != 100 && (!count($old_permissions[$ugroup_id]['permissions']) || $perms_cleared)){
                    $permission = $permission_definition[$wanted_permissions[$ugroup_id]]['type'];
                    permission_add_ugroup($group_id, $permission,  $item_id, $ugroup_id, $force);
                    $history[$permission] = true;
                    $done_permissions[$ugroup_id] = $wanted_permissions[$ugroup_id];
                } else {
                    $done_permissions[$ugroup_id] = 100;
                }
            }
        }
    }
    function _getBiggerOrEqualParent($permission_definition, $done_permissions, $parent, $wanted_permission) {
        if ($wanted_permission == 100) {
            return false;
        } else {
            if ($permission_definition[$done_permissions[$parent]]['order'] >= $permission_definition[$wanted_permission]['order']) {
                return $parent;
            } else {
                if (($parent = ugroup_get_parent($parent)) !== false) {
                    return $this->_getBiggerOrEqualParent($permission_definition, $done_permissions, $parent, $wanted_permission);
                } else {
                    return false;
                }
            }
        }
    }
    
    function change_view() {
        $request =& HTTPRequest::instance();
        if ($request->exist('selected_view') && Docman_View_Browse::isViewAllowed($request->get('selected_view'))) {
            $item_factory =& $this->_getItemFactory();
            $folder = $item_factory->getItemFromDb($request->get('id'));
            if ($folder) {
                user_set_preference(
                    PLUGIN_DOCMAN_VIEW_PREF .'_'. $folder->getGroupId(),
                    $request->get('selected_view')
                );
                $this->_controler->forceView($request->get('selected_view'));
            }
        }
    }
    
    function delete() {
        $user =& $this->_controler->getUser();
        $request =& $this->_controler->request;
        
        $item_factory =& $this->_getItemFactory();
        $item =& $item_factory->getItemFromDb($request->get('id'));
        if ($item) {
            if (!$item_factory->isRoot($item)) {
                $item_bo =& new Docman_ItemBo($request->get('group_id'));
                $item =& $item_bo->getItemSubTree($request->get('id'), array('user' => &$user));
                if ($item) {
                    $deletor =& new DocmanActionsDeleteVisitor($this->_getFileStorage(), $this->_controler);
                    if ($item->accept($deletor, array('user' => &$user, 'parent' => $item_factory->getItemFromDb($item->getParentId())))) {
                        $this->_controler->feedback->log('info', $GLOBALS['Language']->getText('plugin_docman', 'info_item_deleted'));
                    }
                }
            } else {
                $this->_controler->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_item_not_deleted'));
            }
        }
        $this->event_manager->processEvent('send_notifications', array());
    }

    function admin_change_view() {
        $request =& HTTPRequest::instance();
        if ($request->exist('selected_view') && Docman_View_Browse::isViewAllowed($request->get('selected_view'))) {
            require_once('Docman_SettingsBo.class.php');
            $sBo =& Docman_SettingsBo::instance($request->get('group_id'));
            if ($sBo->updateView($request->get('selected_view'))) {
                $this->_controler->feedback->log('info', $GLOBALS['Language']->getText('plugin_docman', 'info_settings_updated'));
            } else {
                $this->_controler->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_settings_updated'));
            }
        }
    }
    
    /**
    * @deprecated
    */
    function install() {
        $request =& HTTPRequest::instance();
        $_gid = (int) $request->get('group_id');

        die('Install forbidden. Please contact administrator');
    }
    
    function admin_set_permissions() {
        list ($return_code, $feedback) = permission_process_selection_form($_POST['group_id'], $_POST['permission_type'], $_POST['object_id'], $_POST['ugroups']);
        if (!$return_code) {
            $this->_controler->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_updated', $feedback));
        } else {
            $this->_controler->feedback->log('info', $GLOBALS['Language']->getText('plugin_docman', 'info_perms_updated'));
        }
    }

    function admin_md_details_update() {
        $request =& HTTPRequest::instance();
        $_label = $request->get('label');
        $_gid = (int) $request->get('group_id');

        $mdFactory = new Docman_MetadataFactory($_gid);
        $md =& $mdFactory->getFromLabel($_label);

        if($md !== null) {
            if($md->getGroupId() == $_gid) {

                // Name
                if($md->canChangeName()) {
                    $_name = $request->get('name');
                    $md->setName($_name);
                }
                
                // Description
                if($md->canChangeDescription()) {
                    $_descr = $request->get('descr');
                    $md->setDescription($_descr);
                }

                // Is empty allowed
                if($md->canChangeIsEmptyAllowed()) {
                    $_isEmptyAllowed = (int) $request->get('empty_allowed');

                    if($_isEmptyAllowed === 1) {
                        $md->setIsEmptyAllowed(PLUGIN_DOCMAN_DB_TRUE);
                    }
                    else {
                        $md->setIsEmptyAllowed(PLUGIN_DOCMAN_DB_FALSE);
                    }
                }

                if($md->canChangeIsMultipleValuesAllowed()) {
                    $_isMultipleValuesAllowed = (int) $request->get('multiplevalues_allowed');

                    if($_isMultipleValuesAllowed === 1) {
                        $md->setIsMultipleValuesAllowed(PLUGIN_DOCMAN_DB_TRUE);
                    }
                    else {
                        $md->setIsMultipleValuesAllowed(PLUGIN_DOCMAN_DB_FALSE);
                    }
                }

                // Usage
                if(!$md->isRequired()) {
                    $_useIt = (int) $request->get('use_it');
                    if($_useIt === 1) {
                        $md->setUseIt(PLUGIN_DOCMAN_METADATA_USED);
                    }
                    else {
                        $md->setUseIt(PLUGIN_DOCMAN_METADATA_UNUSED);
                    }
                }

                // Default value
                if($md->canChangeDefaultValue()) {
                    $_dflt_value = $request->get('dflt_value');
                    Docman_MetadataValueFactory::validateInput($md, $_dflt_value);
                    $md->setDefaultValue($_dflt_value);
                }                

                $updated = $mdFactory->update($md);
                if($updated) {
                    $this->_controler->feedback->log('info', 'Metadata successfully updated');
                }
                else {
                    $this->_controler->feedback->log('warning', 'Metadata not updated');
                }
            }
            else {
                $this->_controler->feedback->log('error', 'Given project id and metadata project id mismatch.');
                $this->_controler->feedback->log('error', 'Metadata not updated');
            }
        }
        else {
            $this->_controler->feedback->log('error', 'Bad metadata label');
            $this->_controler->feedback->log('error', 'Metadata not updated');
        }
    }

    function admin_create_metadata() {
        $request =& HTTPRequest::instance();

        $_gid          = (int) $request->get('group_id');
        $_name         = $request->get('name');
        $_description  = $request->get('descr');
        $_emptyallowed = (int) $request->get('empty_allowed');
        $_dfltvalue    = $request->get('dflt_value');
        $_useit        = $request->get('use_it');
        $_type         = (int) $request->get('type');

        $mdFactory = new Docman_MetadataFactory($_gid);

        //$mdrow['group_id'] = $_gid;
        $mdrow['name'] = $_name;
        $mdrow['description'] = $_description;
        $mdrow['data_type'] = $_type;
        //$mdrow['label'] = 
        $mdrow['required'] = false;
        $mdrow['empty_ok'] = $_emptyallowed;
        $mdrow['special'] = false;
        $mdrow['default_value'] = $_dfltvalue;
        $mdrow['use_it'] = $_useit;
        
        $md =& $mdFactory->_createFromRow($mdrow);

        $mdId = $mdFactory->create($md);
        if($mdId !== false) {
            $this->_controler->feedback->log('info', 'Property successfully created');
        }
        else {
            $this->_controler->feedback->log('error', 'An error occured on propery creation');
        }
    }


    function admin_delete_metadata() {
        $md = $this->_controler->_actionParams['md'];

        $name = $md->getName();

        $mdFactory = new Docman_MetadataFactory($md->getGroupId());

        $deleted = $mdFactory->delete($md);
        if($deleted) {
            $this->_controler->feedback->log('info', $GLOBALS['Language']->getText('plugin_docman',
                                                                                   'md_del_success',
                                                                                   array($name)));
        }
        else {
            $this->_controler->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman',
                                                                                    'md_del_failure',
                                                                                    array($name)));
        }
        $this->_controler->view = 'RedirectAfterCrud';
        $this->_controler->_viewParams['default_url_params'] = array('action' => 'admin_metadata');
    }

    function admin_create_love() {
        $request =& HTTPRequest::instance();
        
        $_name         = $request->get('name');
        $_description  = $request->get('descr');
        $_rank         = $request->get('rank');
        //$_dfltvalue    = (int) $request->get('dflt_value');
        $_mdLabel      = $request->get('md');
        $_gid          = (int) $request->get('group_id');

        $mdFactory = new Docman_MetadataFactory($_gid);
        $md =& $mdFactory->getFromLabel($_mdLabel);
        
        if($md !== null 
           && $md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST 
           && $md->getLabel() != 'status') {

            $loveFactory = new Docman_MetadataListOfValuesElementFactory($md->getId());
            
            $love = new Docman_MetadataListOfValuesElement();
            $love->setName($_name);
            $love->setDescription($_description);
            $love->setRank($_rank);
            $loveFactory->create($love);
        }
    }

    function admin_delete_love() {
        $request =& HTTPRequest::instance();

        $_loveId  = (int) $request->get('loveid');
        $_mdLabel = $request->get('md');
        $_gid = (int) $request->get('group_id');

        $mdFactory = new Docman_MetadataFactory($_gid);
        $md =& $mdFactory->getFromLabel($_mdLabel);

        if($md !== null 
           && $md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST 
           && $md->getLabel() != 'status') {

            $love = new Docman_MetadataListOfValuesElement($md->getId());
            $love->setId($_loveId);

            $updated = true;
            if($_loveId == $md->getDefaultValue()) {
                // We are about to delete the default value
                // so reset to none (value == 100)
                $md->setDefaultValue(100);

                $updated = $mdFactory->update($md);
                if($updated) {
                    $this->_controler->feedback->log('info', 'Property default value reset to None.');
                }
                else {
                    $this->_controler->feedback->log('error', 'Cannot reset property default value to none.');
                }
            }
            
            if($updated) {
                // Delete value
                $loveFactory = new Docman_MetadataListOfValuesElementFactory($md->getId());
                $deleted = $loveFactory->delete($love);

                if($deleted) {
                    $this->_controler->feedback->log('info', 'Element successfully deleted.');

                    // Update values to current default                
                    $mdvFactory = new Docman_MetadataValueFactory($_gid);
                    $reset = $mdvFactory->updateToMetadataDefaultValue($_loveId, $md);

                    if($reset) {
                        $this->_controler->feedback->log('info', 'Documents labeled with the deleted element was successfuly updated.');
                    }
                    else {
                        $this->_controler->feedback->log('warning', 'The element was deleted but an error occured on documents update. Some documents may still be labeled with the deleted value.');
                    }
                }
                else {
                    $this->_controler->feedback->log('error', 'An error occured on element deletion.');
                }
            }
        }
        else {            
            // Sth really strange is happening... user try to delete a value
            // that do not belong to a metadata with a List type !?
            // If this happen, shutdown the server, format the hard drive and
            // leave computer science to keep goat on the Larzac.
        }
    }

    function admin_update_love() {
        $md   = $this->_controler->_actionParams['md'];
        $love = $this->_controler->_actionParams['love'];

        $loveFactory = new Docman_MetadataListOfValuesElementFactory($md->getId());
        $updated = $loveFactory->update($love);

        if($updated) {
            $this->_controler->feedback->log('info', 'Element successfully updated');

            $this->_controler->view = 'RedirectAfterCrud';
            $this->_controler->_viewParams['default_url_params']  = array('action' => 'admin_md_details',
                                                                          'md'     => $md->getLabel());
        }
        else {
            $this->_controler->feedback->log('error', 'Unable to update element.');

            $this->_controler->view = 'RedirectAfterCrud';
            $this->_controler->_viewParams['default_url_params']  = array('action' => 'admin_display_love',
                                                                          'md'     => $md->getLabel(),
                                                                          'loveid' => $love->getId());
        }
    }
    
    function monitor($params) {
        $user = $this->_controler->getUser();
        if (!$user->isAnonymous()) {
            $something_happen  = false;
            $already_monitored = $this->_controler->notificationsManager->exist($user->getId(), $params['item']->getId());
            $already_cascaded  = $this->_controler->notificationsManager->exist($user->getId(), $params['item']->getId(), PLUGIN_DOCMAN_NOTIFICATION_CASCADE);
            if ($params['monitor'] && !$already_monitored) {
                //monitor
                if (!$this->_controler->notificationsManager->add($user->getId(), $params['item']->getId())) {
                    $this->_controler->feedback->log('error', "Unable to add monitoring on '". $params['item']->getTitle() ."'.");
                }
                $something_happen = true;
            } else if(!$params['monitor'] && $already_monitored) {
                //unmonitor
                if (!$this->_controler->notificationsManager->remove($user->getId(), $params['item']->getId())) {
                    $this->_controler->feedback->log('error', "Unable to remove monitoring on '". $params['item']->getTitle() ."'.");
                }
                $something_happen = true;
            }
            if (isset($params['cascade']) && $params['cascade'] && $params['monitor'] && !$already_cascaded) {
                //cascade
                if (!$this->_controler->notificationsManager->add($user->getId(), $params['item']->getId(), PLUGIN_DOCMAN_NOTIFICATION_CASCADE)) {
                    $this->_controler->feedback->log('error', "Unable to add cascade on '". $params['item']->getTitle() ."'.");
                }
                $something_happen = true;
            } else if(!(isset($params['cascade']) && $params['cascade'] && $params['monitor']) && $already_cascaded) {
                //uncascade
                if (!$this->_controler->notificationsManager->remove($user->getId(), $params['item']->getId(), PLUGIN_DOCMAN_NOTIFICATION_CASCADE)) {
                    $this->_controler->feedback->log('error', "Unable to remove cascade on '". $params['item']->getTitle() ."'.");
                }
                $something_happen = true;
            }
            //Feedback
            if ($something_happen) {
                if ($this->_controler->notificationsManager->exist($user->getId(), $params['item']->getId())) {
                    if ($this->_controler->notificationsManager->exist($user->getId(), $params['item']->getId(), PLUGIN_DOCMAN_NOTIFICATION_CASCADE)) {
                        $this->_controler->feedback->log('info', $GLOBALS['Language']->getText('plugin_docman', 'notifications_cascade_on', array($params['item']->getTitle())));
                    } else {
                        $this->_controler->feedback->log('info', $GLOBALS['Language']->getText('plugin_docman', 'notifications_on', array($params['item']->getTitle())));
                    }
                } else {
                    $this->_controler->feedback->log('info', $GLOBALS['Language']->getText('plugin_docman', 'notifications_off', array($params['item']->getTitle())));
                }
            }
        }
    }

    function action_copy($params) {
        //
        // Param
        $user = $this->_controler->getUser();
        $item = $this->_controler->_actionParams['item'];

        //
        // Action
        $itemFactory =& $this->_getItemFactory();

        $itemFactory->delCopyPreference();
        $itemFactory->setCopyPreference($item);

        //
        // Message
        $type = $itemFactory->getItemTypeForItem($item);
        if(PLUGIN_DOCMAN_ITEM_TYPE_FOLDER == $type) {
            $_itemtype = 'folder';
        }
        else {
            $_itemtype = 'doc';
        }
        
        $_logmsg = $GLOBALS['Language']->getText('plugin_docman', 'info_copy_notify_cp_'.$_itemtype).' '.$GLOBALS['Language']->getText('plugin_docman', 'info_copy_notify_cp');
        
        $this->_controler->feedback->log('info', $_logmsg);
    }

    function paste($params) {
        //
        // Param
        $user        = $this->_controler->getUser();
        $item        = $this->_controler->_actionParams['item'];
        $rank        = $this->_controler->_actionParams['rank'];
        $itemToPaste = $this->_controler->_actionParams['itemToPaste'];
        $dataRoot    = $this->_controler->getProperty('docman_root');
        $mdMapping   = false;

        
        if($itemToPaste->getGroupId() != $item->getGroupId()) {
            // Permissions
            $ugroupsMapping = false;

            // Metadata
            $mdMapping = array();
            $srcMdFactory = new Docman_MetadataFactory($itemToPaste->getGroupId());
            $dstMdFactory = new Docman_MetadataFactory($item->getGroupId());

            // Get the list of metadata in source project
            $srcMda = $srcMdFactory->getRealMetadataList(true);
            $srcMdIter = new ArrayIterator($srcMda);
            while($srcMdIter->valid()) {
                $srcMd = $srcMdIter->current();

                // For each source metadata, try to find one in this project
                // with the same name and the same type. By default take the
                // first that match these 2 conditions.
                $dstMda = array();
                $dstMdFactory->_findRealMetadataByName($srcMd->getName(),
                                                       $dstMda);
                $dstMdIter = new ArrayIterator($dstMda);
                $found = false;
                while(!$found && $dstMdIter->valid()) {
                    $dstMd = $dstMdIter->current();
                    if($dstMd->getType() == $srcMd->getType() &&
                       $dstMd->isUsed()) {
                        $found = true;
                    }
                    $dstMdIter->next();
                }
                if($found) {
                    $mdMapping['md'][$srcMd->getId()] = $dstMd->getId();
                
                    // Now for ListOfValues metadata, applies same approach for
                    // values
                    if($srcMd->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                        $srcLoveFactory = new Docman_MetadataListOfValuesElementFactory($srcMd->getId());
                        $dstLoveFactory = new Docman_MetadataListOfValuesElementFactory($dstMd->getId());
                        $srcLoveArray = $srcLoveFactory->getListByFieldId($srcMd->getId(), $srcMd->getLabel(), false);
                        $srcLoveIter = new ArrayIterator($srcLoveArray);
                        while($srcLoveIter->valid()) {
                            $srcLove = $srcLoveIter->current();
                            
                            $dstLoveIter = $dstLoveFactory->getByName($srcLove->getName(),
                                                                      $srcMd->getLabel());
                            // Take the first one
                            if($dstLoveIter->valid()) {
                                $dstLove = $dstLoveIter->current();
                                $mdMapping['love'][$srcLove->getId()] = $dstLove->getId();
                            }
                            $srcLoveIter->next();
                        }
                    }
                }

                $srcMdIter->next();
            }
        } else {
            // Permissions
            $ugroupsMapping = true;

            // Metadata
            $mdMapping = array();
            $mdFactory = new Docman_MetadataFactory($item->getGroupId());
            $mda = $mdFactory->getRealMetadataList(true);
            $mdIter = new ArrayIterator($mda);
            while($mdIter->valid()) {
                $md = $mdIter->current();
                $mdMapping['md'][$md->getId()] = $md->getId();
                if($md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                    $loveFactory = new Docman_MetadataListOfValuesElementFactory($md->getId());
                    $loveArray = $loveFactory->getListByFieldId($md->getId(), $md->getLabel(), false);
                    $loveIter = new ArrayIterator($loveArray);
                    while($loveIter->valid()) {
                        $love = $loveIter->current();
                        $mdMapping['love'][$love->getId()] = $love->getId();
                        $loveIter->next();
                    }
                }
                $mdIter->next();
            }
        }

        //
        // Action
        $itemFactory =& $this->_getItemFactory();
        $itemFactory->cloneItems($itemToPaste->getGroupId(),
                                 $item->getGroupId(),
                                 $user, 
                                 $mdMapping,
                                 $ugroupsMapping,
                                 $dataRoot, 
                                 $itemToPaste->getId(),
                                 $item->getId(), 
                                 $rank);

        $itemFactory->delCopyPreference();

        //
        // Message
        
    }

    function approval_update() {
        // Params
        $item       = $this->_controler->_actionParams['item'];
        $user       = $this->_controler->getUser();
        $sStatus    = $this->_controler->_actionParams['status'];
        $notification = $this->_controler->_actionParams['notification'];
        $description =  $this->_controler->_actionParams['description'];

        $atf = new Docman_ApprovalTableFactory($item->getId());
        if($atf->createTableIfNotExist($user->getId())) {
            $updated = $atf->updateTable($sStatus, $notification, $description);
            if($updated) {
                $this->_controler->feedback->log('info', Docman::txt('approval_tableupd_success'));
            } else {
                $this->_controler->feedback->log('warning', Docman::txt('approval_tableupd_failure'));
            }
        } else {
            $this->_controler->feedback->log('error', Docman::txt('approval_tableins_failure'));
        }
    }

    function approval_delete() {
        // Params
        $item = $this->_controler->_actionParams['item'];
        $atf = new Docman_ApprovalTableFactory($item->getId());
        if($atf->tableExist()) {
            $deleted = $atf->deleteTable();
            if($deleted) {
                $this->_controler->feedback->log('info', Docman::txt('approval_tabledel_success'));
            } else {
                $this->_controler->feedback->log('error', Docman::txt('approval_tabledel_failure'));
            }
        }
    }

    function approval_add_user() {
        // Params
        $item       = $this->_controler->_actionParams['item'];
        $user       = $this->_controler->getUser();
        $usUserList = $this->_controler->_actionParams['user_list'];
        $sUgroup    = $this->_controler->_actionParams['ugroup'];

        // Extract info
        $usUserArray = explode(',', $usUserList);

        // Action
        $_canAddUsers = false;
        $atf = new Docman_ApprovalTableFactory($item->getId());
        if($atf->createTableIfNotExist($user->getId())) {
            $userAdded = $atf->addUsers($usUserArray);

            $ugrpAdded = false;
            if($sUgroup > 0 && $sUgroup != 100) {
                $ugrpAdded = $atf->addUgroup($sUgroup);
            }

            if($userAdded || $ugrpAdded) {
                $this->_controler->feedback->log('info', Docman::txt('approval_useradd_success'));
            }
            else {
                $this->_controler->feedback->log('warning', Docman::txt('approval_useradd_failure'));
            }
        } else {
            $this->_controler->feedback->log('error', Docman::txt('approval_tableins_failure'));
        }
    }

   function approval_upd_user() {
        // Params
        $item    = $this->_controler->_actionParams['item'];
        $sUserId = $this->_controler->_actionParams['user_id'];
        $usRank  = $this->_controler->_actionParams['rank'];

        // Action
        $atf = new Docman_ApprovalTableFactory($item->getId());
        $atf->updateUser($sUserId, $usRank);
    }

    function approval_del_user() {
        // Params
        $item       = $this->_controler->_actionParams['item'];
        $sUserId = $this->_controler->_actionParams['user_id'];

        // Action
        $atf = new Docman_ApprovalTableFactory($item->getId());
        $deleted = $atf->delUser($sUserId);
        if($deleted) {
            $this->_controler->feedback->log('info', Docman::txt('approval_userdel_success'));
        } else {
            $this->_controler->feedback->log('error', Docman::txt('approval_userdel_failure'));
        }
    }

    function approval_user_commit() {
        // Params
        $item      = $this->_controler->_actionParams['item'];
        $svState   = $this->_controler->_actionParams['svState'];
        $sVersion  = $this->_controler->_actionParams['sVersion'];
        $usComment = $this->_controler->_actionParams['usComment'];
        $user      = $this->_controler->getUser();

        $review = new Docman_ApprovalReviewer();
        $review->setId($user->getId());
        $review->setState($svState);
        $review->setComment($usComment);
        if($svState != PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET) {
            $review->setVersion($sVersion);
            $review->setReviewDate(time());
        }
        else {
            $review->setVersion(null);
            $review->setReviewDate(null);
        }

        $atf = new Docman_ApprovalTableFactory($item->getId());
        $updated = $atf->updateReview($review);
        if($updated) {
            $this->_controler->feedback->log('info', Docman::txt('approval_review_success'));
        } else {
            $this->_controler->feedback->log('error', Docman::txt('approval_review_failure'));
        }

        $this->monitor($this->_controler->_actionParams);
    }

    function approval_notif_resend() {
        // Params
        $item      = $this->_controler->_actionParams['item'];

        $atf = new Docman_ApprovalTableFactory($item->getId());
        $res = $atf->notifyReviewers();
        if($res) {
            $this->_controler->feedback->log('info', Docman::txt('approval_notification_success'));
        }
        else {
            $this->_controler->feedback->log('warning', Docman::txt('approval_notification_failure'));
        }
    }

}

?>

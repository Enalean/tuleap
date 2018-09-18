<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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

use Tuleap\PHPWiki\WikiPage;

require_once('Docman_FileStorage.class.php');
require_once('Docman_VersionFactory.class.php');
class Docman_ActionsDeleteVisitor /* implements Visitor */ {
    protected $user;
    protected $response;

    public function __construct() {
        //More coherent to have only one delete date for a whole hierarchy.
        $this->deleteDate = time();
        $this->response   = $GLOBALS['Response'];
    }
    
    /**
     * 
     * Enter description here ...
     *
     * @param Docman_Folder $item
     * @param $params
     */
    public function visitFolder(&$item, $params = array()) {
        //delete all sub items before
        $items = $item->getAllItems();
        if (isset($params['parent'])) {
            $parent = $params['parent'];
        } else {
            $parent = $this->_getItemFactory()->getItemFromDb($item->getParentId());
        }
        $one_item_has_not_been_deleted = false;
        if ($items->size()) {
            $it = $items->iterator();
            while($it->valid()) {
                $o = $it->current();
                $params['parent'] = $item;
                if (!$o->accept($this, $params)) {
                    $one_item_has_not_been_deleted = true;
                }
                $it->next();
            }
        }
        
        if ($one_item_has_not_been_deleted) {
            $this->response->addFeedback('error', $GLOBALS['Language']->getText('plugin_docman', 'error_delete_notempty', $item->getTitle()));
            return false;
        } else {
            //Mark the folder as deleted;
            $params['parent'] = $parent;
            return $this->_deleteItem($item, $params);
        }
    }
    
    public function visitDocument($item, $params = array()) {
        //Mark the document as deleted
        return $this->_deleteItem($item, $params);
    }

    /**
    * Handles wiki page deletion with two different behaviors:
    * 1- User decides to keep wiki page in wiki service. In this case, we restrict access to that wiki page to wiki admins only.
    * 2- User decides to cascade deletion of the wiki page to wiki service too. In that case, we completely remove the wiki page from wiki service.
    *
    * @param Docman_Item $item
    * @param array $params params.
    *
    * @return boolean $deleted. True if there is no error.  False otherwise.
    */
    public function visitWiki(&$item, $params = array()) {
        // delete the document.
        $deleted = $this->visitDocument($item, $params);

        if($deleted) {
            if(!$params['cascadeWikiPageDeletion']) {
                // grant a wiki permission only to wiki admins on the corresponding wiki page.
                $this->restrictAccess($item, $params);

                $wiki_page = new WikiPage($item->getGroupId(), $item->getPageName());

                if ($wiki_page->getId()) {
                    $event_manager = EventManager::instance();
                    $event_manager->processEvent(
                        "wiki_page_updated",
                        array(
                            'group_id'   => $item->getGroupId(),
                            'wiki_page'  => $item->getPageName(),
                            'referenced' => false,
                            'user'       => $params['user']
                        )
                    );
                }

            } else { // User have choosen to delete wiki page from wiki service too
                $dIF = $this->_getItemFactory();
                if ($dIF->deleteWikiPage($item->getPageName(), $item->getGroupId())) {
                    $this->response->addFeedback('info', $GLOBALS['Language']->getText('plugin_docman', 'docman_wiki_delete_wiki_page_success'));
                } else {
                    $this->response->addFeedback('error', $GLOBALS['Language']->getText('plugin_docman', 'docman_wiki_delete_wiki_page_failed'));
                }
            }
        }
        return $deleted;
    }
    
    public function visitLink(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }

    public function visitFile($item, $params = array()) {
        if ($this->getPermissionManager($item->getGroupId())->userCanWrite($params['user'], $item->getId())) {
            if (isset($params['version']) && $params['version'] !== false) {
                return $this->_deleteVersion($item, $params['version'], $params['user']);
            } else {
                return $this->_deleteFile($item, $params);
            }
        } else {
            $this->response->addFeedback('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_delete_item', $item->getTitle()));
            return false;
        }
    }

    public function visitEmbeddedFile(&$item, $params = array()) {
        return $this->visitFile($item, $params);
    }

    public function visitEmpty(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }

    public function restrictAccess($item, $params = array()) {
        // Check whether there is other references to this wiki page.
        $dao = $this->_getItemDao();
        $referenced = $dao->isWikiPageReferenced($item->getPageName(), $item->getGroupId());
        if(!$referenced) {
            $dIF = $this->_getItemFactory();
            $id_in_wiki = $dIF->getIdInWikiOfWikiPageItem($item->getPageName(), $item->getGroupId());
            // Restrict access to wiki admins if the page already exists in wiki.
            if($id_in_wiki !== null) {
                permission_clear_all($item->getGroupId(), 'WIKIPAGE_READ', $id_in_wiki, false);
                permission_add_ugroup($item->getGroupId(), 'WIKIPAGE_READ', $id_in_wiki, $GLOBALS['UGROUP_WIKI_ADMIN']);
            }
        }
    }

    function _deleteItem($item, $params) {
       if ($this->getPermissionManager($item->getGroupId())->userCanWrite($params['user'], $item->getId())) {
            $dIF = $this->_getItemFactory();
            $dIF->delete($item);
            return true;
        } else {
            $this->response->addFeedback('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_delete_item', $item->getTitle()));
            return false;
        }
    }

    /**
     * Delete a file (all versions of the file)
     * 
     * @param Docman_File $item
     * @param Array       $params
     * 
     * @return Boolean
     */
    function _deleteFile(Docman_File $item, $params) {
        // Delete all versions before
        $version_factory = $this->_getVersionFactory();
        if ($versions = $version_factory->getAllVersionForItem($item)) {
            if (count($versions)) {
                $um = UserManager::instance();
                $user = $um->getCurrentUser();
                foreach ($versions as $version) {
                    $this->_deleteVersion($item, $version, $user);
                }
            }
        }
        return $this->visitDocument($item, $params);
    }

    /**
     * Delete a version of a file
     * 
     * @param Docman_File    $item
     * @param Docman_Version $version
     * @param PFUser           $user
     * 
     * @return Boolean
     */
    function _deleteVersion(Docman_File $item, Docman_Version $version, PFUser $user) {
        // Proceed to deletion
        $version_factory = $this->_getVersionFactory();
        return $version_factory->deleteSpecificVersion($item, $version->getNumber());
    }

    function _getEventManager() {
        return EventManager::instance();
    }
    
    var $version_factory;
    function _getVersionFactory() {
        if (!$this->version_factory) {
            $this->version_factory = new Docman_VersionFactory();
        }
        return $this->version_factory;
    }
    
    var $item_factory;
    function _getItemFactory() {
        if (!$this->item_factory) {
            $this->item_factory = new Docman_ItemFactory();
        }
        return $this->item_factory;
    }
    
    var $lock_factory;
    function _getLockFactory() {
        if (!$this->lock_factory) {
            $this->lock_factory = new Docman_LockFactory();
        }
        return $this->lock_factory;
    }   
     
    function _getFileStorage() {
        return new Docman_FileStorage();
    }
    
    function _getItemDao() {
        return new Docman_ItemDao(CodendiDataAccess::instance());
    }
    
    function getPermissionManager($groupId) {
        return Docman_PermissionsManager::instance($groupId);
    }
}
?>

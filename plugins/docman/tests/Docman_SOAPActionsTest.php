<?php
/**
 * Originally written by Clément Plantier, 2008
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
 */

require_once('common/language/BaseLanguage.class.php');
$GLOBALS['Language'] = new BaseLanguage();

require_once(dirname(__FILE__).'/../include/Docman_SOAPActions.class.php');
require_once(dirname(__FILE__).'/../include/Docman_SOAPController.class.php');
require_once('common/include/SOAPRequest.class.php');

Mock::generate('BaseLanguage');
Mock::generate('Docman_SOAPController');
Mock::generate('Feedback');
Mock::generate('Docman_ItemFactory');
Mock::generate('Docman_File');
Mock::generate('Docman_Version');
Mock::generate('Docman_VersionFactory');
Mock::generate('Docman_FileStorage');
Mock::generate('UserManager');
Mock::generate('User');
Mock::generate('EventManager');
Mock::generate('PermissionsManager');
Mock::generate('SOAPRequest');
Mock::generatePartial('Docman_SOAPActions', 'Docman_SOAPActions_Test', array('_getItemFactory', '_checkOwnerChange'));

/**
 * Unit tests for Docman_SOAPActions
 */
class Docman_SOAPActionsTest extends UnitTestCase {
    private $MD5Map;
    private $itemFactory;
    private $action;
    
    function Docman_SOAPActionsTest($name = 'Docman_Actions test') {
        $this->UnitTestCase($name);
    }
    
    public function setUp() {
        // Mock instanciation
        $controller = new MockDocman_SOAPController();
        $controller->feedback = new MockFeedback();
        
        $version = new MockDocman_Version();
        $version->setReturnValue('getNumber', 0);
        
        $itemFactory = new MockDocman_ItemFactory();
        $fileStorage = new MockDocman_FileStorage();
        
        // Item MD5 Map: id => md5sum
        $this->MD5Map = array(128000 => '99999999999999999999999999999999');
        foreach ($this->MD5Map as $itemId => $md5) {
            $file = new MockDocman_File();
            $file->setReturnValue('getID', $itemId);
            $file->setReturnValue('getCurrentVersion', $version);
            $itemFactory->setReturnValue('getItemFromDb', $file, array($itemId));
            $itemFactory->setReturnValue('getItemTypeForItem', PLUGIN_DOCMAN_ITEM_TYPE_FILE, array($file));
            $fileStorage->setReturnValue('getFileMD5sum', $md5, array('*', $itemId, '*'));
        }
        
        $user = new MockUser();
        $user->setReturnValue('getId', 9);
        
        $userManager = new MockUserManager();
        $userManager->setReturnValue('getUserById', $user);
        $userManager->setReturnValue('getUserByUserName', $user);
        $userManager->setReturnValue('getCurrentUser', $user);
        
        $controller->setReturnValue('getUser', $user);
        
        $versionFactory = new MockDocman_VersionFactory();
        $versionFactory->setReturnValue('getAllVersionForItem', array($version));
        
        // Partial mock of Docman_SOAPActions
        $action = new Docman_SOAPActions_Test();
        $action->setReturnValue('_getItemFactory', $itemFactory);
        $action->setReturnValue('_checkOwnerChange', 101, array('*', '*'));
        $action->Docman_SOAPActions($controller);

        // Mock injection
        $action->version_factory = $versionFactory;
        $action->userManager = $userManager;
        $action->permissions_manager = new MockPermissionsManager();
        $action->event_manager = new MockEventManager();
        $action->filestorage = $fileStorage;
        
        $this->action = $action;
        $this->itemFactory = $itemFactory;
    }
    
    /**
     * Nominal case: getFileMD5sum is called with a correct file ID
     */
    public function testGetFileMD5sumNominal() {
        $action = $this->action;
        
        $params = array(
              'item_id' => 128000,
              'group_id'=> 2,
          );
        $request = new MockSOAPRequest($params);
        $request->setReturnValue('exist', true);
        $request->setReturnValue('get', $params['item_id'], array('item_id'));
        
        $action->getControler()->request = $request;
            
        $action->getFileMD5sum();
        $this->assertEqual($action->getControler()->_viewParams['action_result'], $this->MD5Map[$params['item_id']]);
    }
    
    public function testGetFileMD5sumAllVersions() {
        $action = $this->action;
        
        $params = array(
              'item_id' => 128000,
              'group_id'=> 2,
              'all_versions'=>true,
          );
        $request = new SOAPRequest($params);
        $action->getControler()->request = $request;
            
        $action->getFileMD5sum();
        $this->assertTrue(is_array($action->getControler()->_viewParams['action_result']));
    }
    
    /**
     * Error case: getFileMD5sum with no item ID
     */
    public function testGetFileMD5sumNoItemError() {
        $action = $this->action;
        
        $params = array();
        $request = new MockSOAPRequest($params);
        $request->setReturnValue('exist', false);
        
        $action->getControler()->request = $request;
        
        $action->getControler()->feedback->expectOnce('log', array('error', '*'));
        
        $action->getFileMD5sum();
    }
    
    /**
     * Error case: getFileMD5sum is called with an incorrect ID
     */
    public function testGetFileMD5sumItemNotFoundError() {
        $action = $this->action;
        
        $params = array('item_id' => 0);
        $request = new MockSOAPRequest($params);
        $request->setReturnValue('exist', true);
        $request->setReturnValue('get', 0, array('item_id'));
        
        $action->getControler()->request = $request;
        
        $action->getControler()->feedback->expectOnce('log', array('error', '*'));
        
        $action->getFileMD5sum();
    }
    
    /**
     * update test with item owner changed
     */
    public function testUpdate() {
        $action = $this->action;
        
        $params = array(
                      'item' => array(
                                    'owner' => 'testuser',
                                    'id'    => 128000,
                                ),
                      'group_id'=> 2,
                  );
        $request = new MockSOAPRequest($params);
        $request->setReturnValue('exist', true, array('item'));
        $request->setReturnValue('get', $params['item'], array('item'));
        
        $action->getControler()->request = $request;
        
        $action->expectOnce('_checkOwnerChange', array($params['item']['owner'], '*'));
        $this->itemFactory->expectOnce('update');
        $action->event_manager->expectAt(0, 'processEvent', array('plugin_docman_event_metadata_update', '*'));
        $action->event_manager->expectAt(1, 'processEvent', array('send_notifications', '*'));
        
        $action->update();
    }
    
    /**
     * New version
     */
    public function test_new_version_update() {
        $action = $this->action;
        
        $params = array(
                      'id'    => 128000,
                      'group_id'=> 2,
                  );
        $request = new MockSOAPRequest($params);
        $request->setReturnValue('exist', true, array('id'));
        $request->setReturnValue('exist', true, array('upload_content'));
        $request->setReturnValue('get', $params['id'], array('id'));
        
        $action->getControler()->request = $request;
        
        $action->filestorage->expectOnce('store');
        $this->itemFactory->expectOnce('update');
        $action->event_manager->expectOnce('processEvent', array('send_notifications', '*'));
        
        $action->new_version();
    }
    
    /**
     * New version with version date
     */
    public function test_new_version_no_update() {
        $action = $this->action;
        
        $params = array(
                      'id'    => 128000,
                      'group_id'=> 2,
                      'date'    => 1,
                  );
        $request = new MockSOAPRequest($params);
        $request->setReturnValue('exist', true, array('id'));
        $request->setReturnValue('exist', true, array('upload_content'));
        $request->setReturnValue('get', $params['id'], array('id'));
        $request->setReturnValue('existAndNonEmpty', true, array('date'));
        
        $action->getControler()->request = $request;
        
        $action->filestorage->expectOnce('store');
        $this->itemFactory->expectNever('update');
        $action->event_manager->expectOnce('processEvent', array('send_notifications', '*'));
        
        $action->new_version();
    }
    
    /**
     * Item creation
     */
    public function testCreateItem() {
        $action = $this->action;
        
        $params = array(
                      'item' => array(
                                    'parent_id'    => 128001,
                                    'title'        => 'test',
                                    'item_type'    => PLUGIN_DOCMAN_ITEM_TYPE_EMPTY,
                                    'create_date'        => 1,
                                    'update_date'        => 2,
                                ),
                      'group_id'=> 2,
                  );
        $request = new MockSOAPRequest($params);
        $request->setReturnValue('exist', true, array('item'));
        $request->setReturnValue('get', $params['item'], array('item'));
        $request->setReturnValue('get', $params['group_id'], array('group_id'));
        
        $this->itemFactory->setReturnValue('create', 128002);
        
        $action->getControler()->request = $request;
        
        $this->itemFactory->expectOnce('create');
        $action->permissions_manager->expectOnce('clonePermissions');
        $action->event_manager->expectAt(0, 'processEvent', array('plugin_docman_event_add', '*')); 
        $action->event_manager->expectAt(1, 'processEvent', array('plugin_docman_event_metadata_update', '*'));
        $action->event_manager->expectAt(2, 'processEvent', array('plugin_docman_event_metadata_update', '*'));
        $action->event_manager->expectAt(3, 'processEvent', array('send_notifications', '*'));
        $action->getControler()->feedback->expectOnce('log', array('info', '*'));
        
        $action->createItem();
    }
    
    /**
     * Item creation, without dates
     */
    public function testCreateItemNoDates() {
                $action = $this->action;
        
        $params = array(
                      'item' => array(
                                    'parent_id'    => 128001,
                                    'title'        => 'test',
                                    'item_type'    => PLUGIN_DOCMAN_ITEM_TYPE_EMPTY,
                                ),
                      'group_id'=> 2,
                  );
        $request = new MockSOAPRequest($params);
        $request->setReturnValue('exist', true, array('item'));
        $request->setReturnValue('get', $params['item'], array('item'));
        $request->setReturnValue('get', $params['group_id'], array('group_id'));
        
        $this->itemFactory->setReturnValue('create', 128002);
        
        $action->getControler()->request = $request;
        
        $action->event_manager->expectAt(0, 'processEvent', array('plugin_docman_event_add', '*')); 
        $action->event_manager->expectAt(1, 'processEvent', array('send_notifications', '*'));
        
        $action->createItem();
    }
    
    /**
     * AppendFileChunk test 
     */
    public function testAppendFileChunk() {
        $action = $this->action;
        
        $params = array('group_id'=> 10, 'item_id'=> 128000, 'chunk_offset' => 10, 'chunk_size' => 64, 'upload_content' => 'abcdef');
        $request = new MockSOAPRequest($params);
        $request->setReturnValue('exist', true, array('group_id'));
        $request->setReturnValue('exist', true, array('item_id'));
        $request->setReturnValue('exist', true, array('chunk_offset'));
        $request->setReturnValue('exist', true, array('chunk_size'));
        $request->setReturnValue('get', $params['group_id'], array('group_id'));
        $request->setReturnValue('get', $params['item_id'], array('item_id'));
        $request->setReturnValue('get', $params['chunk_offset'], array('chunk_offset'));
        $request->setReturnValue('get', $params['chunk_size'], array('chunk_size'));
        $request->setReturnValue('get', $params['upload_content'], array('upload_content'));
        
        $action->getControler()->request = $request;
        
        $action->filestorage->expect('store', array($params['upload_content'], $params['group_id'], $params['item_id'], 0, $params['chunk_offset'], $params['chunk_size']));
        
        $action->appendFileChunk();
    }
    
    /**
     * Test: getTreeInfo with no parameters supplied
     */
    public function testGetTreeInfoError() {
        $action = $this->action;
        
        $request = new MockSOAPRequest();
        $action->getControler()->request = $request;
        
        $action->getControler()->feedback->expectOnce('log', array('error', '*'));
        
        $action->getTreeInfo();
    }
}

?>
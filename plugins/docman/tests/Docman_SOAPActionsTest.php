<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Originally written by Clément Plantier, 2008
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Mockery as M;

require_once 'bootstrap.php';

Mock::generate('BaseLanguage');
Mock::generate('Docman_SOAPController');
Mock::generate('Feedback');
Mock::generate('Docman_FolderFactory');
Mock::generate('Docman_FileStorage');
Mock::generate('UserManager');
Mock::generate('PFUser');
Mock::generate('PermissionsManager');
Mock::generate('Docman_PermissionsManager');
Mock::generate('SOAPRequest');
Mock::generate('Docman_LockFactory');
Mock::generatePartial(
    'Docman_SOAPActions',
    'Docman_SOAPActions_Test',
    array(
        '_getItemFactory',
        '_checkOwnerChange',
        '_getFolderFactory',
        '_getUserManagerInstance',
        '_getVersionFactory',
        '_getPermissionsManagerInstance',
        '_getDocmanPermissionsManagerInstance',
        '_getEventManager',
        '_getFileStorage',
    )
);

/**
 * Unit tests for Docman_SOAPActions
 */
class Docman_SOAPActionsTest extends TuleapTestCase
{
    private $MD5Map;
    private $itemFactory;
    private $action;
    private $permissionManager;
    private $docmanPermissionsManager;
    private $fileStorage;
    private $lockFactory;

    public function setUp()
    {
        parent::setUp();

        // Mock instanciation
        $controller = new MockDocman_SOAPController();
        $controller->feedback = new MockFeedback();

        $version = new Docman_Version(['number' => 0]);

        $this->itemFactory = M::spy(Docman_ItemFactory::class);
        $folderFactory = new MockDocman_FolderFactory();
        $this->fileStorage = new MockDocman_FileStorage();

        // Item MD5 Map: id => md5sum
        $this->MD5Map = array(128000 => '99999999999999999999999999999999');
        foreach ($this->MD5Map as $itemId => $md5) {
            $file = M::spy(Docman_File::class, ['getID' => $itemId, 'getCurrentVersion' => $version]);
            $this->itemFactory->shouldReceive('getItemFromDb')->with($itemId)->andReturn($file);
            $this->itemFactory->shouldReceive('getItemTypeForItem')->with($file)->andReturn(PLUGIN_DOCMAN_ITEM_TYPE_FILE);
            $this->fileStorage->setReturnValue('getFileMD5sum', $md5);
        }

        $user = mock('PFUser');
        $user->setReturnValue('getId', 9);

        $userManager = new MockUserManager();
        $userManager->setReturnValue('getUserById', $user);
        $userManager->setReturnValue('getUserByUserName', $user);
        $userManager->setReturnValue('getCurrentUser', $user);

        $controller->setReturnValue('getUser', $user);

        $versionFactory = M::spy(Docman_VersionFactory::class, ['getAllVersionForItem' => [$version]]);

        $this->lockFactory = new MockDocman_LockFactory();

        $this->permissionManager = new MockPermissionsManager();

        $this->docmanPermissionsManager = new MockDocman_PermissionsManager();
        $this->docmanPermissionsManager->setReturnValue('getLockFactory', $this->lockFactory);

        // Partial mock of Docman_SOAPActions
        $this->action = new Docman_SOAPActions_Test();
        $this->action->setReturnValue('_getItemFactory', $this->itemFactory);
        $this->action->setReturnValue('_getFolderFactory', $folderFactory);
        $this->action->setReturnValue('_checkOwnerChange', 101, array('*', '*'));
        $this->action->setReturnValue('_getUserManagerInstance', $userManager);
        $this->action->setReturnValue('_getVersionFactory', $versionFactory);
        $this->action->setReturnValue('_getPermissionsManagerInstance', $this->permissionManager);
        $this->action->setReturnValue('_getDocmanPermissionsManagerInstance', $this->docmanPermissionsManager);
        $this->action->setReturnValue('_getEventManager', \Mockery::spy(EventManager::class));
        $this->action->setReturnValue('_getFileStorage', $this->fileStorage);
        $this->action->__construct($controller);
    }

    public function tearDown()
    {
        unset(
            $GLOBALS['Language'],
            $this->itemFactory,
            $this->fileStorage,
            $this->MD5Map,
            $this->permissionManager,
            $this->docmanPermissionsManager,
            $this->action,
            $this->lockFactory
        );

        parent::tearDown();
    }

    /**
     * Nominal case: getFileMD5sum is called with a correct file ID
     */
    public function testGetFileMD5sumNominal()
    {
        $action = $this->action;

        $params = array(
              'item_id' => 128000,
              'group_id'=> 2,
          );
        $request = new MockSOAPRequest($params);
        $request->setReturnValue('exist', true, array('item_id'));
        $request->setReturnValue('get', $params['item_id'], array('item_id'));
        $request->setReturnValue('exist', false, array('version'));

        $action->getControler()->request = $request;

        $action->getFileMD5sum();
        $this->assertEqual($action->getControler()->_viewParams['action_result'], $this->MD5Map[$params['item_id']]);
    }

    /**
     * Nominal case: getFileMD5sum is called with a correct file ID for a given version
     */
    public function testGetFileMD5sumGivenVersionNominal()
    {
        $action = $this->action;

        $params = array(
              'item_id' => 128000,
              'group_id'=> 2,
              'version' => 2,
        );
        $request = new MockSOAPRequest($params);
        $request->setReturnValue('exist', true, array('item_id'));
        $request->setReturnValue('get', $params['item_id'], array('item_id'));
        $request->setReturnValue('exist', true, array('version'));
        $request->setReturnValue('get', $params['version'], array('version'));

        $action->getControler()->request = $request;

        $action->getFileMD5sum();
        $this->assertEqual($action->getControler()->_viewParams['action_result'], $this->MD5Map[$params['item_id']]);
    }


    public function testGetFileMD5sumAllVersions()
    {
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
    public function testGetFileMD5sumNoItemError()
    {
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
    public function testGetFileMD5sumItemNotFoundError()
    {
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
    public function testUpdate()
    {
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
        $this->itemFactory->shouldReceive('update')->once();
        $action->event_manager->expectAt(0, 'processEvent', array('plugin_docman_event_metadata_update', '*'));
        $action->event_manager->expectAt(1, 'processEvent', array('send_notifications', '*'));

        $action->update();
    }

    /**
     * New version
     */
    public function test_new_version_update()
    {
        $action = $this->action;

        $this->lockFactory->setReturnValue('itemIsLocked', false);

        $params = array(
                      'id'    => 128000,
                      'group_id'=> 2,
                  );
        $request = new MockSOAPRequest($params);
        $request->setReturnValue('exist', true, array('id'));
        $request->setReturnValue('exist', true, array('upload_content'));
        $request->setReturnValue('get', $params['id'], array('id'));

        $action->getControler()->request = $request;

        $this->fileStorage->expectOnce('store');
        $this->itemFactory->shouldReceive('update');
        $action->event_manager->expectAt('1', 'processEvent', array('send_notifications', '*'));
        $action->event_manager->expectCallCount('processEvent', '1');
        $action->new_version();
    }

    /**
     * New version with version date
     */
    public function test_new_version_no_update()
    {
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

        $this->fileStorage->expectOnce('store');
        $this->itemFactory->shouldNotReceive('update');
        $action->event_manager->expectAt('1', 'processEvent', array('send_notifications', '*'));
        $action->event_manager->expectCallCount('processEvent', '1');

        $action->new_version();
    }

    /**
     * Item creation
     */
    public function testCreateItem()
    {
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

        $action->getControler()->request = $request;

        $this->itemFactory->shouldReceive('create')->once()->andReturn(128002);

        $item = partial_mock('Docman_Item', array('getEventManager'));
        $item->setReturnValue('getEventManager', $action->event_manager);
        $this->itemFactory->shouldReceive('getItemFromDb')->andReturn($item);

        $this->permissionManager->expectOnce('clonePermissions');
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
    public function testCreateItemNoDates()
    {
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

        $this->itemFactory->shouldReceive('create')->andReturn(128002);

        $item = partial_mock('Docman_Item', array('getEventManager'));
        $item->setReturnValue('getEventManager', $action->event_manager);
        $this->itemFactory->shouldReceive('getItemFromDb')->andReturn($item);

        $action->getControler()->request = $request;

        $action->event_manager->expectAt(0, 'processEvent', array('plugin_docman_event_add', '*'));
        $action->event_manager->expectAt(1, 'processEvent', array('send_notifications', '*'));

        $action->createItem();
    }

    /**
     * AppendFileChunk test
     */
    public function testAppendFileChunk()
    {
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

        $this->fileStorage->expect('store', array($params['upload_content'], $params['group_id'], $params['item_id'], 0, $params['chunk_offset'], $params['chunk_size']));

        $action->appendFileChunk();
    }

    /**
     * Test: getTreeInfo with no parameters supplied
     */
    public function testGetTreeInfoError()
    {
        $action = $this->action;

        $request = new MockSOAPRequest();
        $action->getControler()->request = $request;

        $action->getControler()->feedback->expectOnce('log', array('error', '*'));

        $action->getTreeInfo();
    }

    public function testGetFileChunk()
    {
        $action = $this->action;

        $params = array('group_id'=> 10, 'item_id'=> 128000, 'chunk_offset' => 10, 'chunk_size' => 64, 'version_number' => 2);
        $request = new MockSOAPRequest($params);
        $request->setReturnValue('exist', true, array('group_id'));
        $request->setReturnValue('exist', true, array('item_id'));
        $request->setReturnValue('exist', true, array('version_number'));
        $request->setReturnValue('exist', true, array('chunk_offset'));
        $request->setReturnValue('exist', true, array('chunk_size'));
        $request->setReturnValue('get', $params['group_id'], array('group_id'));
        $request->setReturnValue('get', $params['item_id'], array('item_id'));
        $request->setReturnValue('get', $params['chunk_offset'], array('chunk_offset'));
        $request->setReturnValue('get', $params['chunk_size'], array('chunk_size'));
        $request->setReturnValue('get', $params['version_number'], array('version_number'));

        $action->getControler()->request = $request;

        $action->getFileChunk();
    }
}

<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Docman_SOAPActions
 */
//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_SOAPActionsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $MD5Map;
    private $itemFactory;
    private $action;
    private $permissionManager;
    private $docmanPermissionsManager;
    private $fileStorage;
    private $lockFactory;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|PFUser
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock instanciation
        $controller           = \Mockery::spy(\Docman_SOAPController::class);
        $controller->feedback = \Mockery::spy(\Feedback::class);

        $version = new Docman_Version(['number' => 0]);

        $this->itemFactory = M::spy(Docman_ItemFactory::class);
        $folderFactory     = \Mockery::spy(\Docman_FolderFactory::class);
        $this->fileStorage = \Mockery::spy(\Docman_FileStorage::class);

        // Item MD5 Map: id => md5sum
        $this->MD5Map = array(128000 => '99999999999999999999999999999999');
        foreach ($this->MD5Map as $itemId => $md5) {
            $file = M::spy(Docman_File::class, ['getID' => $itemId, 'getCurrentVersion' => $version]);
            $this->itemFactory->shouldReceive('getItemFromDb')->with($itemId)->andReturn($file);
            $this->itemFactory->shouldReceive('getItemTypeForItem')->with($file)->andReturn(
                PLUGIN_DOCMAN_ITEM_TYPE_FILE
            );
            $this->fileStorage->shouldReceive('getFileMD5sum')->andReturns($md5);
        }

        $this->user = \Mockery::spy(\PFUser::class);
        $this->user->shouldReceive('getId')->andReturns(9);

        $userManager = \Mockery::spy(\UserManager::class);
        $userManager->shouldReceive('getUserById')->andReturns($this->user);
        $userManager->shouldReceive('getUserByUserName')->andReturns($this->user);
        $userManager->shouldReceive('getCurrentUser')->andReturns($this->user);

        $controller->shouldReceive('getUser')->andReturns($this->user);

        $versionFactory = M::spy(Docman_VersionFactory::class, ['getAllVersionForItem' => [$version]]);

        $this->lockFactory = \Mockery::spy(\Docman_LockFactory::class);

        $this->permissionManager = \Mockery::spy(\PermissionsManager::class);

        $this->docmanPermissionsManager = \Mockery::spy(\Docman_PermissionsManager::class);
        $this->docmanPermissionsManager->shouldReceive('getLockFactory')->andReturns($this->lockFactory);

        // Partial mock of Docman_SOAPActions
        $this->action = \Mockery::mock(\Docman_SOAPActions::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->action->shouldReceive('_getItemFactory')->andReturns($this->itemFactory);
        $this->action->shouldReceive('_getFolderFactory')->andReturns($folderFactory);
        $this->action->shouldReceive('_checkOwnerChange')->with('*', '*')->andReturns(101);
        $this->action->shouldReceive('_getUserManagerInstance')->andReturns($userManager);
        $this->action->shouldReceive('_getVersionFactory')->andReturns($versionFactory);
        $this->action->shouldReceive('_getPermissionsManagerInstance')->andReturns($this->permissionManager);
        $this->action->shouldReceive('_getDocmanPermissionsManagerInstance')->andReturns(
            $this->docmanPermissionsManager
        );
        $this->action->shouldReceive('_getEventManager')->andReturns(\Mockery::spy(EventManager::class));
        $this->action->shouldReceive('_getFileStorage')->andReturns($this->fileStorage);
        $this->action->__construct($controller);
    }

    protected function tearDown(): void
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
    public function testGetFileMD5sumNominal(): void
    {
        $action = $this->action;

        $params  = array(
            'item_id'  => 128000,
            'group_id' => 2,
        );
        $request = \Mockery::spy(\SOAPRequest::class);
        $request->shouldReceive('exist')->with('item_id')->andReturns(true);
        $request->shouldReceive('get')->with('item_id')->andReturns($params['item_id']);
        $request->shouldReceive('exist')->with('version')->andReturns(false);

        $action->getControler()->request = $request;

        $action->getFileMD5sum();
        $this->assertEquals($this->MD5Map[$params['item_id']], $action->getControler()->_viewParams['action_result']);
    }

    /**
     * Nominal case: getFileMD5sum is called with a correct file ID for a given version
     */
    public function testGetFileMD5sumGivenVersionNominal()
    {
        $action = $this->action;

        $params  = array(
            'item_id'  => 128000,
            'group_id' => 2,
            'version'  => 2,
        );
        $request = \Mockery::spy(\SOAPRequest::class);
        $request->shouldReceive('exist')->with('item_id')->andReturns(true);
        $request->shouldReceive('get')->with('item_id')->andReturns($params['item_id']);
        $request->shouldReceive('exist')->with('version')->andReturns(true);
        $request->shouldReceive('get')->with('version')->andReturns($params['version']);

        $action->getControler()->request = $request;

        $action->getFileMD5sum();
        $this->assertEquals($this->MD5Map[$params['item_id']], $action->getControler()->_viewParams['action_result']);
    }


    public function testGetFileMD5sumAllVersions()
    {
        $action = $this->action;

        $params                          = array(
            'item_id'      => 128000,
            'group_id'     => 2,
            'all_versions' => true,
        );
        $request                         = new SOAPRequest($params);
        $action->getControler()->request = $request;

        $action->getFileMD5sum();
        $this->assertTrue(is_array($action->getControler()->_viewParams['action_result']));
    }

    /**
     * Error case: getFileMD5sum with no item ID
     */
    public function testGetFileMD5sumNoItemError(): void
    {
        $action = $this->action;

        $params  = array();
        $request = \Mockery::spy(\SOAPRequest::class);
        $request->shouldReceive('exist')->andReturns(false);

        $action->getControler()->request = $request;

        $action->getControler()->feedback->shouldReceive('log')->with(
            'error',
            'Error while getting file checksum.'
        )->once();

        $action->getFileMD5sum();
    }

    /**
     * Error case: getFileMD5sum is called with an incorrect ID
     */
    public function testGetFileMD5sumItemNotFoundError(): void
    {
        $action = $this->action;

        $params  = array('item_id' => 0);
        $request = \Mockery::spy(\SOAPRequest::class);
        $request->shouldReceive('exist')->andReturns(true);
        $request->shouldReceive('get')->with('item_id')->andReturns(0);

        $action->getControler()->request = $request;

        $action->getControler()->feedback->shouldReceive('log')->with('error', 'The file cannot be found.')->once();

        $action->getFileMD5sum();
    }

    /**
     * update test with item owner changed
     */
    public function testUpdate(): void
    {
        $action = $this->action;

        $params  = array(
            'item'     => array(
                'owner' => 'testuser',
                'id'    => 128000,
            ),
            'group_id' => 2,
        );
        $request = \Mockery::spy(\SOAPRequest::class);
        $request->shouldReceive('exist')->with('item')->andReturns(true);
        $request->shouldReceive('get')->with('item')->andReturns($params['item']);

        $action->getControler()->request = $request;

        $action->shouldReceive('_checkOwnerChange')->with($params['item']['owner'], $this->user)->once();
        $this->itemFactory->shouldReceive('update')->once();
        $action->event_manager->shouldReceive('processEvent')->with(
            'plugin_docman_event_metadata_update',
            '*'
        )->ordered();
        $action->event_manager->shouldReceive('processEvent')->with('send_notifications', '*')->ordered();

        $action->update();
    }

    /**
     * New version
     */
    public function testNewVersionUpdate(): void
    {
        $action = $this->action;

        $this->lockFactory->shouldReceive('itemIsLocked')->andReturns(false);

        $params  = array(
            'id'       => 128000,
            'group_id' => 2,
        );
        $request = \Mockery::spy(\SOAPRequest::class);
        $request->shouldReceive('exist')->with('id')->andReturns(true);
        $request->shouldReceive('exist')->with('upload_content')->andReturns(true);
        $request->shouldReceive('get')->with('id')->andReturns($params['id']);

        $action->getControler()->request = $request;

        $this->fileStorage->shouldReceive('store')->once();
        $this->itemFactory->shouldReceive('update');
        $action->event_manager->shouldReceive('processEvent')->with('send_notifications', '*')->ordered();
        $action->event_manager->shouldReceive('processEvent')->once();
        $action->new_version();
    }

    /**
     * New version with version date
     */
    public function testNewVersionNoUpdate(): void
    {
        $action = $this->action;

        $params  = array(
            'id'       => 128000,
            'group_id' => 2,
            'date'     => 1,
        );
        $request = \Mockery::spy(\SOAPRequest::class);
        $request->shouldReceive('exist')->with('id')->andReturns(true);
        $request->shouldReceive('exist')->with('upload_content')->andReturns(true);
        $request->shouldReceive('get')->with('id')->andReturns($params['id']);
        $request->shouldReceive('existAndNonEmpty')->with('date')->andReturns(true);

        $action->getControler()->request = $request;

        $this->fileStorage->shouldReceive('store')->once();
        $this->itemFactory->shouldNotReceive('update');
        $action->event_manager->shouldReceive('processEvent')->with('send_notifications', '*')->ordered();
        $action->event_manager->shouldReceive('processEvent')->once();

        $action->new_version();
    }

    /**
     * Item creation
     */
    public function testCreateItem(): void
    {
        $action = $this->action;

        $params  = array(
            'item'     => array(
                'parent_id'   => 128001,
                'title'       => 'test',
                'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_EMPTY,
                'create_date' => 1,
                'update_date' => 2,
            ),
            'group_id' => 2,
        );
        $request = \Mockery::spy(\SOAPRequest::class);
        $request->shouldReceive('exist')->with('item')->andReturns(true);
        $request->shouldReceive('get')->with('item')->andReturns($params['item']);
        $request->shouldReceive('get')->with('group_id')->andReturns($params['group_id']);

        $action->getControler()->request = $request;

        $this->itemFactory->shouldReceive('create')->once()->andReturn(128002);

        $item = \Mockery::mock(\Docman_Item::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $item->shouldReceive('getEventManager')->andReturns($action->event_manager);
        $this->itemFactory->shouldReceive('getItemFromDb')->andReturn($item);
        $this->itemFactory->shouldReceive('getItemTypeForItem')->with($item)->andReturn(PLUGIN_DOCMAN_ITEM_TYPE_EMPTY);

        $this->permissionManager->shouldReceive('clonePermissions')->once();
        $action->event_manager->shouldReceive('processEvent')->with('plugin_docman_event_add', '*')->ordered();
        $action->event_manager->shouldReceive('processEvent')->with(
            'plugin_docman_event_metadata_update',
            '*'
        )->ordered();
        $action->event_manager->shouldReceive('processEvent')->with(
            'plugin_docman_event_metadata_update',
            '*'
        )->ordered();
        $action->event_manager->shouldReceive('processEvent')->with('send_notifications', '*')->ordered();
        $action->getControler()->feedback->shouldReceive('log')->with('info', 'Document successfully created.')->once();

        $action->createItem();
    }

    /**
     * Item creation, without dates
     */
    public function testCreateItemNoDates(): void
    {
        $action = $this->action;

        $params  = array(
            'item'     => array(
                'parent_id' => 128001,
                'title'     => 'test',
                'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_EMPTY,
            ),
            'group_id' => 2,
        );
        $request = \Mockery::spy(\SOAPRequest::class);
        $request->shouldReceive('exist')->with('item')->andReturns(true);
        $request->shouldReceive('get')->with('item')->andReturns($params['item']);
        $request->shouldReceive('get')->with('group_id')->andReturns($params['group_id']);

        $this->itemFactory->shouldReceive('create')->andReturn(128002);

        $item = \Mockery::mock(\Docman_Item::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $item->shouldReceive('getEventManager')->andReturns($action->event_manager);
        $this->itemFactory->shouldReceive('getItemFromDb')->andReturn($item);
        $this->itemFactory->shouldReceive('getItemTypeForItem')->with($item)->andReturn(PLUGIN_DOCMAN_ITEM_TYPE_EMPTY);

        $action->getControler()->request = $request;

        $action->event_manager->shouldReceive('processEvent')->with('plugin_docman_event_add', '*')->ordered();
        $action->event_manager->shouldReceive('processEvent')->with('send_notifications', '*')->ordered();

        $action->createItem();
    }

    /**
     * AppendFileChunk test
     */
    public function testAppendFileChunk(): void
    {
        $action = $this->action;

        $params  = array(
            'group_id'       => 10,
            'item_id'        => 128000,
            'chunk_offset'   => 10,
            'chunk_size'     => 64,
            'upload_content' => 'abcdef'
        );
        $request = \Mockery::spy(\SOAPRequest::class);
        $request->shouldReceive('exist')->with('group_id')->andReturns(true);
        $request->shouldReceive('exist')->with('item_id')->andReturns(true);
        $request->shouldReceive('exist')->with('chunk_offset')->andReturns(true);
        $request->shouldReceive('exist')->with('chunk_size')->andReturns(true);
        $request->shouldReceive('get')->with('group_id')->andReturns($params['group_id']);
        $request->shouldReceive('get')->with('item_id')->andReturns($params['item_id']);
        $request->shouldReceive('get')->with('chunk_offset')->andReturns($params['chunk_offset']);
        $request->shouldReceive('get')->with('chunk_size')->andReturns($params['chunk_size']);
        $request->shouldReceive('get')->with('upload_content')->andReturns($params['upload_content']);

        $action->getControler()->request = $request;

        $this->fileStorage->shouldReceive('store')->with(
            $params['upload_content'],
            $params['group_id'],
            $params['item_id'],
            0,
            $params['chunk_offset'],
            $params['chunk_size']
        )->once();

        $action->appendFileChunk();
    }

    /**
     * Test: getTreeInfo with no parameters supplied
     */
    public function testGetTreeInfoError(): void
    {
        $action = $this->action;

        $request                         = \Mockery::spy(\SOAPRequest::class);
        $action->getControler()->request = $request;

        $action->getControler()->feedback->shouldReceive('log')->with('error', 'Parameter parent_id is missing')->once(
        );

        $action->getTreeInfo();
    }

    public function testGetFileChunk(): void
    {
        $action = $this->action;

        $params  = array(
            'group_id'       => 10,
            'item_id'        => 128000,
            'chunk_offset'   => 10,
            'chunk_size'     => 64,
            'version_number' => 2
        );
        $request = \Mockery::spy(\SOAPRequest::class);
        $request->shouldReceive('exist')->with('group_id')->andReturns(true);
        $request->shouldReceive('exist')->with('item_id')->andReturns(true);
        $request->shouldReceive('exist')->with('version_number')->andReturns(true);
        $request->shouldReceive('exist')->with('chunk_offset')->andReturns(true);
        $request->shouldReceive('exist')->with('chunk_size')->andReturns(true);
        $request->shouldReceive('get')->with('group_id')->andReturns($params['group_id']);
        $request->shouldReceive('get')->with('item_id')->andReturns($params['item_id']);
        $request->shouldReceive('get')->with('chunk_offset')->andReturns($params['chunk_offset']);
        $request->shouldReceive('get')->with('chunk_size')->andReturns($params['chunk_size']);
        $request->shouldReceive('get')->with('version_number')->andReturns($params['version_number']);

        $action->getControler()->request = $request;

        $action->getFileChunk();
    }
}

<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Webdav;

use DocmanPlugin;
use ForgeConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Conflict;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\RequestedRangeNotSatisfiable;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class WebDAVDocmanFolderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var Project
     */
    private $project;

    protected function setUp(): void
    {
        $this->user    = UserTestBuilder::aUser()->build();
        $this->project = ProjectTestBuilder::aProject()->build();
    }

    /**
     * Testing when the folder have no childrens
     */
    public function testGetChildListNoChildrens(): void
    {
        $utils                   = \Mockery::spy(\WebDAVUtils::class);
        $docmanPermissionManager = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionManager->shouldReceive('userCanAccess')->andReturns(true);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($docmanPermissionManager);
        $docmanItemFactory = \Mockery::spy(\Docman_ItemFactory::class);
        $docmanItemFactory->shouldReceive('getChildrenFromParent')->andReturns([]);
        $utils->shouldReceive('getDocmanItemFactory')->andReturns($docmanItemFactory);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $this->assertSame([], $webDAVDocmanFolder->getChildList());
    }

    /**
     * Testing when the User can't access/read the child node
     */
    public function testGetChildListUserCanNotAccess(): void
    {
        $item              = \Mockery::spy(\Docman_Item::class);
        $docmanItemFactory = \Mockery::spy(\Docman_ItemFactory::class);
        $docmanItemFactory->shouldReceive('getChildrenFromParent')->andReturns([$item]);

        $utils                   = \Mockery::spy(\WebDAVUtils::class);
        $docmanPermissionManager = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionManager->shouldReceive('userCanAccess')->andReturns(false);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($docmanPermissionManager);
        $utils->shouldReceive('getDocmanItemFactory')->andReturns($docmanItemFactory);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $this->assertEquals([], $webDAVDocmanFolder->getChildList());
    }

    /**
     * Testing when the folder contain a duplicate name
     */
    public function testGetChildListDuplicateName(): void
    {
        $item1 = \Mockery::spy(\Docman_Folder::class);
        $item1->shouldReceive('getTitle')->andReturns('SameName');
        $item2 = \Mockery::spy(\Docman_Folder::class);
        $item2->shouldReceive('getTitle')->andReturns('SameName');
        $docmanItemFactory = \Mockery::spy(\Docman_ItemFactory::class);
        $docmanItemFactory->shouldReceive('getChildrenFromParent')->andReturns([$item1, $item2]);

        $utils                   = \Mockery::spy(\WebDAVUtils::class);
        $docmanPermissionManager = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionManager->shouldReceive('userCanAccess')->andReturns(true);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($docmanPermissionManager);
        $utils->shouldReceive('getDocmanItemFactory')->andReturns($docmanItemFactory);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $children = $webDAVDocmanFolder->getChildList();

        $this->assertTrue(isset($children['SameName']));
        $this->assertSame(1, sizeof($children));
        $this->assertSame('duplicate', $children['SameName']);
    }

    /**
     * Testing when the folder contain some items
     */
    public function testGetChildListFilled(): void
    {
        $item1 = \Mockery::spy(\Docman_Folder::class);
        $item1->shouldReceive('getTitle')->andReturns('SameName');
        $item2 = \Mockery::spy(\Docman_Folder::class);
        $item2->shouldReceive('getTitle')->andReturns('AnotherName');
        $docmanItemFactory = \Mockery::spy(\Docman_ItemFactory::class);
        $docmanItemFactory->shouldReceive('getChildrenFromParent')->andReturns([$item1, $item2]);

        $utils                   = \Mockery::spy(\WebDAVUtils::class);
        $docmanPermissionManager = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionManager->shouldReceive('userCanAccess')->andReturns(true);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($docmanPermissionManager);
        $utils->shouldReceive('getDocmanItemFactory')->andReturns($docmanItemFactory);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $children = $webDAVDocmanFolder->getChildList();

        $this->assertTrue(isset($children['SameName']));
        $this->assertTrue(isset($children['AnotherName']));
        $this->assertSame(2, sizeof($children));
    }

    public function testGetChildrenDoesntListLinksWiki(): void
    {
        $item1             = new \Docman_Folder(['title' => 'leFolder', 'parent_id' => '115']);
        $item2             = new \Docman_Link(['title' => 'leLink', 'link_url' => 'https://example.com']);
        $docmanItemFactory = \Mockery::mock(\Docman_ItemFactory::class, ['getChildrenFromParent' => [$item1, $item2]]);

        $docmanPermissionManager = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionManager->shouldReceive('userCanAccess')->andReturns(true);

        $utils = new \WebDAVUtils();
        $utils->setDocmanPermissionsManager($this->project, $docmanPermissionManager);
        $utils->setDocmanItemFactory($docmanItemFactory);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $children = $webDAVDocmanFolder->getChildren();

        self::assertCount(1, $children);
        self::assertEquals('leFolder', array_pop($children)->getName());
    }

    /**
     * Testing when the folder have no childrens
     */
    public function testGetChildrenNoChildrens(): void
    {
        $webDAVDocmanFolder = \Mockery::mock(\WebDAVDocmanFolder::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVDocmanFolder->shouldReceive('getChildList')->andReturns([]);

        $this->assertSame([], $webDAVDocmanFolder->getChildren());
    }

    /**
     * Testing when the folder contain a duplicate name
     */
    public function testGetChildrenDuplicateName(): void
    {
        $webDAVDocmanFolder = \Mockery::mock(\WebDAVDocmanFolder::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVDocmanFolder->shouldReceive('getChildList')->andReturns(['SomeName' => 'duplicate']);

        $this->assertSame([], $webDAVDocmanFolder->getChildren());
    }

    /**
     * Testing when the folder contain some items
     */
    public function testGetChildrenFilled(): void
    {
        $item1 = \Mockery::spy(\Docman_Folder::class);
        $item1->shouldReceive('getTitle')->andReturns('SameName');
        $item2 = \Mockery::spy(\Docman_Folder::class);
        $item2->shouldReceive('getTitle')->andReturns('AnotherName');
        $docmanItemFactory = \Mockery::spy(\Docman_ItemFactory::class);
        $docmanItemFactory->shouldReceive('getChildrenFromParent')->andReturns([$item1, $item2]);

        $utils                   = \Mockery::spy(\WebDAVUtils::class);
        $docmanPermissionManager = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionManager->shouldReceive('userCanAccess')->andReturns(true);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($docmanPermissionManager);
        $utils->shouldReceive('getDocmanItemFactory')->andReturns($docmanItemFactory);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $children = $webDAVDocmanFolder->getChildren();

        $this->assertTrue(isset($children['SameName']));
        $this->assertTrue(isset($children['AnotherName']));
        $this->assertSame(2, sizeof($children));
    }

    /**
     * Testing when the folder have no childrens
     */
    public function testGetChildNotFound(): void
    {
        $utils                   = \Mockery::spy(\WebDAVUtils::class);
        $docmanPermissionManager = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionManager->shouldReceive('userCanAccess')->andReturns(true);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($docmanPermissionManager);
        $docmanItemFactory = \Mockery::spy(\Docman_ItemFactory::class);
        $docmanItemFactory->shouldReceive('getChildrenFromParent')->andReturns([]);
        $utils->shouldReceive('getDocmanItemFactory')->andReturns($docmanItemFactory);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $this->expectException(NotFound::class);
        $webDAVDocmanFolder->getChild('whatever');
    }

    /**
     * Testing when the item is duplicated
     */
    public function testGetChildDuplicatedSameCase(): void
    {
        $item1 = \Mockery::spy(\Docman_Folder::class);
        $item1->shouldReceive('getTitle')->andReturns('SameName');
        $item2 = \Mockery::spy(\Docman_Folder::class);
        $item2->shouldReceive('getTitle')->andReturns('SameName');
        $docmanItemFactory = \Mockery::spy(\Docman_ItemFactory::class);
        $docmanItemFactory->shouldReceive('getChildrenFromParent')->andReturns([$item1, $item2]);

        $utils                   = \Mockery::spy(\WebDAVUtils::class);
        $docmanPermissionManager = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionManager->shouldReceive('userCanAccess')->andReturns(true);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($docmanPermissionManager);
        $utils->shouldReceive('getDocmanItemFactory')->andReturns($docmanItemFactory);
        $utils->shouldReceive('retrieveName')->andReturns('SameName');

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $this->expectException(Conflict::class);
        $webDAVDocmanFolder->getChild('SameName');
    }

    /**
     * Testing when the item is duplicated
     */
    public function testGetChildDuplicatedDifferentCase(): void
    {
        $item1 = \Mockery::spy(\Docman_Folder::class);
        $item1->shouldReceive('getTitle')->andReturns('SameName');
        $item2 = \Mockery::spy(\Docman_Folder::class);
        $item2->shouldReceive('getTitle')->andReturns('samename');
        $docmanItemFactory = \Mockery::spy(\Docman_ItemFactory::class);
        $docmanItemFactory->shouldReceive('getChildrenFromParent')->andReturns([$item1, $item2]);

        $utils                   = \Mockery::spy(\WebDAVUtils::class);
        $docmanPermissionManager = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionManager->shouldReceive('userCanAccess')->andReturns(true);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($docmanPermissionManager);
        $utils->shouldReceive('getDocmanItemFactory')->andReturns($docmanItemFactory);
        $utils->shouldReceive('retrieveName')->andReturns('SameName');

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $this->expectException(Conflict::class);
        $webDAVDocmanFolder->getChild('SameName');
    }

    public function testGetChildIsWiki(): void
    {
        $utils                   = \Mockery::spy(\WebDAVUtils::class);
        $docmanPermissionManager = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionManager->shouldReceive('userCanAccess')->andReturns(true);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($docmanPermissionManager);

        $docmanItemFactory = \Mockery::spy(\Docman_ItemFactory::class);
        $docmanItemFactory->shouldReceive('getChildrenFromParent')->andReturns([new \Docman_Wiki(['title' => 'leWiki', 'wiki_page' => 'HomePage'])]);
        $utils->shouldReceive('getDocmanItemFactory')->andReturns($docmanItemFactory);

        $utils->shouldReceive("retrieveName")->andReturnArg(0);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder(
            UserTestBuilder::aUser()->build(),
            ProjectTestBuilder::aProject()->build(),
            new \Docman_Folder([]),
            $utils
        );

        $this->expectException(BadRequest::class);
        $webDAVDocmanFolder->getChild('leWiki');
    }

    /**
     * Testing when the folder have childrens
     */
    public function testGetChildSuccess(): void
    {
        $base_folder = new \Docman_Folder(['title' => 'leFolder', 'parent_id' => 121]);

        $item = \Mockery::spy(\Docman_Folder::class);
        $item->shouldReceive('getTitle')->andReturns('SomeName');

        $docmanItemFactory = \Mockery::spy(\Docman_ItemFactory::class);
        $docmanItemFactory->shouldReceive('getChildrenFromParent')->andReturns([$item]);

        $docmanPermissionManager = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionManager->shouldReceive('userCanAccess')->andReturns(true);

        $utils = new \WebDAVUtils();
        $utils->setDocmanPermissionsManager($this->project, $docmanPermissionManager);
        $utils->setDocmanItemFactory($docmanItemFactory);

        $expected_folder = new \WebDAVDocmanFolder($this->user, $this->project, $item, $utils);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, $base_folder, $utils);
        self::assertEquals($expected_folder, $webDAVDocmanFolder->getChild('SomeName'));
    }

    public function testCreateDirectoryNoWriteEnabled(): void
    {
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(false);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $this->expectException(Forbidden::class);
        $webDAVDocmanFolder->createDirectory('name');
    }

    public function testCreateDirectorySuccess(): void
    {
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $utils->shouldReceive('processDocmanRequest')->once();

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $webDAVDocmanFolder->createDirectory('name');
    }

    public function testDeleteDirectoryNoWriteEnabled(): void
    {
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(false);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $this->expectException(Forbidden::class);
        $webDAVDocmanFolder->delete();
    }

    public function testSetNameNoWriteEnabled(): void
    {
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(false);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $this->expectException(MethodNotAllowed::class);
        $webDAVDocmanFolder->setName('newName');
    }

    public function testSetNameSuccess(): void
    {
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $utils->shouldReceive('processDocmanRequest')->once();

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $webDAVDocmanFolder->setName('newName');
    }

    public function testCreateFileNoWriteEnabled(): void
    {
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(false);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $this->expectException(Forbidden::class);
        $data = fopen(dirname(__FILE__) . '/_fixtures/test.txt', 'r');
        $webDAVDocmanFolder->createFile('name', $data);
    }

    public function testCreateFileBigFile(): void
    {
        $docman_item_factory = \Mockery::spy(\Docman_ItemFactory::class);
        $docman_item_factory->shouldReceive('getChildrenFromParent')->andReturns([]);

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $utils->shouldReceive('getDocmanItemFactory')->andReturn($docman_item_factory);
        $utils->shouldReceive('processDocmanRequest')->never();

        ForgeConfig::set(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, 23);

        $this->expectException(RequestedRangeNotSatisfiable::class);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $data = fopen(__DIR__ . '/_fixtures/test.txt', 'r');
        $webDAVDocmanFolder->createFile('name', $data);
    }

    public function testCreateFileSucceed(): void
    {
        $docman_item_factory = \Mockery::spy(\Docman_ItemFactory::class);
        $docman_item_factory->shouldReceive('getChildrenFromParent')->andReturns([]);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $utils->shouldReceive('processDocmanRequest')->once();
        $utils->shouldReceive('getDocmanItemFactory')->andReturn($docman_item_factory);

        ForgeConfig::set(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, 2000);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $data = fopen(__DIR__ . '/_fixtures/test.txt', 'r');
        $webDAVDocmanFolder->createFile('name', $data);
    }
}

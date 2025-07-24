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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WebDAVDocmanFolderTest extends \Tuleap\Test\PHPUnit\TestCase
{
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

    #[\Override]
    protected function setUp(): void
    {
        $this->user    = UserTestBuilder::aUser()->build();
        $this->project = ProjectTestBuilder::aProject()->build();
        $GLOBALS['Language']->method('getText')->willReturn('');
    }

    /**
     * Testing when the folder have no childrens
     */
    public function testGetChildListNoChildrens(): void
    {
        $utils                   = $this->createMock(\WebDAVUtils::class);
        $docmanPermissionManager = $this->createMock(\Docman_PermissionsManager::class);
        $docmanPermissionManager->method('userCanAccess')->willReturn(true);
        $utils->method('getDocmanPermissionsManager')->willReturn($docmanPermissionManager);
        $docmanItemFactory = $this->createMock(\Docman_ItemFactory::class);
        $docmanItemFactory->method('getChildrenFromParent')->willReturn([]);
        $utils->method('getDocmanItemFactory')->willReturn($docmanItemFactory);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        self::assertSame([], $webDAVDocmanFolder->getChildList());
    }

    /**
     * Testing when the User can't access/read the child node
     */
    public function testGetChildListUserCanNotAccess(): void
    {
        $item = $this->createMock(\Docman_Item::class);
        $item->method('getId')->willReturn(1);

        $docmanItemFactory = $this->createMock(\Docman_ItemFactory::class);
        $docmanItemFactory->method('getChildrenFromParent')->willReturn([$item]);

        $utils                   = $this->createMock(\WebDAVUtils::class);
        $docmanPermissionManager = $this->createMock(\Docman_PermissionsManager::class);
        $docmanPermissionManager->method('userCanAccess')->willReturn(false);
        $utils->method('getDocmanPermissionsManager')->willReturn($docmanPermissionManager);
        $utils->method('getDocmanItemFactory')->willReturn($docmanItemFactory);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        self::assertEquals([], $webDAVDocmanFolder->getChildList());
    }

    /**
     * Testing when the folder contain a duplicate name
     */
    public function testGetChildListDuplicateName(): void
    {
        $item1 = $this->createMock(\Docman_Folder::class);
        $item1->method('getTitle')->willReturn('SameName');
        $item1->method('getId')->willReturn(1);

        $item2 = $this->createMock(\Docman_Folder::class);
        $item2->method('getTitle')->willReturn('SameName');
        $item2->method('getId')->willReturn(2);

        $docmanItemFactory = $this->createMock(\Docman_ItemFactory::class);
        $docmanItemFactory->method('getChildrenFromParent')->willReturn([$item1, $item2]);

        $utils                   = $this->createMock(\WebDAVUtils::class);
        $docmanPermissionManager = $this->createMock(\Docman_PermissionsManager::class);
        $docmanPermissionManager->method('userCanAccess')->willReturn(true);
        $utils->method('getDocmanPermissionsManager')->willReturn($docmanPermissionManager);
        $utils->method('getDocmanItemFactory')->willReturn($docmanItemFactory);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $children = $webDAVDocmanFolder->getChildList();

        self::assertTrue(isset($children['SameName']));
        self::assertSame(1, sizeof($children));
        self::assertSame('duplicate', $children['SameName']);
    }

    /**
     * Testing when the folder contain some items
     */
    public function testGetChildListFilled(): void
    {
        $item1 = $this->createMock(\Docman_Folder::class);
        $item1->method('getTitle')->willReturn('SameName');
        $item1->method('getId')->willReturn(1);

        $item2 = $this->createMock(\Docman_Folder::class);
        $item2->method('getTitle')->willReturn('AnotherName');
        $item2->method('getId')->willReturn(2);

        $docmanItemFactory = $this->createMock(\Docman_ItemFactory::class);
        $docmanItemFactory->method('getChildrenFromParent')->willReturn([$item1, $item2]);

        $utils                   = $this->createMock(\WebDAVUtils::class);
        $docmanPermissionManager = $this->createMock(\Docman_PermissionsManager::class);
        $docmanPermissionManager->method('userCanAccess')->willReturn(true);
        $utils->method('getDocmanPermissionsManager')->willReturn($docmanPermissionManager);
        $utils->method('getDocmanItemFactory')->willReturn($docmanItemFactory);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $children = $webDAVDocmanFolder->getChildList();

        self::assertTrue(isset($children['SameName']));
        self::assertTrue(isset($children['AnotherName']));
        self::assertSame(2, sizeof($children));
    }

    public function testGetChildrenDoesntListLinksWiki(): void
    {
        $item1             = new \Docman_Folder(['title' => 'leFolder', 'parent_id' => '115']);
        $item2             = new \Docman_Link(['title' => 'leLink', 'link_url' => 'https://example.com']);
        $docmanItemFactory = $this->createMock(\Docman_ItemFactory::class);
        $docmanItemFactory->method('getChildrenFromParent')->willReturn([$item1, $item2]);

        $docmanPermissionManager = $this->createMock(\Docman_PermissionsManager::class);
        $docmanPermissionManager->method('userCanAccess')->willReturn(true);

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
        $webDAVDocmanFolder = $this->getMockBuilder(\WebDAVDocmanFolder::class)
            ->setConstructorArgs([$this->user, $this->project, new \Docman_Folder(), $this->createMock(\WebDAVUtils::class)])
            ->onlyMethods(['getChildList'])
            ->getMock();

        $webDAVDocmanFolder->method('getChildList')->willReturn([]);

        self::assertSame([], $webDAVDocmanFolder->getChildren());
    }

    /**
     * Testing when the folder contain a duplicate name
     */
    public function testGetChildrenDuplicateName(): void
    {
        $webDAVDocmanFolder = $this->getMockBuilder(\WebDAVDocmanFolder::class)
            ->setConstructorArgs([$this->user, $this->project, new \Docman_Folder(), $this->createMock(\WebDAVUtils::class)])
            ->onlyMethods(['getChildList'])
            ->getMock();

        $webDAVDocmanFolder->method('getChildList')->willReturn(['SomeName' => 'duplicate']);

        self::assertSame([], $webDAVDocmanFolder->getChildren());
    }

    /**
     * Testing when the folder contain some items
     */
    public function testGetChildrenFilled(): void
    {
        $item1 = $this->createMock(\Docman_Folder::class);
        $item1->method('getTitle')->willReturn('SameName');
        $item1->method('getId')->willReturn(1);

        $item2 = $this->createMock(\Docman_Folder::class);
        $item2->method('getTitle')->willReturn('AnotherName');
        $item2->method('getId')->willReturn(2);

        $docmanItemFactory = $this->createMock(\Docman_ItemFactory::class);
        $docmanItemFactory->method('getChildrenFromParent')->willReturn([$item1, $item2]);

        $utils                   = $this->createMock(\WebDAVUtils::class);
        $docmanPermissionManager = $this->createMock(\Docman_PermissionsManager::class);
        $docmanPermissionManager->method('userCanAccess')->willReturn(true);
        $utils->method('getDocmanPermissionsManager')->willReturn($docmanPermissionManager);
        $utils->method('getDocmanItemFactory')->willReturn($docmanItemFactory);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $children = $webDAVDocmanFolder->getChildren();

        self::assertTrue(isset($children['SameName']));
        self::assertTrue(isset($children['AnotherName']));
        self::assertSame(2, sizeof($children));
    }

    /**
     * Testing when the folder have no childrens
     */
    public function testGetChildNotFound(): void
    {
        $utils                   = $this->createMock(\WebDAVUtils::class);
        $docmanPermissionManager = $this->createMock(\Docman_PermissionsManager::class);
        $docmanPermissionManager->method('userCanAccess')->willReturn(true);
        $utils->method('getDocmanPermissionsManager')->willReturn($docmanPermissionManager);
        $docmanItemFactory = $this->createMock(\Docman_ItemFactory::class);
        $docmanItemFactory->method('getChildrenFromParent')->willReturn([]);
        $utils->method('getDocmanItemFactory')->willReturn($docmanItemFactory);
        $utils->method('retrieveName')->willReturn('whatever');

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $this->expectException(NotFound::class);
        $webDAVDocmanFolder->getChild('whatever');
    }

    /**
     * Testing when the item is duplicated
     */
    public function testGetChildDuplicatedSameCase(): void
    {
        $item1 = $this->createMock(\Docman_Folder::class);
        $item1->method('getTitle')->willReturn('SameName');
        $item1->method('getId')->willReturn(1);

        $item2 = $this->createMock(\Docman_Folder::class);
        $item2->method('getTitle')->willReturn('SameName');
        $item2->method('getId')->willReturn(2);

        $docmanItemFactory = $this->createMock(\Docman_ItemFactory::class);
        $docmanItemFactory->method('getChildrenFromParent')->willReturn([$item1, $item2]);

        $utils                   = $this->createMock(\WebDAVUtils::class);
        $docmanPermissionManager = $this->createMock(\Docman_PermissionsManager::class);
        $docmanPermissionManager->method('userCanAccess')->willReturn(true);
        $utils->method('getDocmanPermissionsManager')->willReturn($docmanPermissionManager);
        $utils->method('getDocmanItemFactory')->willReturn($docmanItemFactory);
        $utils->method('retrieveName')->willReturn('SameName');

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $this->expectException(Conflict::class);
        $webDAVDocmanFolder->getChild('SameName');
    }

    /**
     * Testing when the item is duplicated
     */
    public function testGetChildDuplicatedDifferentCase(): void
    {
        $item1 = $this->createMock(\Docman_Folder::class);
        $item1->method('getTitle')->willReturn('SameName');
        $item1->method('getId')->willReturn(1);

        $item2 = $this->createMock(\Docman_Folder::class);
        $item2->method('getTitle')->willReturn('samename');
        $item2->method('getId')->willReturn(2);

        $docmanItemFactory = $this->createMock(\Docman_ItemFactory::class);
        $docmanItemFactory->method('getChildrenFromParent')->willReturn([$item1, $item2]);

        $utils                   = $this->createMock(\WebDAVUtils::class);
        $docmanPermissionManager = $this->createMock(\Docman_PermissionsManager::class);
        $docmanPermissionManager->method('userCanAccess')->willReturn(true);
        $utils->method('getDocmanPermissionsManager')->willReturn($docmanPermissionManager);
        $utils->method('getDocmanItemFactory')->willReturn($docmanItemFactory);
        $utils->method('retrieveName')->willReturn('SameName');

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $this->expectException(Conflict::class);
        $webDAVDocmanFolder->getChild('SameName');
    }

    public function testGetChildIsWiki(): void
    {
        $utils                   = $this->createMock(\WebDAVUtils::class);
        $docmanPermissionManager = $this->createMock(\Docman_PermissionsManager::class);
        $docmanPermissionManager->method('userCanAccess')->willReturn(true);
        $utils->method('getDocmanPermissionsManager')->willReturn($docmanPermissionManager);

        $docmanItemFactory = $this->createMock(\Docman_ItemFactory::class);
        $docmanItemFactory->method('getChildrenFromParent')->willReturn([new \Docman_Wiki(['title' => 'leWiki', 'wiki_page' => 'HomePage'])]);
        $utils->method('getDocmanItemFactory')->willReturn($docmanItemFactory);

        $utils->method('retrieveName')->willReturn('leWiki');

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

        $item = $this->createMock(\Docman_Folder::class);
        $item->method('getTitle')->willReturn('SomeName');
        $item->method('getId')->willReturn(1);

        $docmanItemFactory = $this->createMock(\Docman_ItemFactory::class);
        $docmanItemFactory->method('getChildrenFromParent')->willReturn([$item]);

        $docmanPermissionManager = $this->createMock(\Docman_PermissionsManager::class);
        $docmanPermissionManager->method('userCanAccess')->willReturn(true);

        $utils = new \WebDAVUtils();
        $utils->setDocmanPermissionsManager($this->project, $docmanPermissionManager);
        $utils->setDocmanItemFactory($docmanItemFactory);

        $expected_folder = new \WebDAVDocmanFolder($this->user, $this->project, $item, $utils);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, $base_folder, $utils);
        self::assertEquals($expected_folder, $webDAVDocmanFolder->getChild('SomeName'));
    }

    public function testCreateDirectoryNoWriteEnabled(): void
    {
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(false);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $this->expectException(Forbidden::class);
        $webDAVDocmanFolder->createDirectory('name');
    }

    public function testCreateDirectorySuccess(): void
    {
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(true);
        $utils->expects($this->once())->method('processDocmanRequest');

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $webDAVDocmanFolder->createDirectory('name');
    }

    public function testDeleteDirectoryNoWriteEnabled(): void
    {
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(false);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $this->expectException(Forbidden::class);
        $webDAVDocmanFolder->delete();
    }

    public function testSetNameNoWriteEnabled(): void
    {
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(false);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $this->expectException(MethodNotAllowed::class);
        $webDAVDocmanFolder->setName('newName');
    }

    public function testSetNameSuccess(): void
    {
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(true);
        $utils->expects($this->once())->method('processDocmanRequest');

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $webDAVDocmanFolder->setName('newName');
    }

    public function testCreateFileNoWriteEnabled(): void
    {
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(false);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $this->expectException(Forbidden::class);
        $data = fopen(dirname(__FILE__) . '/_fixtures/test.txt', 'r');
        $webDAVDocmanFolder->createFile('name', $data);
    }

    public function testCreateFileBigFile(): void
    {
        $docman_item_factory = $this->createMock(\Docman_ItemFactory::class);
        $docman_item_factory->method('getChildrenFromParent')->willReturn([]);

        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(true);
        $utils->method('getDocmanItemFactory')->willReturn($docman_item_factory);
        $utils->method('retrieveName')->willReturn('name');
        $utils->expects($this->never())->method('processDocmanRequest');
        $docmanPermissionManager = $this->createMock(\Docman_PermissionsManager::class);
        $docmanPermissionManager->method('userCanAccess')->willReturn(true);
        $utils->method('getDocmanPermissionsManager')->willReturn($docmanPermissionManager);

        ForgeConfig::set(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, 23);

        $this->expectException(RequestedRangeNotSatisfiable::class);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $data = fopen(__DIR__ . '/_fixtures/test.txt', 'r');
        $webDAVDocmanFolder->createFile('name', $data);
    }

    public function testCreateFileSucceed(): void
    {
        $docman_item_factory = $this->createMock(\Docman_ItemFactory::class);
        $docman_item_factory->method('getChildrenFromParent')->willReturn([]);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(true);
        $utils->expects($this->once())->method('processDocmanRequest');
        $utils->method('getDocmanItemFactory')->willReturn($docman_item_factory);
        $utils->method('retrieveName')->willReturn('name');
        $docmanPermissionManager = $this->createMock(\Docman_PermissionsManager::class);
        $docmanPermissionManager->method('userCanAccess')->willReturn(true);
        $utils->method('getDocmanPermissionsManager')->willReturn($docmanPermissionManager);

        ForgeConfig::set(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, 2000);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($this->user, $this->project, new \Docman_Folder(), $utils);

        $data = fopen(__DIR__ . '/_fixtures/test.txt', 'r');
        $webDAVDocmanFolder->createFile('name', $data);
    }
}

<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\WebDAV;

use Docman_ItemFactory;
use FRSPackage;
use FRSRelease;
use Sabre\DAV\Exception\MethodNotAllowed;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\WebDAV\Docman\DocumentDownloader;
use WebDAVDocmanFolder;
use WebDAVFRSFile;
use WebDAVFRSPackage;
use WebDAVFRSRelease;
use WebDAVTree;

require_once __DIR__ . '/bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WebDAVTreeTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private \PFUser $user;
    private \Project $project;
    private FRSPackage $package;
    /**
     * @var FRSRelease&\PHPUnit\Framework\MockObject\MockObject
     */
    private $release;
    /**
     * @var \FRSFile&\PHPUnit\Framework\MockObject\MockObject
     */
    private $file;
    /**
     * @var \Docman_Folder&\PHPUnit\Framework\MockObject\MockObject
     */
    private $docman_folder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user          = UserTestBuilder::anActiveUser()->build();
        $this->project       = ProjectTestBuilder::aProject()->withId(101)->build();
        $this->package       = new FRSPackage();
        $this->release       = $this->createMock(FRSRelease::class);
        $this->file          = $this->createMock(\FRSFile::class);
        $this->docman_folder = $this->createMock(\Docman_Folder::class);

        $docman_item_factory = $this->createMock(\Docman_ItemFactory::class);
        $docman_item_factory->method('getItemFromDb')->willReturn($this->createMock(\Docman_Item::class));
        Docman_ItemFactory::setInstance(101, $docman_item_factory);

        $GLOBALS['Language']->method('getText')->willReturn('');
    }

    protected function tearDown(): void
    {
        Docman_ItemFactory::clearInstance(101);
        parent::tearDown();
    }

    public function testCanBeMovedFailNotMovable(): void
    {
        $source      = null;
        $destination = null;
        $tree        = $this->getTestTree();

        self::assertFalse($tree->canBeMoved($source, $destination));
    }

    public function testCanBeMovedFailSourceNotReleaseDestinationPackage(): void
    {
        $source      = null;
        $destination = $this->getTestPackage();
        $tree        = $this->getTestTree();

        self::assertFalse($tree->canBeMoved($source, $destination));
    }

    public function testCanBeMovedFailSourceNotFileDestinationRelease(): void
    {
        $source      = null;
        $destination = $this->getTestRelease();
        $tree        = $this->getTestTree();

        self::assertFalse($tree->canBeMoved($source, $destination));
    }

    public function testCanBeMovedFailSourceReleaseDestinationNotPackage(): void
    {
        $source      = $this->getTestRelease();
        $destination = null;
        $tree        = $this->getTestTree();

        self::assertFalse($tree->canBeMoved($source, $destination));
    }

    public function testCanBeMovedFailSourceFileDestinationNotRelease(): void
    {
        $source      = $tree = $this->getTestFile();
        $destination = null;
        $tree        = $this->getTestTree();

        self::assertFalse($tree->canBeMoved($source, $destination));
    }

    public function testCanBeMovedFailSourceReleaseDestinationPackageNotSameProject(): void
    {
        $source      = $this->getTestRelease2();
        $destination = $this->getTestPackage();
        $tree        = $this->getTestTree();

        self::assertFalse($tree->canBeMoved($source, $destination));
    }

    public function testCanBeMovedFailSourceFileDestinationReleaseNotSameProject(): void
    {
        $source      = $tree = $this->getTestFile();
        $destination = $this->getTestRelease2();
        $tree        = $this->getTestTree();

        self::assertFalse($tree->canBeMoved($source, $destination));
    }

    public function testCanBeMovedSucceedeSourceReleaseDestinationPackage(): void
    {
        $source      = $this->getTestRelease();
        $destination = $this->getTestPackage();
        $tree        = $this->getTestTree();

        self::assertTrue($tree->canBeMoved($source, $destination));
    }

    public function testCanBeMovedSucceedeSourceFileDestinationRelease(): void
    {
        $source = new WebDAVFRSFile($this->user, ProjectTestBuilder::aProject()->withId(1)->build(), $this->file, $this->createMock(\WebDAVUtils::class));

        $destination = $this->getTestRelease();
        $tree        = $this->getTestTree();

        self::assertTrue($tree->canBeMoved($source, $destination));
    }

    public function testMoveOnlyRename(): void
    {
        $node = $this->createMock(\WebDAVFRSRelease::class);
        $tree = $this->createPartialMock(WebDAVTree::class, ['canBeMoved', 'getNodeForPath', 'getUtils']);
        $tree->method('canBeMoved')->willReturn(true);
        $tree->method('getNodeForPath')->willReturn($node);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(true);
        $utils->method('getDocmanItemFactory')->willReturn(null);
        $tree->method('getUtils')->willReturn($utils);

        $node->expects($this->once())->method('setName');
        $node->expects(self::never())->method('move');

        $tree->move('project1/package1/release1', 'project1/package1/release2');
    }

    public function testMoveCanNotMove(): void
    {
        $node = $this->createMock(\WebDAVFRSRelease::class);
        $tree = $this->createPartialMock(WebDAVTree::class, ['getNodeForPath', 'canBeMoved', 'getUtils']);
        $tree->method('canBeMoved')->willReturn(false);
        $tree->method('getNodeForPath')->willReturn($node);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(true);
        $utils->method('getDocmanItemFactory')->willReturn(null);
        $tree->method('getUtils')->willReturn($utils);

        $node->expects(self::never())->method('setName');
        $node->expects(self::never())->method('move');
        $this->expectException(MethodNotAllowed::class);

        $tree->move('project1/package1/release1', 'project1/package2/release2');
    }

    public function testMoveSucceed(): void
    {
        $node = $this->createMock(\WebDAVFRSRelease::class);
        $tree = $this->createPartialMock(WebDAVTree::class, ['getNodeForPath', 'canBeMoved', 'getUtils']);
        $tree->method('canBeMoved')->willReturn(true);
        $tree->method('getNodeForPath')->willReturn($node);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(true);
        $utils->method('getDocmanItemFactory')->willReturn(null);
        $tree->method('getUtils')->willReturn($utils);

        $node->expects(self::never())->method('setName');
        //$node->expectOnce('move');
        $this->expectException(MethodNotAllowed::class);

        $tree->move('project1/package1/release1', 'project1/package2/release2');
    }

    public function testCopyNoWriteEnabled(): void
    {
        $tree  = $this->getTestTree();
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(false);
        $tree->method('getUtils')->willReturn($utils);

        $this->expectException(MethodNotAllowed::class);
        $tree->copy('source', 'destination/item');
    }

    /**
     * Fail when destination is not a docman folder
     */
    public function testCopyWrongDestination(): void
    {
        $tree  = $this->getTestTree();
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(true);
        $tree->method('getUtils')->willReturn($utils);
        $destination = $this->getTestRelease();
        $tree->method('getNodeForPath')->with('destination')->willReturn($destination);
        $source = $this->getTestFolder($this->docman_folder);
        $tree->method('getNodeForPath')->with('destination')->willReturn($source);

        $this->expectException(MethodNotAllowed::class);
        $tree->copy('source', 'destination/item');
    }

    /**
     * Fail when source is not a docman folder
     */
    public function testCopyWrongSource(): void
    {
        $tree  = $this->getTestTree();
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(true);
        $tree->method('getUtils')->willReturn($utils);
        $destination = $this->getTestFolder($this->docman_folder);
        $tree->method('getNodeForPath')->with('destination')->willReturn($destination);
        $source = $this->getTestRelease();
        $tree->method('getNodeForPath')->with('destination')->willReturn($source);

        $this->expectException(MethodNotAllowed::class);
        $tree->copy('source', 'destination/item');
    }

    public function testCopyNotTheSameProject(): void
    {
        $sourceItem      = new \Docman_Folder(['group_id' => 1]);
        $destinationItem = new \Docman_Folder(['group_id' => 2]);

        $tree  = $this->getTestTree();
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(true);
        $tree->method('getUtils')->willReturn($utils);
        $destination = $this->getTestFolder($destinationItem);
        $tree->method('getNodeForPath')->with('destination')->willReturn($destination);
        $source = $this->getTestFolder($sourceItem);
        $tree->method('getNodeForPath')->with('source')->willReturn($source);

        $this->expectException(MethodNotAllowed::class);
        $tree->copy('source', 'destination/item');
    }

    public function testCopyNoReadOnSource(): void
    {
        $sourceItem      = new \Docman_Folder(['group_id' => 1]);
        $destinationItem = new \Docman_Folder(['group_id' => 2]);

        $tree  = $this->getTestTree();
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(true);
        $tree->method('getUtils')->willReturn($utils);
        $destination = $this->getTestFolder($destinationItem);
        $tree->method('getNodeForPath')->with('destination')->willReturn($destination);
        $source = $this->getTestFolder($sourceItem);
        $tree->method('getNodeForPath')->with('source')->willReturn($source);

        $dpm = $this->createMock(\Docman_PermissionsManager::class);
        $dpm->method('userCanAccess')->willReturn(false);
        $dpm->method('userCanWrite')->willReturn(true);
        $utils->method('getDocmanPermissionsManager')->willReturn($dpm);

        $this->expectException(MethodNotAllowed::class);
        $tree->copy('source', 'destination/item');
    }

    public function testCopyNoWriteOnDestination(): void
    {
        $sourceItem      = new \Docman_Folder(['group_id' => 1]);
        $destinationItem = new \Docman_Folder(['group_id' => 2]);

        $tree  = $this->getTestTree();
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(true);
        $tree->method('getUtils')->willReturn($utils);
        $destination = $this->getTestFolder($destinationItem);
        $tree->method('getNodeForPath')->with('destination')->willReturn($destination);
        $source = $this->getTestFolder($sourceItem);
        $tree->method('getNodeForPath')->with('source')->willReturn($source);

        $dpm = $this->createMock(\Docman_PermissionsManager::class);
        $dpm->method('userCanAccess')->willReturn(true);
        $dpm->method('userCanWrite')->willReturn(false);
        $utils->method('getDocmanPermissionsManager')->willReturn($dpm);

        $this->expectException(MethodNotAllowed::class);
        $tree->copy('source', 'destination/item');
    }

    public function testCopySucceede(): void
    {
        $sourceItem      = new \Docman_Folder(['group_id' => 1]);
        $destinationItem = new \Docman_Folder(['group_id' => 1]);

        $tree  = $this->getTestTree();
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(true);
        $tree->method('getUtils')->willReturn($utils);
        $destination = $this->getTestFolder($destinationItem);
        $tree->method('getNodeForPath')->with('destination')->willReturn($destination);
        $source = $this->getTestFolder($sourceItem);
        $tree->method('getNodeForPath')->with('source')->willReturn($source);

        $dpm = $this->createMock(\Docman_PermissionsManager::class);
        $dpm->method('userCanAccess')->willReturn(true);
        $dpm->method('userCanWrite')->willReturn(true);
        $utils->method('getDocmanPermissionsManager')->willReturn($dpm);
        $dif = $this->createMock(\Docman_ItemFactory::class);
        $utils->method('getDocmanItemFactory')->willReturn($dif);

        //self::assertNoErrors();
        $this->expectException(MethodNotAllowed::class);
        $tree->copy('source', 'destination/item');
    }

    public function testMoveDocmanSucceed(): void
    {
        $sourceItem      = new \Docman_Folder(['item_id' => 128, 'group_id' => 1]);
        $destinationItem = new \Docman_Folder(['item_id' => 256, 'group_id' => 1]);

        $tree  = $this->createPartialMock(WebDAVTree::class, ['getNodeForPath', 'getUtils']);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(true);
        $tree->method('getUtils')->willReturn($utils);
        $destination = $this->getTestFolder($destinationItem);
        $source      = $this->getTestFolder($sourceItem);
        $tree->method('getNodeForPath')->willReturnMap([
            ['destination', $destination],
            ['source', $source],
        ]);

        $dpm = $this->createMock(\Docman_PermissionsManager::class);
        $dpm->method('userCanAccess')->willReturn(true);
        $dpm->method('userCanWrite')->willReturn(true);
        $dpm->method('currentUserCanWriteSubItems')->willReturn(true);
        $utils->method('getDocmanPermissionsManager')->willReturn($dpm);
        $dif = $this->createMock(\Docman_ItemFactory::class);
        $utils->method('getDocmanItemFactory')->willReturn($dif);

        //$dif->expectOnce('setNewParent', array(128, 256, 'beginning'));
        //$sourceItem->expectOnce('fireEvent', array('plugin_docman_event_move', $source->getUser(), $destinationItem));

        //self::assertNoErrors();
        $this->expectException(MethodNotAllowed::class);
        $tree->move('source', 'destination/item');
    }

    public function testMoveDocmanNoWriteOnSubItems(): void
    {
        $sourceItem = $this->createMock(\Docman_Folder::class);
        $sourceItem->method('getGroupId')->willReturn(1);
        $destinationItem = $this->createMock(\Docman_Folder::class);
        $destinationItem->method('getGroupId')->willReturn(1);

        $tree  = $this->createPartialMock(WebDAVTree::class, ['getNodeForPath', 'getUtils']);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(true);
        $tree->method('getUtils')->willReturn($utils);
        $destination = $this->getTestFolder($destinationItem);
        $source      = $this->getTestFolder($sourceItem);
        $tree->method('getNodeForPath')->willReturnMap([
            ['destination', $destination],
            ['source', $source],
        ]);

        $dpm = $this->createMock(\Docman_PermissionsManager::class);
        $dpm->method('userCanAccess')->willReturn(true);
        $dpm->method('userCanWrite')->willReturn(true);
        $dpm->method('currentUserCanWriteSubItems')->willReturn(false);
        $utils->method('getDocmanPermissionsManager')->willReturn($dpm);
        $dif = $this->createMock(\Docman_ItemFactory::class);
        $utils->method('getDocmanItemFactory')->willReturn($dif);

        $dif->expects(self::never())->method('setNewParent');
        $sourceItem->expects(self::never())->method('fireEvent');

        $this->expectException(MethodNotAllowed::class);
        $tree->move('source', 'destination/item');
    }

    public function testMoveDocmanNoWriteEnabled(): void
    {
        $tree  = $this->getTestTree();
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(false);
        $tree->method('getUtils')->willReturn($utils);
        $utils->method('getDocmanItemFactory')->willReturn(null);

        $this->expectException(MethodNotAllowed::class);
        $tree->move('source', 'destination/item');
    }

    public function testMoveDocmanNotTheSameProject(): void
    {
        $sourceItem      = new \Docman_Folder(['group_id' => 1]);
        $destinationItem = new \Docman_Folder(['group_id' => 11]);

        $tree  = $this->createPartialMock(WebDAVTree::class, ['getNodeForPath', 'getUtils']);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(true);
        $utils->method('getDocmanItemFactory')->willReturn(null);
        $tree->method('getUtils')->willReturn($utils);
        $destination = $this->getTestFolder($destinationItem);
        $source      = $this->getTestFolder($sourceItem);
        $tree->method('getNodeForPath')->willReturnMap([
            ['destination', $destination],
            ['source', $source],
        ]);

        $this->expectException(MethodNotAllowed::class);
        $tree->move('source', 'destination/item');
    }

    public function testMoveDocmanNoReadOnSource(): void
    {
        $sourceItem      = new \Docman_Folder(['group_id' => 1]);
        $destinationItem = new \Docman_Folder(['group_id' => 1]);

        $tree  = $this->createPartialMock(WebDAVTree::class, ['getNodeForPath', 'getUtils']);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(true);
        $utils->method('getDocmanItemFactory')->willReturn(null);
        $tree->method('getUtils')->willReturn($utils);
        $destination = $this->getTestFolder($destinationItem);
        $source      = $this->getTestFolder($sourceItem);
        $tree->method('getNodeForPath')->willReturnMap([
            ['destination', $destination],
            ['source', $source],
        ]);

        $dpm = $this->createMock(\Docman_PermissionsManager::class);
        $dpm->method('userCanAccess')->willReturn(false);
        $dpm->method('userCanWrite')->willReturn(true);
        $utils->method('getDocmanPermissionsManager')->willReturn($dpm);
        $dpm->expects(self::never())->method('currentUserCanWriteSubItems');

        $this->expectException(MethodNotAllowed::class);
        $tree->move('source', 'destination/item');
    }

    public function testMoveDocmanNoWriteOnDestination(): void
    {
        $sourceItem      = new \Docman_Folder(['group_id' => 1]);
        $destinationItem = new \Docman_Folder(['group_id' => 1]);

        $tree  = $this->createPartialMock(WebDAVTree::class, ['getNodeForPath', 'getUtils']);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(true);
        $utils->method('getDocmanItemFactory')->willReturn(null);
        $tree->method('getUtils')->willReturn($utils);
        $destination = $this->getTestFolder($destinationItem);
        $source      = $this->getTestFolder($sourceItem);
        $tree->method('getNodeForPath')->willReturnMap([
            ['destination', $destination],
            ['source', $source],
        ]);

        $dpm = $this->createMock(\Docman_PermissionsManager::class);
        $dpm->method('userCanAccess')->willReturn(true);
        $dpm->method('userCanWrite')->willReturn(false);
        $utils->method('getDocmanPermissionsManager')->willReturn($dpm);
        $dpm->expects(self::never())->method('currentUserCanWriteSubItems');

        $this->expectException(MethodNotAllowed::class);
        $tree->move('source', 'destination/item');
    }

    public function testMoveDocmanWrongDestinationItemType(): void
    {
        $sourceItem = new \Docman_Folder(['group_id' => 1]);

        $tree  = $this->createPartialMock(WebDAVTree::class, ['getNodeForPath', 'getUtils']);
        $utils = $this->createMock(\WebDAVUtils::class);
        $utils->method('isWriteEnabled')->willReturn(true);
        $utils->method('getDocmanItemFactory')->willReturn(null);
        $tree->method('getUtils')->willReturn($utils);
        $destination = new \WebDAVDocmanFile($this->user, $this->project, new \Docman_File(), $this->createMock(DocumentDownloader::class), $utils);
        $source      = $this->getTestFolder($sourceItem);
        $tree->method('getNodeForPath')->willReturnMap([
            ['destination', $destination],
            ['source', $source],
        ]);

        $this->expectException(MethodNotAllowed::class);
        $tree->move('source', 'destination/item');
    }

    /**
     * @return WebDAVTree&\PHPUnit\Framework\MockObject\MockObject
     */
    private function getTestTree()
    {
        $tree = $this->createPartialMock(WebDAVTree::class, ['getNodeForPath', 'getUtils']);
        $tree->method('getNodeForPath')->willReturn($this->createMock(\WebDAVFRSFile::class));

        return $tree;
    }

    private function getTestFile(): WebDAVFRSFile
    {
        return new WebDAVFRSFile(
            $this->user,
            ProjectTestBuilder::aProject()->withId(1)->build(),
            new \FRSFile([]),
            $this->createMock(\WebDAVUtils::class)
        );
    }

    /**
     * @return WebDAVFRSRelease&\PHPUnit\Framework\MockObject\MockObject
     */
    private function getTestRelease()
    {
        return $this->getMockBuilder(WebDAVFRSRelease::class)
            ->setConstructorArgs([
                $this->user,
                ProjectTestBuilder::aProject()->withId(1)->build(),
                $this->package,
                $this->release,
                0,
            ])
            ->onlyMethods([])
            ->getMock();
    }

    /**
     * @return WebDAVFRSRelease&\PHPUnit\Framework\MockObject\MockObject
     */
    private function getTestRelease2()
    {
        return $this->getMockBuilder(WebDAVFRSRelease::class)
            ->setConstructorArgs([
                $this->user,
                ProjectTestBuilder::aProject()->withId(2)->build(),
                $this->package,
                $this->release,
                0,
            ])
            ->onlyMethods([])
            ->getMock();
    }

    private function getTestFolder(\Docman_Folder $item): WebDAVDocmanFolder
    {
        return new WebDAVDocmanFolder($this->user, $this->project, $item, \WebDAVUtils::getInstance());
    }

    /**
     * @return WebDAVFRSPackage&\PHPUnit\Framework\MockObject\MockObject
     */
    private function getTestPackage()
    {
        return $this->getMockBuilder(\WebDAVFRSPackage::class)
            ->setConstructorArgs([
                $this->user,
                ProjectTestBuilder::aProject()->withId(1)->build(),
                $this->package,
                0,
            ])
            ->onlyMethods(['getReleaseList'])
            ->getMock();
    }
}

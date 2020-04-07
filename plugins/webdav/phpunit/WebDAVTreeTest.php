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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;
use WebDAVDocmanFolder;
use WebDAVFRSFile;
use WebDAVFRSPackage;
use WebDAVFRSRelease;
use WebDAVTree;

require_once __DIR__ . '/bootstrap.php';

final class WebDAVTreeTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    private $user;
    private $project;
    private $package;
    private $release;
    private $file;
    private $docman_folder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user            = \Mockery::spy(\PFUser::class);
        $this->project         = \Mockery::spy(\Project::class)->shouldReceive('getID')->with()->andReturns(101)->getMock();
        $this->package         = new FRSPackage();
        $this->release         = \Mockery::spy(FRSRelease::class);
        $this->file            = \Mockery::spy(\FRSFile::class);
        $this->docman_folder   = \Mockery::spy(\Docman_Folder::class);

        $docman_item_factory = \Mockery::spy(\Docman_ItemFactory::class)->shouldReceive('getItemFromDb')->with()->andReturns(\Mockery::spy(\Docman_Item::class));
        Docman_ItemFactory::setInstance(101, $docman_item_factory);
    }

    protected function tearDown(): void
    {
        Docman_ItemFactory::clearInstance(101);
        parent::tearDown();
    }

    public function testCanBeMovedFailNotMovable(): void
    {
        $source = null;
        $destination = null;
        $tree = $this->getTestTree();

        $this->assertFalse($tree->canBeMoved($source, $destination));
    }

    public function testCanBeMovedFailSourceNotReleaseDestinationPackage(): void
    {
        $source = null;
        $destination = $this->getTestPackage();
        $tree = $this->getTestTree();

        $this->assertFalse($tree->canBeMoved($source, $destination));
    }

    public function testCanBeMovedFailSourceNotFileDestinationRelease(): void
    {
        $source = null;
        $destination = $this->getTestRelease();
        $tree = $this->getTestTree();

        $this->assertFalse($tree->canBeMoved($source, $destination));
    }

    public function testCanBeMovedFailSourceReleaseDestinationNotPackage(): void
    {
        $source = $this->getTestRelease();
        $destination = null;
        $tree = $this->getTestTree();

        $this->assertFalse($tree->canBeMoved($source, $destination));
    }

    public function testCanBeMovedFailSourceFileDestinationNotRelease(): void
    {
        $source = $tree = $this->getTestFile();
        $destination = null;
        $tree = $this->getTestTree();

        $this->assertFalse($tree->canBeMoved($source, $destination));
    }

    public function testCanBeMovedFailSourceReleaseDestinationPackageNotSameProject(): void
    {
        $source = $this->getTestRelease2();
        $destination = $this->getTestPackage();
        $tree = $this->getTestTree();

        $this->assertFalse($tree->canBeMoved($source, $destination));
    }

    public function testCanBeMovedFailSourceFileDestinationReleaseNotSameProject(): void
    {
        $source = $tree = $this->getTestFile();
        $destination = $this->getTestRelease2();
        $tree = $this->getTestTree();

        $this->assertFalse($tree->canBeMoved($source, $destination));
    }

    public function testCanBeMovedSucceedeSourceReleaseDestinationPackage(): void
    {
        $source = $this->getTestRelease();
        $destination = $this->getTestPackage();
        $tree = $this->getTestTree();

        $this->assertTrue($tree->canBeMoved($source, $destination));
    }

    public function testCanBeMovedSucceedeSourceFileDestinationRelease(): void
    {
        $source = Mockery::mock(
            WebDAVFRSFile::class,
            [$this->user, $this->project, $this->package, $this->release, $this->file]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->project->shouldReceive('getGroupId')->andReturns(1);

        $destination = $this->getTestRelease();
        $tree = $this->getTestTree();

        $this->assertTrue($tree->canBeMoved($source, $destination));
    }

    public function testMoveOnlyRename(): void
    {
        $node = \Mockery::spy(\WebDAVFRSRelease::class);
        $tree = Mockery::mock(WebDAVTree::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $tree->shouldReceive('canBeMoved')->andReturns(true);
        $tree->shouldReceive('getNodeForPath')->andReturns($node);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $tree->shouldReceive('getUtils')->andReturns($utils);

        $node->shouldReceive('setName')->once();
        $node->shouldReceive('move')->never();

        $tree->move('project1/package1/release1', 'project1/package1/release2');
    }

    public function testMoveCanNotMove(): void
    {
        $node = \Mockery::spy(\WebDAVFRSRelease::class);
        $tree = $this->getTestTree();
        $tree->shouldReceive('canBeMoved')->andReturns(false);
        $tree->shouldReceive('getNodeForPath')->andReturns($node);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $tree->shouldReceive('getUtils')->andReturns($utils);

        $node->shouldReceive('setName')->never();
        $node->shouldReceive('move')->never();
        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');

        $tree->move('project1/package1/release1', 'project1/package2/release2');
    }

    public function testMoveSucceed(): void
    {
        $node = \Mockery::spy(\WebDAVFRSRelease::class);
        $tree = $this->getTestTree();
        $tree->shouldReceive('canBeMoved')->andReturns(true);
        $tree->shouldReceive('getNodeForPath')->andReturns($node);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $tree->shouldReceive('getUtils')->andReturns($utils);

        $node->shouldReceive('setName')->never();
        //$node->expectOnce('move');
        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');

        $tree->move('project1/package1/release1', 'project1/package2/release2');
    }

    public function testCopyNoWriteEnabled(): void
    {
        $tree = $this->getTestTree();
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(false);
        $tree->shouldReceive('getUtils')->andReturns($utils);

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->copy('source', 'destination/item');
    }

    /**
     * Fail when destination is not a docman folder
     */
    public function testCopyWrongDestination(): void
    {
        $tree = $this->getTestTree();
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $tree->shouldReceive('getUtils')->andReturns($utils);
        $destination = $this->getTestRelease();
        $tree->shouldReceive('getNodeForPath')->with('destination')->andReturns($destination);
        $source = $this->getTestFolder($this->docman_folder);
        $tree->shouldReceive('getNodeForPath')->with('destination')->andReturns($source);

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->copy('source', 'destination/item');
    }

    /**
     * Fail when source is not a docman folder
     */
    public function testCopyWrongSource(): void
    {
        $tree = $this->getTestTree();
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $tree->shouldReceive('getUtils')->andReturns($utils);
        $destination = $this->getTestFolder($this->docman_folder);
        $tree->shouldReceive('getNodeForPath')->with('destination')->andReturns($destination);
        $source = $this->getTestRelease();
        $tree->shouldReceive('getNodeForPath')->with('destination')->andReturns($source);

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->copy('source', 'destination/item');
    }

    public function testCopyNotTheSameProject(): void
    {
        $sourceItem = \Mockery::spy(\Docman_Item::class);
        $sourceItem->shouldReceive('getGroupId')->andReturns(1);
        $destinationItem = \Mockery::spy(\Docman_Item::class);
        $destinationItem->shouldReceive('getGroupId')->andReturns(2);

        $tree = $this->getTestTree();
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $tree->shouldReceive('getUtils')->andReturns($utils);
        $destination = $this->getTestFolder($destinationItem);
        $tree->shouldReceive('getNodeForPath')->with('destination')->andReturns($destination);
        $source = $this->getTestFolder($sourceItem);
        $tree->shouldReceive('getNodeForPath')->with('source')->andReturns($source);

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->copy('source', 'destination/item');
    }

    public function testCopyNoReadOnSource(): void
    {
        $sourceItem = \Mockery::spy(\Docman_Item::class);
        $sourceItem->shouldReceive('getGroupId')->andReturns(1);
        $destinationItem = \Mockery::spy(\Docman_Item::class);
        $destinationItem->shouldReceive('getGroupId')->andReturns(1);

        $tree = $this->getTestTree();
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $tree->shouldReceive('getUtils')->andReturns($utils);
        $destination = $this->getTestFolder($destinationItem);
        $tree->shouldReceive('getNodeForPath')->with('destination')->andReturns($destination);
        $source = $this->getTestFolder($sourceItem);
        $tree->shouldReceive('getNodeForPath')->with('source')->andReturns($source);

        $dpm = \Mockery::spy(\Docman_PermissionsManager::class);
        $dpm->shouldReceive('userCanAccess')->andReturns(false);
        $dpm->shouldReceive('userCanWrite')->andReturns(true);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($dpm);

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->copy('source', 'destination/item');
    }

    public function testCopyNoWriteOnDestination(): void
    {
        $sourceItem = \Mockery::spy(\Docman_Item::class);
        $sourceItem->shouldReceive('getGroupId')->andReturns(1);
        $destinationItem = \Mockery::spy(\Docman_Item::class);
        $destinationItem->shouldReceive('getGroupId')->andReturns(1);

        $tree = $this->getTestTree();
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $tree->shouldReceive('getUtils')->andReturns($utils);
        $destination = $this->getTestFolder($destinationItem);
        $tree->shouldReceive('getNodeForPath')->with('destination')->andReturns($destination);
        $source = $this->getTestFolder($sourceItem);
        $tree->shouldReceive('getNodeForPath')->with('source')->andReturns($source);

        $dpm = \Mockery::spy(\Docman_PermissionsManager::class);
        $dpm->shouldReceive('userCanAccess')->andReturns(true);
        $dpm->shouldReceive('userCanWrite')->andReturns(false);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($dpm);

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->copy('source', 'destination/item');
    }

    public function testCopySucceede(): void
    {
        $sourceItem = \Mockery::spy(\Docman_Item::class);
        $sourceItem->shouldReceive('getGroupId')->andReturns(1);
        $destinationItem = \Mockery::spy(\Docman_Item::class);
        $destinationItem->shouldReceive('getGroupId')->andReturns(1);

        $tree = $this->getTestTree();
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $tree->shouldReceive('getUtils')->andReturns($utils);
        $destination = $this->getTestFolder($destinationItem);
        $tree->shouldReceive('getNodeForPath')->with('destination')->andReturns($destination);
        $source = $this->getTestFolder($sourceItem);
        $tree->shouldReceive('getNodeForPath')->with('source')->andReturns($source);

        $dpm = \Mockery::spy(\Docman_PermissionsManager::class);
        $dpm->shouldReceive('userCanAccess')->andReturns(true);
        $dpm->shouldReceive('userCanWrite')->andReturns(true);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($dpm);
        $dif = \Mockery::spy(\Docman_ItemFactory::class);
        $utils->shouldReceive('getDocmanItemFactory')->andReturns($dif);

        //$this->assertNoErrors();
        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->copy('source', 'destination/item');
    }

    public function testMoveDocmanSucceed(): void
    {
        $sourceItem = \Mockery::spy(\Docman_Item::class);
        $sourceItem->shouldReceive('getGroupId')->andReturns(1);
        $sourceItem->shouldReceive('getId')->andReturns(128);
        $destinationItem = \Mockery::spy(\Docman_Item::class);
        $destinationItem->shouldReceive('getGroupId')->andReturns(1);
        $destinationItem->shouldReceive('getId')->andReturns(256);

        $tree = $this->getTestTree();
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $tree->shouldReceive('getUtils')->andReturns($utils);
        $destination = $this->getTestFolder($destinationItem);
        $tree->shouldReceive('getNodeForPath')->with('destination')->andReturns($destination);
        $source = $this->getTestFolder($sourceItem);
        $tree->shouldReceive('getNodeForPath')->with('source')->andReturns($source);

        $dpm = \Mockery::spy(\Docman_PermissionsManager::class);
        $dpm->shouldReceive('userCanAccess')->andReturns(true);
        $dpm->shouldReceive('userCanWrite')->andReturns(true);
        $dpm->shouldReceive('currentUserCanWriteSubItems')->andReturns(true);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($dpm);
        $dif = \Mockery::spy(\Docman_ItemFactory::class);
        $utils->shouldReceive('getDocmanItemFactory')->andReturns($dif);

        //$dif->expectOnce('setNewParent', array(128, 256, 'beginning'));
        //$sourceItem->expectOnce('fireEvent', array('plugin_docman_event_move', $source->getUser(), $destinationItem));

        //$this->assertNoErrors();
        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->move('source', 'destination/item');
    }

    public function testMoveDocmanNoWriteOnSubItems(): void
    {
        $sourceItem = \Mockery::spy(\Docman_Item::class);
        $sourceItem->shouldReceive('getGroupId')->andReturns(1);
        $destinationItem = \Mockery::spy(\Docman_Item::class);
        $destinationItem->shouldReceive('getGroupId')->andReturns(1);

        $tree = $this->getTestTree();
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $tree->shouldReceive('getUtils')->andReturns($utils);
        $destination = $this->getTestFolder($destinationItem);
        $tree->shouldReceive('getNodeForPath')->with('destination')->andReturns($destination);
        $source = $this->getTestFolder($sourceItem);
        $tree->shouldReceive('getNodeForPath')->with('source')->andReturns($source);

        $dpm = \Mockery::spy(\Docman_PermissionsManager::class);
        $dpm->shouldReceive('userCanAccess')->andReturns(true);
        $dpm->shouldReceive('userCanWrite')->andReturns(true);
        $dpm->shouldReceive('currentUserCanWriteSubItems')->andReturns(false);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($dpm);
        $dif = \Mockery::spy(\Docman_ItemFactory::class);
        $utils->shouldReceive('getDocmanItemFactory')->andReturns($dif);

        $dif->shouldReceive('setNewParent')->never();
        $sourceItem->shouldReceive('fireEvent')->never();

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->move('source', 'destination/item');
    }

    public function testMoveDocmanNoWriteEnabled(): void
    {
        $tree = $this->getTestTree();
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(false);
        $tree->shouldReceive('getUtils')->andReturns($utils);

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->move('source', 'destination/item');
    }

    public function testMoveDocmanNotTheSameProject(): void
    {
        $sourceItem = \Mockery::spy(\Docman_Item::class);
        $sourceItem->shouldReceive('getGroupId')->andReturns(1);
        $destinationItem = \Mockery::spy(\Docman_Item::class);
        $destinationItem->shouldReceive('getGroupId')->andReturns(11);

        $tree = $this->getTestTree();
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $tree->shouldReceive('getUtils')->andReturns($utils);
        $destination = $this->getTestFolder($destinationItem);
        $tree->shouldReceive('getNodeForPath')->with('destination')->andReturns($destination);
        $source = $this->getTestFolder($sourceItem);
        $tree->shouldReceive('getNodeForPath')->with('source')->andReturns($source);


        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->move('source', 'destination/item');
    }

    public function testMoveDocmanNoReadOnSource(): void
    {
        $sourceItem = \Mockery::spy(\Docman_Item::class);
        $sourceItem->shouldReceive('getGroupId')->andReturns(1);
        $destinationItem = \Mockery::spy(\Docman_Item::class);
        $destinationItem->shouldReceive('getGroupId')->andReturns(1);

        $tree = $this->getTestTree();
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $tree->shouldReceive('getUtils')->andReturns($utils);
        $destination = $this->getTestFolder($destinationItem);
        $tree->shouldReceive('getNodeForPath')->with('destination')->andReturns($destination);
        $source = $this->getTestFolder($sourceItem);
        $tree->shouldReceive('getNodeForPath')->with('source')->andReturns($source);

        $dpm = \Mockery::spy(\Docman_PermissionsManager::class);
        $dpm->shouldReceive('userCanAccess')->andReturns(false);
        $dpm->shouldReceive('userCanWrite')->andReturns(true);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($dpm);
        $dpm->shouldReceive('currentUserCanWriteSubItems')->never();

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->move('source', 'destination/item');
    }

    public function testMoveDocmanNoWriteOnDestination(): void
    {
        $sourceItem = \Mockery::spy(\Docman_Item::class);
        $sourceItem->shouldReceive('getGroupId')->andReturns(1);
        $destinationItem = \Mockery::spy(\Docman_Item::class);
        $destinationItem->shouldReceive('getGroupId')->andReturns(1);

        $tree = $this->getTestTree();
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $tree->shouldReceive('getUtils')->andReturns($utils);
        $destination = $this->getTestFolder($destinationItem);
        $tree->shouldReceive('getNodeForPath')->with('destination')->andReturns($destination);
        $source = $this->getTestFolder($sourceItem);
        $tree->shouldReceive('getNodeForPath')->with('source')->andReturns($source);

        $dpm = \Mockery::spy(\Docman_PermissionsManager::class);
        $dpm->shouldReceive('userCanAccess')->andReturns(true);
        $dpm->shouldReceive('userCanWrite')->andReturns(false);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($dpm);
        $dpm->shouldReceive('currentUserCanWriteSubItems')->never();

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->move('source', 'destination/item');
    }

    public function testMoveDocmanWrongDestinationItemType(): void
    {
        $sourceItem = \Mockery::spy(\Docman_Item::class);
        $destinationItem = \Mockery::spy(\Docman_Item::class);

        $tree = $this->getTestTree();
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $tree->shouldReceive('getUtils')->andReturns($utils);
        $destination = $this->getTestFolder($destinationItem);
        $tree->shouldReceive('getNodeForPath')->with('destination')->andReturns($destination);
        $source = $this->getTestFolder($sourceItem);
        $tree->shouldReceive('getNodeForPath')->with('source')->andReturns($source);


        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->move('source', 'destination/item');
    }

    private function getTestTree()
    {
        $tree = Mockery::mock(WebDAVTree::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $tree->shouldReceive('getNodeForPath')->andReturn(\Mockery::spy(\WebDAVFRSFile::class));

        return $tree;
    }

    private function getTestFile()
    {
        $file = Mockery::mock(WebDAVFRSFile::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getGroupId')->andReturns(1);
        $file->shouldReceive('getProject')->andReturn($project);

        return $file;
    }

    private function getTestRelease()
    {
        $release = Mockery::mock(
            WebDAVFRSRelease::class,
            [$this->user, $this->project, $this->package, $this->release, 0]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getGroupId')->andReturns(1);
        $release->shouldReceive('getProject')->andReturn($project);

        return $release;
    }

    private function getTestRelease2()
    {
        $release = Mockery::mock(
            WebDAVFRSRelease::class,
            [$this->user, $this->project, $this->package, $this->release, 0]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getGroupId')->andReturns(2);
        $release->shouldReceive('getProject')->andReturn($project);

        return $release;
    }

    private function getTestFolder($item)
    {
        $folder = Mockery::mock(
            WebDAVDocmanFolder::class,
            [$this->user, $this->project, $item]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        return $folder;
    }

    private function getTestPackage()
    {
        $package = Mockery::mock(
            WebDAVFRSPackage::class,
            [$this->user, $this->project, $this->package, 0]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getGroupId')->andReturns(1);
        $package->shouldReceive('getProject')->andReturn($project);

        return $package;
    }
}

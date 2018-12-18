<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\Docman\REST\v1;

use Docman_Item;
use Docman_ItemFactory;
use Docman_PermissionsManager;
use Mockery;
use PFUser;
use Tuleap\Docman\Item\PaginatedDocmanItemCollection;
use Tuleap\User\REST\MinimalUserRepresentation;
use UserManager;

class ItemRepresentationCollectionBuilderTest extends \PHPUnit\Framework\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    /**
     * @var ItemRepresentationBuilder
     */
    private $item_representation_builder;
    /**
     * @var \Docman_ItemDao
     */
    private $dao;
    /**
     * @var ItemRepresentationVisitor
     */
    private $item_representation_visitor;
    /**
     * @var Docman_ItemFactory
     */
    private $item_factory;
    /**
     * @var Docman_PermissionsManager
     */
    private $permission_manager;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var ItemRepresentationCollectionBuilder
     */
    private $item_representation_collection_builder;

    /**
     * @var Docman_ItemFactory
     */
    private $item_version_factory;

    /**
     * @var \Docman_LinkVersionFactory
     */
    private $link_version_factory;

    protected function setUp()
    {
        parent::setUp();
        $this->user_manager                           = Mockery::mock(UserManager::class);
        $this->item_factory                           = Mockery::mock(Docman_ItemFactory::class);
        $this->permission_manager                     = Mockery::mock(Docman_PermissionsManager::class);
        $this->item_representation_builder            = Mockery::mock(ItemRepresentationBuilder::class);
        $this->item_version_factory                   = Mockery::mock(\Docman_VersionFactory::class);
        $this->link_version_factory                   = Mockery::mock(\Docman_LinkVersionFactory::class);
        $this->item_representation_visitor            = new ItemRepresentationVisitor(
            $this->item_representation_builder,
            $this->item_version_factory,
            $this->link_version_factory
        );
        $this->dao                                    = Mockery::mock(\Docman_ItemDao::class);
        $this->item_representation_collection_builder = new ItemRepresentationCollectionBuilder(
            $this->item_factory,
            $this->permission_manager,
            $this->item_representation_visitor,
            $this->dao
        );
    }

    public function testItReturnsRepresentationOfItemUserCanSee()
    {
        $item = Mockery::mock(Docman_Item::class);
        $item->shouldReceive('getId')->andReturn(3);
        $item->shouldReceive('getGroupId')->andReturn(101);
        $user = Mockery::mock(PFUser::class);


        $dar_item_1 = [
            'item_id'     => 1,
            'title'       => 'folder 1',
            'user_id'     => 101,
            'update_date' => 1542099693,
            'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        ];
        $dar_item_2 = [
            'item_id'     => 2,
            'title'       => 'item A',
            'user_id'     => 101,
            'update_date' => 1542099693,
            'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_WIKI

        ];
        $dar_item_3 = [
            'item_id'     => 3,
            'title'       => 'item B',
            'user_id'     => 101,
            'update_date' => 1542099693,
            'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_FILE
        ];

        $this->dao->shouldReceive('searchByParentIdWithPagination')->andReturn(
            [
                $dar_item_1,
                $dar_item_2,
                $dar_item_3
            ]
        );
        $this->dao->shouldReceive("foundRows")->andReturn(3);

        $docman_item1 = new \Docman_Folder($dar_item_1);
        $docman_item3 = new \Docman_File($dar_item_3);

        $this->item_factory->shouldReceive("getItemFromRow")->andReturn($docman_item1, $docman_item3);

        $this->permission_manager->shouldReceive("userCanRead")->withArgs([$user, 1])->andReturns(true);
        $this->permission_manager->shouldReceive("userCanRead")->withArgs([$user, 2])->andReturns(false);
        $this->permission_manager->shouldReceive("userCanRead")->withArgs([$user, 3])->andReturns(true);

        $user_representation = Mockery::mock(MinimalUserRepresentation::class);
        $representation1     = new ItemRepresentation();
        $representation1->build(
            $docman_item1,
            $user_representation,
            true,
            ItemRepresentation::TYPE_FOLDER,
            null
        );

        $version_data_item3   = [
            'id'        => 1,
            'authorId'  => 101,
            'itemId'    => 3,
            'number'    => null,
            'label'     => null,
            'changeLog' => null,
            'date'      => null,
            'filename'  => 'item B',
            'filesize'  => null,
            'filetype'  => 'application/pdf',
            'path'      => null,
            '_content'  => null,
        ];
        $docman_version_item3 = new \Docman_Version($version_data_item3);
        $file_properties      = new FilePropertiesRepresentation();
        $file_properties->build(
            $docman_version_item3,
            '/plugins/docman/?group_id=' . urlencode($item->getGroupId()) . '&action=show&id=' . urlencode($docman_version_item3->getItemId())
        );

        $representation2 = new ItemRepresentation();
        $representation2->build(
            $docman_item3,
            $user_representation,
            true,
            ItemRepresentation::TYPE_FILE,
            $file_properties
        );

        $this->item_representation_builder->shouldReceive('buildItemRepresentation')
            ->withArgs([$docman_item1, $user, ItemRepresentation::TYPE_FOLDER, null, null, null])
            ->andReturns($representation1);

        $this->item_version_factory->shouldReceive('getCurrentVersionForItem')
            ->withArgs([$docman_item3])
            ->andReturns($docman_version_item3);

        $file_properties2 = new FilePropertiesRepresentation();
        $file_properties2->build(
            $docman_version_item3,
            '/plugins/docman/?group_id=' . urlencode($item->getGroupId()) . '&action=show&id=' . urlencode($docman_version_item3->getItemId())
        );
        $this->item_representation_builder->shouldReceive('buildItemRepresentation')
            ->withArgs([$docman_item3, $user, ItemRepresentation::TYPE_FILE, Mockery::any(), null, null, null])
            ->andReturns($representation2);

        $representation = $this->item_representation_collection_builder->buildFolderContent($item, $user, 50, 0);

        $expected_representation = new PaginatedDocmanItemCollection([$representation1, $representation2], 3);

        $this->assertEquals($expected_representation, $representation);
    }

    public function testItReturnsRepresentationOfParentsItems()
    {
        $dar_folder_1    = [
            'item_id'     => 2,
            'title'       => 'folder 1',
            'user_id'     => 101,
            'update_date' => 1542099693,
            'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER,
            'parent_id'   => 0
        ];
        $dar_folder_2    = [
            'item_id'     => 3,
            'title'       => 'folder 2',
            'user_id'     => 101,
            'update_date' => 1542099693,
            'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_WIKI,
            'parent_id'   => 1
        ];
        $dar_item        = [
            'item_id'     => 4,
            'title'       => 'item',
            'user_id'     => 101,
            'update_date' => 1542099693,
            'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_WIKI,
            'parent_id'   => 2
        ];

        $docman_folder1     = new \Docman_Folder($dar_folder_1);
        $docman_folder2     = new \Docman_Folder($dar_folder_2);
        $item               = new \Docman_File($dar_item);

        $user                = Mockery::mock(PFUser::class);
        $user_representation = Mockery::mock(MinimalUserRepresentation::class);

        $this->permission_manager->shouldReceive('userCanRead')->andReturns(true);

        $this->item_factory->shouldReceive('getItemFromDb')->withArgs([$item->getParentId()])->andReturn($docman_folder2);
        $this->item_factory->shouldReceive('getItemFromDb')->withArgs([$docman_folder2->getParentId()])->andReturn($docman_folder1);

        $project         = Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(101);
        $representation1 = new ItemRepresentation();
        $representation1->build(
            $docman_folder1,
            $user_representation,
            true,
            ItemRepresentation::TYPE_FOLDER
        );
        $representation2 = new ItemRepresentation();
        $representation2->build(
            $docman_folder2,
            $user_representation,
            true,
            ItemRepresentation::TYPE_FOLDER
        );

        $this->item_representation_builder->shouldReceive('buildItemRepresentation')
            ->withArgs([$docman_folder1, $user, ItemRepresentation::TYPE_FOLDER, null, null, null])
                                          ->andReturns($representation1);
        $this->item_representation_builder->shouldReceive('buildItemRepresentation')
            ->withArgs([$docman_folder2, $user, ItemRepresentation::TYPE_FOLDER, null, null, null])
                                          ->andReturns($representation2);

        $representation = $this->item_representation_collection_builder->buildParents($item, $user, $project, 50, 0);

        $expected_representation = new PaginatedDocmanItemCollection([$representation1, $representation2], 2);

        $this->assertEquals($expected_representation, $representation);
    }

    public function testItReturnsAnEmptyCollectionForRootFolderParents()
    {
        $user = Mockery::mock(PFUser::class);

        $dar_item = [
            'item_id'     => 4,
            'title'       => 'item',
            'user_id'     => 101,
            'update_date' => 1542099693,
            'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_WIKI,
            'parent_id'   => 0
        ];
        $item     = new \Docman_File($dar_item);

        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(101);

        $this->permission_manager->shouldReceive('userCanRead')->andReturns(true);


        $representation = $this->item_representation_collection_builder->buildParents($item, $user, $project, 50, 0);

        $expected_representation = new PaginatedDocmanItemCollection([], 0);

        $this->assertEquals($expected_representation, $representation);
    }
}

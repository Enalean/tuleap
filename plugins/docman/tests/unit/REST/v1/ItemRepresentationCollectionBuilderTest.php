<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

use Codendi_HTMLPurifier;
use Docman_Item;
use Docman_ItemFactory;
use Docman_PermissionsManager;
use Mockery;
use PFUser;
use Tuleap\Docman\Item\PaginatedDocmanItemCollection;
use Tuleap\Docman\Item\PaginatedParentRowCollection;
use Tuleap\Docman\REST\v1\Files\FilePropertiesRepresentation;
use Tuleap\Docman\REST\v1\Folders\FolderPropertiesRepresentation;
use Tuleap\Docman\REST\v1\Folders\ParentFolderRepresentation;
use Tuleap\Docman\REST\v1\Metadata\ItemMetadataRepresentation;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\User\REST\MinimalUserRepresentation;

final class ItemRepresentationCollectionBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \EventManager|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $event_manager;
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->item_factory                = Mockery::mock(Docman_ItemFactory::class);
        $this->permission_manager          = Mockery::mock(Docman_PermissionsManager::class);
        $this->item_representation_builder = Mockery::mock(ItemRepresentationBuilder::class);
        $this->item_version_factory        = Mockery::mock(\Docman_VersionFactory::class);
        $this->link_version_factory        = Mockery::mock(\Docman_LinkVersionFactory::class);
        $this->event_manager               = Mockery::mock(\EventManager::class);
        $event_adder                       = $this->createMock(DocmanItemsEventAdder::class);
        $event_adder->expects(self::once())->method('addLogEvents');
        $this->item_representation_visitor            = new ItemRepresentationVisitor(
            $this->item_representation_builder,
            $this->item_version_factory,
            $this->link_version_factory,
            $this->item_factory,
            $this->event_manager,
            $event_adder
        );
        $this->dao                                    = Mockery::mock(\Docman_ItemDao::class);
        $this->item_representation_collection_builder = new ItemRepresentationCollectionBuilder(
            $this->item_factory,
            $this->permission_manager,
            $this->item_representation_visitor,
            $this->dao
        );
    }

    public function testItReturnsRepresentationOfItemUserCanSee(): void
    {
        $item = Mockery::mock(Docman_Item::class);
        $item->shouldReceive('getId')->andReturn(3);
        $item->shouldReceive('getGroupId')->andReturn(101);
        $user          = Mockery::mock(PFUser::class);
        $html_purifier = Mockery::mock(Codendi_HTMLPurifier::class);
        $html_purifier->shouldReceive('purifyTextWithReferences')->atLeast()->once()
            ->andReturn('description with processed ref');

        $dar_item_1 = [
            'item_id'     => 1,
            'title'       => 'folder 1',
            'user_id'     => 101,
            'update_date' => 1542099693,
            'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER,
        ];
        $dar_item_2 = [
            'item_id'     => 2,
            'title'       => 'item A',
            'user_id'     => 101,
            'update_date' => 1542099693,
            'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_WIKI,

        ];
        $dar_item_3 = [
            'item_id'     => 3,
            'title'       => 'item B',
            'user_id'     => 101,
            'update_date' => 1542099693,
            'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_FILE,
        ];

        $this->dao->shouldReceive('searchByParentIdWithPagination')->andReturn(
            [
                $dar_item_1,
                $dar_item_2,
                $dar_item_3,
            ]
        );
        $this->dao->shouldReceive("foundRows")->andReturn(3);

        $docman_item1 = new \Docman_Folder($dar_item_1);
        $docman_item3 = new \Docman_File($dar_item_3);

        $this->item_factory->shouldReceive("getItemFromRow")->andReturn($docman_item1, $docman_item3);

        $this->permission_manager->shouldReceive("userCanRead")->withArgs([$user, 1])->andReturns(true);
        $this->permission_manager->shouldReceive("userCanRead")->withArgs([$user, 2])->andReturns(false);
        $this->permission_manager->shouldReceive("userCanRead")->withArgs([$user, 3])->andReturns(true);

        $this->permission_manager->shouldReceive('userCanManage')
            ->withArgs([$user, 1])
            ->andReturns(true);
        $this->permission_manager->shouldReceive('userCanManage')
            ->withArgs([$user, 2])
            ->andReturns(false);
        $this->permission_manager->shouldReceive('userCanManage')
            ->withArgs([$user, 3])
            ->andReturns(false);

        $user_representation = Mockery::mock(MinimalUserRepresentation::class);
        $representation1     = ItemRepresentation::build(
            $docman_item1,
            $html_purifier,
            $user_representation,
            true,
            true,
            ItemRepresentation::TYPE_FOLDER,
            false,
            true,
            [
                new ItemMetadataRepresentation(
                    'name',
                    PLUGIN_DOCMAN_METADATA_TYPE_STRING,
                    false,
                    'value',
                    'processed value',
                    [],
                    true,
                    "name"
                ),
            ],
            false,
            false,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            FolderPropertiesRepresentation::build($docman_item1)
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
            'filesize'  => 48000,
            'filetype'  => 'application/pdf',
            'path'      => null,
            '_content'  => null,
        ];
        $docman_version_item3 = new \Docman_Version($version_data_item3);
        $file_properties      = FilePropertiesRepresentation::build(
            $docman_version_item3,
            '/plugins/docman/?group_id=' . urlencode((string) $item->getGroupId()) . '&action=show&id=' . urlencode((string) $docman_version_item3->getItemId()),
            'open/href'
        );

        $this->event_manager
            ->shouldReceive('dispatch')
            ->andReturnArg(0);

        $representation2 = ItemRepresentation::build(
            $docman_item3,
            $html_purifier,
            $user_representation,
            true,
            true,
            ItemRepresentation::TYPE_FILE,
            false,
            false,
            [
                new ItemMetadataRepresentation(
                    'name',
                    PLUGIN_DOCMAN_METADATA_TYPE_STRING,
                    false,
                    'value',
                    'processed value',
                    [],
                    true,
                    'name'
                ),
            ],
            false,
            false,
            null,
            null,
            null,
            $file_properties,
            null,
            null,
            null,
            null
        );

        $this->item_representation_builder->shouldReceive('buildItemRepresentation')
            ->withArgs([$docman_item1, $user, ItemRepresentation::TYPE_FOLDER, null, null, null, null, null])
            ->andReturns($representation1);

        $this->item_version_factory->shouldReceive('getCurrentVersionForItem')
            ->withArgs([$docman_item3])
            ->andReturns($docman_version_item3);

        $this->item_representation_builder->shouldReceive('buildItemRepresentation')
            ->withArgs([$docman_item3, $user, ItemRepresentation::TYPE_FILE, Mockery::any(), null, null, null])
            ->andReturns($representation2);

        $representation = $this->item_representation_collection_builder->buildFolderContent($item, $user, 50, 0);

        $expected_representation = new PaginatedDocmanItemCollection([$representation1, $representation2], 3);

        $this->assertEquals($expected_representation, $representation);
    }

    public function testItReturnsRepresentationOfParentsItems(): void
    {
        $dar_folder_1 = [
            'item_id'     => 2,
            'title'       => 'folder 1',
            'user_id'     => 101,
            'update_date' => 1542099693,
            'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER,
            'parent_id'   => 0,
        ];
        $dar_folder_2 = [
            'item_id'     => 3,
            'title'       => 'folder 2',
            'user_id'     => 101,
            'update_date' => 1542099693,
            'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER,
            'parent_id'   => 1,
        ];
        $dar_item     = [
            'item_id'     => 4,
            'title'       => 'item',
            'user_id'     => 101,
            'update_date' => 1542099693,
            'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_WIKI,
            'parent_id'   => 2,
        ];

        $docman_folder1 = new \Docman_Folder($dar_folder_1);
        $docman_folder2 = new \Docman_Folder($dar_folder_2);
        $item           = new \Docman_File($dar_item);

        $user                = Mockery::mock(PFUser::class);
        $user_representation = Mockery::mock(MinimalUserRepresentation::class);

        $this->permission_manager->shouldReceive('userCanRead')->andReturns(true);
        $this->permission_manager->shouldReceive('userCanManage')->andReturns(false);

        $this->item_factory->shouldReceive('getItemFromDb')->withArgs([$item->getParentId()])->andReturn($docman_folder2);
        $this->item_factory->shouldReceive('getItemFromDb')->withArgs([$docman_folder2->getParentId()])->andReturn($docman_folder1);

        $html_purifier = Mockery::mock(Codendi_HTMLPurifier::class);
        $html_purifier->shouldReceive('purifyTextWithReferences')->atLeast()->once()
            ->andReturn('description with processed ref');

        $representation1 = ItemRepresentation::build(
            $docman_folder1,
            $html_purifier,
            $user_representation,
            true,
            true,
            ItemRepresentation::TYPE_FOLDER,
            false,
            false,
            [
                new ItemMetadataRepresentation(
                    'name',
                    PLUGIN_DOCMAN_METADATA_TYPE_STRING,
                    false,
                    'value',
                    'processed value',
                    [],
                    true,
                    "name"
                ),
            ],
            false,
            false,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            FolderPropertiesRepresentation::build($docman_folder1)
        );
        $representation2 = ItemRepresentation::build(
            $docman_folder2,
            $html_purifier,
            $user_representation,
            true,
            true,
            ItemRepresentation::TYPE_FOLDER,
            false,
            false,
            [
                new ItemMetadataRepresentation(
                    'name',
                    PLUGIN_DOCMAN_METADATA_TYPE_STRING,
                    false,
                    'value',
                    'processed value',
                    [],
                    true,
                    'name'
                ),
            ],
            false,
            false,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            FolderPropertiesRepresentation::build($docman_folder2)
        );

        $this->item_representation_builder->shouldReceive('buildItemRepresentation')
            ->withArgs([$docman_folder1, $user, ItemRepresentation::TYPE_FOLDER, null, null, null, null, null])
            ->andReturns($representation1);
        $this->item_representation_builder->shouldReceive('buildItemRepresentation')
            ->withArgs([$docman_folder2, $user, ItemRepresentation::TYPE_FOLDER, null, null, null, null, null])
            ->andReturns($representation2);

        $representation = $this->item_representation_collection_builder->buildParentsItemRepresentation($item, $user, 50, 0);

        $expected_representation = new PaginatedDocmanItemCollection([$representation1, $representation2], 2);

        $this->assertEquals($expected_representation, $representation);
    }

    public function testItReturnsAnEmptyCollectionForRootFolderParents(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $dar_item = [
            'item_id'     => 4,
            'title'       => 'item',
            'user_id'     => 101,
            'update_date' => 1542099693,
            'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_WIKI,
            'parent_id'   => 0,
        ];
        $item     = new \Docman_File($dar_item);

        $this->permission_manager->shouldReceive('userCanRead')->andReturns(true);
        $this->permission_manager->shouldReceive('userCanManage')->andReturns(true);

        $representation = $this->item_representation_collection_builder->buildParentsItemRepresentation($item, $user, 50, 0);

        $expected_representation = new PaginatedDocmanItemCollection([], 0);

        $this->assertEquals($expected_representation, $representation);
    }

    public function testItThrowsAnExceptionWhenUserCanNotRead(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $dar_item = [
            'item_id'     => 4,
            'title'       => 'item',
            'user_id'     => 101,
            'update_date' => 1542099693,
            'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_WIKI,
            'parent_id'   => 200,
        ];
        $item     = new \Docman_File($dar_item);

        $this->permission_manager->shouldReceive('userCanRead')->andReturns(false);

        $this->expectException(ForbiddenException::class);
        $this->item_representation_collection_builder->buildParentRowCollection($item, $user, 50, 0);
    }

    public function testItBuildAFolderRepresentation(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $dar_item = [
            'item_id'     => 4,
            'title'       => 'item',
            'user_id'     => 101,
            'update_date' => 1542099693,
            'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_WIKI,
            'parent_id'   => 100,
        ];
        $item     = new \Docman_File($dar_item);

        $this->permission_manager->shouldReceive('userCanRead')->andReturns(true);
        $dar_parent = [
            'item_id'     => 100,
            'title'       => 'folder',
            'user_id'     => 101,
            'update_date' => 1542099693,
            'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER,
            'parent_id'   => 0,
        ];
        $parent     = new \Docman_Folder($dar_parent);
        $this->item_factory->shouldReceive('getItemFromDb')->andReturn($parent);

        $representation = $this->item_representation_collection_builder->buildParentRowCollection($item, $user, 50, 0);

        $expected_representation = new PaginatedParentRowCollection([ParentFolderRepresentation::build($parent)], 1);

        $this->assertEquals($expected_representation, $representation);
    }
}

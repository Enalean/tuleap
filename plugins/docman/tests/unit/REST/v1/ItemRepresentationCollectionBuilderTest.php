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

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1;

use Codendi_HTMLPurifier;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_ItemDao;
use Docman_ItemFactory;
use Docman_LinkVersionFactory;
use Docman_PermissionsManager;
use Docman_Version;
use Docman_VersionFactory;
use EventManager;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Docman\Item\PaginatedDocmanItemCollection;
use Tuleap\Docman\Item\PaginatedParentRowCollection;
use Tuleap\Docman\REST\v1\Files\FilePropertiesRepresentation;
use Tuleap\Docman\REST\v1\Folders\FolderPropertiesRepresentation;
use Tuleap\Docman\REST\v1\Folders\ParentFolderRepresentation;
use Tuleap\Docman\REST\v1\Metadata\ItemMetadataRepresentation;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use Tuleap\User\REST\MinimalUserRepresentation;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ItemRepresentationCollectionBuilderTest extends TestCase
{
    private EventManager&MockObject $event_manager;
    private ItemRepresentationBuilder&MockObject $item_representation_builder;
    private Docman_ItemDao&MockObject $dao;
    private Docman_ItemFactory&MockObject $item_factory;
    private Docman_PermissionsManager&MockObject $permission_manager;
    private ItemRepresentationCollectionBuilder $item_representation_collection_builder;
    private Docman_VersionFactory&MockObject $item_version_factory;

    protected function setUp(): void
    {
        $this->item_factory                = $this->createMock(Docman_ItemFactory::class);
        $this->permission_manager          = $this->createMock(Docman_PermissionsManager::class);
        $this->item_representation_builder = $this->createMock(ItemRepresentationBuilder::class);
        $this->item_version_factory        = $this->createMock(Docman_VersionFactory::class);
        $this->event_manager               = $this->createMock(EventManager::class);
        $event_adder                       = $this->createMock(DocmanItemsEventAdder::class);
        $event_adder->expects($this->once())->method('addLogEvents');
        $this->dao                                    = $this->createMock(Docman_ItemDao::class);
        $this->item_representation_collection_builder = new ItemRepresentationCollectionBuilder(
            $this->item_factory,
            $this->permission_manager,
            new ItemRepresentationVisitor(
                $this->item_representation_builder,
                $this->item_version_factory,
                $this->createMock(Docman_LinkVersionFactory::class),
                $this->item_factory,
                $this->event_manager,
                $event_adder
            ),
            $this->dao
        );
    }

    public function testItReturnsRepresentationOfItemUserCanSee(): void
    {
        $item = new Docman_Folder(['item_id' => 42, 'group_id' => 101]);
        $user = UserTestBuilder::buildWithDefaults();

        $html_purifier = $this->createMock(Codendi_HTMLPurifier::class);
        $html_purifier->expects($this->atLeastOnce())->method('purifyTextWithReferences')->willReturn('description with processed ref');

        $dar_item_1         = [
            'item_id'     => 1,
            'title'       => 'folder 1',
            'user_id'     => 101,
            'update_date' => 1542099693,
            'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER,
        ];
        $dar_item_2         = [
            'item_id'     => 2,
            'title'       => 'item A',
            'user_id'     => 101,
            'update_date' => 1542099693,
            'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_WIKI,
        ];
        $dar_item_3         = [
            'item_id'     => 3,
            'title'       => 'item B',
            'user_id'     => 101,
            'update_date' => 1542099693,
            'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_FILE,
        ];
        $dar_unknown_item_4 = [
            'item_id'     => 4,
            'title'       => 'item C',
            'user_id'     => 101,
            'update_date' => 1542099693,
            'item_type'   => Docman_Item::TYPE_OTHER,
        ];

        $this->dao->method('searchByParentIdWithPagination')->willReturn([
            $dar_item_1,
            $dar_item_2,
            $dar_item_3,
            $dar_unknown_item_4,
        ]);
        $this->dao->method('foundRows')->willReturn(4);

        $docman_item1 = new Docman_Folder($dar_item_1);
        $docman_item3 = new Docman_File($dar_item_3);

        $this->item_factory->method('getItemFromRow')->willReturn($docman_item1, $docman_item3, null);

        $this->permission_manager->method('userCanRead')->willReturnCallback(static fn(PFUser $user, int $id) => match ($id) {
            1, 3, 4 => true,
            2       => false,
        });

        $user_representation = MinimalUserRepresentation::build($user, ProvideUserAvatarUrlStub::build());
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
                    (string) PLUGIN_DOCMAN_METADATA_TYPE_STRING,
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
            '',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            FolderPropertiesRepresentation::build($docman_item1),
            null,
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
        $docman_version_item3 = new Docman_Version($version_data_item3);
        $file_properties      = FilePropertiesRepresentation::build(
            $docman_version_item3,
            '/plugins/docman/?group_id=' . urlencode((string) $item->getGroupId()) . '&action=show&id=' . urlencode((string) $docman_version_item3->getItemId()),
            'open/href'
        );

        $this->event_manager->method('dispatch')->willReturnArgument(0);

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
                    (string) PLUGIN_DOCMAN_METADATA_TYPE_STRING,
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
            '',
            null,
            null,
            null,
            $file_properties,
            null,
            null,
            null,
            null,
            null,
        );

        $this->item_representation_builder->method('buildItemRepresentation')->willReturnCallback(
            static fn(Docman_Item $item, PFUser $user, string $type) => match (true) {
                $item === $docman_item1 && $type === ItemRepresentation::TYPE_FOLDER => $representation1,
                $item === $docman_item3 && $type === ItemRepresentation::TYPE_FILE   => $representation2,
            }
        );

        $this->item_version_factory->method('getCurrentVersionForItem')->with($docman_item3)->willReturn($docman_version_item3);

        $representation = $this->item_representation_collection_builder->buildFolderContent($item, $user, 50, 0);

        $expected_representation = new PaginatedDocmanItemCollection([$representation1, $representation2], 4);

        self::assertEquals($expected_representation, $representation);
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

        $docman_folder1 = new Docman_Folder($dar_folder_1);
        $docman_folder2 = new Docman_Folder($dar_folder_2);
        $item           = new Docman_File($dar_item);

        $user                = UserTestBuilder::buildWithDefaults();
        $user_representation = MinimalUserRepresentation::build($user, ProvideUserAvatarUrlStub::build());

        $this->permission_manager->method('userCanRead')->willReturn(true);
        $this->permission_manager->method('userCanManage')->willReturn(false);

        $this->item_factory->method('getItemFromDb')->willReturnCallback(static fn($id) => match ($id) {
            $item->getParentId()           => $docman_folder2,
            $docman_folder2->getParentId() => $docman_folder1,
        });

        $html_purifier = $this->createMock(Codendi_HTMLPurifier::class);
        $html_purifier->expects($this->atLeastOnce())->method('purifyTextWithReferences')->willReturn('description with processed ref');

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
                    (string) PLUGIN_DOCMAN_METADATA_TYPE_STRING,
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
            '',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            FolderPropertiesRepresentation::build($docman_folder1),
            null,
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
                    (string) PLUGIN_DOCMAN_METADATA_TYPE_STRING,
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
            '',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            FolderPropertiesRepresentation::build($docman_folder2),
            null,
        );

        $this->item_representation_builder->method('buildItemRepresentation')->willReturnCallback(
            static fn(Docman_Item $item) => match ($item) {
                $docman_folder1 => $representation1,
                $docman_folder2 => $representation2,
            }
        );

        $representation = $this->item_representation_collection_builder->buildParentsItemRepresentation($item, $user, 50, 0);

        $expected_representation = new PaginatedDocmanItemCollection([$representation1, $representation2], 2);

        self::assertEquals($expected_representation, $representation);
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
        $item     = new Docman_File($dar_item);

        $this->permission_manager->method('userCanRead')->willReturn(true);
        $this->permission_manager->method('userCanManage')->willReturn(true);

        $representation = $this->item_representation_collection_builder->buildParentsItemRepresentation($item, $user, 50, 0);

        $expected_representation = new PaginatedDocmanItemCollection([], 0);

        self::assertEquals($expected_representation, $representation);
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
        $item     = new Docman_File($dar_item);

        $this->permission_manager->method('userCanRead')->willReturn(false);

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
        $item     = new Docman_File($dar_item);

        $this->permission_manager->method('userCanRead')->willReturn(true);
        $dar_parent = [
            'item_id'     => 100,
            'title'       => 'folder',
            'user_id'     => 101,
            'update_date' => 1542099693,
            'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER,
            'parent_id'   => 0,
        ];
        $parent     = new Docman_Folder($dar_parent);
        $this->item_factory->method('getItemFromDb')->willReturn($parent);

        $representation = $this->item_representation_collection_builder->buildParentRowCollection($item, $user, 50, 0);

        $expected_representation = new PaginatedParentRowCollection([ParentFolderRepresentation::build($parent)], 1);

        self::assertEquals($expected_representation, $representation);
    }
}

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

namespace Tuleap\Docman\REST;

use Docman_Item;
use Docman_ItemFactory;
use Docman_PermissionsManager;
use Mockery;
use PFUser;
use Tuleap\Docman\Item\PaginatedDocmanItemCollection;
use Tuleap\Docman\REST\v1\ItemRepresentation;
use Tuleap\Docman\REST\v1\ItemRepresentationBuilder;
use Tuleap\Docman\REST\v1\ItemRepresentationCollectionBuilder;
use Tuleap\Docman\REST\v1\ItemRepresentationVisitor;
use Tuleap\User\REST\MinimalUserRepresentation;
use UserManager;

require_once __DIR__ . '/../../bootstrap.php';

class ItemRepresentationCollectionBuilderTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
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

    protected function setUp()
    {
        parent::setUp();
        $this->user_manager                           = Mockery::mock(UserManager::class);
        $this->item_factory                           = Mockery::mock(Docman_ItemFactory::class);
        $this->permission_manager                     = Mockery::mock(Docman_PermissionsManager::class);
        $this->item_representation_builder            = Mockery::mock(ItemRepresentationBuilder::class);
        $this->item_representation_visitor            = new ItemRepresentationVisitor(
            $this->item_representation_builder
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
        $user = Mockery::mock(PFUser::class);


        $dar_item_1 = [
            'item_id'     => 1,
            'title'       => 'folder 1',
            'user_id'     => 101,
            'update_date' => 1542099693,
            'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        ];
        $dar_item_2        = [
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

        $representation1 = new ItemRepresentation(
            $docman_item1,
            $user_representation,
            ItemRepresentation::TYPE_FOLDER
        );
        $representation2 = new ItemRepresentation(
            $docman_item3,
            $user_representation,
            ItemRepresentation::TYPE_FILE
        );

        $this->item_representation_builder->shouldReceive('buildItemRepresentation')
            ->withArgs([$docman_item1, ItemRepresentation::TYPE_FOLDER])
            ->andReturns($representation1);
        $this->item_representation_builder->shouldReceive('buildItemRepresentation')
            ->withArgs([$docman_item3, ItemRepresentation::TYPE_FILE])
            ->andReturns($representation2);

        $representation = $this->item_representation_collection_builder->buildFolderContent($item, $user, 50, 0);

        $expected_representation = new PaginatedDocmanItemCollection([$representation1, $representation2], 3);

        $this->assertEquals($expected_representation, $representation);
    }
}

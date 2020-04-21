<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1;

use Codendi_HTMLPurifier;
use Docman_ItemDao;
use Docman_ItemFactory;
use Mockery;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\ApprovalTable\ApprovalTableStateMapper;
use Tuleap\Docman\REST\v1\Metadata\ItemMetadataRepresentation;
use Tuleap\Docman\REST\v1\Metadata\MetadataRepresentationBuilder;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsBuilder;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsRepresentation;
use Tuleap\GlobalLanguageMock;

class ItemRepresentationBuilderTest extends \PHPUnit\Framework\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var Mockery\MockInterface|ApprovalTableRetriever
     */
    private $approval_table_retriever;
    /**
     * @var Mockery\MockInterface|MetadataRepresentationBuilder
     */
    private $metadata_representation_builder;

    /**
     * @var Docman_ItemDao
     */
    private $dao;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var Docman_ItemFactory
     */
    private $docman_item_factory;
    /**
     * @var \Docman_PermissionsManager
     */
    private $permissions_manager;
    /**
     * @var \Docman_LockFactory
     */
    private $lock_factory;

    /**
     * @var ItemRepresentationBuilder
     */
    private $item_representation_builder;

    /**
     * @var ApprovalTableStateMapper
     */
    private $approval_table_state_mapper;

    /**
     * @var Mockery\MockInterface|DocmanItemPermissionsForGroupsBuilder
     */
    private $item_permissions_for_groups_builder;
    /**
     * @var Codendi_HTMLPurifier|Mockery\MockInterface
     */
    private $html_purifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao                         = Mockery::Mock(Docman_ItemDao::class);
        $this->user_manager                = Mockery::Mock(\UserManager::class);
        $this->docman_item_factory         = Mockery::Mock(Docman_ItemFactory::class);
        $this->permissions_manager         = Mockery::Mock(\Docman_PermissionsManager::class);
        $this->lock_factory                = Mockery::Mock(\Docman_LockFactory::class);
        $this->approval_table_state_mapper = new ApprovalTableStateMapper();

        $this->docman_item_factory                 = Mockery::Mock(Docman_ItemFactory::class);
        $this->permissions_manager                 = Mockery::Mock(\Docman_PermissionsManager::class);
        $this->lock_factory                        = Mockery::Mock(\Docman_LockFactory::class);
        $this->metadata_representation_builder     = Mockery::mock(MetadataRepresentationBuilder::class);
        $this->approval_table_retriever            = Mockery::mock(ApprovalTableRetriever::class);
        $this->item_permissions_for_groups_builder = Mockery::mock(DocmanItemPermissionsForGroupsBuilder::class);
        $this->html_purifier                       = Mockery::mock(Codendi_HTMLPurifier::class);

        $this->item_representation_builder = new ItemRepresentationBuilder(
            $this->dao,
            $this->user_manager,
            $this->docman_item_factory,
            $this->permissions_manager,
            $this->lock_factory,
            $this->approval_table_state_mapper,
            $this->metadata_representation_builder,
            $this->approval_table_retriever,
            $this->item_permissions_for_groups_builder,
            $this->html_purifier
        );
    }

    public function testItBuildsAnItemRepresentationOfAnItem(): void
    {
        $owner_id       = 123;
        $docman_item_id = 666;
        $current_user   = Mockery::mock(\PFUser::class);
        $current_user->shouldReceive('getId')->andReturns($owner_id);
        $current_user->shouldReceive('getName')->andReturns('toto');
        $current_user->shouldReceive('getUserName')->andReturns('toto');
        $current_user->shouldReceive('getRealName')->andReturns('toto');
        $current_user->shouldReceive('isAnonymous')->andReturns(false);
        $current_user->shouldReceive('isNone')->andReturns(false);
        $current_user->shouldReceive('getLdapId')->andReturns('');
        $current_user->shouldReceive('getAvatarUrl')->andReturns('some/avatar/url');
        $current_user->shouldReceive('hasAvatar')->andReturns(false);
        $this->user_manager->shouldReceive('getCurrentUser')->andReturns($current_user);
        $current_user->shouldReceive('getPreference')->with('username_display')->andReturns('toto');
        $this->html_purifier->shouldReceive('purifyTextWithReferences')->andReturn('description with processed ref');

        $metadata_representation = new ItemMetadataRepresentation(
            "metadata name",
            'date',
            false,
            '2019-02-06T15:00:00+01:00',
            '2019-02-06T15:00:00+01:00',
            [],
            false,
            "metadata"
        );
        $this->metadata_representation_builder->shouldReceive('build')->andReturn([$metadata_representation]);

        $docman_item    = new \Docman_Item();
        $docman_item->setId($docman_item_id);
        $docman_item->setTitle('My file.txt');
        $docman_item->setOwnerId($owner_id);

        $item_approval_table = Mockery::Mock(\Docman_ApprovalTableFile::class);
        $item_approval_table->shouldReceive('getOwner')->andReturns($owner_id);
        $item_approval_table->shouldReceive('getDate')->andReturns(1549462600);
        $item_approval_table->shouldReceive('isEnabled')->andReturns(true);
        $item_approval_table->shouldReceive('getApprovalState')->andReturns(0);
        $item_approval_table->shouldReceive('getId')->andReturn(10);

        $this->approval_table_retriever->shouldReceive('hasApprovalTable')->with($docman_item)->andReturn(
            true
        );

        $this->approval_table_retriever->shouldReceive('retrieveByItem')->with($docman_item)->andReturn(
            $item_approval_table
        );

        $this->user_manager->shouldReceive('getUserById')
            ->withArgs([$owner_id])
            ->andReturns($current_user);

        $this->permissions_manager->shouldReceive('userCanWrite')
            ->withArgs([$current_user, $docman_item_id])
            ->andReturns(true);

        $this->permissions_manager->shouldReceive('userCanManage')
            ->withArgs([$current_user, $docman_item_id])
            ->andReturns(true);

        $this->lock_factory->shouldReceive('getLockInfoForItem')
            ->withArgs([$docman_item])
            ->andReturns(["user_id" => $owner_id, "lock_date" => 1549461600]);

        $permissions_for_groups_representation = new DocmanItemPermissionsForGroupsRepresentation();
        $this->item_permissions_for_groups_builder->shouldReceive('getRepresentation')
            ->with($current_user, $docman_item)
            ->andReturn($permissions_for_groups_representation);

        $representation = $this->item_representation_builder->buildItemRepresentation(
            $docman_item,
            $current_user,
            ItemRepresentation::TYPE_FILE
        );

        $this->assertEquals($representation->title, 'My file.txt');
        $this->assertEquals($representation->is_expanded, false);
        $this->assertEquals($representation->user_can_write, true);
        $this->assertEquals($representation->can_user_manage, true);
        $this->assertEquals($representation->lock_info->locked_by->id, $owner_id);
        $this->assertEquals($representation->lock_info->lock_date, '2019-02-06T15:00:00+01:00');
        $this->assertEquals($representation->file_properties, null);
        $this->assertEquals($representation->embedded_file_properties, null);
        $this->assertEquals($representation->link_properties, null);
        $this->assertEquals($representation->wiki_properties, null);
        $this->assertEquals($representation->permissions_for_groups, $permissions_for_groups_representation);

        $this->assertEquals($representation->approval_table->id, 10);
        $this->assertEquals($representation->approval_table->approval_state, 'Not yet');
        $this->assertEquals($representation->approval_table->table_owner->id, $owner_id);
        $this->assertEquals($representation->approval_table->approval_request_date, '2019-02-06T15:16:40+01:00');
        $this->assertEquals($representation->approval_table->has_been_approved, false);
        $this->assertEquals($representation->metadata[0]->value, '2019-02-06T15:00:00+01:00');
        $this->assertEquals($representation->metadata[0]->post_processed_value, '2019-02-06T15:00:00+01:00');
        $this->assertEquals($representation->metadata[0]->name, 'metadata name');
        $this->assertEquals($representation->metadata[0]->type, 'date');
    }
}

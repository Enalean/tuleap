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
use Docman_ApprovalTableFile;
use Docman_Item;
use Docman_ItemDao;
use Docman_ItemFactory;
use Docman_LockFactory;
use Docman_PermissionsManager;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\ApprovalTable\ApprovalTableStateMapper;
use Tuleap\Docman\REST\v1\Metadata\ItemMetadataRepresentation;
use Tuleap\Docman\REST\v1\Metadata\MetadataRepresentationBuilder;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsBuilder;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsRepresentation;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ItemRepresentationBuilderTest extends TestCase
{
    use GlobalLanguageMock;

    private ApprovalTableRetriever&MockObject $approval_table_retriever;
    private MetadataRepresentationBuilder&MockObject $metadata_representation_builder;
    private UserManager&MockObject $user_manager;
    private Docman_PermissionsManager&MockObject $permissions_manager;
    private Docman_LockFactory&MockObject $lock_factory;
    private ItemRepresentationBuilder $item_representation_builder;
    private DocmanItemPermissionsForGroupsBuilder&MockObject $item_permissions_for_groups_builder;
    private Codendi_HTMLPurifier&MockObject $html_purifier;

    #[\Override]
    protected function setUp(): void
    {
        $this->user_manager                        = $this->createMock(UserManager::class);
        $this->permissions_manager                 = $this->createMock(Docman_PermissionsManager::class);
        $this->lock_factory                        = $this->createMock(Docman_LockFactory::class);
        $this->permissions_manager                 = $this->createMock(Docman_PermissionsManager::class);
        $this->lock_factory                        = $this->createMock(Docman_LockFactory::class);
        $this->metadata_representation_builder     = $this->createMock(MetadataRepresentationBuilder::class);
        $this->approval_table_retriever            = $this->createMock(ApprovalTableRetriever::class);
        $this->item_permissions_for_groups_builder = $this->createMock(DocmanItemPermissionsForGroupsBuilder::class);
        $this->html_purifier                       = $this->createMock(Codendi_HTMLPurifier::class);

        $this->item_representation_builder = new ItemRepresentationBuilder(
            $this->createMock(Docman_ItemDao::class),
            $this->user_manager,
            $this->createMock(Docman_ItemFactory::class),
            $this->permissions_manager,
            $this->lock_factory,
            new ApprovalTableStateMapper(),
            $this->metadata_representation_builder,
            $this->approval_table_retriever,
            $this->item_permissions_for_groups_builder,
            $this->html_purifier,
            ProvideUserAvatarUrlStub::build(),
        );

        UserManager::setInstance($this->user_manager);
    }

    #[\Override]
    protected function tearDown(): void
    {
        UserManager::clearInstance();
    }

    public function testItBuildsAnItemRepresentationOfAnItem(): void
    {
        $owner_id       = 123;
        $docman_item_id = 666;
        $current_user   = UserTestBuilder::anActiveUser()
            ->withId($owner_id)
            ->withUserName('toto')
            ->withRealName('toto')
            ->withLdapId('')
            ->withAvatarUrl('some/avatar/url')
            ->build();
        $this->user_manager->method('getCurrentUser')->willReturn($current_user);
        $this->html_purifier->method('purifyTextWithReferences')->willReturn('description with processed ref');

        $metadata_representation = new ItemMetadataRepresentation(
            'metadata name',
            'date',
            false,
            '2019-02-06T15:00:00+01:00',
            '2019-02-06T15:00:00+01:00',
            [],
            false,
            'metadata'
        );
        $this->metadata_representation_builder->method('build')->willReturn([$metadata_representation]);

        $docman_item = new Docman_Item();
        $docman_item->setId($docman_item_id);
        $docman_item->setTitle('My file.txt');
        $docman_item->setOwnerId($owner_id);

        $item_approval_table = $this->createMock(Docman_ApprovalTableFile::class);
        $item_approval_table->method('getOwner')->willReturn($owner_id);
        $item_approval_table->method('getDate')->willReturn(1549462600);
        $item_approval_table->method('isEnabled')->willReturn(true);
        $item_approval_table->method('getApprovalState')->willReturn(0);
        $item_approval_table->method('getId')->willReturn(10);

        $this->approval_table_retriever->method('hasApprovalTable')->with($docman_item)->willReturn(true);

        $this->approval_table_retriever->method('retrieveByItem')->with($docman_item)->willReturn($item_approval_table);

        $this->user_manager->method('getUserById')->with($owner_id)->willReturn($current_user);

        $this->permissions_manager->method('userCanWrite')->with($current_user, $docman_item_id)->willReturn(true);
        $this->permissions_manager->method('userCanManage')->with($current_user, $docman_item_id)->willReturn(true);
        $this->permissions_manager->method('userCanDelete')->with($current_user, $docman_item)->willReturn(true);

        $this->lock_factory->method('getLockInfoForItem')
            ->with($docman_item)->willReturn(['user_id' => $owner_id, 'lock_date' => 1549461600]);

        $permissions_for_groups_representation = new DocmanItemPermissionsForGroupsRepresentation();
        $this->item_permissions_for_groups_builder->method('getRepresentation')
            ->with($current_user, $docman_item)->willReturn($permissions_for_groups_representation);

        $representation = $this->item_representation_builder->buildItemRepresentation(
            $docman_item,
            $current_user,
            ItemRepresentation::TYPE_FILE
        );

        self::assertEquals('My file.txt', $representation->title);
        self::assertFalse($representation->is_expanded);
        self::assertTrue($representation->user_can_write);
        self::assertTrue($representation->user_can_delete);
        self::assertTrue($representation->can_user_manage);
        self::assertEquals($owner_id, $representation->lock_info->locked_by->id);
        self::assertEquals('2019-02-06T15:00:00+01:00', $representation->lock_info->lock_date);
        self::assertNull($representation->file_properties);
        self::assertNull($representation->embedded_file_properties);
        self::assertNull($representation->link_properties);
        self::assertNull($representation->wiki_properties);
        self::assertEquals($permissions_for_groups_representation, $representation->permissions_for_groups);

        self::assertEquals(10, $representation->approval_table->id);
        self::assertEquals('Not yet', $representation->approval_table->approval_state);
        self::assertEquals($owner_id, $representation->approval_table->table_owner->id);
        self::assertEquals('2019-02-06T15:16:40+01:00', $representation->approval_table->approval_request_date);
        self::assertFalse($representation->approval_table->has_been_approved);
        self::assertEquals('2019-02-06T15:00:00+01:00', $representation->metadata[0]->value);
        self::assertEquals('2019-02-06T15:00:00+01:00', $representation->metadata[0]->post_processed_value);
        self::assertEquals('metadata name', $representation->metadata[0]->name);
        self::assertEquals('date', $representation->metadata[0]->type);
    }
}

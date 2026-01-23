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
use Docman_ApprovalTableFactoriesFactory;
use Docman_ApprovalTableFile;
use Docman_ApprovalTableFileFactory;
use Docman_ItemDao;
use Docman_ItemFactory;
use Docman_LockFactory;
use Docman_NotificationsManager;
use Docman_PermissionsManager;
use Docman_Version;
use Docman_VersionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\ApprovalTable\ApprovalTableStateMapper;
use Tuleap\Docman\Builders\DocmanFileTestBuilder;
use Tuleap\Docman\Item\Icon\ItemIconPresenterBuilder;
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
    private Docman_ApprovalTableFactoriesFactory&MockObject $factories_factory;
    private Docman_VersionFactory&MockObject $version_factory;
    private ItemIconPresenterBuilder $docman_icons;

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
        $this->factories_factory                   = $this->createMock(Docman_ApprovalTableFactoriesFactory::class);
        $this->version_factory                     = $this->createMock(Docman_VersionFactory::class);
        $event_dispatcher                          = $this->createMock(EventDispatcherInterface::class);
        $event_dispatcher->method('dispatch')->willReturnArgument(0);
        $this->docman_icons = new ItemIconPresenterBuilder(
            $event_dispatcher,
            $this->version_factory,
        );

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
            $this->version_factory,
            $this->createStub(Docman_NotificationsManager::class),
            $this->docman_icons,
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
        $this->version_factory->method('getCurrentVersionForItem')->willReturn(null);

        $docman_item = DocmanFileTestBuilder::aFile()
            ->withId($docman_item_id)
            ->withTitle('My file.txt')
            ->withOwnerId($owner_id)
            ->withGroupId(101)
            ->build();

        $item_approval_table = $this->createMock(Docman_ApprovalTableFile::class);
        $item_approval_table->method('getOwner')->willReturn($owner_id);
        $item_approval_table->method('getDate')->willReturn(1549462600);
        $item_approval_table->method('isEnabled')->willReturn(true);
        $item_approval_table->method('getApprovalState')->willReturn(0);
        $item_approval_table->method('getId')->willReturn(10);
        $item_approval_table->method('isDisabled')->willReturn(false);
        $item_approval_table->method('isClosed')->willReturn(false);
        $item_approval_table->method('getVersionNumber')->willReturn('2');
        $item_approval_table->method('getNotification')->willReturn(PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED);
        $item_approval_table->method('getDescription')->willReturn('');
        $item_approval_table->method('getReviewerArray')->willReturn([]);
        $item_approval_table->method('getStatus')->willReturn('enabled');
        $item_approval_table->method('getNotificationOccurence')->willReturn(0);

        $approval_table_factory = $this->createMock(Docman_ApprovalTableFileFactory::class);
        $approval_table_factory->method('getNotificationTypeName')->with(PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED)->willReturn('Disabled');

        $this->factories_factory->method('getFromItem')->with($docman_item)->willReturn($approval_table_factory);

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

        $this->version_factory->method('getSpecificVersion')->with($docman_item, '2')->willReturn(new Docman_Version([
            'label' => '1.0.0-rc3',
        ]));

        $representation = $this->item_representation_builder->buildItemRepresentation(
            $docman_item,
            $current_user,
            ItemRepresentation::TYPE_FILE
        );

        self::assertSame('My file.txt', $representation->title);
        self::assertFalse($representation->is_expanded);
        self::assertTrue($representation->user_can_write);
        self::assertTrue($representation->user_can_delete);
        self::assertTrue($representation->can_user_manage);
        $item_lock_info = $representation->lock_info;
        if ($item_lock_info === null) {
            self::fail('Lock info should not be null');
        }
        self::assertSame($owner_id, $item_lock_info->locked_by->id);
        self::assertSame('2019-02-06T15:00:00+01:00', $item_lock_info->lock_date);
        self::assertNull($representation->file_properties);
        self::assertNull($representation->embedded_file_properties);
        self::assertNull($representation->link_properties);
        self::assertNull($representation->wiki_properties);
        self::assertSame($permissions_for_groups_representation, $representation->permissions_for_groups);

        $table_representation = $representation->approval_table;
        if ($table_representation === null) {
            self::fail('Approval table should not be null');
        }
        self::assertSame(10, $table_representation->id);
        self::assertSame('Not yet', $table_representation->approval_state);
        self::assertSame($owner_id, $table_representation->table_owner->id);
        self::assertSame('2019-02-06T15:16:40+01:00', $table_representation->approval_request_date);
        self::assertFalse($table_representation->has_been_approved);
        self::assertSame('', $table_representation->description);
        self::assertFalse($table_representation->is_closed);
        self::assertSame('disabled', $table_representation->notification_type);
        self::assertSame(2, $table_representation->version_number);
        self::assertSame([], $table_representation->reviewers);
        self::assertSame('1.0.0-rc3', $table_representation->version_label);
        self::assertSame(0, $table_representation->reminder_occurence);
        self::assertSame('2019-02-06T15:00:00+01:00', $representation->metadata[0]->value);
        self::assertSame('2019-02-06T15:00:00+01:00', $representation->metadata[0]->post_processed_value);
        self::assertSame('metadata name', $representation->metadata[0]->name);
        self::assertSame('date', $representation->metadata[0]->type);
    }
}

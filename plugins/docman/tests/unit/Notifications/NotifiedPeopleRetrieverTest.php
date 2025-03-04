<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Docman\Notifications;

use ArrayIterator;
use Docman_Item;
use Docman_ItemFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectUGroup;
use TestHelper;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UGroupManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NotifiedPeopleRetrieverTest extends TestCase
{
    private NotifiedPeopleRetriever $retriever;
    private UsersToNotifyDao&MockObject $notified_users_dao;
    private UgroupsToNotifyDao&MockObject $notified_ugroups_dao;
    private Docman_ItemFactory&MockObject $item_factory;
    private Project $project;
    private int $user_id;
    private int $item_id;
    private int $parent_item_id;
    private ProjectUGroup $custom_ugroup;

    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()->build();

        $this->notified_users_dao   = $this->createMock(UsersToNotifyDao::class);
        $this->notified_ugroups_dao = $this->createMock(UgroupsToNotifyDao::class);
        $ugroup_manager             = $this->createMock(UGroupManager::class);
        $this->item_factory         = $this->createMock(Docman_ItemFactory::class);

        $this->retriever = new NotifiedPeopleRetriever(
            $this->notified_users_dao,
            $this->notified_ugroups_dao,
            $this->item_factory,
            $ugroup_manager
        );

        $this->user_id        = 105;
        $this->item_id        = 66;
        $this->parent_item_id = 65;

        $this->custom_ugroup = ProjectUGroupTestBuilder::aCustomUserGroup(169)->withProject($this->project)->build();
        $ugroup_manager->method('getUGroup')->with($this->project, 169)->willReturn($this->custom_ugroup);
    }

    public function testItNotifiesUsersListeningToItem(): void
    {
        $this->notified_users_dao->method('searchUserIdByObjectIdAndType')
            ->with($this->item_id, PLUGIN_DOCMAN_NOTIFICATION)
            ->willReturn(TestHelper::arrayToDar([
                'item_id' => $this->item_id,
                'user_id' => $this->user_id,
                'type'    => PLUGIN_DOCMAN_NOTIFICATION,
            ]));
        $this->notified_ugroups_dao->method('searchUgroupsByItemIdAndType')
            ->with($this->item_id, PLUGIN_DOCMAN_NOTIFICATION)
            ->willReturn(TestHelper::arrayToDar(false));

        $docman_item = new Docman_Item();
        $docman_item->setParentId($this->parent_item_id);
        $this->item_factory->method('getItemFromDb')->willReturnCallback(fn(int $item_id) => match ($item_id) {
            $this->item_id => $docman_item,
            default        => null,
        });

        $result = $this->retriever->getNotifiedUsers($this->project, $this->item_id);

        $expected_result = new ArrayIterator([
            $this->user_id => [
                'item_id' => $this->item_id,
                'user_id' => $this->user_id,
                'type'    => PLUGIN_DOCMAN_NOTIFICATION,
            ],
        ]);
        self::assertEquals(iterator_to_array($expected_result), iterator_to_array($result));
    }

    public function testItNotifiesUsersListeningToParentOfItem(): void
    {
        $this->notified_users_dao->method('searchUserIdByObjectIdAndType')->willReturnCallback(fn(int $item_id, string $type) => match (true) {
            $item_id === $this->item_id && $type === PLUGIN_DOCMAN_NOTIFICATION                => TestHelper::arrayToDar(false),
            $item_id === $this->parent_item_id && $type === PLUGIN_DOCMAN_NOTIFICATION_CASCADE => TestHelper::arrayToDar([
                'item_id' => $this->parent_item_id,
                'user_id' => $this->user_id,
                'type'    => PLUGIN_DOCMAN_NOTIFICATION_CASCADE,
            ]),
        });
        $this->notified_ugroups_dao->method('searchUgroupsByItemIdAndType')->willReturnCallback(fn(int $item_id, string $type) => match ($item_id) {
            $this->item_id,
            $this->parent_item_id => TestHelper::arrayToDar(false),
        });

        $docman_item = new Docman_Item();
        $docman_item->setParentId($this->parent_item_id);
        $parent_docman_item = new Docman_Item();
        $this->item_factory->method('getItemFromDb')->willReturnCallback(fn(?int $item_id) => match ($item_id) {
            $this->item_id        => $docman_item,
            $this->parent_item_id => $parent_docman_item,
            default               => null,
        });

        $result = $this->retriever->getNotifiedUsers($this->project, $this->item_id);

        $expected_result = new ArrayIterator(
            [
                $this->user_id => [
                    'item_id' => '65',
                    'user_id' => $this->user_id,
                    'type'    => PLUGIN_DOCMAN_NOTIFICATION_CASCADE,

                ],
            ]
        );
        $this->assertEquals(iterator_to_array($expected_result), iterator_to_array($result));
    }

    public function testItDoesNotNotifyTwiceTheSameUser(): void
    {
        $this->notified_users_dao->method('searchUserIdByObjectIdAndType')->willReturnCallback(fn(int $item_id, string $type) => match (true) {
            $item_id === $this->item_id && $type === PLUGIN_DOCMAN_NOTIFICATION                => TestHelper::arrayToDar([
                'item_id' => $this->item_id,
                'user_id' => $this->user_id,
                'type'    => PLUGIN_DOCMAN_NOTIFICATION,
            ]),
            $item_id === $this->parent_item_id && $type === PLUGIN_DOCMAN_NOTIFICATION_CASCADE => TestHelper::arrayToDar([
                'item_id' => $this->parent_item_id,
                'user_id' => $this->user_id,
                'type'    => PLUGIN_DOCMAN_NOTIFICATION_CASCADE,
            ]),
        });
        $this->notified_ugroups_dao->method('searchUgroupsByItemIdAndType')->willReturnCallback(fn(int $item_id, string $type) => match ($item_id) {
            $this->item_id,
            $this->parent_item_id => TestHelper::arrayToDar(false),
        });

        $docman_item = new Docman_Item();
        $docman_item->setParentId($this->parent_item_id);
        $parent_docman_item = new Docman_Item();
        $this->item_factory->method('getItemFromDb')->willReturnCallback(fn(?int $item_id) => match ($item_id) {
            $this->item_id        => $docman_item,
            $this->parent_item_id => $parent_docman_item,
            default               => null,
        });

        $result = $this->retriever->getNotifiedUsers($this->project, $this->item_id);

        $expected_result = new ArrayIterator([
            $this->user_id => [
                'item_id' => '65',
                'user_id' => $this->user_id,
                'type'    => PLUGIN_DOCMAN_NOTIFICATION_CASCADE,

            ],
        ]);
        self::assertEquals(iterator_to_array($expected_result), iterator_to_array($result));
    }

    public function testItNotifiesUgroupMembersListeningToItem(): void
    {
        $this->notified_users_dao->method('searchUserIdByObjectIdAndType')
            ->with($this->item_id, PLUGIN_DOCMAN_NOTIFICATION)
            ->willReturn(TestHelper::arrayToDar(false));
        $this->notified_ugroups_dao->method('searchUgroupsByItemIdAndType')
            ->with($this->item_id, PLUGIN_DOCMAN_NOTIFICATION)
            ->willReturn(TestHelper::arrayToDar(['ugroup_id' => 169]));

        $docman_item = new Docman_Item();
        $docman_item->setParentId($this->parent_item_id);
        $this->item_factory->method('getItemFromDb')->willReturnCallback(fn(?int $item_id) => match ($item_id) {
            $this->item_id => $docman_item,
            default        => null,
        });

        $this->custom_ugroup->setMembers(UserTestBuilder::buildWithId($this->user_id));

        $result = $this->retriever->getNotifiedUsers($this->project, $this->item_id);

        $expected_result = new ArrayIterator([
            $this->user_id => [
                'item_id' => $this->item_id,
                'user_id' => $this->user_id,
                'type'    => PLUGIN_DOCMAN_NOTIFICATION,
            ],
        ]);
        self::assertEquals(iterator_to_array($expected_result), iterator_to_array($result));
    }

    public function testItNotifiesUgroupMembersListeningToParentOfItem(): void
    {
        $this->notified_users_dao->method('searchUserIdByObjectIdAndType')
            ->willReturn(TestHelper::arrayToDar(false));

        $this->notified_ugroups_dao->method('searchUgroupsByItemIdAndType')->willReturnCallback(fn(int $item_id, string $type) => match ($item_id) {
            $this->item_id        => TestHelper::arrayToDar(false),
            $this->parent_item_id => TestHelper::arrayToDar(['ugroup_id' => 169]),
        });

        $docman_item = new Docman_Item();
        $docman_item->setParentId($this->parent_item_id);
        $parent_docman_item = new Docman_Item();
        $this->item_factory->method('getItemFromDb')->willReturnCallback(fn(?int $item_id) => match ($item_id) {
            $this->item_id        => $docman_item,
            $this->parent_item_id => $parent_docman_item,
            default               => null,
        });

        $this->custom_ugroup->setMembers(UserTestBuilder::buildWithId($this->user_id));

        $result = $this->retriever->getNotifiedUsers($this->project, $this->item_id);

        $expected_result = new ArrayIterator([
            $this->user_id => [
                'item_id' => '65',
                'user_id' => $this->user_id,
                'type'    => PLUGIN_DOCMAN_NOTIFICATION_CASCADE,
            ],
        ]);
        self::assertEquals(iterator_to_array($expected_result), iterator_to_array($result));
    }

    public function testItDoesNotNotifyTwiceTheSameUserInUgroupAndInList(): void
    {
        $this->notified_users_dao->method('searchUserIdByObjectIdAndType')
            ->with($this->item_id, PLUGIN_DOCMAN_NOTIFICATION)
            ->willReturn(TestHelper::arrayToDar([
                'item_id' => $this->item_id,
                'user_id' => $this->user_id,
                'type'    => PLUGIN_DOCMAN_NOTIFICATION,
            ]));
        $this->notified_ugroups_dao->method('searchUgroupsByItemIdAndType')
            ->with($this->item_id, PLUGIN_DOCMAN_NOTIFICATION)
            ->willReturn(TestHelper::arrayToDar(['ugroup_id' => 169]));

        $docman_item = new Docman_Item();
        $docman_item->setParentId($this->parent_item_id);
        $this->item_factory->method('getItemFromDb')->willReturnCallback(fn(?int $item_id) => match ($item_id) {
            $this->item_id => $docman_item,
            default        => null,
        });

        $this->custom_ugroup->setMembers(UserTestBuilder::buildWithId($this->user_id));

        $result = $this->retriever->getNotifiedUsers($this->project, $this->item_id);

        $expected_result = new ArrayIterator([
            $this->user_id => [
                'item_id' => $this->item_id,
                'user_id' => $this->user_id,
                'type'    => PLUGIN_DOCMAN_NOTIFICATION,
            ],
        ]);
        self::assertEquals(iterator_to_array($expected_result), iterator_to_array($result));
    }
}

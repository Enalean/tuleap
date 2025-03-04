<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 * Copyright (c) STMicroelectronics 2011. All rights reserved
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman;

use ArrayIterator;
use Docman_Item;
use Docman_NotificationsManager_Delete;
use Docman_PermissionsManager;
use Feedback;
use MailBuilder;
use Tuleap\Docman\Notifications\NotifiedPeopleRetriever;
use Tuleap\Docman\Notifications\UGroupsRetriever;
use Tuleap\Docman\Notifications\UgroupsUpdater;
use Tuleap\Docman\Notifications\UsersRetriever;
use Tuleap\Docman\Notifications\UsersToNotifyDao;
use Tuleap\Docman\Notifications\UsersUpdater;
use Tuleap\Document\LinkProvider\DocumentLinkProvider;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NotificationsManager_DeleteTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    /*
     * Test the case when deleting a docman item the notification mail
     * is sent to all monitoring users not only one of them
     */
    public function testStoreEventsDoNotOverrideUsers(): void
    {
        $listening_users = new ArrayIterator([
            ['user_id' => 1, 'item_id' => 1],
            ['user_id' => 2, 'item_id' => 1],
            ['user_id' => 3, 'item_id' => 1],
        ]);

        $user1 = UserTestBuilder::buildWithId(1);
        $user2 = UserTestBuilder::buildWithId(2);
        $user3 = UserTestBuilder::buildWithId(3);
        $um    = $this->createMock(UserManager::class);
        $um->method('getUserById')->willReturnCallback(static fn(int $id) => match ($id) {
            1 => $user1,
            2 => $user2,
            3 => $user3,
        });

        $dpm = $this->createMock(Docman_PermissionsManager::class);
        $dpm->method('userCanRead')->willReturn(true);
        $dpm->method('userCanAccess')->willReturn(true);

        $params = ['item' => new Docman_Item(['item_id' => 1])];

        $project                   = ProjectTestBuilder::aProject()->withId(101)->withAccessPrivate()->build();
        $feedback                  = $this->createMock(Feedback::class);
        $mail_builder              = $this->createMock(MailBuilder::class);
        $notifications_dao         = $this->createMock(UsersToNotifyDao::class);
        $notified_people_retriever = $this->createMock(NotifiedPeopleRetriever::class);
        $notified_people_retriever->method('getNotifiedUsers')->willReturn($listening_users);
        $users_remover   = $this->createMock(UsersUpdater::class);
        $ugroups_remover = $this->createMock(UgroupsUpdater::class);

        $link_url_provider     = new DocumentLinkProvider('', $project);
        $notifications_manager = $this->getMockBuilder(Docman_NotificationsManager_Delete::class)
            ->setConstructorArgs([
                $project,
                $link_url_provider,
                $feedback,
                $mail_builder,
                $notifications_dao,
                $this->createMock(UsersRetriever::class),
                $this->createMock(UGroupsRetriever::class),
                $notified_people_retriever,
                $users_remover,
                $ugroups_remover,
            ])
            ->onlyMethods([
                '_getUserManager',
                '_getPermissionsManager',
                'getUrlProvider',
            ])
            ->getMock();
        $notifications_manager->method('_getUserManager')->willReturn($um);
        $notifications_manager->method('_getPermissionsManager')->willReturn($dpm);
        $notifications_manager->method('getUrlProvider')->willReturn($link_url_provider);
        $notifications_manager->_listeners = [];

        $notifications_manager->_storeEvents(1, 'removed', $params);

        self::assertEquals($user1, $notifications_manager->_listeners[1]['user']);
        self::assertEquals($user2, $notifications_manager->_listeners[2]['user']);
        self::assertEquals($user3, $notifications_manager->_listeners[3]['user']);
    }
}

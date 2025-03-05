<?php
/**
 * Copyright (c) Enalean, 2017-present. All rights reserved
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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

use Docman_Item;
use Docman_NotificationsManager;
use Docman_Path;
use Feedback;
use MailBuilder;
use PHPUnit\Framework\MockObject\MockObject;
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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NotificationsManagerTest extends TestCase
{
    private Docman_NotificationsManager $notification_manager;
    private UsersRetriever&MockObject $users_retriever;
    private UGroupsRetriever&MockObject $ugroups_retriever;

    protected function setUp(): void
    {
        $this->users_retriever      = $this->createMock(UsersRetriever::class);
        $this->ugroups_retriever    = $this->createMock(UGroupsRetriever::class);
        $project                    = ProjectTestBuilder::aProject()->withPublicName('My project')->withTruncatedEmails(false)->build();
        $this->notification_manager = new Docman_NotificationsManager(
            $project,
            new DocumentLinkProvider('https://www.example.com', $project),
            new Feedback(),
            $this->createMock(MailBuilder::class),
            $this->createMock(UsersToNotifyDao::class),
            $this->users_retriever,
            $this->ugroups_retriever,
            $this->createMock(NotifiedPeopleRetriever::class),
            $this->createMock(UsersUpdater::class),
            $this->createMock(UgroupsUpdater::class)
        );
    }

    public function testGetMessageForUserSameListenedItem(): void
    {
        $user           = UserTestBuilder::aUser()->withId(2)->withRealName('John Doe')->build();
        $params['path'] = new Docman_Path();
        $params['item'] = new Docman_Item(['item_id' => 1, 'title' => 'Folder1/Folder2/File']);
        $this->users_retriever->method('getListeningUsers')->willReturn([$user->getId() => $params['item']]);
        $params['wiki_page'] = 'wiki';
        $params['url']       = 'https://www.example.com/plugins/docman/';

        $details_link_url       = 'https://www.example.com/plugins/document/testproject/preview/1';
        $notifications_link_url = 'https://www.example.com/plugins/docman/?group_id=101&action=details&section=notifications&id=1';
        $plugin_link_url        = 'https://www.example.com/plugins/docman/';

        $message1 = "Folder1/Folder2/File has been modified by John Doe.\n$details_link_url\n\n\n--------------------------------------------------------------------\nYou are receiving this message because you are monitoring this item.\nTo stop monitoring, please visit:\n$notifications_link_url";
        $message2 = "Folder1/Folder2/File has been modified by John Doe.\n$details_link_url\n\n\n--------------------------------------------------------------------\nYou are receiving this message because you are monitoring this item.\nTo stop monitoring, please visit:\n$notifications_link_url";
        $message3 = "New version of wiki wiki page was created by John Doe.\n$plugin_link_url\n\n\n--------------------------------------------------------------------\nYou are receiving this message because you are monitoring this item.\nTo stop monitoring, please visit:\n$notifications_link_url";
        $message4 = "Something happen!\n\n--------------------------------------------------------------------\nYou are receiving this message because you are monitoring this item.\nTo stop monitoring, please visit:\n$notifications_link_url";

        self::assertEquals($message1, $this->notification_manager->_getMessageForUser($user, 'modified', $params));
        self::assertEquals($message2, $this->notification_manager->_getMessageForUser($user, 'new_version', $params));
        self::assertEquals($message3, $this->notification_manager->_getMessageForUser($user, 'new_wiki_version', $params));
        self::assertEquals($message4, $this->notification_manager->_getMessageForUser($user, 'something happen', $params));
    }

    public function testGetMessageForUserParentListened(): void
    {
        $user           = UserTestBuilder::aUser()->withId(2)->withRealName('John Doe')->build();
        $params['path'] = new Docman_Path();
        $params['item'] = new Docman_Item(['item_id' => 10, 'title' => 'Folder1/Folder2/File']);
        $parent_item    = new Docman_Item(['item_id' => 1]);
        $this->users_retriever->method('getListeningUsers')->willReturn([$user->getId() => $parent_item]);
        $params['wiki_page'] = 'wiki';
        $params['url']       = 'https://www.example.com/plugins/docman/';

        $details_link_url       = 'https://www.example.com/plugins/document/testproject/preview/10';
        $notifications_link_url = 'https://www.example.com/plugins/docman/?group_id=101&action=details&section=notifications&id=1';

        $message1 = "Folder1/Folder2/File has been modified by John Doe.\n$details_link_url\n\n\n--------------------------------------------------------------------\nYou are receiving this message because you are monitoring this item.\nTo stop monitoring, please visit:\n$notifications_link_url";
        $message2 = "Folder1/Folder2/File has been modified by John Doe.\n$details_link_url\n\n\n--------------------------------------------------------------------\nYou are receiving this message because you are monitoring this item.\nTo stop monitoring, please visit:\n$notifications_link_url";
        $message3 = "New version of wiki wiki page was created by John Doe.\nhttps://www.example.com/plugins/docman/\n\n\n--------------------------------------------------------------------\nYou are receiving this message because you are monitoring this item.\nTo stop monitoring, please visit:\n$notifications_link_url";
        $message4 = "Something happen!\n\n--------------------------------------------------------------------\nYou are receiving this message because you are monitoring this item.\nTo stop monitoring, please visit:\n$notifications_link_url";

        self::assertEquals($message1, $this->notification_manager->_getMessageForUser($user, 'modified', $params));
        self::assertEquals($message2, $this->notification_manager->_getMessageForUser($user, 'new_version', $params));
        self::assertEquals($message3, $this->notification_manager->_getMessageForUser($user, 'new_wiki_version', $params));
        self::assertEquals($message4, $this->notification_manager->_getMessageForUser($user, 'something happen', $params));
    }

    public function testItReturnsTrueWhenAtLeastOneUserIsNotified(): void
    {
        $this->users_retriever->expects(self::once())->method('doesNotificationExistByUserAndItemId')->willReturn(true);
        $this->ugroups_retriever->expects(self::never())->method('doesNotificationExistByUGroupAndItemId');

        self::assertTrue($this->notification_manager->userExists('101', '201', PLUGIN_DOCMAN_NOTIFICATION));
    }

    public function testItReturnsTrueWhenAtLeastAGroupIsNotified(): void
    {
        $this->users_retriever->expects(self::never())->method('doesNotificationExistByUserAndItemId');
        $this->ugroups_retriever->expects(self::once())->method('doesNotificationExistByUGroupAndItemId')->willReturn(true);

        self::assertTrue($this->notification_manager->ugroupExists('101', '201', PLUGIN_DOCMAN_NOTIFICATION));
    }

    public function testItReturnsFalseWhenNoGroupAndNoUserReceiveNotifications(): void
    {
        $this->users_retriever->expects(self::once())->method('doesNotificationExistByUserAndItemId')->willReturn(false);
        $this->ugroups_retriever->expects(self::once())->method('doesNotificationExistByUGroupAndItemId')->willReturn(false);

        self::assertFalse($this->notification_manager->userExists('101', '201', PLUGIN_DOCMAN_NOTIFICATION));
        self::assertFalse($this->notification_manager->ugroupExists('101', '201', PLUGIN_DOCMAN_NOTIFICATION));
    }
}

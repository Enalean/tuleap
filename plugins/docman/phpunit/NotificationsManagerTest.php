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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Docman\ExternalLinks\ILinkUrlProvider;
use Tuleap\Docman\Notifications\NotifiedPeopleRetriever;
use Tuleap\Docman\Notifications\UGroupsRetriever;
use Tuleap\Docman\Notifications\UgroupsUpdater;
use Tuleap\Docman\Notifications\UsersRetriever;
use Tuleap\Docman\Notifications\UsersToNotifyDao;
use Tuleap\Docman\Notifications\UsersUpdater;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_NotificationsManagerTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ILinkUrlProvider
     */
    private $link_url_provider;

    /**
     * @var Docman_NotificationsManager
     */
    private $notification_manager;

    /**
     * @var Tuleap\Docman\Notifications\UsersRetriever
     */
    private $users_retriever;

    /**
     * @var Tuleap\Docman\Notifications\UGroupsRetriever
     */
    private $ugroups_retriever;

    protected function setUp(): void
    {
        parent::setUp();

        $this->users_retriever   = Mockery::mock(UsersRetriever::class);
        $this->ugroups_retriever = Mockery::mock(UGroupsRetriever::class);
        $project                 = Mockery::mock(Project::class);
        $project->shouldReceive('isError')->andReturn(false);
        $project->shouldReceive('getPublicName')->andReturn("My project");
        $project->shouldReceive('getTruncatedEmailsUsage')->andReturn(false);
        $this->link_url_provider    = Mockery::mock(ILinkUrlProvider::class);
        $this->notification_manager = new Docman_NotificationsManager(
            $project,
            $this->link_url_provider,
            Mockery::mock(Feedback::class),
            Mockery::mock(MailBuilder::class),
            Mockery::mock(UsersToNotifyDao::class),
            $this->users_retriever,
            $this->ugroups_retriever,
            Mockery::mock(NotifiedPeopleRetriever::class),
            Mockery::mock(UsersUpdater::class),
            Mockery::mock(UgroupsUpdater::class)
        );
    }

    public function testSendNotificationsSuccess(): void
    {
        $mail = \Mockery::mock(Codendi_Mail_Interface::class);
        $mail->shouldReceive('send')->andReturns(true);

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getEmail')->andReturn('foo@codendi.org');
        $this->notification_manager->_messages = [
            [
                'title'   => 'Move',
                'content' => 'Changed',
                'to'      => [$user]
            ]
        ];

        $this->notification_manager->sendNotifications('', '');
    }

    public function testGetMessageForUserSameListenedItem(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getRealName')->andReturn('John Doe');
        $user->shouldReceive('getId')->andReturn(2);
        $params['path'] = Mockery::mock(Docman_Path::class);
        $params['path']->shouldReceive('get')->andReturn('Folder1/Folder2/File');
        $params['item'] = Mockery::mock(Docman_Item::class);
        $params['item']->shouldReceive('getId')->andReturn(1);
        $this->users_retriever->shouldReceive('getListeningUsers')->andReturn(
            [$user->getId() => $params['item']]
        );
        $params['wiki_page'] = 'wiki';
        $params['url']       = 'http://www.example.com/plugins/docman/';

        $details_link_url = "http://www.example.com/plugins/docman/&action=details&section=notifications&id=1";
        $this->link_url_provider->shouldReceive('getDetailsLinkUrl')->andReturn($details_link_url);

        $notifications_link_url = "http://www.example.com/plugins/docman/&action=details&section=notifications&id=1";
        $this->link_url_provider->shouldReceive('getNotificationLinkUrl')->andReturn($notifications_link_url);

        $plugin_link_url = "http://www.example.com/plugins/docman/";
        $this->link_url_provider->shouldReceive('getPluginLinkUrl')->andReturn($plugin_link_url);

        $message1 = "Folder1/Folder2/File has been modified by John Doe.\n$details_link_url\n\n\n--------------------------------------------------------------------\nYou are receiving this message because you are monitoring this item.\nTo stop monitoring, please visit:\n$notifications_link_url";
        $message2 = "Folder1/Folder2/File has been modified by John Doe.\n$details_link_url\n\n\n--------------------------------------------------------------------\nYou are receiving this message because you are monitoring this item.\nTo stop monitoring, please visit:\n$notifications_link_url";
        $message3 = "New version of wiki wiki page was created by John Doe.\n$plugin_link_url\n\n\n--------------------------------------------------------------------\nYou are receiving this message because you are monitoring this item.\nTo stop monitoring, please visit:\n$notifications_link_url";
        $message4 = "Something happen!\n\n--------------------------------------------------------------------\nYou are receiving this message because you are monitoring this item.\nTo stop monitoring, please visit:\n$notifications_link_url";

        $this->assertEquals($message1, $this->notification_manager->_getMessageForUser($user, 'modified', $params));
        $this->assertEquals($message2, $this->notification_manager->_getMessageForUser($user, 'new_version', $params));
        $this->assertEquals(
            $message3,
            $this->notification_manager->_getMessageForUser($user, 'new_wiki_version', $params)
        );
        $this->assertEquals(
            $message4,
            $this->notification_manager->_getMessageForUser($user, 'something happen', $params)
        );
    }

    public function testGetMessageForUserParentListened(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getRealName')->andReturn('John Doe');
        $user->shouldReceive('getId')->andReturn(2);
        $params['path'] = Mockery::mock(Docman_Path::class);
        $params['path']->shouldReceive('get')->andReturn('Folder1/Folder2/File');
        $params['item'] = Mockery::mock(Docman_Item::class);
        $params['item']->shouldReceive('getId')->andReturn(10);
        $parent_item = Mockery::mock(Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturn(1);
        $this->users_retriever->shouldReceive('getListeningUsers')->andReturn([$user->getId() => $parent_item]);
        $params['wiki_page'] = 'wiki';
        $params['url']       = 'http://www.example.com/plugins/docman/';

        $details_link_url = "http://www.example.com/plugins/docman/&action=details&section=notifications&id=1";
        $this->link_url_provider->shouldReceive('getDetailsLinkUrl')->andReturn($details_link_url);

        $notifications_link_url = "http://www.example.com/plugins/docman/&action=details&section=notifications&id=1";
        $this->link_url_provider->shouldReceive('getNotificationLinkUrl')->andReturn($notifications_link_url);

        $message1 = "Folder1/Folder2/File has been modified by John Doe.\n$details_link_url\n\n\n--------------------------------------------------------------------\nYou are receiving this message because you are monitoring this item.\nTo stop monitoring, please visit:\n$notifications_link_url";
        $message2 = "Folder1/Folder2/File has been modified by John Doe.\n$details_link_url\n\n\n--------------------------------------------------------------------\nYou are receiving this message because you are monitoring this item.\nTo stop monitoring, please visit:\n$notifications_link_url";
        $message3 = "New version of wiki wiki page was created by John Doe.\nhttp://www.example.com/plugins/docman/\n\n\n--------------------------------------------------------------------\nYou are receiving this message because you are monitoring this item.\nTo stop monitoring, please visit:\n$notifications_link_url";
        $message4 = "Something happen!\n\n--------------------------------------------------------------------\nYou are receiving this message because you are monitoring this item.\nTo stop monitoring, please visit:\n$notifications_link_url";

        $this->assertEquals($message1, $this->notification_manager->_getMessageForUser($user, 'modified', $params));
        $this->assertEquals($message2, $this->notification_manager->_getMessageForUser($user, 'new_version', $params));
        $this->assertEquals(
            $message3,
            $this->notification_manager->_getMessageForUser($user, 'new_wiki_version', $params)
        );
        $this->assertEquals(
            $message4,
            $this->notification_manager->_getMessageForUser($user, 'something happen', $params)
        );
    }

    public function testItReturnsTrueWhenAtLeastOneUserIsNotified(): void
    {
        $this->users_retriever->shouldReceive('doesNotificationExistByUserAndItemId')->andReturn(true)->once();
        $this->ugroups_retriever->shouldReceive('doesNotificationExistByUGroupAndItemId')->never();

        $this->assertTrue(
            $this->notification_manager->userExists('101', '201', PLUGIN_DOCMAN_NOTIFICATION)
        );
    }

    public function testItReturnsTrueWhenAtLeastAGroupIsNotified(): void
    {
        $this->users_retriever->shouldReceive('doesNotificationExistByUserAndItemId')->never();
        $this->ugroups_retriever->shouldReceive('doesNotificationExistByUGroupAndItemId')->andReturn(true)->once();

        $this->assertTrue(
            $this->notification_manager->ugroupExists('101', '201', PLUGIN_DOCMAN_NOTIFICATION)
        );
    }

    public function testItReturnsFalseWhenNoGroupAndNoUserReceiveNotifications(): void
    {
        $this->users_retriever->shouldReceive('doesNotificationExistByUserAndItemId')->andReturn(false)->once();
        $this->ugroups_retriever->shouldReceive('doesNotificationExistByUGroupAndItemId')->andReturn(false)->once();

        $this->assertFalse(
            $this->notification_manager->userExists('101', '201', PLUGIN_DOCMAN_NOTIFICATION)
        );
        $this->assertFalse(
            $this->notification_manager->ugroupExists('101', '201', PLUGIN_DOCMAN_NOTIFICATION)
        );
    }
}

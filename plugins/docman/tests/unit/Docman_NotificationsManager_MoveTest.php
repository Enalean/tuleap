<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

use Docman_Folder;
use Docman_NotificationsManager_Move;
use Docman_Path;
use Docman_PermissionsManager;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Document\LinkProvider\DocumentLinkProvider;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Docman_NotificationsManager_MoveTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotPascalCase
{
    private const string DETAILS_URL       = 'https://www.example.com/plugins/document/monocarbonic/preview/10';
    private const string NOTIFICATIONS_URL = 'https://www.example.com/plugins/docman/?group_id=101&action=details&section=notifications&id=1';

    private Docman_Path $path;
    private Docman_Folder $old_parent;
    private PFUser $user;
    private Docman_PermissionsManager&MockObject $permission_manager;
    private Docman_Folder $parent_folder;
    private Docman_Folder $folder;
    private Docman_NotificationsManager_Move&MockObject $notification_manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->notification_manager = $this->createPartialMock(Docman_NotificationsManager_Move::class, [
            'getUrlProvider',
            '_getMonitoredItemForUser',
            '_getPermissionsManager',
        ]);

        $this->folder             = new Docman_Folder(['item_id' => 1, 'title' => 'My title']);
        $this->old_parent         = new Docman_Folder(['item_id' => 100, 'title' => '/my/old/path']);
        $this->parent_folder      = new Docman_Folder(['item_id' => 10, 'title' => '/my/new/path']);
        $this->permission_manager = $this->createMock(Docman_PermissionsManager::class);
        $this->user               = UserTestBuilder::aUser()->withRealName('UserRealName')->build();
        $this->path               = new Docman_Path();
        $link_provider            = new DocumentLinkProvider(
            'https://www.example.com',
            ProjectTestBuilder::aProject()->withUnixName('monocarbonic')->build(),
        );
        $this->notification_manager->method('getUrlProvider')->willReturn($link_provider);
    }

    public function testItBuildMovedMessageForUser(): void
    {
        $params['item']         = $this->folder;
        $params['parent']       = $this->parent_folder;
        $params['user_monitor'] = $this->user;
        $params['old_parent']   = $this->old_parent;
        $params['path']         = $this->path;

        $this->notification_manager->method('_getMonitoredItemForUser')->willReturn($this->folder);
        $this->notification_manager->method('_getPermissionsManager')->willReturn($this->permission_manager);
        $this->permission_manager->method('userCanAccess')->willReturn(true);

        $message = $this->notification_manager->_getMessageForUser(
            $this->user,
            $this->notification_manager::MESSAGE_MOVED,
            $params
        );

        $expected_message  = "My title has been modified by UserRealName.\n";
        $expected_message .= self::DETAILS_URL . "\n\n";
        $expected_message .= "Moved from:\n /my/old/path\n        to:\n /my/new/path\n\n";
        $expected_message .= "--------------------------------------------------------------------\n";
        $expected_message .= "You are receiving this message because you are monitoring this item.\n";
        $expected_message .= "To stop monitoring, please visit:\n";
        $expected_message .= self::NOTIFICATIONS_URL;

        self::assertEquals($expected_message, $message);
    }

    public function testItBuildMovedFromMessageForUser(): void
    {
        $params['item']         = $this->folder;
        $params['parent']       = $this->parent_folder;
        $params['user_monitor'] = $this->user;
        $params['path']         = $this->path;
        $params['old_parent']   = $this->old_parent;

        $this->notification_manager->method('_getMonitoredItemForUser')->willReturn($this->folder);
        $this->notification_manager->method('_getPermissionsManager')->willReturn($this->permission_manager);
        $this->permission_manager->method('userCanAccess')->willReturn(true);

        $message = $this->notification_manager->_getMessageForUser(
            $this->user,
            $this->notification_manager::MESSAGE_MOVED_TO,
            $params
        );

        $expected_message  = "/my/new/path has been modified by UserRealName.\n";
        $expected_message .= self::DETAILS_URL . "\n\n";
        $expected_message .= "Moved from:\n /my/old/path to:\n /my/new/path\n\n";
        $expected_message .= "--------------------------------------------------------------------\n";
        $expected_message .= "You are receiving this message because you are monitoring this item.\n";
        $expected_message .= "To stop monitoring, please visit:\n";
        $expected_message .= self::NOTIFICATIONS_URL;

        self::assertEquals($expected_message, $message);
    }

    public function testItBuildMovedToMessageForUser(): void
    {
        $params['item']         = $this->folder;
        $params['parent']       = $this->parent_folder;
        $params['user_monitor'] = $this->user;
        $params['path']         = $this->path;
        $params['old_parent']   = $this->old_parent;

        $this->notification_manager->method('_getMonitoredItemForUser')->willReturn($this->folder);
        $this->notification_manager->method('_getPermissionsManager')->willReturn($this->permission_manager);
        $this->permission_manager->method('userCanAccess')->willReturn(true);

        $message = $this->notification_manager->_getMessageForUser(
            $this->user,
            $this->notification_manager::MESSAGE_MOVED_FROM,
            $params
        );

        $expected_message  = "/my/old/path has been modified by UserRealName.\n";
        $expected_message .= self::DETAILS_URL . "\n\n";
        $expected_message .= "Moved from:\n /my/old/path to:\n /my/new/path\n\n";
        $expected_message .= "--------------------------------------------------------------------\n";
        $expected_message .= "You are receiving this message because you are monitoring this item.\n";
        $expected_message .= "To stop monitoring, please visit:\n";
        $expected_message .= self::NOTIFICATIONS_URL;

        self::assertEquals($expected_message, $message);
    }

    public function testItBuildMovedWithoutSeparationIfUserCanNotAccess(): void
    {
        $params['item']         = $this->folder;
        $params['parent']       = $this->parent_folder;
        $params['user_monitor'] = $this->user;
        $params['old_parent']   = $this->old_parent;
        $params['path']         = $this->path;

        $this->notification_manager->method('_getMonitoredItemForUser')->willReturn($this->folder);
        $this->notification_manager->method('_getPermissionsManager')->willReturn($this->permission_manager);
        $this->permission_manager->method('userCanAccess')->willReturn(false);

        $message = $this->notification_manager->_getMessageForUser(
            $this->user,
            $this->notification_manager::MESSAGE_MOVED,
            $params
        );

        $expected_message  = "My title has been modified by UserRealName.\n";
        $expected_message .= self::DETAILS_URL . "\n\n";
        $expected_message .= "Moved \n\n";
        $expected_message .= "--------------------------------------------------------------------\n";
        $expected_message .= "You are receiving this message because you are monitoring this item.\n";
        $expected_message .= "To stop monitoring, please visit:\n";
        $expected_message .= self::NOTIFICATIONS_URL;

        self::assertEquals($expected_message, $message);
    }
}

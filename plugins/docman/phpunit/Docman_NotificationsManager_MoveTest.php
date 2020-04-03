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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\ExternalLinks\ILinkUrlProvider;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_NotificationsManager_MoveTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ILinkUrlProvider
     */
    private $link_provider;
    /**
     * @var Docman_Path|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $path;
    /**
     * @var Docman_Folder|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $old_parent;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var Docman_PermissionsManager|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $permission_manager;
    /**
     * @var Docman_Folder|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $parent_folder;
    /**
     * @var Docman_Folder|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $folder;

    /**
     * @var Docman_NotificationsManager_Move
     */
    private $notification_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notification_manager       = Mockery::mock(Docman_NotificationsManager_Move::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $this->notification_manager->_url = "http://www.example.com/plugins/docman/";

        $this->folder = Mockery::mock(Docman_Folder::class);

        $this->old_parent = Mockery::mock(Docman_Folder::class);

        $this->parent_folder = Mockery::mock(Docman_Folder::class);
        $this->parent_folder->shouldReceive('getId')->once()->andReturn(10);

        $this->permission_manager = Mockery::mock(Docman_PermissionsManager::class);

        $this->user = Mockery::mock(PFUser::class);
        $this->user->shouldReceive('getRealName')->once()->andReturn('UserRealName');

        $this->path = Mockery::mock(Docman_Path::class);
        $this->path->shouldReceive('get')->withArgs([$this->old_parent])->andReturn("/my/old/path");
        $this->path->shouldReceive('get')->withArgs([$this->parent_folder])->andReturn("/my/new/path");

        $this->link_provider = Mockery::mock(ILinkUrlProvider::class);
        $this->notification_manager->shouldReceive('getUrlProvider')->andReturn($this->link_provider);
    }

    public function testItBuildMovedMessageForUser(): void
    {
        $params['item']         = $this->folder;
        $params['parent']       = $this->parent_folder;
        $params['user_monitor'] = $this->user;
        $params['old_parent']   = $this->old_parent;
        $params['path']         = $this->path;
        $this->old_parent->shouldReceive('getId')->andReturn(100);

        $this->notification_manager->shouldReceive('_getMonitoredItemForUser')->andReturn($this->folder);
        $this->notification_manager->shouldReceive('_getPermissionsManager')->andReturn($this->permission_manager);
        $this->permission_manager->shouldReceive('userCanAccess')->andReturn(true);

        $details_url = "http://www.example.com/plugins/docman/project_name/preview/100/";
        $this->link_provider->shouldReceive('getShowLinkUrl')->andReturn($details_url);
        $notifications_url = "http://www.example.com/plugins/docman/&action=details&section=notifications&id=1";
        $this->link_provider->shouldReceive('getNotificationLinkUrl')->andReturn($notifications_url);

        $this->folder->shouldReceive('getTitle')->once()->andReturn("My title");

        $message = $this->notification_manager->_getMessageForUser(
            $this->user,
            $this->notification_manager::MESSAGE_MOVED,
            $params
        );

        $expected_message = "My title has been modified by UserRealName.\n";
        $expected_message .= $details_url . "\n\n";
        $expected_message .= "Moved from:\n /my/old/path\n        to:\n /my/new/path\n\n";
        $expected_message .= "--------------------------------------------------------------------\n";
        $expected_message .= "You are receiving this message because you are monitoring this item.\n";
        $expected_message .= "To stop monitoring, please visit:\n";
        $expected_message .= $notifications_url;

        $this->assertEquals($expected_message, $message);
    }

    public function testItBuildMovedFromMessageForUser(): void
    {
        $params['item']         = $this->folder;
        $params['parent']       = $this->parent_folder;
        $params['user_monitor'] = $this->user;
        $params['path']         = $this->path;
        $params['old_parent']   = $this->old_parent;
        $this->old_parent->shouldReceive('getId')->andReturn(100);

        $this->notification_manager->shouldReceive('_getMonitoredItemForUser')->andReturn($this->folder);
        $this->notification_manager->shouldReceive('_getPermissionsManager')->andReturn($this->permission_manager);
        $this->permission_manager->shouldReceive('userCanAccess')->andReturn(true);

        $details_url = "http://www.example.com/plugins/docman/project_name/preview/100/";
        $this->link_provider->shouldReceive('getShowLinkUrl')->andReturn($details_url);
        $notifications_url = "http://www.example.com/plugins/docman/&action=details&section=notifications&id=1";
        $this->link_provider->shouldReceive('getNotificationLinkUrl')->andReturn($notifications_url);

        $message = $this->notification_manager->_getMessageForUser(
            $this->user,
            $this->notification_manager::MESSAGE_MOVED_TO,
            $params
        );

        $expected_message = "/my/new/path has been modified by UserRealName.\n";
        $expected_message .= $details_url . "\n\n";
        $expected_message .= "Moved from:\n /my/old/path to:\n /my/new/path\n\n";
        $expected_message .= "--------------------------------------------------------------------\n";
        $expected_message .= "You are receiving this message because you are monitoring this item.\n";
        $expected_message .= "To stop monitoring, please visit:\n";
        $expected_message .= $notifications_url;

        $this->assertEquals($expected_message, $message);
    }

    public function testItBuildMovedToMessageForUser(): void
    {
        $params['item']         = $this->folder;
        $params['parent']       = $this->parent_folder;
        $params['user_monitor'] = $this->user;
        $params['path']         = $this->path;
        $params['old_parent']   = $this->old_parent;
        $this->old_parent->shouldReceive('getId')->andReturn(100);

        $this->notification_manager->shouldReceive('_getMonitoredItemForUser')->andReturn($this->folder);
        $this->notification_manager->shouldReceive('_getPermissionsManager')->andReturn($this->permission_manager);
        $this->permission_manager->shouldReceive('userCanAccess')->andReturn(true);

        $details_url = "http://www.example.com/plugins/docman/project_name/preview/100/";
        $this->link_provider->shouldReceive('getShowLinkUrl')->andReturn($details_url);
        $notifications_url = "http://www.example.com/plugins/docman/&action=details&section=notifications&id=1";
        $this->link_provider->shouldReceive('getNotificationLinkUrl')->andReturn($notifications_url);

        $message = $this->notification_manager->_getMessageForUser(
            $this->user,
            $this->notification_manager::MESSAGE_MOVED_FROM,
            $params
        );

        $expected_message = "/my/old/path has been modified by UserRealName.\n";
        $expected_message .= $details_url . "\n\n";
        $expected_message .= "Moved from:\n /my/old/path to:\n /my/new/path\n\n";
        $expected_message .= "--------------------------------------------------------------------\n";
        $expected_message .= "You are receiving this message because you are monitoring this item.\n";
        $expected_message .= "To stop monitoring, please visit:\n";
        $expected_message .= $notifications_url;

        $this->assertEquals($expected_message, $message);
    }

    public function testItBuildMovedWithoutSeparationIfUserCanNotAccess(): void
    {
        $params['item']         = $this->folder;
        $params['parent']       = $this->parent_folder;
        $params['user_monitor'] = $this->user;
        $params['old_parent']   = $this->old_parent;
        $params['path']         = $this->path;
        $this->old_parent->shouldReceive('getId')->andReturn(100);

        $this->notification_manager->shouldReceive('_getMonitoredItemForUser')->andReturn($this->folder);
        $this->notification_manager->shouldReceive('_getPermissionsManager')->andReturn($this->permission_manager);
        $this->permission_manager->shouldReceive('userCanAccess')->andReturn(false);

        $details_url = "http://www.example.com/plugins/docman/project_name/preview/100/";
        $this->link_provider->shouldReceive('getShowLinkUrl')->andReturn($details_url);
        $notifications_url = "http://www.example.com/plugins/docman/&action=details&section=notifications&id=1";
        $this->link_provider->shouldReceive('getNotificationLinkUrl')->andReturn($notifications_url);

        $this->folder->shouldReceive('getTitle')->once()->andReturn("My title");

        $message = $this->notification_manager->_getMessageForUser(
            $this->user,
            $this->notification_manager::MESSAGE_MOVED,
            $params
        );

        $expected_message = "My title has been modified by UserRealName.\n";
        $expected_message .= $details_url . "\n\n";
        $expected_message .= "Moved \n\n";
        $expected_message .= "--------------------------------------------------------------------\n";
        $expected_message .= "You are receiving this message because you are monitoring this item.\n";
        $expected_message .= "To stop monitoring, please visit:\n";
        $expected_message .= $notifications_url;

        $this->assertEquals($expected_message, $message);
    }
}

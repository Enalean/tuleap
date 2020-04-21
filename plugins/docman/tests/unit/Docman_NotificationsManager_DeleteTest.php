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
class Docman_NotificationsManager_DeleteTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ILinkUrlProvider
     */
    private $link_provider;
    /**
     * @var Docman_NotificationsManager_Delete
     */
    private $notification_manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notification_manager       = Mockery::mock(Docman_NotificationsManager_Delete::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $this->notification_manager->_url = "http://www.example.com/plugins/docman/";

        $this->link_provider = Mockery::mock(ILinkUrlProvider::class);
        $this->notification_manager->shouldReceive('getUrlProvider')->andReturn($this->link_provider);
    }

    public function testItBuildMessageRemovedForUser(): void
    {
        $folder = Mockery::mock(Docman_Folder::class);
        $folder->shouldReceive('getId')->andReturn(1);
        $params['parent'] = $folder;
        $params['path']   = Mockery::mock(Docman_Path::class);
        $params['path']->shouldReceive('get')->andReturn("/my/folder/parent");
        $item = Mockery::mock(Docman_File::class);
        $item->shouldReceive('getTitle')->andReturn("my file name");
        $item->shouldReceive('getId')->andReturn(100);
        $params['item'] = $item;
        $this->notification_manager->shouldReceive('_getMonitoredItemForUser')->andReturn($folder);

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getRealName')->andReturn("UserName");

        $plugin_url = "http://www.example.com/plugins/docman/";
        $this->link_provider->shouldReceive('getPluginLinkUrl')->andReturn($plugin_url);

        $message          = $this->notification_manager->_getMessageForUser(
            $user,
            $this->notification_manager::MESSAGE_REMOVED,
            $params
        );
        $expected_message = "/my/folder/parent has been removed by UserName.\n";
        $expected_message .= "You are receiving this message because you are monitoring this item.\n";
        $expected_message .= $plugin_url;
        $this->assertEquals($expected_message, $message);
    }

    public function testItBuildMessageRemovedFromForUser(): void
    {
        $folder = Mockery::mock(Docman_Folder::class);
        $folder->shouldReceive('getId')->andReturn(1);
        $params['parent'] = $folder;
        $params['path']   = Mockery::mock(Docman_Path::class);
        $params['path']->shouldReceive('get')->andReturn("/my/folder/parent");
        $item = Mockery::mock(Docman_File::class);
        $item->shouldReceive('getTitle')->andReturn("my file name");
        $item->shouldReceive('getId')->andReturn(100);
        $params['item'] = $item;
        $this->notification_manager->shouldReceive('_getMonitoredItemForUser')->andReturn($folder);

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getRealName')->andReturn("UserName");

        $details_url = "http://www.example.com/plugins/docman/project_name/preview/100/";
        $this->link_provider->shouldReceive('getShowLinkUrl')->andReturn($details_url);
        $notifications_url = "http://www.example.com/plugins/docman/&action=details&section=notifications&id=1";
        $this->link_provider->shouldReceive('getNotificationLinkUrl')->andReturn($notifications_url);

        $message          = $this->notification_manager->_getMessageForUser(
            $user,
            $this->notification_manager::MESSAGE_REMOVED_FROM,
            $params
        );
        $expected_message = "/my/folder/parent has been modified by UserName.\n";
        $expected_message .= $details_url . "\n\n";
        $expected_message .= "Removed:\nmy file name\n\n";
        $expected_message .= "--------------------------------------------------------------------\n";
        $expected_message .= "You are receiving this message because you are monitoring this item.\n";
        $expected_message .= "To stop monitoring, please visit:\n";
        $expected_message .= $notifications_url;
        $this->assertEquals($expected_message, $message);
    }
}

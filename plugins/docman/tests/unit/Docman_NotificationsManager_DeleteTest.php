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

use Docman_File;
use Docman_Folder;
use Docman_NotificationsManager_Delete;
use Docman_Path;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Document\LinkProvider\DocumentLinkProvider;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Docman_NotificationsManager_DeleteTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Docman_NotificationsManager_Delete&MockObject $notification_manager;

    protected function setUp(): void
    {
        $this->notification_manager = $this->createPartialMock(Docman_NotificationsManager_Delete::class, [
            'getUrlProvider',
            '_getMonitoredItemForUser',
        ]);

        $link_provider = new DocumentLinkProvider('https://www.example.com', ProjectTestBuilder::aProject()->build());
        $this->notification_manager->method('getUrlProvider')->willReturn($link_provider);
    }

    public function testItBuildMessageRemovedForUser(): void
    {
        $folder           = new Docman_Folder(['item_id' => 1, 'title' => '/my/folder/parent']);
        $params['parent'] = $folder;
        $params['path']   = new Docman_Path();
        $item             = new Docman_File(['item_id' => 100, 'title' => 'my file name']);
        $params['item']   = $item;
        $this->notification_manager->method('_getMonitoredItemForUser')->willReturn($folder);

        $plugin_url = 'https://www.example.com/plugins/document/testproject/';

        $message           = $this->notification_manager->_getMessageForUser(
            UserTestBuilder::aUser()->withRealName('UserName')->build(),
            $this->notification_manager::MESSAGE_REMOVED,
            $params
        );
        $expected_message  = "my file name has been removed by UserName.\n";
        $expected_message .= "You are receiving this message because you are monitoring this item.\n";
        $expected_message .= $plugin_url;
        self::assertEquals($expected_message, $message);
    }

    public function testItBuildMessageRemovedFromForUser(): void
    {
        $folder           = new Docman_Folder(['item_id' => 1, 'title' => '/my/folder/parent']);
        $params['parent'] = $folder;
        $params['path']   = new Docman_Path();
        $item             = new Docman_File(['item_id' => 100, 'title' => 'my file name']);
        $params['item']   = $item;
        $this->notification_manager->method('_getMonitoredItemForUser')->willReturn($folder);

        $user = UserTestBuilder::aUser()->withRealName('UserName')->build();

        $details_url       = 'https://www.example.com/plugins/document/testproject/preview/1';
        $notifications_url = 'https://www.example.com/plugins/docman/?group_id=101&action=details&section=notifications&id=1';

        $message           = $this->notification_manager->_getMessageForUser(
            $user,
            $this->notification_manager::MESSAGE_REMOVED_FROM,
            $params
        );
        $expected_message  = "/my/folder/parent has been modified by UserName.\n";
        $expected_message .= $details_url . "\n\n";
        $expected_message .= "Removed:\nmy file name\n\n";
        $expected_message .= "--------------------------------------------------------------------\n";
        $expected_message .= "You are receiving this message because you are monitoring this item.\n";
        $expected_message .= "To stop monitoring, please visit:\n";
        $expected_message .= $notifications_url;
        self::assertEquals($expected_message, $message);
    }
}

<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

namespace Tuleap\Git;

use Git_GitoliteDriver;
use Git_SystemEventManager;
use GitPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Tuleap\Test\PHPUnit\TestCase;

final class GitPluginPostSystemEvents extends TestCase
{
    private GitPlugin&MockObject $plugin;
    private Git_GitoliteDriver&MockObject $gitolite_driver;

    #[\Override]
    protected function setUp(): void
    {
        $this->plugin          = $this->createPartialMock(GitPlugin::class, [
            'getGitSystemEventManager',
            'getGitoliteDriver',
            'getLogger',
        ]);
        $this->gitolite_driver = $this->createMock(Git_GitoliteDriver::class);

        $this->plugin->method('getGitSystemEventManager')->willReturn($this->createMock(Git_SystemEventManager::class));
        $this->plugin->method('getGitoliteDriver')->willReturn($this->gitolite_driver);
        $this->plugin->method('getLogger')->willReturn(new NullLogger());
    }

    public function testItDoesNotProcessPostSystemEventsActionsIfNotGitRelated(): void
    {
        $this->gitolite_driver->expects(self::never())->method('commit');
        $this->gitolite_driver->expects(self::never())->method('push');

        $params = [
            'executed_events_ids' => [54156],
            'queue_name'          => 'owner',
        ];

        $this->plugin->post_system_events_actions($params);
    }
}

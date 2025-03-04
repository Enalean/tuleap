<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\Git\RemoteServer;

use Git_Driver_Gerrit_User;
use Git_RemoteServer_GerritServer;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GerritServerTest extends TestCase
{
    public function testItDoesNotNeedToCustomizeSSHConfigOfCodendiadmOrRoot(): void
    {
        $server = new Git_RemoteServer_GerritServer(
            1,
            'le_host',
            'le_ssh_port',
            'le_http_port',
            'le_login',
            'le_identity_file',
            '',
            false,
            '2.5',
            '1234',
            ''
        );

        $expected = 'ext::ssh -p le_ssh_port -i le_identity_file le_login@le_host %S le_project';
        self::assertEquals($expected, $server->getCloneSSHUrl('le_project'));
    }

    public function testItPrunesDefaultHTTPPortForAdminUrl(): void
    {
        $server = new Git_RemoteServer_GerritServer(
            1,
            'le_host',
            'le_ssh_port',
            '80',
            'le_login',
            'le_identity_file',
            '',
            false,
            '2.5',
            '1234',
            ''
        );

        self::assertEquals(
            'http://le_host/#/admin/projects/gerrit_project_name',
            $server->getProjectAdminUrl('gerrit_project_name')
        );
    }

    public function testItUseTheCustomHTTPPortForAdminUrl(): void
    {
        $server = new Git_RemoteServer_GerritServer(
            1,
            'le_host',
            'le_ssh_port',
            '8080',
            'le_login',
            'le_identity_file',
            '',
            false,
            '2.5',
            '1234',
            ''
        );

        self::assertEquals(
            'http://le_host:8080/#/admin/projects/gerrit_project_name',
            $server->getProjectAdminUrl('gerrit_project_name')
        );
    }

    public function testItGivesTheUrlToProjectRequests(): void
    {
        $server = new Git_RemoteServer_GerritServer(
            1,
            'le_host',
            'le_ssh_port',
            '8080',
            'le_login',
            'le_identity_file',
            '',
            false,
            '2.5',
            '1234',
            ''
        );

        self::assertEquals(
            'http://le_host:8080/#/q/project:gerrit_project_name,n,z',
            $server->getProjectUrl('gerrit_project_name')
        );
    }

    public function testItGivesTheUrlWithHTTPSToProjectRequestsIfWeUseSSL(): void
    {
        $server = new Git_RemoteServer_GerritServer(
            1,
            'le_host',
            'le_ssh_port',
            '8080',
            'le_login',
            'le_identity_file',
            '',
            true,
            '2.5',
            '1234',
            ''
        );

        self::assertEquals(
            'https://le_host:8080/#/q/project:gerrit_project_name,n,z',
            $server->getProjectUrl('gerrit_project_name')
        );
    }

    public function testItGivesTheReplicationKeyToProjectRequests(): void
    {
        $replication_key = '';
        $server          = new Git_RemoteServer_GerritServer(
            1,
            'le_host',
            'le_ssh_port',
            '8080',
            'le_login',
            'le_identity_file',
            $replication_key,
            false,
            '2.5',
            '1234',
            ''
        );

        self::assertEquals(
            $replication_key,
            $server->getReplicationKey()
        );
    }

    public function testItGivesTheCloneUrlForTheEndUserWhoWantToCloneRepository(): void
    {
        $server = new Git_RemoteServer_GerritServer(
            1,
            'le_host',
            '29418',
            '8080',
            'le_login',
            'le_identity_file',
            '',
            false,
            '2.5',
            '1234',
            ''
        );

        $user = $this->createMock(Git_Driver_Gerrit_User::class);
        $user->method('getSshUserName')->willReturn('blurp');

        self::assertEquals(
            'ssh://blurp@le_host:29418/gerrit_project_name.git',
            $server->getEndUserCloneUrl('gerrit_project_name', $user)
        );
    }
}

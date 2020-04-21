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

namespace Tuleap\Git\Gitolite\SSHKey\Provider;

use Git_RemoteServer_Dao;
use Git_RemoteServer_Gerrit_ReplicationSSHKey;
use Tuleap\Git\Gitolite\SSHKey\Key;

require_once __DIR__ . '/../../../../bootstrap.php';

class GerritServerTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItExtractsGerritServerSSHKey(): void
    {
        $replication_key = new Git_RemoteServer_Gerrit_ReplicationSSHKey();
        $replication_key->setGerritHostId(1);
        $server1_key = new Key(
            $replication_key->getUserName(),
            'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQDgTGQXojsjAemABiCqPS9k7h5VLigeNhfJFc1Xx3DRZ0B1+eCAI7IT65VzYEHlkW8pTK9IZO6yFLM5aYiLF5GD1VoDxP7zuslCU5gTIl1eWJzMQY/5mc4IP+8dk+p4CoTlXwU5xnZatUWwiF8PnaM2evga4sAwLHBZ8QqiNIaHEQ=='
        );
        $replication_key->setGerritHostId(3);
        $server3_key = new Key(
            $replication_key->getUserName(),
            'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQDgTGQXojsjAemABiCqPS9k7h5VLigeNhfJFc1Xx3DRZ0B1+eCAI7IT65VzYEHlkW8pTK9IZO6yFLM5aYiLF5GD1VoDxP7zuslCU5gTIl1eWJzMQY/5mc4IP+8dk+p4CoTlXwU5xnZatUWwiF8PnaM2evga4sAwLHBZ8QqiNIaHEQ=='
        );

        $gerrit_server_access_result = [
            array(
                'id'      => 1,
                'ssh_key' => $server1_key->getKey()
            ),
            array(
                'id'      => 3,
                'ssh_key' => $server3_key->getKey()
            )
        ];
        $gerrit_server_dao = \Mockery::mock(Git_RemoteServer_Dao::class);
        $gerrit_server_dao->shouldReceive('searchAllServersWithSSHKey')->andReturns($gerrit_server_access_result);

        $gerrit_server_provider = new GerritServer($gerrit_server_dao);
        $expected_result        = array($server1_key, $server3_key);

        $this->assertEquals($expected_result, array_values(iterator_to_array($gerrit_server_provider)));
    }

    public function testItThrowsAnExceptionIfGerritServerDataCanNotBeAccessed(): void
    {
        $gerrit_server_dao = \Mockery::mock(Git_RemoteServer_Dao::class);
        $gerrit_server_dao->shouldReceive('searchAllServersWithSSHKey')->andThrows(new \PDOException());

        $this->expectException('Tuleap\Git\Gitolite\SSHKey\Provider\AccessException');
        new GerritServer($gerrit_server_dao);
    }
}

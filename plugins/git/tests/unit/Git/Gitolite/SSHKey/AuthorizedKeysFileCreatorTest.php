<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Gitolite\SSHKey;

use Mockery;
use Tuleap\Git\Gitolite\SSHKey\Provider\IProvideKey;

require_once __DIR__ . '/../../../bootstrap.php';

class AuthorizedKeysFileCreatorTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItGeneratesAuthorizedKeysFile(): void
    {
        $key1 = new Key('user1', 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQDgTGQXojsjAemABiCqPS9k7h5VLigeNhfJFc1Xx3DRZ0B1+eCAI7IT65VzYEHlkW8pTK9IZO6yFLM5aYiLF5GD1VoDxP7zuslCU5gTIl1eWJzMQY/5mc4IP+8dk+p4CoTlXwU5xnZatUWwiF8PnaM2evga4sAwLHBZ8QqiNIaHEQ==');
        $key2 = new Key('user2', 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQDF8IKZurK86EWGW2Q8jYkiXmkWZfEQ2SJlYnIylWMey0tRB5pr9G9oKbKt25RHigfeFJXgKIvPhAku5R08ejfoAG+/V3H8cXqf0zk0VxuIuTZk7OJ+8ll0i8x52Daepr102i7agnNk2c7CQ9Tz2+sXgYrMVPK4QroEOXY1rFCbHQ== user2@example.com');

        $keys = new class extends \ArrayIterator implements IProvideKey
        {
            //Do nothing
        };
        $keys->append($key1);
        $keys->append($key2);
        $system_command = Mockery::spy('System_Command');

        $temporary_file = tempnam(sys_get_temp_dir(), 'AuthorizedKeysFileCreatorUnitTests');

        $invalid_keys_collector       = new InvalidKeysCollector();
        $authorized_keys_file_creator = new AuthorizedKeysFileCreator($keys, $system_command);
        $authorized_keys_file_creator->dump($temporary_file, '/bin/false', 'some-options', $invalid_keys_collector);
        $generated_authorized_keys = file_get_contents($temporary_file);
        @unlink($temporary_file);

        $expected_authorized_keys = 'command="/bin/false user1",some-options ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQDgTGQXojsjAemABiCqPS9k7h5VLigeNhfJFc1Xx3DRZ0B1+eCAI7IT65VzYEHlkW8pTK9IZO6yFLM5aYiLF5GD1VoDxP7zuslCU5gTIl1eWJzMQY/5mc4IP+8dk+p4CoTlXwU5xnZatUWwiF8PnaM2evga4sAwLHBZ8QqiNIaHEQ==
command="/bin/false user2",some-options ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQDF8IKZurK86EWGW2Q8jYkiXmkWZfEQ2SJlYnIylWMey0tRB5pr9G9oKbKt25RHigfeFJXgKIvPhAku5R08ejfoAG+/V3H8cXqf0zk0VxuIuTZk7OJ+8ll0i8x52Daepr102i7agnNk2c7CQ9Tz2+sXgYrMVPK4QroEOXY1rFCbHQ== user2@example.com
';

        $this->assertEquals($expected_authorized_keys, $generated_authorized_keys);
        $this->assertFalse($invalid_keys_collector->hasInvalidKeys());
    }

    public function testItRejectsInvalidGeneratedKeysFile(): void
    {
        $key1 = new Key('invalid_user', 'ssh-rsa not_valid');
        $key2 = new Key('valid_user', 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQDF8IKZurK86EWGW2Q8jYkiXmkWZfEQ2SJlYnIylWMey0tRB5pr9G9oKbKt25RHigfeFJXgKIvPhAku5R08ejfoAG+/V3H8cXqf0zk0VxuIuTZk7OJ+8ll0i8x52Daepr102i7agnNk2c7CQ9Tz2+sXgYrMVPK4QroEOXY1rFCbHQ== user2@example.com');

        $keys = new class extends \ArrayIterator implements IProvideKey
        {
            //Do nothing
        };
        $keys->append($key1);
        $keys->append($key2);
        $system_command = Mockery::mock('System_Command');
        $system_command->shouldReceive('exec')->once()->andThrow(new \System_Command_CommandException('', array(), 1))->ordered();
        $system_command->shouldReceive('exec')->once()->andThrow(new \System_Command_CommandException('', array(), 1))->ordered();
        $system_command->shouldReceive('exec')->once()->andReturnTrue()->ordered();
        $temporary_file = tempnam(sys_get_temp_dir(), 'AuthorizedKeysFileCreatorUnitTests');

        $invalid_keys_collector       = new InvalidKeysCollector();
        $authorized_keys_file_creator = new AuthorizedKeysFileCreator($keys, $system_command);
        $authorized_keys_file_creator->dump($temporary_file, '/bin/false', 'some-options', $invalid_keys_collector);
        $generated_authorized_keys = file_get_contents($temporary_file);
        @unlink($temporary_file);

        $expected_authorized_keys = 'command="/bin/false valid_user",some-options ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQDF8IKZurK86EWGW2Q8jYkiXmkWZfEQ2SJlYnIylWMey0tRB5pr9G9oKbKt25RHigfeFJXgKIvPhAku5R08ejfoAG+/V3H8cXqf0zk0VxuIuTZk7OJ+8ll0i8x52Daepr102i7agnNk2c7CQ9Tz2+sXgYrMVPK4QroEOXY1rFCbHQ== user2@example.com
';

        $this->assertEquals($expected_authorized_keys, $generated_authorized_keys);
        $this->assertTrue($invalid_keys_collector->hasInvalidKeys());
        $invalid_keys = $invalid_keys_collector->getInvalidKeys();
        $this->assertEquals($invalid_keys[0], $key1);
    }
}

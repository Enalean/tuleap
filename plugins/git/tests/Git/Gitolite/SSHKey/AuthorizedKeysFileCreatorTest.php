<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use TuleapTestCase;

require_once __DIR__ . '/../../../bootstrap.php';

class AuthorizedKeysFileCreatorTest extends TuleapTestCase
{
    public function itGeneratesAuthorizedKeysFile()
    {
        $key1 = new Key('user1', 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQDgTGQXojsjAemABiCqPS9k7h5VLigeNhfJFc1Xx3DRZ0B1+eCAI7IT65VzYEHlkW8pTK9IZO6yFLM5aYiLF5GD1VoDxP7zuslCU5gTIl1eWJzMQY/5mc4IP+8dk+p4CoTlXwU5xnZatUWwiF8PnaM2evga4sAwLHBZ8QqiNIaHEQ==');
        $key2 = new Key('user2', 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQDF8IKZurK86EWGW2Q8jYkiXmkWZfEQ2SJlYnIylWMey0tRB5pr9G9oKbKt25RHigfeFJXgKIvPhAku5R08ejfoAG+/V3H8cXqf0zk0VxuIuTZk7OJ+8ll0i8x52Daepr102i7agnNk2c7CQ9Tz2+sXgYrMVPK4QroEOXY1rFCbHQ== user2@example.com');

        $keys = mock('Tuleap\Git\Gitolite\SSHKey\Provider\IProvideKey');
        stub($keys)->current()->returnsAt(0, $key1);
        stub($keys)->valid()->returnsAt(0, true);
        stub($keys)->current()->returnsAt(1, $key2);
        stub($keys)->valid()->returnsAt(1, true);
        $system_command = mock('System_Command');
        $logger         = mock('Logger');
        stub($logger)->expectNever('warn');

        $temporary_file = tempnam(sys_get_temp_dir(), 'AuthorizedKeysFileCreatorUnitTests');

        $authorized_keys_file_creator = new AuthorizedKeysFileCreator($keys, $system_command, $logger);
        $authorized_keys_file_creator->dump($temporary_file, '/bin/false', 'some-options');
        $generated_authorized_keys = file_get_contents($temporary_file);
        @unlink($temporary_file);

        $expected_authorized_keys = 'command="/bin/false user1",some-options ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQDgTGQXojsjAemABiCqPS9k7h5VLigeNhfJFc1Xx3DRZ0B1+eCAI7IT65VzYEHlkW8pTK9IZO6yFLM5aYiLF5GD1VoDxP7zuslCU5gTIl1eWJzMQY/5mc4IP+8dk+p4CoTlXwU5xnZatUWwiF8PnaM2evga4sAwLHBZ8QqiNIaHEQ==
command="/bin/false user2",some-options ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQDF8IKZurK86EWGW2Q8jYkiXmkWZfEQ2SJlYnIylWMey0tRB5pr9G9oKbKt25RHigfeFJXgKIvPhAku5R08ejfoAG+/V3H8cXqf0zk0VxuIuTZk7OJ+8ll0i8x52Daepr102i7agnNk2c7CQ9Tz2+sXgYrMVPK4QroEOXY1rFCbHQ== user2@example.com
';

        $this->assertEqual($expected_authorized_keys, $generated_authorized_keys);
    }

    public function itRejectsInvalidGeneratedKeysFile()
    {
        $key1 = new Key('invalid_user', 'ssh-rsa not_valid');
        $key2 = new Key('valid_user', 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQDF8IKZurK86EWGW2Q8jYkiXmkWZfEQ2SJlYnIylWMey0tRB5pr9G9oKbKt25RHigfeFJXgKIvPhAku5R08ejfoAG+/V3H8cXqf0zk0VxuIuTZk7OJ+8ll0i8x52Daepr102i7agnNk2c7CQ9Tz2+sXgYrMVPK4QroEOXY1rFCbHQ== user2@example.com');

        $keys = mock('Tuleap\Git\Gitolite\SSHKey\Provider\IProvideKey');
        stub($keys)->current()->returnsAt(0, $key1);
        stub($keys)->valid()->returnsAt(0, true);
        stub($keys)->current()->returnsAt(1, $key2);
        stub($keys)->valid()->returnsAt(1, true);
        $system_command = mock('System_Command');
        stub($system_command)->throwAt(0, 'exec', new \System_Command_CommandException('', array(), 1));
        stub($system_command)->throwAt(1, 'exec', new \System_Command_CommandException('', array(), 1));
        $logger = mock('Logger');
        stub($logger)->expectOnce('warn');
        $temporary_file = tempnam(sys_get_temp_dir(), 'AuthorizedKeysFileCreatorUnitTests');

        $authorized_keys_file_creator = new AuthorizedKeysFileCreator($keys, $system_command, $logger);
        $authorized_keys_file_creator->dump($temporary_file, '/bin/false', 'some-options');
        $generated_authorized_keys = file_get_contents($temporary_file);
        @unlink($temporary_file);

        $expected_authorized_keys = 'command="/bin/false invalid_user",some-options ssh-rsa not_valid
command="/bin/false valid_user",some-options ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQDF8IKZurK86EWGW2Q8jYkiXmkWZfEQ2SJlYnIylWMey0tRB5pr9G9oKbKt25RHigfeFJXgKIvPhAku5R08ejfoAG+/V3H8cXqf0zk0VxuIuTZk7OJ+8ll0i8x52Daepr102i7agnNk2c7CQ9Tz2+sXgYrMVPK4QroEOXY1rFCbHQ== user2@example.com
';

        $this->assertEqual($expected_authorized_keys, $generated_authorized_keys);
    }
}

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

namespace Tuleap\Git\Gitolite\SSHKey\Provider;

use ArrayIterator;
use Tuleap\Git\Gitolite\SSHKey\Key;

require_once __DIR__ . '/../../../../bootstrap.php';

class UserTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItExtractsUserSSHKeys(): void
    {
        $key1_user1 = new Key('user1', 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQDgTGQXojsjAemABiCqPS9k7h5VLigeNhfJFc1Xx3DRZ0B1+eCAI7IT65VzYEHlkW8pTK9IZO6yFLM5aYiLF5GD1VoDxP7zuslCU5gTIl1eWJzMQY/5mc4IP+8dk+p4CoTlXwU5xnZatUWwiF8PnaM2evga4sAwLHBZ8QqiNIaHEQ== Home');
        $key2_user1 = new Key('user1', 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQDF8IKZurK86EWGW2Q8jYkiXmkWZfEQ2SJlYnIylWMey0tRB5pr9G9oKbKt25RHigfeFJXgKIvPhAku5R08ejfoAG+/V3H8cXqf0zk0VxuIuTZk7OJ+8ll0i8x52Daepr102i7agnNk2c7CQ9Tz2+sXgYrMVPK4QroEOXY1rFCbHQ== Work');
        $key1_user2 = new Key('user2', 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQC2qLUXmQdrRbYcSgdK2uTs/CHOyHQBOlotCp18WPcueL0d4DT/IDLGp5BFL6ePYS4rJslroGlPCkYDzY2jt7acOJp2oXy+bELaaNeYzW2DgYkGhOSZO/I292R1UXt+V/bFYYfqApFUw+s8UvPB7qUWJISmHbG4tVm4iYdiR2i1Uw==');

        $user1 = \Mockery::spy(\PFUser::class);
        $user1->shouldReceive('getUsername')->andReturns('user1');
        $user1->shouldReceive('getAuthorizedKeysArray')->andReturns(array($key1_user1->getKey(), $key2_user1->getKey()));
        $user2 = \Mockery::spy(\PFUser::class);
        $user2->shouldReceive('getUsername')->andReturns('user2');
        $user2->shouldReceive('getAuthorizedKeysArray')->andReturns(array($key1_user2->getKey()));
        $user_manager       = \Mockery::spy(\UserManager::class);
        $users_with_ssh_key = new ArrayIterator(array($user1, $user2));
        $user_manager->shouldReceive('getUsersWithSshKey')->andReturns($users_with_ssh_key);

        $user_with_ssh_key_provider = new User($user_manager);
        $expected_result            = array($key1_user1, $key2_user1, $key1_user2);
        $this->assertEquals($expected_result, array_values(iterator_to_array($user_with_ssh_key_provider)));
    }

    public function testItDoesNotFindSSHKeyIfNoUsersHaveUploadedOne(): void
    {
        $user_manager       = \Mockery::spy(\UserManager::class);
        $users_with_ssh_key = new ArrayIterator(array());
        $user_manager->shouldReceive('getUsersWithSshKey')->andReturns($users_with_ssh_key);

        $user_with_ssh_key_provider = new User($user_manager);

        $this->assertEmpty(iterator_to_array($user_with_ssh_key_provider));
    }
}

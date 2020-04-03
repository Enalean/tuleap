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

use Tuleap\GlobalLanguageMock;

require_once __DIR__ . '/../../../bootstrap.php';

class KeyTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    public function testItChecksUsernameValidity(): void
    {
        $this->expectException('\InvalidArgumentException');
        new Key(
            'This is not a valid " username',
            'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQDgTGQXojsjAemABiCqPS9k7h5VLigeNhfJFc1Xx3DRZ0B1+eCAI7IT65VzYEHlkW8pTK9IZO6yFLM5aYiLF5GD1VoDxP7zuslCU5gTIl1eWJzMQY/5mc4IP+8dk+p4CoTlXwU5xnZatUWwiF8PnaM2evga4sAwLHBZ8QqiNIaHEQ=='
        );
    }

    public function testItCleansKey(): void
    {
        $key = new Key(
            'user',
            '
            ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQDgTGQXojsjAemABiCqPS9k7h5VLigeNhfJFc1Xx3DRZ0B1+eCAI7IT65VzYEHlkW8pTK9IZO6yFLM5aYiLF5GD1VoDxP7zuslCU5gTIl1eWJzMQY/5mc4IP+8dk+p4CoTlXwU5xnZatUWwiF8PnaM2evga4sAwLHBZ8QqiNIaHEQ==
            '
        );

        $this->assertEquals(
            'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQDgTGQXojsjAemABiCqPS9k7h5VLigeNhfJFc1Xx3DRZ0B1+eCAI7IT65VzYEHlkW8pTK9IZO6yFLM5aYiLF5GD1VoDxP7zuslCU5gTIl1eWJzMQY/5mc4IP+8dk+p4CoTlXwU5xnZatUWwiF8PnaM2evga4sAwLHBZ8QqiNIaHEQ==',
            $key->getKey()
        );
    }

    public function testItAcceptsOnlyOneKey(): void
    {
        $this->expectException('\InvalidArgumentException');
        new Key(
            'user',
            'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQDgTGQXojsjAemABiCqPS9k7h5VLigeNhfJFc1Xx3DRZ0B1+eCAI7IT65VzYEHlkW8pTK9IZO6yFLM5aYiLF5GD1VoDxP7zuslCU5gTIl1eWJzMQY/5mc4IP+8dk+p4CoTlXwU5xnZatUWwiF8PnaM2evga4sAwLHBZ8QqiNIaHEQ==
            ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQDF8IKZurK86EWGW2Q8jYkiXmkWZfEQ2SJlYnIylWMey0tRB5pr9G9oKbKt25RHigfeFJXgKIvPhAku5R08ejfoAG+/V3H8cXqf0zk0VxuIuTZk7OJ+8ll0i8x52Daepr102i7agnNk2c7CQ9Tz2+sXgYrMVPK4QroEOXY1rFCbHQ=='
        );
    }
}

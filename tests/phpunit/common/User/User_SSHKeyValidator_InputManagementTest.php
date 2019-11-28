<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

class User_SSHKeyValidator_InputManagementTest extends User_SSHKeyValidatorBase // phpcs:ignore
{
    use \Tuleap\GlobalResponseMock, \Tuleap\GlobalLanguageMock;

    public function testItUpdatesWithOneKey() : void
    {
        $keys = $this->validator->validateAllKeys(array($this->key1));

        $this->assertCount(1, $keys);
        $this->assertEquals($this->key1, $keys[0]);
    }

    public function testItUpdatesWithTwoKeys() : void
    {
        $keys = $this->validator->validateAllKeys(array(
            $this->key1,
            $this->key2
        ));

        $this->assertCount(2, $keys);
        $this->assertEquals($this->key1, $keys[0]);
        $this->assertEquals($this->key2, $keys[1]);
    }

    public function testItUpdatesWithAnExtraSpaceAfterFirstKey() : void
    {
        $keys = $this->validator->validateAllKeys(array(
            $this->key1." ",
            $this->key2
        ));

        $this->assertCount(2, $keys);
        $this->assertEquals($this->key1, $keys[0]);
        $this->assertEquals($this->key2, $keys[1]);
    }

    public function testItUpdatesWithAnEmptyKey() : void
    {
        $keys = $this->validator->validateAllKeys(array(
            $this->key1,
            '',
            $this->key2
        ));

        $this->assertCount(2, $keys);
        $this->assertEquals($this->key1, $keys[0]);
        $this->assertEquals($this->key2, $keys[1]);
    }
}

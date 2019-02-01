<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\GitLFS\LFSObject;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class LFSObjectTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCanConstructValidObject()
    {
        $oid  = \Mockery::mock(LFSObjectID::class);
        $size = 123;

        $lfs_object = new LFSObject($oid, $size);

        $this->assertSame($oid, $lfs_object->getOID());
        $this->assertSame($size, $lfs_object->getSize());
    }

    public function testInvalidSizeIsRejected()
    {
        $this->expectException(\UnexpectedValueException::class);
        new LFSObject(\Mockery::mock(LFSObjectID::class), -123);
    }
}

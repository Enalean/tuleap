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

use PHPUnit\Framework\TestCase;

class LFSObjectIDTest extends TestCase
{
    public function testCanConstructValidOID()
    {
        $oid_value = 'ca978112ca1bbdcafac231b39a23dc4da786eff8147c4e72b9807785afee48bb';
        $oid       = new LFSObjectID($oid_value);

        $this->assertSame($oid_value, $oid->getValue());
    }

    public function testInvalidOIDValueIsRejected()
    {
        $this->expectException(\UnexpectedValueException::class);
        new LFSObjectID('invalid_oid');
    }
}

<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Reference;

use PHPUnit\Framework\TestCase;
use Reference;

final class ReferenceTest extends TestCase
{
    public function testScope() : void
    {
        $ref = new Reference(1, "art", "Goto artifact", '/tracker/?func=detail&aid=$1&group_id=$group_id', 'S', 'tracker', 'artifact', 1, 101);
        $this->assertTrue($ref->isSystemReference());
        $ref2 = new Reference(1, "art", "Goto artifact", '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', 'tracker', 'artifact', 1, 101);
        $this->assertFalse($ref2->isSystemReference());
    }

    public function testComputeNumParams() : void
    {
        $ref = new Reference(1, "art", "Goto artifact", '/tracker/?func=detail&aid=$1&group_id=$group_id', 'S', 'tracker', 'artifact', 1, 101);
        $this->assertSame(1, $ref->getNumParam());
        $ref = new Reference(1, "art", "Goto artifact", '/tracker/?func=detail&aid=$5&group_id=$group_id', 'S', 'tracker', 'artifact', 1, 101);
        $this->assertSame(5, $ref->getNumParam());
        $ref = new Reference(1, "test", "Goto test", '/test/?proj=$projname&param1=$1&param5=$5&param3=$3&param4=$4&param2=$2&testname=$0&group_id=$group_id', 'P', 'tracker', 'artifact', 1, 101);
        $this->assertSame(5, $ref->getNumParam());
        $ref = new Reference(1, "test", "Goto test", '/test/?proj=$projname&param1=$1&param5=$1&param3=$1&param4=$1&param2=$1&testname=$0&group_id=$group_id', 'P', 'tracker', 'artifact', 1, 101);
        $this->assertSame(1, $ref->getNumParam());
    }


    public function testReplace() : void
    {
        // Test with full list
        $ref = new Reference(1, "test", "Goto test", '/test/?proj=$projname&param1=$1&param5=$5&param3=$3&param4=$4&param2=$2&testname=$0&group_id=$group_id', 'P', 'tracker', 'artifact', 1, 101);
        $args = array('arg1','arg2','arg3','arg4','arg5');
        $ref->replaceLink($args, 'name');
        $this->assertSame('/test/?proj=name&param1=arg1&param5=arg5&param3=arg3&param4=arg4&param2=arg2&testname=test&group_id=101', $ref->getLink());

        // real one
        $ref = new Reference(1, "art", "Goto artifact", '/tracker/?func=detail&aid=$1&group_id=$group_id', 'S', 'tracker', 'artifact', 1, 101);
        $args = array(1000);
        $ref->replaceLink($args);
        $this->assertSame('/tracker/?func=detail&aid=1000&group_id=101', $ref->getLink());
    }
}

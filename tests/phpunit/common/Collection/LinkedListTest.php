<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Collection;

use LinkedList;
use PHPUnit\Framework\TestCase;
use stdClass;

class LinkedListTest extends TestCase
{
    public function testOrder(): void
    {
        $a = new stdClass();
        $b = new stdClass();
        $c = new stdClass();
        $l = new LinkedList();
        $l->add($c);
        $l->add($b);
        $l->add($a);
        $it = $l->iterator();
        $element = $it->current();
        $this->assertSame($element, $c);
        $it->next();
        $element = $it->current();
        $this->assertSame($element, $b);
        $it->next();
        $element = $it->current();
        $this->assertSame($element, $a);
    }

    public function testEqualsDifferentOrder(): void
    {
        $a = new stdClass();
        $b = new stdClass();
        $l1 = new LinkedList();
        $l1->add($a);
        $l1->add($b);
        $l2 = new LinkedList();
        $l2->add($b);
        $l2->add($a);
        $this->assertFalse($l1->equals($l2));
    }
}

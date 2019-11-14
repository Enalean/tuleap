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

use PHPUnit\Framework\TestCase;
use PrioritizedList;
use stdClass;

class PrioritizedListTest extends TestCase
{
    public function testOrder(): void
    {
        $a = new stdClass();
        $b = new stdClass();
        $c = new stdClass();
        $l = new PrioritizedList();
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
        $l1 = new PrioritizedList();
        $l1->add($a);
        $l1->add($b);
        $l2 = new PrioritizedList();
        $l2->add($b);
        $l2->add($a);
        $this->assertFalse($l1->equals($l2));
    }

    public function testSimplePriority(): void
    {
        $a1 = new stdClass();
        $a2 = new stdClass();
        $b  = new stdClass();
        $c  = new stdClass();
        $l  = new PrioritizedList();
        $l->add($a2, 10);
        $l->add($a1, 10);
        $l->add($c, 30);
        $l->add($b, 20);
        $it = $l->iterator();
        $element = $it->current();
        $this->assertSame($element, $c);
        $it->next();
        $element = $it->current();
        $this->assertSame($element, $b);
        $it->next();
        $element = $it->current();
        $this->assertSame($element, $a2);
        $it->next();
        $element = $it->current();
        $this->assertSame($element, $a1);
    }

    public function testComplexePriority(): void
    {
        $a1 = new stdClass();
        $a2 = new stdClass();
        $a3 = new stdClass();
        $b1 = new stdClass();
        $b2 = new stdClass();
        $b3 = new stdClass();
        $c1 = new stdClass();
        $c2 = new stdClass();
        $c3 = new stdClass();
        $l  = new PrioritizedList();
        $l->add($a1, 10);
        $l->add($a2, 10);
        $l->add($a3, 10);
        $l->add($c2, 30);
        $l->add($c3, 30);
        $l->add($c1, 30);
        $l->add($b3, 20);
        $l->add($b2, 20);
        $l->add($b1, 20);
        $it = $l->iterator();
        $element = $it->current();
        $this->assertSame($element, $c2);
        $it->next();
        $element = $it->current();
        $this->assertSame($element, $c3);
        $it->next();
        $element = $it->current();
        $this->assertSame($element, $c1);
        $it->next();
        $element = $it->current();
        $this->assertSame($element, $b3);
        $it->next();
        $element = $it->current();
        $this->assertSame($element, $b2);
        $it->next();
        $element = $it->current();
        $this->assertSame($element, $b1);
        $it->next();
        $element = $it->current();
        $this->assertSame($element, $a1);
        $it->next();
        $element = $it->current();
        $this->assertSame($element, $a2);
        $it->next();
        $element = $it->current();
        $this->assertSame($element, $a3);
    }

    public function testNegativeAndDefaultPriority(): void
    {
        $a = new stdClass();
        $b = new stdClass();
        $c = new stdClass();
        $d = new stdClass();
        $l  = new PrioritizedList();
        $l->add($a, 10);
        $l->add($b, -10);
        $l->add($d);
        $l->add($c);
        $it = $l->iterator();
        $element = $it->current();
        $this->assertSame($element, $a);
        $it->next();
        $element = $it->current();
        $this->assertSame($element, $d);
        $it->next();
        $element = $it->current();
        $this->assertSame($element, $c);
        $it->next();
        $element = $it->current();
        $this->assertSame($element, $b);
    }

    public function testNegativeAndDefaultPriority2(): void
    {
        $a = new stdClass(); //'#1 (10)';
        $b = new stdClass(); //'#2 (-5)';
        $c = new stdClass(); //'#3 (-5)';
        $d = new stdClass(); //'#4 (-100)';
        $e = new stdClass(); //'#5 (-100)';
        $f = new stdClass(); //'#6 (-500)';
        $l = new PrioritizedList();
        $l->add($d, -100);
        $l->add($b, -5);
        $l->add($a, 10);
        $l->add($e, -100);
        $l->add($f, -500);
        $l->add($c, -5);
        $it = $l->iterator();
        $element = $it->current();
        $this->assertSame($element, $a);
        $it->next();
        $element = $it->current();
        $this->assertSame($element, $b);
        $it->next();
        $element = $it->current();
        $this->assertSame($element, $c);
        $it->next();
        $element = $it->current();
        $this->assertSame($element, $d);
        $it->next();
        $element = $it->current();
        $this->assertSame($element, $e);
        $it->next();
        $element = $it->current();
        $this->assertSame($element, $f);
    }
}

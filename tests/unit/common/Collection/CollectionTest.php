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

use Collection;
use PHPUnit\Framework\TestCase;
use stdClass;

class CollectionTest extends TestCase
{
    public function testEmptyCollection()
    {
        $c = new Collection();
        $this->assertTrue($c->isEmpty());
    }
    public function testNonEmptyCollection()
    {
        $c = new Collection();
        $a = new stdClass();
        $c->add($a);
        $this->assertFalse($c->isEmpty());
    }
    public function testContains()
    {
        $col = new Collection();
        $a = new stdClass();
        $a->toto = 1;
        $b = new stdClass();
        $b->toto = 2;
        $c = new stdClass();
        $c->toto = 3;
        $d = new stdClass();
        $d->toto = 4;
        $col->add($a);
        $col->add($b);
        $col->add($c);
        $this->assertTrue($col->contains($a));
        $this->assertTrue($col->contains($b));
        $this->assertTrue($col->contains($c));
        $this->assertFalse($col->contains($d));

        $key1 = 'key';
        $key2 = 'key';
        $col = new Collection();
        $this->assertFalse($col->contains($key2));
        $col->add($key1);
        $this->assertTrue($col->contains($key2));

        $key3_val = 'key';
        $key3 = $key3_val;
        $col = new Collection();
        $col->add($key3);
        $this->assertTrue($col->contains($key3_val));
    }
    public function testReference()
    {
        $col = new Collection();
        $a = new stdClass();
        $a->toto = 1;
        $b = new stdClass();
        $b->toto = 2;
        $col->add($a);
        $this->assertTrue($col->contains($a));
        $this->assertFalse($col->contains($b));
    }
    public function testSize()
    {
        $a = new stdClass();
        $a->toto = 1;
        $b = new stdClass();
        $b->toto = 2;
        $col = new Collection();
        $this->assertEquals($col->size(), 0);
        $col->add($a);
        $this->assertEquals($col->size(), 1);
        $col->add($a);
        $this->assertEquals($col->size(), 2);
        $col->add($b);
        $this->assertEquals($col->size(), 3);
    }

    public function testNotEqualsNotCollection()
    {
        $a = 'a';
        $col1 = new Collection();
        $this->assertFalse($col1->equals($a));
    }

    public function testEqualsNoElements()
    {
        $col1 = new Collection();
        $col2 = new Collection();
        $this->assertTrue($col1->equals($col2));
    }

    public function testNotEqualsOneElement()
    {
        $a = new stdClass();
        $col1 = new Collection();
        $col2 = new Collection();
        $col1->add($a);
        $this->assertFalse($col1->equals($col2));
    }

    public function testEqualsOneElement()
    {
        $a = new stdClass();
        $a->toto = 1;
        $b = new stdClass();
        $b->toto = 2;
        $col1 = new Collection();
        $col2 = new Collection();
        $col1->add($a);
        $col2->add($a);
        $this->assertTrue($col1->equals($col2));
    }

    public function testNotEqualsTwoElements()
    {
        $a = new stdClass();
        $a->toto = 1;
        $b = new stdClass();
        $b->toto = 2;
        $col1 = new Collection();
        $col2 = new Collection();
        $col1->add($a);
        $col2->add($a);
        $col1->add($b);
        $this->assertFalse($col1->equals($col2));
    }
    public function testEqualsTwoElements()
    {
        $a = new stdClass();
        $a->toto = 1;
        $b = new stdClass();
        $b->toto = 2;
        $col1 = new Collection();
        $col2 = new Collection();
        $col1->add($a);
        $col2->add($a);
        $col1->add($b);
        $col2->add($b);
        $this->assertTrue($col1->equals($col2));
    }

    public function testEqualsDifferentOrder()
    {
        $a = new stdClass();
        $a->toto = 1;
        $b = new stdClass();
        $b->toto = 2;
        $col1 = new Collection();
        $col2 = new Collection();
        $col1->add($a);
        $col1->add($b);
        $col2->add($b);
        $col2->add($a);
        $this->assertTrue($col1->equals($col2));
    }

    public function testEqualsDifferentSizes()
    {
        $a = new stdClass();
        $col1 = new Collection();
        $col2 = new Collection();
        $col1->add($a);
        $col1->add($a);
        $col2->add($a);
        $this->assertFalse($col1->equals($col2));
    }

    public function testEqualsSameAndDifferentElements()
    {
        $a = new stdClass();
        $a->toto = 1;
        $b = new stdClass();
        $b->toto = 2;
        $c = new stdClass();
        $c->toto = 3;
        $col1 = new Collection();
        $col2 = new Collection();
        $col1->add($a);
        $col2->add($a);
        $col1->add($b);
        $col2->add($c);
        $this->assertFalse($col1->equals($col2));
    }

    public function testEqualsUniqueAndNonUniqueElements()
    {
        $a = new stdClass();
        $a->toto = 1;
        $b = new stdClass();
        $b->toto = 2;
        $col1 = new Collection();
        $col2 = new Collection();
        $col1->add($a);
        $col1->add($a);
        $col2->add($a);
        $col2->add($b);
        $this->assertFalse($col1->equals($col2));
    }

    public function testInitialArray()
    {
        $a = new stdClass();
        $a->toto = 1;
        $b = new stdClass();
        $b->toto = 2;
        $arr = [];
        $arr[] = $a;
        $arr[] = $b;
        $col = new Collection($arr);
        $this->assertTrue($col->contains($a));
        $this->assertTrue($col->contains($b));
    }

    public function testRemove()
    {
        $a = new stdClass();
        $col = new Collection();
        $col->add($a);
        $this->assertTrue($col->contains($a));
        $this->assertTrue($col->remove($a));
        $this->assertFalse($col->contains($a));
        $col->remove($a);
        $this->assertFalse($col->remove($a));
    }
}

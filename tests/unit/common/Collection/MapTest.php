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
use Map;
use PHPUnit\Framework\TestCase;
use stdClass;

class MapTest extends TestCase
{
    public function testEmptyMap(): void
    {
        $m = new Map();
        $this->assertTrue($m->isEmpty());
    }
    public function testNonEmptyMap(): void
    {
        $key = 'key';
        $m = new Map();
        $value = 'value';
        $m->put($key, $value);
        $this->assertFalse($m->isEmpty());
    }
    public function testOneValue(): void
    {
        $key = 'key';
        $m = new Map();
        $value = new stdClass();
        $m->put($key, $value);
        $this->assertEquals($value, $m->get($key));
    }
    public function testNoValue(): void
    {
        $key = 'key';
        $false_key = 'false_key';
        $m = new Map();
        $value = new stdClass();
        $m->put($key, $value);
        $this->assertFalse($m->get($false_key));
    }
    public function testTwoValues(): void
    {
        $key1 = 'key1';
        $key2 = 'key2';
        $m = new Map();
        $value1 = new stdClass();
        $m->put($key1, $value1);
        $value2 = new stdClass();
        $m->put($key2, $value2);
        $this->assertEquals($value1, $m->get($key1));
        $this->assertEquals($value2, $m->get($key2));
    }
    public function testSize(): void
    {
        $key1 = 'key1';
        $key2 = 'key2';
        $m = new Map();
        $value1 = new stdClass();
        $m->put($key1, $value1);
        $value2 = new stdClass();
        $m->put($key2, $value2);
        $this->assertEquals($m->size(), 2);
    }
    public function testGetKeys(): void
    {
        $key1 = 'key1';
        $key2 = 'key2';
        $m = new Map();
        $value1 = new stdClass();
        $m->put($key1, $value1);
        $value2 = new stdClass();
        $m->put($key2, $value2);
        $keys = $m->getKeys();
        $expected = new Collection();
        $expected->add($key1);
        $expected->add($key2);
        $this->assertTrue($keys->equals($expected));
    }
    public function testContains(): void
    {
        $key            = 'key';
        $value          = 'value';
        $does_not_exist = 'does not exist';
        $m = new Map();
        $m->put($key, $value);
        $this->assertTrue($m->containsKey($key));
        $this->assertTrue($m->containsValue($value));
        $this->assertFalse($m->containsKey($does_not_exist));
        $this->assertFalse($m->containsValue($does_not_exist));
    }
    public function testEquals(): void
    {
        $key            = 'key';
        $value          = new stdClass();
        $m1 = new Map();
        $m2 = new Map();
        $this->assertTrue($m1->equals($m2));
        $m1->put($key, $value);
        $this->assertFalse($m1->equals($m2));
        $m2->put($key, $value);
        $this->assertTrue($m1->equals($m2));
        $key2 = 'key2';
        $m1->put($key2, $value);
        $m2->put($key2, $value);
        $this->assertTrue($m1->equals($m2));
    }
    public function testRemove(): void
    {
        $key            = 'key';
        $value          = new stdClass();
        $m = new Map();
        $m->put($key, $value);
        $this->assertTrue($m->containsKey($key));
        $this->assertTrue($m->containsValue($value));
        $this->assertTrue($m->remove($key, $value));
        $this->assertFalse($m->containsKey($key));
        $this->assertFalse($m->containsValue($value));
        $this->assertFalse($m->remove($key, $value));

        $key    = 'key';
        $value1 = 'value';
        $value2 = 'value';
        $m = new Map();
        $m->put($key, $value1);
        $this->assertTrue($m->remove($key, $value2));
    }
}

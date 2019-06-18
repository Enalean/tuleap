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

require_once('common/collection/Map.class.php');
require_once('common/collection/Collection.class.php');

class MapTest extends TuleapTestCase {

    function testEmptyMap() {
        $m = new Map();
        $this->assertTrue($m->isEmpty());
    }	
    function testNonEmptyMap() {
        $key = 'key';
        $m = new Map();
        $value = 'value';
        $m->put($key, $value);
        $this->assertFalse($m->isEmpty());
    }
    function testOneValue() {
        $key = 'key';
        $m = new Map();
        $value = new StdClass();
        $m->put($key, $value);
        $this->assertEqual($value, $m->get($key));
    }
    function testNoValue() {
        $key = 'key';
        $false_key = 'false_key';
        $m = new Map();
        $value = new StdClass();
        $m->put($key, $value);
        $this->assertFalse($m->get($false_key));
    }
    function testTwoValues() {
        $key1 = 'key1';
        $key2 = 'key2';
        $m = new Map();
        $value1 = new StdClass();
        $m->put($key1, $value1);
        $value2 = new StdClass();
        $m->put($key2, $value2);
        $this->assertEqual($value1, $m->get($key1));
        $this->assertEqual($value2, $m->get($key2));
    }
    function testSize() {
        $key1 = 'key1';
        $key2 = 'key2';
        $m = new Map();
        $value1 = new StdClass();
        $m->put($key1, $value1);
        $value2 = new StdClass();
        $m->put($key2, $value2);
        $this->assertEqual($m->size(), 2);
    }
    function testGetKeys() {
        $key1 = 'key1';
        $key2 = 'key2';
        $m = new Map();
        $value1 = new StdClass();
        $m->put($key1, $value1);
        $value2 = new StdClass();
        $m->put($key2, $value2);
        $keys = $m->getKeys();
        $expected = new Collection();
        $expected->add($key1);
        $expected->add($key2);
        $this->assertTrue($keys->equals($expected));
    }
    function testContains() {
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
    function testEquals() {
        $key            = 'key';
        $value          = new StdClass();
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
    function testRemove() {
        $key            = 'key';
        $value          = new StdClass();
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

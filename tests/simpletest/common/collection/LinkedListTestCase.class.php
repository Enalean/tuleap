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

require_once('CollectionTestCase.class.php');

class LinkedListTestCase extends CollectionTestCase {
    
    function __construct($name = 'Collection test', $collection_class_name = 'you_must_define_classname') {
        parent::__construct($name, $collection_class_name);
    }
    
    function testOrder() {
        $a = new StdClass();
        $b = new StdClass();
        $c = new StdClass();
        $l = new $this->collection_class_name();
        $l->add($c);
        $l->add($b);
        $l->add($a);
        $it = $l->iterator();
        $element = $it->current();
        $this->assertReference($element, $c);
        $it->next();
        $element = $it->current();
        $this->assertReference($element, $b);
        $it->next();
        $element = $it->current();
        $this->assertReference($element, $a);
    }
    
    function testEqualsDifferentOrder() {
        $a = new StdClass();
        $b = new StdClass();
        $l1 = new $this->collection_class_name();
        $l1->add($a);
        $l1->add($b);
        $l2 = new $this->collection_class_name();
        $l2->add($b);
        $l2->add($a);
        $this->assertFalse($l1->equals($l2));
    }

}
//We just tells SimpleTest to always ignore this testcase
SimpleTest::ignore('LinkedListTestCase');

?>

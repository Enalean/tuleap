<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once dirname(__FILE__).'/../../tools/continuous_integration/singletons/SingletonCounter.class.php';

/**
 * Avoid contaminating new classes with singleton lookup
 */
class NoMoreSingletonitusTest extends TuleapTestCase {
    
    public function testThereAreNoNewSingletonLookups() {
        $singleton_counter          = new SingletonCount();
        $expected_singleton_lookups = $singleton_counter->contentsOfCountFile();
        $actual_singleton_lookups   = $singleton_counter->countSingletonLookupsInProject();
        $new_singletons             = $actual_singleton_lookups - $expected_singleton_lookups;

        $this->assertFalse($new_singletons > 0, 
                "$new_singletons singleton lookup(s) was(were) introduced, please check to see if you can avoid this by injecting it(them)
                 before increasing the allowed number of singleton lookups");
        $this->assertFalse($new_singletons < 0, 
                "Great job! You removed one or more singleton lookups, you're a Dependency Injection champion!
                 please decrease the current_number_of_singleton_lookups variable in this test>
                 It should be : $actual_singleton_lookups");
    }
}

class SingletonCounterTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->counter = new SingletonCount();
        $this->value_b4_test = $this->counter->contentsOfCountFile();
    }
    
    public function itCanReplaceTheCurrentAmount() {
        $this->counter->replaceCurrentSingletonCountWith(0);
        $this->assertNotEqual($this->counter->contentsOfCountFile(), $this->counter->countSingletonLookupsInProject());
        
        $this->counter->replaceCurrentSingletonCountWithActualCount();
        $this->assertEqual($this->counter->contentsOfCountFile(), $this->counter->countSingletonLookupsInProject());
    }
    
    public function tearDown() {
        parent::tearDown();
        $this->counter->replaceCurrentSingletonCountWith($this->value_b4_test);
    }

}



?>

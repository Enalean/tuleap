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

/**
 * Avoid contaminating new classes with singleton lookup
 */
class NoMoreSingletonitusTest extends TuleapTestCase {
    
    public function testThereAreNoNewSingletonLookups() {
        $expected_singleton_lookups = file_get_contents(SINGLETON_COUNT_FILE);
        $basedir                    = dirname(__FILE__).'/../..';
        $singleton_counter          = new SingletonCount();
        $actual_singleton_lookups   = $singleton_counter->countSingletonLookupsInProject($basedir);
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

class SingletonCountTest extends TuleapTestCase {
    public function setUp() {
        parent::setUp();
        $this->current_value = file_get_contents(SINGLETON_COUNT_FILE);
    }
    public function itCanReplaceTheCurrentAmount() {
        $counter = new SingletonCount();
        file_put_contents(SINGLETON_COUNT_FILE, "0");

        $basedir                    = dirname(__FILE__).'/../..';
        
        $this->assertNotEqual(file_get_contents(SINGLETON_COUNT_FILE), $counter->countSingletonLookupsInProject($basedir));
        $counter->replaceCurrentSingletonCountWithActualCount($basedir);
        
        $this->assertEqual(file_get_contents(SINGLETON_COUNT_FILE), $counter->countSingletonLookupsInProject($basedir));
    }
    
    public function tearDown() {
        parent::tearDown();
        file_put_contents(SINGLETON_COUNT_FILE, $this->current_value);
    }

}

define("SINGLETON_COUNT_FILE", dirname(__FILE__).'/current_singleton_count.txt');

class SingletonCount {

    public function countSingletonLookupsInProject($basedir) {
        $dirs                       = "$basedir/plugins $basedir/src $basedir/tools";
        $count_command              = "grep -rc --exclude='*~' '::instance()' $dirs| awk -F: '{n=n+$2} END { print n}'";
        $output                     = $this->getSystemOutput($count_command);
        return $output[0];
    }

    private function getSystemOutput($cmd) {
        $result;
        exec($cmd, $result);
        return $result;
    }
   
    public function replaceCurrentSingletonCountWithActualCount($basedir) {
        file_put_contents(SINGLETON_COUNT_FILE, $this->countSingletonLookupsInProject($basedir));
    }

}

?>

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
    public function testThereAreNoNewClassFilesContainingSingletonLookups() {
        $expected_singleton_lookups = 746;
        $actual_singleton_lookups = $this->getSystemOutput('grep -rc  "::instance()" * | grep -v :0| grep -c ""');
        $this->assertTrue($actual_singleton_lookups[0] <= $expected_singleton_lookups, 
                "A new singleton lookup was introduced, please check to see if you can avoid this by injecting it
                 before increasing the allowed number of singleton lookups");
        $this->assertEqual($actual_singleton_lookups[0] >= $expected_singleton_lookups, 
                "Great job! You removed one or more singleton lookups, you're a Dependency Injection champion!
                 please decrease the current_number_of_singleton_lookups variable in this test>
                 It should be : $actual_singleton_lookups");
    }
    
    private function getSystemOutput($cmd) {
        $result;
        exec($cmd, $result);
        return $result;
    }

}

?>

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
        $expected_singleton_lookups = 2398;
        $output                     = $this->getSystemOutput("grep -rc  '::instance()' * | awk -F: 'BEGIN { n=0} {n=n+$2} END { print n}'");
        $actual_singleton_lookups   = $output[0];
        $new_singletons             = $actual_singleton_lookups - $expected_singleton_lookups;
        $this->assertTrue($actual_singleton_lookups <= $expected_singleton_lookups, 
                "$new_singletons singleton lookup(s) was(were) introduced, please check to see if you can avoid this by injecting it(them)
                 before increasing the allowed number of singleton lookups");
        $this->assertTrue($actual_singleton_lookups >= $expected_singleton_lookups, 
                "Great job! You removed one or more singleton lookups, you're a Dependency Injection champion!
                 please decrease the expected_singleton_lookups variable in this test
                 It should be : $actual_singleton_lookups");
    }
    
    private function getSystemOutput($cmd) {
        $result;
        exec($cmd, $result);
        return $result;
    }

}

?>
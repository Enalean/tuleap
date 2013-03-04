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

require_once dirname(__FILE__).'/../../../../../tools/continuous_integration/singletons/SingletonCounter.class.php';

/**
 * We're also testing the utility class, because it is used to update the expected singleton count as \
 * well as report on the current amount
 */
class SingletonCounterTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->counter = new SingletonCounter();
        $this->value_b4_test = $this->counter->expectedSingletonCount();
    }

    /**
     * Disabling test at is behaving deifferently on different boxes
     */
    public function _itCanReplaceTheCurrentAmount() {
        $this->counter->replaceExpectedSingletonCountWith(0);
        $this->assertNotEqual($this->counter->expectedSingletonCount(), $this->counter->countSingletonLookupsInProject());
        
        $this->counter->replaceExpectedSingletonCountWithActualCount();
        $this->assertEqual($this->counter->expectedSingletonCount(), $this->counter->countSingletonLookupsInProject());
    }
    
    public function tearDown() {
        parent::tearDown();
        $this->counter->replaceExpectedSingletonCountWith($this->value_b4_test);
    }

}
?>

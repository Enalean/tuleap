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

require_once dirname(__FILE__) . '/simpletest/test_case.php';

class TestsPluginOrderedSuite extends TestSuite {
    
    public function runByOrder(&$reporter, $order) {
        if ($order === 'invert') {
            $this->_test_cases = array_reverse($this->_test_cases);
        } elseif ($order === 'random') {
            shuffle($this->_test_cases);
        }
        $reporter->paintGroupStart($this->getLabel(), $this->getSize());
        for ($i = 0, $count = count($this->_test_cases); $i < $count; $i++) {
            if (is_string($this->_test_cases[$i])) {
                $class = $this->_test_cases[$i];
                $test = new $class();
                $test->run($reporter);
                unset($test);
            } elseif (get_class($this->_test_cases[$i]) === __CLASS__) {
                $this->_test_cases[$i]->runByOrder($reporter, $order);
            } else {
                $this->_test_cases[$i]->run($reporter);
            }
        }
        $reporter->paintGroupEnd($this->getLabel());
        return $reporter->getStatus();
    }
}
?>
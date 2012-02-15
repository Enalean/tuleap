<?php
/*
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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


require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

require_once('common/include/Response.class.php');
Mock::generate('Response');

require_once 'common/layout/Layout.class.php';
Mock::generate('Layout');

/**
 * Abstract class to use for unit tests inside Tuleap.
 *
 * It typically setUp globals objects like Response and Language, common in all the platform.
 */
abstract class TuleapTestCase extends UnitTestCase {
    
    /**
     * @var Save/restore the GLOBALS
     */
    private $globals;
    
    /**
     * SetUp a test (called before each test)
     */
    public function setUp() {
        $this->globals = array();  // it is too simple to do a $g = $GLOBALS;
        foreach ($GLOBALS as $key => $value) {
            $this->globals[$key] = $value;
        }
        $GLOBALS['Language'] = new MockBaseLanguage();
        $GLOBALS['HTML']     = new MockLayout();
        $GLOBALS['Response'] = $GLOBALS['HTML'];
    }
    
    /**
     * tearDown a test (called after each test)
     */
    function tearDown() {
        unset($GLOBALS['Response']);
        unset($GLOBALS['Language']);
        unset($GLOBALS['HTML']);
        $GLOBALS = $this->globals;
    }
    
}
?>

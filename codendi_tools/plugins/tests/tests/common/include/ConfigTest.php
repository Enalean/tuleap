<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/include/Config.class.php');

class ConfigTest extends UnitTestCase {
    
    public function setUp() {
        Config::store();
    }
    
    public function tearDown() {
        Config::restore();
    }
    
    public function testUsage() {
        $this->assertFalse(Config::get('toto'));
        $this->assertFalse(Config::load('does_not_exist'));
        $this->assertTrue(Config::load(dirname(__FILE__).'/_fixtures/config/local.inc'));
        $this->assertEqual(Config::get('toto'), 66);
        $this->assertFalse(Config::get('titi')); //not defined should return false
    }
    
    public function testDefault() {
        $this->assertEqual(Config::get('toto', 99), 99); //not defined should return default value given in parameter
        $this->assertTrue(Config::load(dirname(__FILE__).'/_fixtures/config/local.inc'));
        $this->assertEqual(Config::get('toto', 99), 66); //now it is defined. Should NOT return default value given in parameter
    }
    
    public function testMultipleFiles() {
        // Unitialized
        $this->assertIdentical(Config::get('toto'), false);
        $this->assertIdentical(Config::get('tutu'), false);
        $this->assertIdentical(Config::get('tata'), false);
        
        // Load the first file
        $this->assertTrue(Config::load(dirname(__FILE__).'/_fixtures/config/local.inc'));
        $this->assertIdentical(Config::get('toto'), 66);
        $this->assertIdentical(Config::get('tutu'), 123);
        $this->assertIdentical(Config::get('tata'), false);
        
        // Load the second one. Merge of the conf
        $this->assertTrue(Config::load(dirname(__FILE__).'/_fixtures/config/other_file.inc.dist'));
        $this->assertIdentical(Config::get('toto'), 66);
        $this->assertIdentical(Config::get('tutu'), 421);
        $this->assertIdentical(Config::get('tata'), 456);
    }
}
?>

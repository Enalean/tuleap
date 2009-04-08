<?php
/* 
 * Copyright (c) The Codendi Team, Xerox, 2009. All Rights Reserved.
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */


require_once('common/backend/Backend.class.php');


class BackendTest extends UnitTestCase {
    
    function __construct($name = 'BackendSystem test') {
        parent::__construct($name);
    }

    
    function testConstructor() {
        $backend = Backend::instance();
    }
    

    function testrecurseDeleteInDir() {
        $test_dir =  dirname(__FILE__).'/_fixtures/test_dir';
        mkdir($test_dir);

        // Create dummy dirs and files
        mkdir($test_dir."/test1");
        mkdir($test_dir."/test1/A");
        mkdir($test_dir."/test1/B");
        mkdir($test_dir."/test2");
        mkdir($test_dir."/test2/A");
        mkdir($test_dir."/test3");
   
        // Run tested method
        Backend::instance()->recurseDeleteInDir($test_dir);

        // Check result

        // Direcory should not be removed
        $this->assertTrue(is_dir($test_dir),"Directory $test_dir should still exist");
        // And should be empty
        $d = opendir($test_dir);
        while (($file = readdir($d)) !== false) {
            $this->assertTrue($file == "." || $file == "..", "Directory should be empty");
        }
        closedir($d);
        rmdir($test_dir);
    }

}
?>

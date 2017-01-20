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
require_once('common/include/Codendi_File.class.php');
class Codendi_FileTest extends TuleapTestCase {
    
    protected $small_file;
    protected $big_file;
    protected $no_sufficient_space;
    
    function __destruct() {
        if (file_exists($this->big_file)) {
            unlink($this->big_file);
        }
    }
    function __construct($name = 'Codendi_File test') {
        parent::__construct($name);
        $this->small_file     = dirname(__FILE__) . '/_fixtures/small_file';
        $this->big_file       = dirname(__FILE__) . '/_fixtures/big_file';
        $this->test_big_files = true;
        
        $realpath = $this->big_file;
        if (is_link($this->big_file)) {
            $realpath = readlink($this->big_file);
        }
        
        if (!file_exists($realpath) || @filesize($realpath) === 0) { //save same ci time, create the big file only once
            touch($realpath);
            if (!file_exists($realpath)) {
                $this->test_big_files = false;
                trigger_error("Unable to create $this->big_file. Cannot test big files.", E_USER_WARNING);
            } else if (`df  $realpath | tail -1 | awk '{print $4}'`  < 4200000) {
                unlink($realpath);
                $this->test_big_files = false;
                trigger_error("No sufficient space to create $this->big_file. Cannot test big files. Tip: link the file to a partition with more than 4Gb available.", E_USER_WARNING);
            } else {
                exec('dd if=/dev/zero of='. $realpath .' bs=1M count=4000');
            }
        }
    }
    
    function test_default_PHP_behavior() {
        $this->assertTrue(is_file($this->small_file));
        $this->assertEqual(filesize($this->small_file), 14);
        if ($this->test_big_files) {
            if (PHP_INT_SIZE == 4) {
                $this->assertFalse(is_file($this->big_file)); // PHP 32 bits behavior is wrong with files > 4Gb. 
                                                              // Hope that it will be fixed one day
                                                              // PHP6?
                $this->assertFalse(filesize($this->big_file));
                $this->assertError();
            } else {
                $this->assertTrue(is_file($this->big_file));
                $this->assertTrue(filesize($this->big_file));
            }
        }
    }
    
    function test_our_hacks() {
        $big_file = $this->big_file;
        if (is_link($this->big_file)) {
            $big_file = readlink($this->big_file);
        }
        
        $this->assertTrue(Codendi_File::isFile($this->small_file));
        $this->assertEqual(Codendi_File::getSize($this->small_file), 14);
        if ($this->test_big_files) {
            $this->assertTrue(Codendi_File::isFile($big_file));
            $this->assertTrue(Codendi_File::getSize($big_file) > 4000000000);
            $this->assertNoErrors();
        }
    }
}
?>

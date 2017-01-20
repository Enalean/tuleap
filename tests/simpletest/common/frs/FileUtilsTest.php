<?php

require_once('www/file/file_utils.php');

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * Tests the file_utils.php file
 */

class FileUtils extends TuleapTestCase {

    function testFileUtilsGetSize_1_Mo_File() {
        $file = dirname(__FILE__) . '/_fixtures/File_1_Mo';
        @copy(dirname(__FILE__) . '/_fixtures/File_1_Mo_sample', $file);
        $this->assertNotNull(file_utils_get_size($file));
        $this->assertTrue(file_utils_get_size($file) > 0);
        $this->assertTrue(file_utils_get_size($file) > 1000000);
        $this->assertTrue(file_utils_get_size($file) < 2000000);
        unlink($file);
    }
    
    function testFileUtilsGetSize_1_Mo_File_with_spaces() {
        $file = dirname(__FILE__) . '/_fixtures/File 1 Mo';
        @copy(dirname(__FILE__) . '/_fixtures/File_1_Mo_sample', $file);
        $this->assertNotNull(file_utils_get_size($file));
        $this->assertTrue(file_utils_get_size($file) > 0);
        $this->assertTrue(file_utils_get_size($file) > 1000000);
        $this->assertTrue(file_utils_get_size($file) < 2000000);
        unlink($file);
    }

    function testFileUtilsGetSize_1_Mo_File_with_spaces_and_quotes() {
        $file = dirname(__FILE__) . '/_fixtures/File "1" Mo';
        @copy(dirname(__FILE__) . '/_fixtures/File_1_Mo_sample', $file);
        $this->assertNotNull(file_utils_get_size($file));
        $this->assertTrue(file_utils_get_size($file) > 0);
        $this->assertTrue(file_utils_get_size($file) > 1000000);
        $this->assertTrue(file_utils_get_size($file) < 2000000);
        unlink($file);
    }
    
    function testFileUtilsGetSize_1_Mo_File_with_spaces_and_quote() {
        $file = dirname(__FILE__) . '/_fixtures/File "1 Mo';
        @copy(dirname(__FILE__) . '/_fixtures/File_1_Mo_sample', $file);
        $this->assertNotNull(file_utils_get_size($file));
        $this->assertTrue(file_utils_get_size($file) > 0);
        $this->assertTrue(file_utils_get_size($file) > 1000000);
        $this->assertTrue(file_utils_get_size($file) < 2000000);
        unlink($file);
    }
    
    function testFileUtilsGetSize_1_Mo_File_with_spaces_and_simple_quote() {
        $file = dirname(__FILE__) . "/_fixtures/File '1 Mo";
        @copy(dirname(__FILE__) . '/_fixtures/File_1_Mo_sample', $file);
        $this->assertNotNull(file_utils_get_size($file));
        $this->assertTrue(file_utils_get_size($file) > 0);
        $this->assertTrue(file_utils_get_size($file) > 1000000);
        $this->assertTrue(file_utils_get_size($file) < 2000000);
        unlink($file);
    }
}
?>

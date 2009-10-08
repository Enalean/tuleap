<?php

/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 * 
 * Originally written by Mohamed CHAARI, 2007.
 * 
 * This file is a part of codendi.
 * 
 * codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * 
 * $Id$
 */

require_once(dirname(__FILE__).'/../include/ForumML_FileStorage.class.php');

Mock::generatePartial('ForumML_FileStorage', 'ForumML_FileStorageTestVersion', array('fileExists'));

class ForumML_FileStorageTest extends UnitTestCase {
	private $_fixture;
    private $_namePattern;

	// Class constructor
	function __construct($name="ForumML Mail Attachments Storage Test") {
        parent::__construct($name);
        $this->_fixture     = dirname(__FILE__).'/_fixtures';
        // validchar for attachment name
        $this->_namePattern = "`[^a-z0-9_-]`i";
	}

    private function _deleteIfExists($path) {
        if (is_dir($path)) {
            rmdir($path);
        } elseif (file_exists($path)) {
            unlink($path);
        }
    }

    private function _getFileStorage($path) {
        $fs = new ForumML_FileStorageTestVersion($this);
        $fs->root = $path;
        $fs->setReturnValue('fileExists', false);
        return $fs;
    }

    function setUp() {
    }
	
    function tearDown() {
        $this->_deleteIfExists($this->_fixture.'/gpig-interest/2007_10_24/Screenshot_jpg');
        $this->_deleteIfExists($this->_fixture.'/gpig-interest/2007_10_24');
        $this->_deleteIfExists($this->_fixture.'/gpig-interest');

    }

	function testForumML_FileStorage() {
		$fstorage = $this->_getFileStorage($this->_fixture);
		$this->assertNotNull($fstorage->root);
		$this->assertIsA($fstorage->root, 'string');
		$this->assertEqual($fstorage->root,$this->_fixture);
		$this->assertNoErrors();
	}
	
    // case 1: an attachment file whose name has more than 64 characters  		
	function test_getPathFileNameWithMoreThan64Char() {
		$fs1 = $this->_getFileStorage($this->_fixture);
		$name1 = "a string with more than 64 characters, which is the limit allowed for ForumML attachments";
		$list1 = "gpig-interest";
		$date1 = "2007_10_24";
		$type1 = "store";

		// check returned path
		$path1 = $fs1->_getPath($name1,$list1,$date1,$type1);
		$this->assertNotNull($path1);
		$this->assertIsA($path1, 'string');
		$this->assertNoErrors();		
		// check filename length is restricted to 64 characters
		$path_array1 = explode("/",$path1);
		$fname1 = $path_array1[count($path_array1) - 1];
		$this->assertNotEqual($name1,$fname1);
		$this->assertEqual(strlen($fname1),63);
		// check other path components
		$flist1 = $path_array1[count($path_array1) - 3];
		$this->assertEqual($flist1,$list1);
		$fdate1 = $path_array1[count($path_array1) - 2];
		$this->assertEqual($fdate1,$date1);
		// check regexp
		$this->assertWantedPattern($this->_namePattern,$name1);
    }

    // case 2: an attachment file whose name has less than 64 characters
    function test_getPathFileNameWithLessThan64Char() {
        $fs1 = $this->_getFileStorage($this->_fixture);
		$name2 = "filename less than 64 chars";
		$list1 = "gpig-interest";
		$date1 = "2007_10_24";
		$type1 = "store";

		$path2 = $fs1->_getPath($name2,$list1,$date1,$type1);
		$this->assertNotNull($path2);
		$this->assertIsA($path2, 'string');
		$this->assertNoErrors();				
		$path_array2 = explode("/",$path2);
		$fname2 = $path_array2[count($path_array2) - 1];		
		$this->assertEqual($fname2,"filename_less_than_64_chars");				
		$this->assertNotEqual(strlen($fname2),64);
		// check path components
		$flist2 = $path_array2[count($path_array2) - 3];
		$this->assertEqual($flist2,$list1);
		$fdate2 = $path_array2[count($path_array2) - 2];
		$this->assertEqual($fdate2,$date1);		
		// check regexp		
		$this->assertWantedPattern($this->_namePattern,$name2);
    }

    // case 3: attachment filename with only alphanumeric characters
    function test_getPathFileNameWithAlphaNumCharsOnly() {
        $fs1 = $this->_getFileStorage($this->_fixture);
		$name3 = "Cx2008-requirements";
		$list1 = "gpig-interest";
		$date1 = "2007_10_24";
		$type1 = "store";

		$path3 = $fs1->_getPath($name3,$list1,$date1,$type1);
		$this->assertNotNull($path3);
		$this->assertIsA($path3, 'string');
		$this->assertNoErrors();		
		$path_array3 = explode("/",$path3);
		$fname3 = $path_array3[count($path_array3) - 1];
		$this->assertNoUnwantedPattern($this->_namePattern,$name3);
    }

    // case 4: attachment filename is an empty string
    function test_getPathFileNameEmpty() {
        $fs1 = $this->_getFileStorage($this->_fixture);
		$name4 = "";
		$list1 = "gpig-interest";
		$date1 = "2007_10_24";
		$type1 = "store";

		$path4 = $fs1->_getPath($name4,$list1,$date1,$type1);
		$this->assertNoErrors();
		$this->assertNotNull($path4);
		$this->assertIsA($path4, 'string');
		$path_array4 = explode("/",$path4);
		$fname4 = $path_array4[count($path_array4) - 1];
		$this->assertWantedPattern('/^attachment.*/', $fname4);
	}
	
    // case 5: same attachment name submitted 2 times same day for same list
    function testGetPathWithSameFileName() {
        $fs = new ForumML_FileStorageTestVersion($this);
        $fs->root = $this->_fixture;
        $fs->setReturnValueAt(0, 'fileExists', false);
        $fs->setReturnValueAt(1, 'fileExists', true);
 
        $list = "gpig-interest";
		$date = "2007_10_24";
		$type = "store";
        $name = 'Screenshot.jpg';

        // First file stored that day
        $path1 = $fs->_getPath($name,$list,$date,$type);

        // Second file with same name
        $path2 = $fs->_getPath($name,$list,$date,$type);

        $this->assertNotEqual($path1, $path2);
    }

}

?>
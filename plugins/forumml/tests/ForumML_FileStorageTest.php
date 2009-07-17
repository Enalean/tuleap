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

class ForumML_FileStorageTest extends UnitTestCase {
	
	// Class constructor
	function ForumML_FileStorageTest($name="ForumML Mail Attachments Storage Test") {

		$this->UnitTestCase($name);
	
	}
	
	function testForumML_FileStorage() {
		 
		$forumml_dir = "/tmp";
		$fstorage =& new ForumML_FileStorage($forumml_dir);
		$this->assertNotNull($fstorage->root);
		$this->assertIsA($fstorage->root, 'string');
		$this->assertEqual($fstorage->root,"/tmp");
		$this->assertIdentical($fstorage->root,"/tmp");
		$this->assertNoErrors();
		
	}
	
	function test_getPath() {
		
		$forumml_dir = "/tmp";
		$fs1 =& new ForumML_FileStorage($forumml_dir);
		
		// case 1: an attachment file whose name has more than 64 characters  		
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
		$froot1 = $path_array1[1];
		$this->assertEqual($froot1,"tmp");
		$flist1 = $path_array1[2];
		$this->assertEqual($flist1,$list1);
		$fdate1 = $path_array1[3];
		$this->assertEqual($fdate1,$date1);
		// check regexp
		$pattern1 = "`[^a-z0-9_-]`i";
		$this->assertWantedPattern($pattern1,$name1);

		// case 2: an attachment file whose name has less than 64 characters
		$name2 = "filename less than 64 chars";
		$path2 = $fs1->_getPath($name2,$list1,$date1,$type1);
		$this->assertNotNull($path2);
		$this->assertIsA($path2, 'string');
		$this->assertNoErrors();				
		$path_array2 = explode("/",$path2);
		$fname2 = $path_array2[count($path_array2) - 1];		
		$this->assertEqual($fname2,"filename_less_than_64_chars");				
		$this->assertNotEqual(strlen($fname2),64);
		// check path components
		$froot2 = $path_array2[1];
		$this->assertEqual($froot2,"tmp");
		$flist2 = $path_array2[2];
		$this->assertEqual($flist2,$list1);
		$fdate2 = $path_array2[3];
		$this->assertEqual($fdate2,$date1);		
		// check regexp		
		$this->assertWantedPattern($pattern1,$name2);
		
		// case 3: attachment filename with only alphanumeric characters
		$name3 = "Cx2008-requirements";
		$path3 = $fs1->_getPath($name3,$list1,$date1,$type1);
		$this->assertNotNull($path3);
		$this->assertIsA($path3, 'string');
		$this->assertNoErrors();		
		$path_array3 = explode("/",$path3);
		$fname3 = $path_array3[count($path_array3) - 1];
		$this->assertNoUnwantedPattern($pattern1,$name3);			
		
		// case 4: attachment filename is an empty string
		$name4 = "";
		$path4 = $fs1->_getPath($name4,$list1,$date1,$type1);
		$this->assertNoErrors();
		$this->assertNotNull($path4);
		$this->assertIsA($path4, 'string');
		$path_array4 = explode("/",$path4);
		$fname4 = $path_array4[count($path_array4) - 1];
		$this->assertEqual($fname4,$name4);
	}
	
}

?>
<?php
require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

require_once('common/reference/ReferenceManager.class.php');
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 *
 *
 *
 * Tests the class ReferenceManager
 */
class ReferenceManagerTest extends UnitTestCase {
	/**
	 * Constructor of the test. Can be ommitted.
	 * Usefull to set the name of the test
	 */
	function ReferenceManagerTest($name = 'ReferenceManager test') {
		$this->UnitTestCase($name);
	}

    function setUp() {
        $GLOBALS['Language'] = new MockBaseLanguage($this);
    }
    function tearDown() {
        unset($GLOBALS['Language']);
    }

	function testSingleton() {
		$this->assertReference(
		ReferenceManager::instance(),
		ReferenceManager::instance());
		$this->assertIsA(ReferenceManager::instance(), 'ReferenceManager');
	}


	function testKeyword() {
		//The Reference manager
		$rm =& new ReferenceManager();
		$this->assertFalse($rm->_isValidKeyword("UPPER"));
		$this->assertFalse($rm->_isValidKeyword("with space"));
		$this->assertFalse($rm->_isValidKeyword("with_special_char"));
		$this->assertFalse($rm->_isValidKeyword('with$pecialchar'));
		$this->assertFalse($rm->_isValidKeyword("with/special/char"));
		$this->assertFalse($rm->_isValidKeyword("with-special"));
		$this->assertFalse($rm->_isValidKeyword("-begin"));
		$this->assertFalse($rm->_isValidKeyword("end-"));
		$this->assertFalse($rm->_isValidKeyword("end "));

		$this->assertTrue($rm->_isValidKeyword("valid"));
		$this->assertTrue($rm->_isValidKeyword("valid123"));
		$this->assertTrue($rm->_isValidKeyword("123")); // should it be?

		$this->assertTrue($rm->_isReservedKeyword("art"));
		$this->assertTrue($rm->_isReservedKeyword("cvs"));
		$this->assertFalse($rm->_isReservedKeyword("artifacts"));
		$this->assertFalse($rm->_isReservedKeyword("john2"));
	}

	function testExtractReference() {
		$rm =& new ReferenceManager();
		$this->assertTrue(count($rm->extractReferences("art #123",0))==1,"Art is a shared keyword for all projects");
		$this->assertTrue(count($rm->extractReferences("arto #123",0))==0,"Should not extract a reference on unknown keyword");
		$this->assertTrue(count($rm->extractReferences("art #1:123",0))==1,"Art is a reference for project num 1");
		$this->assertTrue(count($rm->extractReferences("art #100:123",0))==1,"Art is a reference for project named codex");
	}

	function testExtractRegexp() {
		$rm =& new ReferenceManager();
		$this->assertFalse(count($rm->_extractAllMatches("art 123"))==1,"No sharp sign");
		$this->assertFalse(count($rm->_extractAllMatches("art#123"))==1,"No space");
		$this->assertFalse(count($rm->_extractAllMatches("art #"))==1,"No reference");

		$this->assertTrue(count($rm->_extractAllMatches("art #123"))==1,"simple reference");
		$this->assertTrue(count($rm->_extractAllMatches("art #abc"))==1,"No number");
		$this->assertTrue(count($rm->_extractAllMatches("art #abc:123"))==1,"groupName:ObjID");
		$this->assertTrue(count($rm->_extractAllMatches("art #123:123"))==1,"groupID:ObjID");
		$this->assertTrue(count($rm->_extractAllMatches("art #abc:abc"))==1,"groupName:ObjName");
		$this->assertTrue(count($rm->_extractAllMatches("art #123:abc"))==1,"groupID:ObjName");
		$this->assertTrue(count($rm->_extractAllMatches("art #123:abc is a reference to art #123 and rev #codex:123 as well as file #123:release1",0))==4,"Multiple extracts");
		$this->assertTrue(count($rm->_extractAllMatches("art #123-rev #123",0))==2,"Multiple extracts with '-'");
		$this->assertTrue(count($rm->_extractAllMatches("art #123:wikipage/2",0))==1,"Wikipage revision number");

		# Projectname with - and _ See SR #1178
		$refarray =array(0=>"art #abc-def:ghi", 1=>"art", 2=>"abc-def:", 3=>"ghi");
		$this->assertTrue(in_array($refarray,$rm->_extractAllMatches("art #abc-def:ghi")),"group-Name:ObjName");
		$refarray =array(0=>"art #abc-de_f:ghi", 1=>"art", 2=>"abc-de_f:", 3=>"ghi");
		$this->assertTrue(in_array($refarray,$rm->_extractAllMatches("art #abc-de_f:ghi")),"group-Na_me:ObjName");
	}
}
?>

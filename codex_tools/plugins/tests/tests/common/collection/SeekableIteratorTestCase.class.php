<?php
require_once('IteratorTestCase.class.php');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 */
class SeekableIteratorTestCase extends IteratorTestCase {
    
    var $iterator_class_name;
    
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function SeekableIteratorTestCase($name = 'SeekableIterator test', $iterator_class_name = 'you_must_define_classname') {
        $this->IteratorTestCase($name, $iterator_class_name);
    }

    function testSeekableIterator() {
        $array = array();
        $obj1 =& new stdClass();
        $obj2 =& new stdClass();
        $obj3 =& new stdClass();
        $array[] =& $obj1;
        $array[] =& $obj2;
        $array[] =& $obj3;
        $i =& new $this->iterator_class_name($array);
        
        $i->seek(1);
        $this->assertNoErrors();
        $this->assertReference($obj2, $i->current());
        $i->seek(2);
        $this->assertNoErrors();
        $this->assertReference($obj3, $i->current());
        $i->seek(0);
        $this->assertNoErrors();
        $this->assertReference($obj1, $i->current());
        $this->expectError();
        $i->seek(10);
    }
}
//We just tells SimpleTest to always ignore this testcase
SimpleTestOptions::ignore('SeekableIteratorTestCase');

?>

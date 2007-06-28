<?php
require_once('common/dao/include/DataAccessResult.class.php');
Mock::generatePartial('DataAccessResult', 'DataAccessResultTestVersion', array('current', 'valid', 'next', 'rewind', 'key'));
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * Tests the class DataAccessResult
 */
class DataAccessResultTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function DataAccessResultTest($name = 'DataAccessResult test') {
        $this->UnitTestCase($name);
    }

    function testDAR() {
        $dar =& new DataAccessResultTestVersion($this);
        $this->assertIsA($dar, 'Iterator');
    }
    
    function testGetRow() {
        $dar =& new DataAccessResultTestVersion($this);
        $dar->expectOnce('current');
        $dar->expectOnce('next');
        $tab = array('col' => 'value');
        $dar->setReturnReference('current', $tab);
        
        $this->assertIdentical($dar->getRow(), $tab);
        $dar->tally();
    }
}
?>

<?php
require_once('common/dao/include/DataAccessResult.class.php');
Mock::generatePartial('DataAccessResult', 'DataAccessResultTestVersion', array('current', 'valid', 'next', 'rewind', 'key'));
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * Tests the class DataAccessResult
 */
class DataAccessResultTest extends UnitTestCase {

    function testGetRow() {
        $dar = new DataAccessResultTestVersion($this);
        $dar->expectOnce('current');
        $dar->expectOnce('next');
        $tab = array('col' => 'value');
        $dar->setReturnReference('current', $tab);
        
        $this->assertIdentical($dar->getRow(), $tab);
    }
}
?>

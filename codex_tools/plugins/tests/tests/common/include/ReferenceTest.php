<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: HTTPRequestTest.php 1901 2005-08-18 14:54:55Z nterray $
 *
 * Tests the class Reference
 */

require_once('common/include/Reference.class.php');


class ReferenceTest extends UnitTestCase {
        
    function ReferenceTest($name = 'Reference test') {
        $this->UnitTestCase($name);
    }

    
    function testScope() {
        $ref =& new Reference(1,"art","Goto artifact",'/tracker/?func=detail&aid=$1&group_id=$group_id','S','tracker',1,101);
        $this->assertTrue($ref->isSystemReference());
        $ref2 =& new Reference(1,"art","Goto artifact",'/tracker/?func=detail&aid=$1&group_id=$group_id','P','tracker',1,101);
        $this->assertFalse($ref2->isSystemReference());
    }

    function testComputeNumParams() {
        $ref =& new Reference(1,"art","Goto artifact",'/tracker/?func=detail&aid=$1&group_id=$group_id','S','tracker',1,101);
        $this->assertIdentical($ref->getNumParam(),1);
        $ref =& new Reference(1,"art","Goto artifact",'/tracker/?func=detail&aid=$5&group_id=$group_id','S','tracker',1,101);
        $this->assertIdentical($ref->getNumParam(),5);
        $ref =& new Reference(1,"test","Goto test",'/test/?proj=$projname&param1=$1&param5=$5&param3=$3&param4=$4&param2=$2&testname=$0&group_id=$group_id','P','tracker',1,101);
        $this->assertIdentical($ref->getNumParam(),5);
        $ref =& new Reference(1,"test","Goto test",'/test/?proj=$projname&param1=$1&param5=$1&param3=$1&param4=$1&param2=$1&testname=$0&group_id=$group_id','P','tracker',1,101);
        $this->assertIdentical($ref->getNumParam(),1);
    }


    function testReplace() {
        // Test with full list
        $ref =& new Reference(1,"test","Goto test",'/test/?proj=$projname&param1=$1&param5=$5&param3=$3&param4=$4&param2=$2&testname=$0&group_id=$group_id','P','tracker',1,101);
        $args=array('arg1','arg2','arg3','arg4','arg5');
        $ref->replaceLink($args, 'name');
        $this->assertIdentical($ref->getLink(),"/test/?proj=name&param1=arg1&param5=arg5&param3=arg3&param4=arg4&param2=arg2&testname=test&group_id=101");

        // real one
        $ref =& new Reference(1,"art","Goto artifact",'/tracker/?func=detail&aid=$1&group_id=$group_id','S','tracker',1,101);
        $args=array(1000);
        $ref->replaceLink($args);
        $this->assertIdentical($ref->getLink(),'/tracker/?func=detail&aid=1000&group_id=101');
    }

}
?>

<?php
//{{{ Ugly fix to by pass error with ugroup_utils
//TODO: Fix it !
require_once('BaseLanguage.class.php');
$name = 'Fake_BaseLanguage_'. md5(uniqid(rand(), true));
eval("class $name extends BaseLanguage {}");
$GLOBALS['Language'] = new $name();
//}}}

require_once('common/frs/FRSPackage.class.php');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id:$
 *
 * Tests the FRSPackage class
 */
class FRSPackageTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function FRSPackageTest($name = 'FRSPackage test') {
        global $GLOBALS;
        
        $this->UnitTestCase($name);
    }

    function testIsActive() {
        global $GLOBALS;
        
        $active_value = 1;
        $hidden_value = 3;
        
        $p =& new FRSPackage();
        $p->setStatusId($active_value);
        $this->assertTrue($p->isActive());
        
        $p->setStatusId($hidden_value);
        $this->assertFalse($p->isActive());
    }

}
?>

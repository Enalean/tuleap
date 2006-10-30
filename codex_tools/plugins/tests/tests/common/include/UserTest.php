<?php
require_once('common/include/User.class');

Mock::generatePartial(
    'User',
    'UserTestVersion',
    array('getStatus', 'getUnixStatus')
);

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id:$
 *
 * Tests the class User
 */
class UserTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function UserTest($name = 'User test') {
        $this->UnitTestCase($name);
    }

    function testStatus() {
        $u1 =& new UserTestVersion($this);
        $u1->setReturnValue('getStatus', 'A');
        $u2 =& new UserTestVersion($this);
        $u2->setReturnValue('getStatus', 'S');
        $u3 =& new UserTestVersion($this);
        $u3->setReturnValue('getStatus', 'D');
        $u4 =& new UserTestVersion($this);
        $u4->setReturnValue('getStatus', 'R');
        
        $this->assertTrue($u1->isActive());
        $this->assertFalse($u1->isSuspended());
        $this->assertFalse($u1->isDeleted());
        $this->assertFalse($u1->isRestricted());
        
        $this->assertFalse($u2->isActive());
        $this->assertTrue($u2->isSuspended());
        $this->assertFalse($u2->isDeleted());
        $this->assertFalse($u2->isRestricted());
        
        $this->assertFalse($u3->isActive());
        $this->assertFalse($u3->isSuspended());
        $this->assertTrue($u3->isDeleted());
        $this->assertFalse($u3->isRestricted());
        
        $this->assertFalse($u4->isActive());
        $this->assertFalse($u4->isSuspended());
        $this->assertFalse($u4->isDeleted());
        $this->assertTrue($u4->isRestricted());
    }

    function testUnixStatus() {
        $u1 =& new UserTestVersion($this);
        $u1->setReturnValue('getUnixStatus', 'A');
        $u2 =& new UserTestVersion($this);
        $u2->setReturnValue('getUnixStatus', 'S');
        $u3 =& new UserTestVersion($this);
        $u3->setReturnValue('getUnixStatus', 'D');
        $u4 =& new UserTestVersion($this);
        $u4->setReturnValue('getUnixStatus', 'N');
        
        $this->assertTrue($u1->hasActiveUnixAccount());
        $this->assertFalse($u1->hasSuspendedUnixAccount());
        $this->assertFalse($u1->hasDeletedUnixAccount());
        $this->assertFalse($u1->hasNoUnixAccount());
        
        $this->assertFalse($u2->hasActiveUnixAccount());
        $this->assertTrue($u2->hasSuspendedUnixAccount());
        $this->assertFalse($u2->hasDeletedUnixAccount());
        $this->assertFalse($u2->hasNoUnixAccount());
        
        $this->assertFalse($u3->hasActiveUnixAccount());
        $this->assertFalse($u3->hasSuspendedUnixAccount());
        $this->assertTrue($u3->hasDeletedUnixAccount());
        $this->assertFalse($u3->hasNoUnixAccount());
        
        $this->assertFalse($u4->hasActiveUnixAccount());
        $this->assertFalse($u4->hasSuspendedUnixAccount());
        $this->assertFalse($u4->hasDeletedUnixAccount());
        $this->assertTrue($u4->hasNoUnixAccount());
    }

}
?>

<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once('common/system_event/SystemEventManager.class.php');
Mock::generatePartial('SystemEventManager', 'SystemEventManagerTestVersion', array('_getDao'));

Mock::generate('SystemEventDao');

require_once('common/user/User.class.php');
Mock::generate('PFUser');

require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');

class SystemEventManagerTest extends UnitTestCase {
    
    public function testConcatParameters() {
        $sem = new SystemEventManagerTestVersion($this);
        $params = array(
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        );
        $this->assertEqual($sem->concatParameters($params, array()), '');
        $this->assertEqual($sem->concatParameters($params, array('key1')), 'value1');
        $this->assertEqual($sem->concatParameters($params, array('key1', 'key3')), 'value1::value3');
        $this->assertEqual($sem->concatParameters($params, array('key3', 'key1')), 'value3::value1');
        $this->assertEqual($sem->concatParameters($params, array('key1', 'key2', 'key3')), 'value1::value2::value3');
    }

    /**
     * 'toto' can be renamed if he is not already scheduled for rename
     */
    public function testCanRenameUser() {
        $user = mock('PFUser');
        $user->setReturnValue('getId', 102);

        $seDao = new MockSystemEventDao($this);
        
        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('rowCount', 0);
        $seDao->setReturnValue('searchWithParam', $dar);
        $seDao->expectOnce('searchWithParam', array('head', 102, array(SystemEvent::TYPE_USER_RENAME), array(SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING)));

        $se = new SystemEventManagerTestVersion($this);
        $se->setReturnValue('_getDao', $seDao);

        $this->assertTrue($se->canRenameUser($user));
    }

    public function testCanRenameUserWithUserAlreadyQueudedForRename() {
        $user = mock('PFUser');
        $user->setReturnValue('getId', 102);

        $seDao = new MockSystemEventDao($this);

        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('rowCount', 1);
        $seDao->setReturnValue('searchWithParam', $dar);
        $seDao->expectOnce('searchWithParam', array('head', 102, array(SystemEvent::TYPE_USER_RENAME), array(SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING)));

        $se = new SystemEventManagerTestVersion($this);
        $se->setReturnValue('_getDao', $seDao);

        $this->assertFalse($se->canRenameUser($user));
    }

    /**
     * Test if string 'titi' is not already in system event queue as a futur
     * new username
     */
    public function testIsUserNameAvailable() {
        $seDao = new MockSystemEventDao($this);

        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('rowCount', 0);
        $seDao->ExpectOnce('searchWithParam', array('tail', 'titi', array(SystemEvent::TYPE_USER_RENAME) , array(SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING)));
        $seDao->setReturnValue('searchWithParam', $dar);

        $se = new SystemEventManagerTestVersion($this);
        $se->setReturnValue('_getDao', $seDao);

        $this->assertTrue($se->isUserNameAvailable('titi'));
    }

   public function testIsUserNameAvailableWithStringAlreadyQueuded() {
        $seDao = new MockSystemEventDao($this);

        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('rowCount', 1);
        $seDao->ExpectOnce('searchWithParam', array('tail', 'titi', array(SystemEvent::TYPE_USER_RENAME) , array(SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING)));
        $seDao->setReturnValue('searchWithParam', $dar);

        $se = new SystemEventManagerTestVersion($this);
        $se->setReturnValue('_getDao', $seDao);

        $this->assertFalse($se->isUserNameAvailable('titi'));
    }
}
?>

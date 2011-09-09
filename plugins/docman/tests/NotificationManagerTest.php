<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__).'/../include/Docman_NotificationsManager.class.php';
Mock::generatePartial('Docman_NotificationsManager',
                      'Docman_NotificationsManager_TestVersion',
                      array('_getMail',
                            '_getItemFactory',
                            '_groupGetObject',
                            '_getDao'));

require_once dirname(__FILE__).'/../include/Docman_ItemFactory.class.php';
Mock::generate('Docman_ItemFactory');

require_once 'common/user/User.class.php';
Mock::generate('User');

require_once 'common/mail/Mail.class.php';
Mock::generate('Mail');

require_once 'common/project/Project.class.php';
Mock::generate('Project');

require_once 'common/include/Feedback.class.php';
Mock::generate('Feedback');

require_once 'common/dao/NotificationsDao.class.php';
Mock::generate('NotificationsDao');

class Docman_NotificationsManagerTest extends UnitTestCase {

    function setUp() {
        $GLOBALS['sys_noreply'] = 'norelpy@codendi.org';
    }

    function tearDown() {
        unset($GLOBALS['sys_noreply']);
    }

    function testSendNotificationsSuccess() {
        $mail     = new MockMail($this);
        $mail->setReturnValue('send', true);
        $feedback = new MockFeedback($this);
        $project  = new MockProject($this);
        $project->setReturnValue('getPublicName', 'Guinea Pig');
        $itemFty  = new MockDocman_ItemFactory($this);
        $notifDao = new MockNotificationsDao($this);

        $nm = new Docman_NotificationsManager_TestVersion($this);
        $nm->setReturnValue('_getDao', $notifDao);
        $nm->setReturnValue('_getItemFactory', $itemFty);
        $nm->setReturnValue('_groupGetObject', $project);
        $nm->setReturnValue('_getMail', $mail);
        $nm->__construct(101, '/toto', $feedback);

        $user = new MockUser($this);
        $user->setReturnValue('getEmail', 'foo@codendi.org');
        $nm->_messages = array(array('title' => 'Move', 'content' => 'Changed', 'to' => array($user)));

        $nm->sendNotifications('', '');
    }

    function testSendNotificationsFirstIsFailure() {
        $feedback = new MockFeedback($this);

        // First message fail
        $mail1     = new MockMail($this);
        $mail1->setReturnValue('send', false);
        
        // Second succeed
        $mail2     = new MockMail($this);
        $mail2->setReturnValue('send', true);

        // Raises an error 
        $feedback->expectOnce('log', array('warning', '*'));

        $project  = new MockProject($this);
        $project->setReturnValue('getPublicName', 'Guinea Pig');
        $itemFty  = new MockDocman_ItemFactory($this);
        $notifDao = new MockNotificationsDao($this);

        $nm = new Docman_NotificationsManager_TestVersion($this);
        $nm->setReturnValue('_getDao', $notifDao);
        $nm->setReturnValue('_getItemFactory', $itemFty);
        $nm->setReturnValue('_groupGetObject', $project);
        $nm->setReturnValueAt(0, '_getMail', $mail1);
        $nm->setReturnValueAt(1, '_getMail', $mail2);
        $nm->__construct(101, '/toto', $feedback);

        $user = new MockUser($this);
        $user->setReturnValue('getEmail', 'foo@codendi.org');
        $nm->_messages[] = array('title' => 'Move 1', 'content' => 'Changed 1', 'to' => array($user));
        $nm->_messages[] = array('title' => 'Move 2', 'content' => 'Changed 2', 'to' => array($user));

        $nm->sendNotifications('', '');
    }

}

?>
<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'common/mail/MailManager.class.php';

Mock::generate('User');

class MailManagerTest extends UnitTestCase {
    
    function testMailShouldCreateHtmlMailForUserByDefault() {
        $mm = TestHelper::getPartialMock('MailManager', array('getConfig'));
        
        $user = new MockUser();
        $this->assertIsA($mm->getMailForUser($user), 'Codendi_Mail');
    }
    
    function testMailShouldCreateTextMailWhenUserAsSetPreference() {
        $mm = TestHelper::getPartialMock('MailManager', array('getConfig'));
        
        $user = new MockUser();
        $user->setReturnValue('getPreference', 'text');
        
        $this->assertIsA($mm->getMailForUser($user), 'Mail');
    }
    
    function testMailShouldBeSetToUserAutomatically() {
        $mm = TestHelper::getPartialMock('MailManager', array('getConfig'));
        
        $user = new MockUser();
        $user->setReturnValue('getStatus', 'A');
        $user->setReturnValue('getEmail', 'john.doe@mailserver.com');
        
        $mail = $mm->getMailForUser($user);
        $this->assertEqual($mail->getTo(), 'john.doe@mailserver.com');
    }
    
    function testMailShouldComeFromNoReply() {
        $mm = TestHelper::getPartialMock('MailManager', array('getConfig'));
        $mm->setReturnValue('getConfig', 'TheName <noreply@thename.com>', array('sys_noreply'));
        
        $user = new MockUser();
        $mail = $mm->getMailForUser($user);
        
        $this->assertEqual($mail->getFrom(), 'noreply@thename.com');
    }
    
    function testMailByTypeShouldBeInHTMLWhenRequested() {
        $mm = TestHelper::getPartialMock('MailManager', array('getConfig'));
        
        $this->assertIsA($mm->getMailByType('html'), 'Codendi_Mail');
    }
    
    function testMailByTypeShouldBeInTextWhenRequested() {
        $mm = TestHelper::getPartialMock('MailManager', array('getConfig'));
        
        $this->assertIsA($mm->getMailByType('text'), 'Mail');
    }
    
    function testMailByTypeShouldBeInHTMLByDefault() {
        $mm = TestHelper::getPartialMock('MailManager', array('getConfig'));
        
        $this->assertIsA($mm->getMailByType(), 'Codendi_Mail');
    }
    
    function testMailByTypeShouldComeFromNoReply() {
        $mm = TestHelper::getPartialMock('MailManager', array('getConfig'));
        $mm->setReturnValue('getConfig', 'TheName <noreply@thename.com>', array('sys_noreply'));
        
        $this->assertEqual($mm->getMailByType()->getFrom(), 'noreply@thename.com');
    }
}

?>

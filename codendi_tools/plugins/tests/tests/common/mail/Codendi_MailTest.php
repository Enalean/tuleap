<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2011. All rights reserved
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


require_once('common/user/User.class.php');
Mock::generate('User');
require_once('common/mail/Codendi_Mail.class.php');
Mock::generatePartial('Codendi_Mail', 'Codendi_MailTestVersion', array());


class Codendi_MailTest extends UnitTestCase {

    function testValidateRecipient() {
        $mail = new Codendi_MailTestVersion($this);

        $user1 = new MockUser();
        $user1->setReturnValue('getRealName', 'user 1');
        $user1->setReturnValue('getEmail', 'user_1@codendi.org');
        $user1->setReturnValue('getStatus', 'A');

        $user2 = new MockUser();
        $user2->setReturnValue('getRealName', 'user 2');
        $user2->setReturnValue('getEmail', 'user_2@codendi.org');
        $user2->setReturnValue('getStatus', 'S');

        $recipArray = array($user1, $user2);

        $recipients = $mail->_validateRecipient($recipArray);
        $retArray = array(array('real_name' => $user1->getRealName(), 'email' => $user1->getEmail()));
        $this->assertEqual($recipients, $retArray);
    }

    function testCleanupMailFormat() {
        $mail = new Codendi_MailTestVersion();
        $this->assertEqual('john.doe@example.com', $mail->_cleanupMailFormat('"Codendi" <john.doe@example.com>'));
        $this->assertEqual('"Codendi" john.doe@example.com', $mail->_cleanupMailFormat('"Codendi" john.doe@example.com'));
        $this->assertEqual('"Codendi" <john.doe@example.com', $mail->_cleanupMailFormat('"Codendi" <john.doe@example.com'));
        $this->assertEqual('"Codendi" john.doe@example.com>', $mail->_cleanupMailFormat('"Codendi" john.doe@example.com>'));
    }
}
?>
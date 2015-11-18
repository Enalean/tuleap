<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2004-2011. All rights reserved
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


require_once('common/user/User.class.php');
Mock::generate('PFUser');
require_once('common/mail/Codendi_Mail.class.php');
Mock::generatePartial('Codendi_Mail', 'Codendi_MailTestVersion', array('getMail'));
Mock::generate('Tuleap_Template_Mail');
class FakeZend_Mail {
    function setBodyHtml() { }
}

class Codendi_MailTest extends TuleapTestCase {

    function testCleanupMailFormat() {
        $mail = new Codendi_MailTestVersion();
        $this->assertEqual(array('john.doe@example.com', 'Tuleap'), $mail->_cleanupMailFormat('"Tuleap" <john.doe@example.com>'));
        $this->assertEqual(array('john.doe@example.com', 'Tuleap'), $mail->_cleanupMailFormat('Tuleap <john.doe@example.com>'));
        $this->assertEqual(array('"Tuleap" john.doe@example.com', ''), $mail->_cleanupMailFormat('"Tuleap" john.doe@example.com'));
        $this->assertEqual(array('"Tuleap" <john.doe@example.com', ''), $mail->_cleanupMailFormat('"Tuleap" <john.doe@example.com'));
        $this->assertEqual(array('"Tuleap" john.doe@example.com>', ''), $mail->_cleanupMailFormat('"Tuleap" john.doe@example.com>'));
    }
    
    function testTemplateLookAndFeel() {
        $body = 'body';
        
        $tpl = new MockTuleap_Template_Mail();
        $tpl->expectOnce('set', array('body', $body));
        $tpl->expectOnce('fetch');
        
        $zm = new FakeZend_Mail();
        
        $mail = new Codendi_MailTestVersion();
        $mail->setLookAndFeelTemplate($tpl);
        $mail->setReturnValue('getMail', $zm);
        
        $mail->setBodyHtml($body);
    }
    
    function testDiscardTemplateLookAndFeel() {
        $body = 'body';
        
        $tpl = new MockTuleap_Template_Mail();
        $tpl->expectNever('set', array('body', $body));
        $tpl->expectNever('fetch');
        
        $zm = new FakeZend_Mail();
        
        $mail = new Codendi_MailTestVersion();
        $mail->setLookAndFeelTemplate($tpl);
        $mail->setReturnValue('getMail', $zm);
        
        $mail->setBodyHtml($body, Codendi_MailTestVersion::DISCARD_COMMON_LOOK_AND_FEEL);
    }

    public function itAddsAttachments() {
        $mail = new Codendi_Mail();
        $mail->addInlineAttachment('dataInline', 'text/plain', 'attachmentInline');
        $mail->addAttachment('data', 'text/plain', 'attachment');

        $this->assertEqual($mail->getMail()->getPartCount(), 2);
    }

    public function itDoesNotSetEmptyAddress() {
        $mail = new Codendi_Mail();


    }
    public function itHasAppropriateTypeForAttachment() {
        $mail = new Codendi_Mail();
        $mail->addInlineAttachment('data', 'text/plain', 'attachment');

        $this->assertEqual($mail->getMail()->getType(), 'multipart/related');
    }
}
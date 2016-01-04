<?php
/**
 * Copyright (c) Enalean, 2015-2017. All Rights Reserved.
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

class Codendi_MailTest extends TuleapTestCase {

    public function testCleanupMailFormat()
    {
        $mail = new Codendi_Mail();
        $this->assertEqual(array('john.doe@example.com', 'Tuleap'), $mail->_cleanupMailFormat('"Tuleap" <john.doe@example.com>'));
        $this->assertEqual(array('john.doe@example.com', 'Tuleap'), $mail->_cleanupMailFormat('Tuleap <john.doe@example.com>'));
        $this->assertEqual(array('"Tuleap" john.doe@example.com', ''), $mail->_cleanupMailFormat('"Tuleap" john.doe@example.com'));
        $this->assertEqual(array('"Tuleap" <john.doe@example.com', ''), $mail->_cleanupMailFormat('"Tuleap" <john.doe@example.com'));
        $this->assertEqual(array('"Tuleap" john.doe@example.com>', ''), $mail->_cleanupMailFormat('"Tuleap" john.doe@example.com>'));
    }
    
    public function testTemplateLookAndFeel()
    {
        $body = 'body';

        $tpl = mock('Tuleap_Template_Mail');
        $tpl->expectOnce('set', array('body', $body));
        $tpl->expectOnce('fetch');
        

        $mail = new Codendi_Mail();
        $mail->setLookAndFeelTemplate($tpl);

        $mail->setBodyHtml($body);
    }
    
    public function testDiscardTemplateLookAndFeel()
    {
        $body = 'body';
        
        $tpl = mock('Tuleap_Template_Mail');
        $tpl->expectNever('set', array('body', $body));
        $tpl->expectNever('fetch');
        

        $mail = new Codendi_Mail();
        $mail->setLookAndFeelTemplate($tpl);

        $mail->setBodyHtml($body, Codendi_Mail::DISCARD_COMMON_LOOK_AND_FEEL);
    }
}
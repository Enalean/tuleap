<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

require_once('common/autoload.php');

class MailEnhancerTest extends TuleapTestCase {

    public function itSetsFromHeader() {
        $mail_enhancer = new MailEnhancer();
        $mail          = new Codendi_Mail();
        $from_mail     = 'from@example.com';

        $mail_enhancer->addHeader('From', $from_mail);
        $mail_enhancer->enhanceMail($mail);

        $this->assertEqual($mail->getFrom(), $from_mail);
    }

    public function itSetsReplyToHeader() {
        $mail_enhancer = new MailEnhancer();
        $mail          = new Codendi_Mail();
        $replyto_mail  = 'replyto@example.com';

        $mail_enhancer->addHeader('Reply-To', $replyto_mail);
        $mail_enhancer->enhanceMail($mail);

        $this->assertEqual($mail->getFrom(), $replyto_mail);
        $mail_headers = $mail->getMail()->getHeaders();
        $this->assertEqual($mail_headers['reply-to'][0], $replyto_mail);
    }

    public function itForcesFromHeader() {
        $mail_enhancer = new MailEnhancer();
        $mail          = new Codendi_Mail();
        $mail->setFrom('noreply@example.com');
        $from_mail     = 'from@example.com';

        $mail_enhancer->addHeader('From', $from_mail);
        $mail_enhancer->enhanceMail($mail);

        $this->assertEqual($mail->getFrom(), $from_mail);
    }

    public function itSetsFromAndReplyToHeaders() {
        $mail_enhancer = new MailEnhancer();
        $mail          = new Codendi_Mail();
        $from_mail     = 'from@example.com';
        $replyto_mail  = 'replyto@example.com';

        $mail_enhancer->addHeader('From', $from_mail);
        $mail_enhancer->addHeader('Reply-To', $replyto_mail);
        $mail_enhancer->enhanceMail($mail);

        $this->assertEqual($mail->getFrom(), $from_mail);
        $mail_headers = $mail->getMail()->getHeaders();
        $this->assertEqual($mail_headers['reply-to'][0], $replyto_mail);
    }

}
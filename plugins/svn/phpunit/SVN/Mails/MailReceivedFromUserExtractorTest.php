<?php
/**
* Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

namespace Tuleap\SVN\Admin;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../bootstrap.php';

class MailReceivedFromUserExtractorTest extends TestCase
{

    public function testItVerifyThatMailListIsValid(): void
    {
        $list_mails = 'validmail@example.com;avalid+&mail1@example.com,mail_with-authorised.values@example.com';

        $mail = new MailReceivedFromUserExtractor($list_mails);
        $this->assertEquals($mail->getValidAdresses(), array('validmail@example.com', 'avalid+&mail1@example.com', 'mail_with-authorised.values@example.com'));
        $this->assertEquals($mail->getInvalidAdresses(), array());
    }

    public function testItVerifyThatMailListIsInvalid(): void
    {
        $list_mails = 'aninvalidmailexample.com;invalid¤mail@example.com,notvalid';

        $mail = new MailReceivedFromUserExtractor($list_mails);
        $this->assertEquals($mail->getValidAdresses(), array());
        $this->assertEquals($mail->getInvalidAdresses(), array('aninvalidmailexample.com', 'invalid¤mail@example.com', 'notvalid'));
    }
}

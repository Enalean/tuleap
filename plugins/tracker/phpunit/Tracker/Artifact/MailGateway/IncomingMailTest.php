<?php
/**
 * Copyright (c) Enalean, 2014-2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\MailGateway;

require_once __DIR__ . '/../../../bootstrap.php';

use PHPUnit\Framework\TestCase;

class IncomingMailTest extends TestCase
{
    /**
     * @dataProvider mailProvider
     */
    public function testBodyRetrieval($mail_content_path)
    {
        $incoming_mail = new IncomingMail(file_get_contents($mail_content_path));

        $this->assertSame('Re: [tasks #1661] Do it', $incoming_mail->getSubject());
        $this->assertSame(['nicolas.terray@example.com'], $incoming_mail->getFrom());
        $this->assertStringEqualsFile(
            __DIR__ . '/_fixtures/expected_followup.text.txt',
            $incoming_mail->getBodyText()
        );
    }

    public function mailProvider()
    {
        return [
            [__DIR__ . '/_fixtures/reply-comment.plain.eml'],
            [__DIR__ . '/_fixtures/reply-comment.plain+html.eml'],
            [__DIR__ . '/_fixtures/reply-comment.html+plain.eml'],
            [__DIR__ . '/_fixtures/reply-comment.(plain+html)+attachment.eml'],
        ];
    }

    public function testUTF8BodyIsReturnedFromISO8859Mail()
    {
        $incoming_mail = new IncomingMail(file_get_contents(__DIR__ . '/_fixtures/mail-iso-8859-1.txt'));
        $this->assertSame('This should be correctly displayed: èàéô', $incoming_mail->getBodyText());
    }

    public function testEmptyBodyIsReturnedWhenNoTextBody()
    {
        $incoming_mail = new IncomingMail(file_get_contents(__DIR__ . '/_fixtures/reply-comment.html.eml'));
        $this->assertSame('', $incoming_mail->getBodyText());
    }
}

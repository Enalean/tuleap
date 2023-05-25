<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\ForumML\Incoming;

final class IncomingMailParsingTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public const HTML_ONLY_BODY                   = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
</head>
<body bgcolor="#ffffff" text="#000000">
My <b>fault<br>
<br>
</b>
</body>
</html>
';
    public const HTML_BODY_WITH_INLINE_ATTACHMENT = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
</head>
<body bgcolor="#ffffff" text="#000000">
My <b>test<br>
<img alt="" src="cid:part1.02040105.07020502@example.com" height="270"
 width="199"><br>
</b>
</body>
</html>
';

    /**
     * @dataProvider sampleMailProvider
     */
    public function testMailParsing(
        string $fixture_path,
        string $expected_body_type,
        string $expected_body_content,
        array $expected_attachments,
    ): void {
        $incoming_mail = new IncomingMail(fopen($fixture_path, 'rb'));

        $body = $incoming_mail->getBody();
        $this->assertSame($expected_body_type, $body->getContentType());
        $this->assertSame($expected_body_content, $body->getContent());

        $attachments = [];
        foreach ($incoming_mail->getAttachments() as $attachment) {
            $attachments[] = [
                'name' => $attachment->getFilename(),
                'content-type' => $attachment->getContentType(),
                'content-id' => $attachment->getContentID(),
            ];
        }

        $this->assertSame($expected_attachments, $attachments);
    }

    public static function sampleMailProvider(): array
    {
        $fixture_path_base = __DIR__ . '/_fixtures/samples';
        return [
            ["$fixture_path_base/pure_text.mbox", 'text/plain', "Pure text\n", []],
            [
                "$fixture_path_base/attachment_only.mbox",
                'text/plain',
                "\n",
                [['name' => 'lock.png', 'content-type' => 'image/png', 'content-id' => '']],
            ],
            [
                "$fixture_path_base/text_plus_attachment.mbox",
                'text/plain',
                "Some text\n",
                [['name' => 'lock.png', 'content-type' => 'image/png', 'content-id' => '']],
            ],
            ["$fixture_path_base/pure_html_text_plus_html.mbox", 'text/html', self::HTML_ONLY_BODY, []],
            ["$fixture_path_base/pure_html_in_html_only.mbox", 'text/html', self::HTML_ONLY_BODY, []],
            [
                "$fixture_path_base/html_with_inline_content_in_text_plus_html.mbox",
                'text/html',
                self::HTML_BODY_WITH_INLINE_ATTACHMENT,
                [['name' => 'lock.png', 'content-type' => 'image/png', 'content-id' => '<part1.02040105.07020502@example.com>']],
            ],
            [
                "$fixture_path_base/html_with_inline_content_in_html_only.mbox",
                'text/html',
                self::HTML_BODY_WITH_INLINE_ATTACHMENT,
                [['name' => 'noname1', 'content-type' => 'image/png', 'content-id' => '<part1.02040105.07020502@example.com>']],
            ],
            [
                "$fixture_path_base/html_with_inline_content_and_attch_in_text_plus_html.mbox",
                'text/html',
                self::HTML_BODY_WITH_INLINE_ATTACHMENT,
                [
                    ['name' => 'noname1', 'content-type' => 'image/png', 'content-id' => '<part1.02040105.07020502@example.com>'],
                    ['name' => 'new_trk_severity_migr.png', 'content-type' => 'image/png', 'content-id' => ''],
                ],
            ],
            [
                "$fixture_path_base/html_with_inline_content_and_attch_in_html_only.mbox",
                'text/html', self::HTML_BODY_WITH_INLINE_ATTACHMENT,
                [
                    ['name' => 'noname1', 'content-type' => 'image/png', 'content-id' => '<part1.02040105.07020502@example.com>'],
                    ['name' => 'new_trk_severity_migr.png', 'content-type' => 'image/png', 'content-id' => ''],
                ],
            ],
            [
                "$fixture_path_base/forwarded_email.mbox",
                'text/plain',
                "Some text\n",
                [
                    [
                        'name'         => '[gpig-events] [gpig]r18476 - in contrib_st_enhancement_115_webdav_docman_write_access_ plugins_webdav_include: _ FS.eml',
                        'content-type' => 'message/rfc822',
                        'content-id'   => '',
                    ],
                ],
            ],
        ];
    }
}

<?php
/**
 * Copyright (c) Enalean, 2017-present. All Rights Reserved.
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

namespace Tuleap\Sanitizer;

use Valid_HTTPURI;

final class URISanitizerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItDoesNotTouchValidURI(): void
    {
        $validator_local_uri = $this->createMock(\Valid_LocalURI::class);
        $validator_local_uri->method('validate')->willReturn(true);

        $validator_ftp_uri = $this->createMock(\Valid_FTPURI::class);
        $validator_ftp_uri->method('validate')->willReturn(false);

        $uri_sanitizer = new URISanitizer($validator_local_uri, $validator_ftp_uri);

        $uri = '/valid_uri';

        self::assertEquals($uri, $uri_sanitizer->sanitizeForHTMLAttribute($uri));
    }

    public function testItDoesNotTouchValidFTPURI(): void
    {
        $validator_local_uri = $this->createMock(\Valid_LocalURI::class);
        $validator_local_uri->method('validate')->willReturn(false);

        $validator_ftp_uri = $this->createMock(\Valid_FTPURI::class);
        $validator_ftp_uri->method('validate')->willReturn(true);

        $uri_sanitizer = new URISanitizer($validator_local_uri, $validator_ftp_uri);

        $uri = 'ftp://example.com';

        self::assertEquals($uri, $uri_sanitizer->sanitizeForHTMLAttribute($uri));
    }

    public function testItManglesInvalidURI(): void
    {
        $validator_local_uri = $this->createMock(\Valid_LocalURI::class);
        $validator_local_uri->method('validate')->willReturn(false);

        $validator_ftp_uri = $this->createMock(\Valid_FTPURI::class);
        $validator_ftp_uri->method('validate')->willReturn(false);

        $uri_sanitizer = new URISanitizer($validator_local_uri, $validator_ftp_uri);

        $uri = 'invalid_uri';

        self::assertEquals('', $uri_sanitizer->sanitizeForHTMLAttribute($uri));
    }

    public function testItAcceptsOnlyOneValidator(): void
    {
        $validator_http_uri = new Valid_HTTPURI();
        $validator_http_uri->disableFeedback();

        $uri_sanitizer = new URISanitizer($validator_http_uri);

        self::assertEquals(
            '',
            $uri_sanitizer->sanitizeForHTMLAttribute('javascript:alert(1);')
        );
        self::assertEquals(
            'http://example.test',
            $uri_sanitizer->sanitizeForHTMLAttribute('http://example.test')
        );
        self::assertEquals(
            'https://example.test',
            $uri_sanitizer->sanitizeForHTMLAttribute('https://example.test')
        );
    }
}

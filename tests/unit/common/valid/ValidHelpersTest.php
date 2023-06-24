<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class ValidHelpersTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testUInt(): void
    {
        $v = new Valid_UInt();
        $v->disableFeedback();

        self::assertTrue($v->validate('0'));
        self::assertTrue($v->validate('1'));
        self::assertTrue($v->validate('2147483647'));

        self::assertFalse($v->validate('-1'));
        // With a value lower than -2^31 it may imply a int overflow that may
        // generate a positive int (in this case: 2^31-1).
        self::assertFalse($v->validate('-2147483649'));
        self::assertFalse($v->validate('0.5'));
        self::assertFalse($v->validate('toto'));
    }

    public function testItValidatesHTTPURI(): void
    {
        $validator = new Valid_HTTPURI();

        self::assertTrue($validator->validate('http://example.com/'));
        self::assertTrue($validator->validate('HTTP://example.com/'));
        self::assertTrue($validator->validate('https://example.com/'));
        self::assertTrue($validator->validate('HTTPS://example.com/'));
        self::assertFalse($validator->validate('gopher://example.com'));
        self::assertFalse($validator->validate('javascript:alert(1);'));
        self::assertFalse($validator->validate('Stringhttp://'));
    }

    public function testItValidatesHTTPSURI(): void
    {
        $validator = new Valid_HTTPSURI();

        self::assertTrue($validator->validate('https://example.com/'));
        self::assertTrue($validator->validate('HTTPS://example.com/'));
        self::assertFalse($validator->validate('http://example.com/'));
        self::assertFalse($validator->validate('gopher://example.com'));
        self::assertFalse($validator->validate('javascript:alert(1);'));
        self::assertFalse($validator->validate('Stringhttps://'));
    }

    public function testItValidatesLocalURI(): void
    {
        $validator = new Valid_LocalURI();

        self::assertTrue($validator->validate('http://example.com/'));
        self::assertTrue($validator->validate('HTTP://example.com/'));
        self::assertTrue($validator->validate('https://example.com/'));
        self::assertTrue($validator->validate('HTTPS://example.com/'));
        self::assertTrue($validator->validate('/projects/localpage'));
        self::assertTrue($validator->validate('#anchor'));
        self::assertTrue($validator->validate('?parameter=1'));
        self::assertFalse($validator->validate('gopher://example.com'));
        self::assertFalse($validator->validate('javascript:alert(1);'));
        self::assertFalse($validator->validate('Stringhttp://'));
    }

    public function testItValidatesFTPURI(): void
    {
        $validator = new Valid_FTPURI();

        self::assertTrue($validator->validate('ftp://example.com'));
        self::assertTrue($validator->validate('FTP://example.com'));
        self::assertTrue($validator->validate('ftps://example.com'));
        self::assertTrue($validator->validate('FTPS://example.com'));
        self::assertFalse($validator->validate('https://notaftp.example.com/'));
        self::assertFalse($validator->validate('ftp://'));
    }

    public function testItValidatesMailtoURI(): void
    {
        $validator = new Valid_MailtoURI();

        self::assertTrue($validator->validate('mailto:tuleap@example.com'));
        self::assertTrue($validator->validate('mailto:tuleap@example.com?subject=Tuleap%20Unit%20Tests'));
        self::assertFalse($validator->validate('mailto: tuleap@example.com'));
        self::assertFalse($validator->validate('mailto:'));
    }
}

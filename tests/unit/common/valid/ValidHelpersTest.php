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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class ValidHelpersTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testUInt(): void
    {
        $v = new Valid_UInt();
        $v->disableFeedback();

        $this->assertTrue($v->validate('0'));
        $this->assertTrue($v->validate('1'));
        $this->assertTrue($v->validate('2147483647'));

        $this->assertFalse($v->validate('-1'));
        // With a value lower than -2^31 it may imply a int overflow that may
        // generate a positive int (in this case: 2^31-1).
        $this->assertFalse($v->validate('-2147483649'));
        $this->assertFalse($v->validate('0.5'));
        $this->assertFalse($v->validate('toto'));
    }

    public function testItValidatesHTTPURI(): void
    {
        $validator = new Valid_HTTPURI();

        $this->assertTrue($validator->validate('http://example.com/'));
        $this->assertTrue($validator->validate('HTTP://example.com/'));
        $this->assertTrue($validator->validate('https://example.com/'));
        $this->assertTrue($validator->validate('HTTPS://example.com/'));
        $this->assertFalse($validator->validate('gopher://example.com'));
        $this->assertFalse($validator->validate('javascript:alert(1);'));
        $this->assertFalse($validator->validate('Stringhttp://'));
    }

    public function testItValidatesHTTPSURI(): void
    {
        $validator = new Valid_HTTPSURI();

        $this->assertTrue($validator->validate('https://example.com/'));
        $this->assertTrue($validator->validate('HTTPS://example.com/'));
        $this->assertFalse($validator->validate('http://example.com/'));
        $this->assertFalse($validator->validate('gopher://example.com'));
        $this->assertFalse($validator->validate('javascript:alert(1);'));
        $this->assertFalse($validator->validate('Stringhttps://'));
    }

    public function testItValidatesLocalURI(): void
    {
        $validator = new Valid_LocalURI();

        $this->assertTrue($validator->validate('http://example.com/'));
        $this->assertTrue($validator->validate('HTTP://example.com/'));
        $this->assertTrue($validator->validate('https://example.com/'));
        $this->assertTrue($validator->validate('HTTPS://example.com/'));
        $this->assertTrue($validator->validate('/projects/localpage'));
        $this->assertTrue($validator->validate('#anchor'));
        $this->assertTrue($validator->validate('?parameter=1'));
        $this->assertFalse($validator->validate('gopher://example.com'));
        $this->assertFalse($validator->validate('javascript:alert(1);'));
        $this->assertFalse($validator->validate('Stringhttp://'));
    }

    public function testItValidatesFTPURI(): void
    {
        $validator = new Valid_FTPURI();

        $this->assertTrue($validator->validate('ftp://example.com'));
        $this->assertTrue($validator->validate('FTP://example.com'));
        $this->assertTrue($validator->validate('ftps://example.com'));
        $this->assertTrue($validator->validate('FTPS://example.com'));
        $this->assertFalse($validator->validate('https://notaftp.example.com/'));
        $this->assertFalse($validator->validate('ftp://'));
    }

    public function testItValidatesMailtoURI(): void
    {
        $validator = new Valid_MailtoURI();

        $this->assertTrue($validator->validate('mailto:tuleap@example.com'));
        $this->assertTrue($validator->validate('mailto:tuleap@example.com?subject=Tuleap%20Unit%20Tests'));
        $this->assertFalse($validator->validate('mailto: tuleap@example.com'));
        $this->assertFalse($validator->validate('mailto:'));
    }
}

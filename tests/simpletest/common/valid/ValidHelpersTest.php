<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

Mock::generatePartial('Valid', 'Valid_For_Inheritance', array());

class ValidHelperTest extends TuleapTestCase
{

    function UnitTestCase($name = 'ValidFactory test')
    {
        $this->UnitTestCase($name);
    }

    function testUInt()
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

    function testValidFactory()
    {
        $v = new Valid_For_Inheritance($this);

        //Does not work in php4 :(
        //$this->assertReference(ValidFactory::getInstance($v), $v);
        $this->assertIsA(ValidFactory::getInstance($v), 'Valid_For_Inheritance');

        $this->assertIsA(ValidFactory::getInstance('string'), 'Valid_String');
        $this->assertIsA(ValidFactory::getInstance('uint'), 'Valid_UInt');
        $this->assertNull(ValidFactory::getInstance('machinbidulechose'));

        $key = md5(uniqid(rand(), true));
        $w = ValidFactory::getInstance('string', $key);
        $this->assertEqual($w->getKey(), $key);
    }

    public function itValidatesHTTPURI()
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

    public function itValidatesHTTPSURI()
    {
        $validator = new Valid_HTTPSURI();

        $this->assertTrue($validator->validate('https://example.com/'));
        $this->assertTrue($validator->validate('HTTPS://example.com/'));
        $this->assertFalse($validator->validate('http://example.com/'));
        $this->assertFalse($validator->validate('gopher://example.com'));
        $this->assertFalse($validator->validate('javascript:alert(1);'));
        $this->assertFalse($validator->validate('Stringhttps://'));
    }

    public function itValidatesLocalURI()
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

    public function itValidatesFTPURI()
    {
        $validator = new Valid_FTPURI();

        $this->assertTrue($validator->validate('ftp://example.com'));
        $this->assertTrue($validator->validate('FTP://example.com'));
        $this->assertTrue($validator->validate('ftps://example.com'));
        $this->assertTrue($validator->validate('FTPS://example.com'));
        $this->assertFalse($validator->validate('https://notaftp.example.com/'));
        $this->assertFalse($validator->validate('ftp://'));
    }

    public function itValidatesMailtoURI()
    {
        $validator = new Valid_MailtoURI();

        $this->assertTrue($validator->validate('mailto:tuleap@example.com'));
        $this->assertTrue($validator->validate('mailto:tuleap@example.com?subject=Tuleap%20Unit%20Tests'));
        $this->assertFalse($validator->validate('mailto: tuleap@example.com'));
        $this->assertFalse($validator->validate('mailto:'));
    }
}

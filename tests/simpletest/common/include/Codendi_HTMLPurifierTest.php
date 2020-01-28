<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class Codendi_HTMLPurifierTest extends TuleapTestCase // phpcs:ignore
{

    private function getHTMLPurifier()
    {
        return new class extends Codendi_HTMLPurifier {
            public function __construct()
            {
            }
        };
    }

    public function testPurifySimple()
    {
        $p = $this->getHTMLPurifier();
        $this->assertEqual('&lt;script&gt;alert(1);&lt;/script&gt;', $p->purify('<script>alert(1);</script>'));
    }

    public function testStripLightForibdden()
    {
        $p = \Mockery::mock(\Codendi_HTMLPurifier::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $rm = \Mockery::spy(\ReferenceManager::class);
        $val = 'bugtest #123';
        $rm->shouldReceive('insertReferences')->andReturns($val);
        $p->shouldReceive('getReferenceManager')->andReturns($rm);

        $this->assertEqual('', $p->purify('<script>alert(1);</script>', CODENDI_PURIFIER_LIGHT));
        $this->assertEqual('Bolded', $p->purify('<s>Bolded</s>', CODENDI_PURIFIER_LIGHT));
        $this->assertEqual($val, $p->purify('bugtest #123', CODENDI_PURIFIER_LIGHT, 102));
        $this->assertEqual('', $p->purify('<form name="test" method="post" action="?"><input type="submit" /></form>', CODENDI_PURIFIER_LIGHT));
        $this->assertEqual('<a href="ftp://test.com">ftp://test.com</a>', $p->purify('ftp://test.com', CODENDI_PURIFIER_LIGHT));
        $this->anchorJsInjection(CODENDI_PURIFIER_LIGHT);
    }

    private function anchorJsInjection($level)
    {
        $p = $this->getHTMLPurifier();
        $this->assertEqual('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net" onblur="evil">Text</a>', $level));
        $this->assertEqual('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net" onclick="evil">Text</a>', $level));
        $this->assertEqual('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net" ondbclick="evil">Text</a>', $level));
        $this->assertEqual('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net" onfocus="evil">Text</a>', $level));
        $this->assertEqual('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net" onkeydown="evil">Text</a>', $level));
        $this->assertEqual('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net" onkeypress="evil">Text</a>', $level));
        $this->assertEqual('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net" onkeyup="evil">Text</a>', $level));
        $this->assertEqual('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net" onmousedown="evil">Text</a>', $level));
        $this->assertEqual('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net" onmousemove="evil">Text</a>', $level));
        $this->assertEqual('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net" onmouseout="evil">Text</a>', $level));
        $this->assertEqual('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net" onmouseover="evil">Text</a>', $level));
        $this->assertEqual('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net" onmouseup="evil">Text</a>', $level));
    }

    public function testStripLightAllowed()
    {
        $p = $this->getHTMLPurifier();

        $this->assertEqual('<p>Text</p>', $p->purify('<p>Text</p>', CODENDI_PURIFIER_LIGHT));
        $this->assertEqual('Text<br />', $p->purify('Text<br />', CODENDI_PURIFIER_LIGHT));

        $this->assertEqual('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net">Text</a>', CODENDI_PURIFIER_LIGHT));

        $this->assertEqual('<strong>Text</strong>', $p->purify('<strong>Text</strong>', CODENDI_PURIFIER_LIGHT));
    }

    public function testStripLightTidy()
    {
        $p = $this->getHTMLPurifier();
        $this->assertEqual('<p>Text</p>', $p->purify('<p>Text', CODENDI_PURIFIER_LIGHT));
        $this->assertEqual('Text<br />', $p->purify('Text<br>', CODENDI_PURIFIER_LIGHT));

        $this->assertEqual('<a href="http://php.net">Text</a>', $p->purify('<a href=\'http://php.net\'>Text', CODENDI_PURIFIER_LIGHT));
    }

    public function testPurifyArraySimple()
    {
        $p = $this->getHTMLPurifier();

        $vRef = array('<script>alert(1);</script>',
                      'toto',
                      '<h1>title</h1>',
                      '<b>bold</b>');
        $vExpect = array('&lt;script&gt;alert(1);&lt;/script&gt;',
                         'toto',
                         '&lt;h1&gt;title&lt;/h1&gt;',
                         '&lt;b&gt;bold&lt;/b&gt;');
        $this->assertIdentical($vExpect, $p->purifyMap($vRef));
    }

    public function testSingleton()
    {
        $p1 = Codendi_HTMLPurifier::instance();
        $p2 = Codendi_HTMLPurifier::instance();
        $this->assertReference($p1, $p2);
        $this->assertIsA($p1, 'Codendi_HTMLPurifier');
    }

    public function testPurifyJsQuoteAndDQuote()
    {
        $p = Codendi_HTMLPurifier::instance();
        $this->assertEqual('\u003C\/script\u003E', $p->purify('</script>', CODENDI_PURIFIER_JS_DQUOTE));
        $this->assertEqual('a\u0022a', $p->purify('a"a', CODENDI_PURIFIER_JS_DQUOTE));
        $this->assertEqual('\u0022a', $p->purify('"a', CODENDI_PURIFIER_JS_DQUOTE));
        $this->assertEqual('a\u0022', $p->purify('a"', CODENDI_PURIFIER_JS_DQUOTE));
        $this->assertEqual('\u0022', $p->purify('"', CODENDI_PURIFIER_JS_DQUOTE));
        $this->assertEqual('\"', $p->purify('"', CODENDI_PURIFIER_JS_QUOTE));
        $this->assertEqual(
            '\u003C\/script\u003E\\\nbla bla\\\n\u003C\/script\u003E\\\nbla bla\\\n\u003C\/script\u003E',
            $p->purify('</script>\nbla bla\n</script>\nbla bla\n</script>', CODENDI_PURIFIER_JS_DQUOTE)
        );
        $this->assertEqual('\u003C\/script\u003E', $p->purify('</script>', CODENDI_PURIFIER_JS_QUOTE));
        $this->assertEqual('100', $p->purify(100, CODENDI_PURIFIER_JS_QUOTE));
    }

    public function testBasicNobr()
    {
        $p = $this->getHTMLPurifier();
        $this->assertEqual("a<br />\nb", $p->purify("a\nb", CODENDI_PURIFIER_BASIC));
        $this->assertEqual("a\nb", $p->purify("a\nb", CODENDI_PURIFIER_BASIC_NOBR));
    }

    public function testMakeLinks()
    {
        $p = \Mockery::mock(\Codendi_HTMLPurifier::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertEqual('', $p->purify('', CODENDI_PURIFIER_BASIC));
        $this->assertEqual('<a href="https://www.example.com">https://www.example.com</a>', $p->purify('https://www.example.com', CODENDI_PURIFIER_BASIC));
        $this->assertEqual('"<a href="https://www.example.com">https://www.example.com</a>"', $p->purify('"https://www.example.com"', CODENDI_PURIFIER_BASIC));
        $this->assertEqual('\'<a href="https://www.example.com">https://www.example.com</a>\'', $p->purify('\'https://www.example.com\'', CODENDI_PURIFIER_BASIC));
        $this->assertEqual('&lt;<a href="https://www.example.com">https://www.example.com</a>&gt;', $p->purify('<https://www.example.com>', CODENDI_PURIFIER_BASIC));
        $this->assertEqual('<a href="mailto:john.doe@example.com">john.doe@example.com</a>', $p->purify('john.doe@example.com', CODENDI_PURIFIER_BASIC));
        $this->assertEqual('"<a href="mailto:john.doe@example.com">john.doe@example.com</a>"', $p->purify('"john.doe@example.com"', CODENDI_PURIFIER_BASIC));
        $this->assertEqual('\'<a href="mailto:john.doe@example.com">john.doe@example.com</a>\'', $p->purify('\'john.doe@example.com\'', CODENDI_PURIFIER_BASIC));
        $this->assertEqual('&lt;<a href="mailto:john.doe@example.com">john.doe@example.com</a>&gt;', $p->purify('<john.doe@example.com>', CODENDI_PURIFIER_BASIC));
        $this->assertEqual('<a href="ssh://gitolite@example.com/tuleap/stable.git">ssh://gitolite@example.com/tuleap/stable.git</a>', $p->purify('ssh://gitolite@example.com/tuleap/stable.git', CODENDI_PURIFIER_BASIC));
        $reference_manager = Mockery::mock(ReferenceManager::class);
        $reference_manager->shouldReceive('insertReferences')->withArgs(function (&$data, $group_id) {
            $data = preg_replace('/art #1/', '<a href="link-to-art-1">art #1</a>', $data);
            return true;
        });
        $p->shouldReceive('getReferenceManager')->andReturns($reference_manager);
        $this->assertPattern('/link-to-art-1/', $p->purify('art #1', CODENDI_PURIFIER_BASIC, 1));
    }

    public function testPurifierLight()
    {
        $p = Codendi_HTMLPurifier::instance();
        $this->assertEqual("foo\nbar", $p->purify("foo\nbar", CODENDI_PURIFIER_LIGHT));
        $this->assertEqual("foo\nbar", $p->purify("foo\r\nbar", CODENDI_PURIFIER_LIGHT));
    }

    public function testItDoesNotDoubleEscapeLinks()
    {
        $reference_manager = Mockery::mock(ReferenceManager::class);
        $reference_manager->shouldReceive('insertReferences')->withArgs(function (&$data, $group_id) {
            $data = preg_replace('/art #1/', '<a href="link-to-art-1">art #1</a>', $data);
            return true;
        });
        $p = \Mockery::mock(\Codendi_HTMLPurifier::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $p->shouldReceive('getReferenceManager')->andReturns($reference_manager);

        $html = 'Text with <a href="http://tuleap.net/">link</a> and a reference to art #1';
        $expected = 'Text with <a href="http://tuleap.net/">link</a> and a reference to <a href="link-to-art-1">art #1</a>';

        $this->assertEqual($expected, $p->purifyHTMLWithReferences($html, 123));
    }
}

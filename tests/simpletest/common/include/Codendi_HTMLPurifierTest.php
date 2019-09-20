<?php
/**
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
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

Mock::generatePartial(
    'Codendi_HTMLPurifier',
    'Codendi_HTMLPurifierTestVersion2',
    array('getReferenceManager')
);
Mock::generate('ReferenceManager');


class Codendi_HTMLPurifierTestVersion extends Codendi_HTMLPurifier
{
    private static $Codendi_HTMLPurifier_testversion_instance;
    // Need to redfine this method too because the parent one return a
    // 'Codendi_HTMLPurifier' object.

    protected function __construct()
    {
    }

    public static function instance()
    {
        if (!isset(self::$Codendi_HTMLPurifier_testversion_instance)) {
            $c = self::class;
            self::$Codendi_HTMLPurifier_testversion_instance = new $c;
        }
        return self::$Codendi_HTMLPurifier_testversion_instance;
    }
}

class ReferenceManagerTestMakeLinks extends MockReferenceManager
{
    function insertReferences(&$data, $group_id)
    {
        $data = preg_replace('/art #1/', '<a href="link-to-art-1">art #1</a>', $data);
    }
}

/**
 * Tests the class CodendiHTMLPurifier
 */
class Codendi_HTMLPurifierTest extends TuleapTestCase
{

    function testPurifySimple()
    {
        $p = Codendi_HTMLPurifier::instance();
        $this->assertEqual('&lt;script&gt;alert(1);&lt;/script&gt;', $p->purify('<script>alert(1);</script>'));
    }

    function testStripLightForibdden()
    {
        $p = new Codendi_HTMLPurifierTestVersion2($this);
        $rm = new MockReferenceManager();
        $val = 'bugtest #123';
        $rm->setReturnValue('insertReferences', $val);
        $p->setReturnValue('getReferenceManager', $rm);

        $this->assertEqual('', $p->purify('<script>alert(1);</script>', CODENDI_PURIFIER_LIGHT));
        $this->assertEqual('Bolded', $p->purify('<s>Bolded</s>', CODENDI_PURIFIER_LIGHT));
        $this->assertEqual($val, $p->purify('bugtest #123', CODENDI_PURIFIER_LIGHT, 102));
        $this->assertEqual('', $p->purify('<form name="test" method="post" action="?"><input type="submit" /></form>', CODENDI_PURIFIER_LIGHT));
        $this->assertEqual('<a href="ftp://test.com">ftp://test.com</a>', $p->purify('ftp://test.com', CODENDI_PURIFIER_LIGHT));
        $this->anchorJsInjection(CODENDI_PURIFIER_LIGHT);
    }

    function anchorJsInjection($level)
    {
        $p = Codendi_HTMLPurifierTestVersion::instance();
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

    function testStripLightAllowed()
    {
        $p = Codendi_HTMLPurifierTestVersion::instance();

        $this->assertEqual('<p>Text</p>', $p->purify('<p>Text</p>', CODENDI_PURIFIER_LIGHT));
        $this->assertEqual('Text<br />', $p->purify('Text<br />', CODENDI_PURIFIER_LIGHT));

        $this->assertEqual('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net">Text</a>', CODENDI_PURIFIER_LIGHT));

        $this->assertEqual('<strong>Text</strong>', $p->purify('<strong>Text</strong>', CODENDI_PURIFIER_LIGHT));
    }

    function testStripLightTidy()
    {
        $p = Codendi_HTMLPurifierTestVersion::instance();
        $this->assertEqual('<p>Text</p>', $p->purify('<p>Text', CODENDI_PURIFIER_LIGHT));
        $this->assertEqual('Text<br />', $p->purify('Text<br>', CODENDI_PURIFIER_LIGHT));

        $this->assertEqual('<a href="http://php.net">Text</a>', $p->purify('<a href=\'http://php.net\'>Text', CODENDI_PURIFIER_LIGHT));
    }

    function testPurifyArraySimple()
    {
        $p = Codendi_HTMLPurifier::instance();

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

    function testSingleton()
    {
        $p1 = Codendi_HTMLPurifier::instance();
        $p2 = Codendi_HTMLPurifier::instance();
        $this->assertReference($p1, $p2);
        $this->assertIsA($p1, 'Codendi_HTMLPurifier');
    }

    function testPurifyJsQuoteAndDQuote()
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

    function testBasicNobr()
    {
        $p = Codendi_HTMLPurifierTestVersion::instance();
        $this->assertEqual("a<br />\nb", $p->purify("a\nb", CODENDI_PURIFIER_BASIC));
        $this->assertEqual("a\nb", $p->purify("a\nb", CODENDI_PURIFIER_BASIC_NOBR));
    }

    function testMakeLinks()
    {
        $p = new Codendi_HTMLPurifierTestVersion2($this);
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
        $rm = new ReferenceManagerTestMakeLinks();
        $p->setReturnValue('getReferenceManager', $rm);
        $this->assertPattern('/link-to-art-1/', $p->purify('art #1', CODENDI_PURIFIER_BASIC, 1));
    }

    function testPurifierLight()
    {
        $p = Codendi_HTMLPurifier::instance();
        $this->assertEqual("foo\nbar", $p->purify("foo\nbar", CODENDI_PURIFIER_LIGHT));
        $this->assertEqual("foo\nbar", $p->purify("foo\r\nbar", CODENDI_PURIFIER_LIGHT));
    }

    public function itDoesNotDoubleEscapeLinks()
    {
        $reference_manager = new ReferenceManagerTestMakeLinks();
        $p = partial_mock('Codendi_HTMLPurifier', array('getReferenceManager'));
        stub($p)->getReferenceManager()->returns($reference_manager);

        $html = 'Text with <a href="http://tuleap.net/">link</a> and a reference to art #1';
        $expected = 'Text with <a href="http://tuleap.net/">link</a> and a reference to <a href="link-to-art-1">art #1</a>';

        $this->assertEqual($expected, $p->purifyHTMLWithReferences($html, 123));
    }
}

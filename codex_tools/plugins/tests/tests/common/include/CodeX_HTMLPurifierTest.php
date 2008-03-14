<?php
/**
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once('common/include/CodeX_HTMLPurifier.class.php');

// Need a TestVersion to by pass '_makeLinks' method (call to utils_make_links
// that perform DB calls).
// Need to create this testversion by hand because with Mock object there is no
// way to tell them "return the parameter as is".
// This method to be used only when mandatory (when the is a utils_make_links call).
class CodeX_HTMLPurifierTestVersion
extends CodeX_HTMLPurifier {
    function CodeX_HTMLPurifierTestVersion() {
        parent::CodeX_HTMLPurifier();
    }
    // Need to redfine this method too because the parent one return a
    // 'CodeX_HTMLPurifier' object.
    function &instance() {
        static $__codex_htmlpurifiertestversion_instance;
        if(!$__codex_htmlpurifiertestversion_instance) {
            $__codex_htmlpurifiertestversion_instance = new CodeX_HTMLPurifierTestVersion();
        }
        return $__codex_htmlpurifiertestversion_instance;
    }
    function _makeLinks($str, $gid) {
        return $str;
    }
}

/**
 * Tests the class CodeXHTMLPurifier
 */
class CodeX_HTMLPurifierTest extends UnitTestCase {

    function UnitTestCase($name = 'CodeX_HTMLPurifier test') {
        $this->UnitTestCase($name);
    }

    function setUp() {
    }

    function tearDown() {
    }



    function testPurifySimple() {
        $p =& CodeX_HTMLPurifier::instance();
        $this->assertEqual('&lt;script&gt;alert(1);&lt;/script&gt;', $p->purify('<script>alert(1);</script>'));
    }

    function testStripLightForibdden() {
        $p =& CodeX_HTMLPurifierTestVersion::instance();
        $this->assertEqual('', $p->purify('<script>alert(1);</script>', CODEX_PURIFIER_LIGHT));
        $this->assertEqual('Bolded', $p->purify('<s>Bolded</s>', CODEX_PURIFIER_LIGHT));
        $this->assertEqual('', $p->purify('<form name="test" method="post" action="?"><input type="submit" /></form>', CODEX_PURIFIER_LIGHT));
        $this->anchorJsInjection(CODEX_PURIFIER_LIGHT);
    }

    function anchorJsInjection($level) {
        $p =& CodeX_HTMLPurifierTestVersion::instance();
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

    function testStripLightAllowed() {
        $p =& CodeX_HTMLPurifierTestVersion::instance();

        $this->assertEqual('<p>Text</p>', $p->purify('<p>Text</p>', CODEX_PURIFIER_LIGHT));
        $this->assertEqual('Text<br />', $p->purify('Text<br />', CODEX_PURIFIER_LIGHT));

        $this->assertEqual('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net">Text</a>', CODEX_PURIFIER_LIGHT));

        $this->assertEqual('<strong>Text</strong>', $p->purify('<strong>Text</strong>', CODEX_PURIFIER_LIGHT));
    }

    function testStripLightTidy() {
        $p =& CodeX_HTMLPurifierTestVersion::instance();
        $this->assertEqual('<p>Text</p>', $p->purify('<p>Text', CODEX_PURIFIER_LIGHT));
        $this->assertEqual('Text<br />', $p->purify('Text<br>', CODEX_PURIFIER_LIGHT));

        $this->assertEqual('<a href="http://php.net">Text</a>', $p->purify('<a href=\'http://php.net\'>Text', CODEX_PURIFIER_LIGHT));

    }

    function testPurifyArraySimple() {
        $p =& CodeX_HTMLPurifier::instance();

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

    function testSingleton() {
        $p1 =& CodeX_HTMLPurifier::instance();
        $p2 =& CodeX_HTMLPurifier::instance();
        $this->assertReference($p1, $p2);
        $this->assertIsA($p1, 'CodeX_HTMLPurifier');
    }

    function testPurifyJsQuoteAndDQuote() {
        $p =& CodeX_HTMLPurifier::instance();
        $this->assertEqual('</"+"script>', $p->purify('</script>', CODEX_PURIFIER_JS_DQUOTE));
        $this->assertEqual('a\"a', $p->purify('a"a', CODEX_PURIFIER_JS_DQUOTE));
        $this->assertEqual('\"a', $p->purify('"a', CODEX_PURIFIER_JS_DQUOTE));
        $this->assertEqual('a\"', $p->purify('a"', CODEX_PURIFIER_JS_DQUOTE));
        $this->assertEqual('\"', $p->purify('"', CODEX_PURIFIER_JS_DQUOTE));
        $this->assertEqual('</"+"script>'."\n".'bla bla'."\n".'</"+"script>'."\n".'bla bla'."\n".'</"+"script>', $p->purify('</script>\nbla bla\n</script>\nbla bla\n</script>', CODEX_PURIFIER_JS_DQUOTE));
        $this->assertEqual("</'+'script>", $p->purify('</script>', CODEX_PURIFIER_JS_QUOTE));
    }
}
?>

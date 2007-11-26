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

/**
 * Tests the class CodeXHTMLPurifier
 */
class CodeX_HTMLPurifierTest extends UnitTestCase {

    function UnitTestCase($name = 'CodeX_HTMLPurifier test') {
        $this->UnitTestCase($name);
    }

    function setUp() {
        if(!function_exists('util_make_links')) {
            // Fake  util_make_links method
            function util_make_links($data, $gid=0) {
                return $data;
            }
        }
    }

    function tearDown() {
    }

    function testPurifySimple() {
        $p =& CodeX_HTMLPurifier::instance();
        $this->assertEqual('&lt;script&gt;alert(1);&lt;/script&gt;', $p->purify('<script>alert(1);</script>'));
    }

    function testStripLightForibdden() {
        $p =& CodeX_HTMLPurifier::instance();
        $this->assertEqual('', $p->purify('<script>alert(1);</script>', CODEX_PURIFIER_LIGHT));
        $this->assertEqual('Bolded', $p->purify('<b>Bolded</b>', CODEX_PURIFIER_LIGHT));
        $this->assertEqual('', $p->purify('<form name="test" method="post" action="?"><input type="submit" /></form>', CODEX_PURIFIER_LIGHT));
        $this->anchorJsInjection(CODEX_PURIFIER_LIGHT);
    }

    function anchorJsInjection($level) {
        $p =& CodeX_HTMLPurifier::instance();
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
        $p =& CodeX_HTMLPurifier::instance();

        $this->assertEqual('<p>Text</p>', $p->purify('<p>Text</p>', CODEX_PURIFIER_LIGHT));
        $this->assertEqual('Text<br />', $p->purify('Text<br />', CODEX_PURIFIER_LIGHT));

        $this->assertEqual('<a href="http://php.net">Text</a>', $p->purify('<a href="http://php.net">Text</a>', CODEX_PURIFIER_LIGHT));

        $this->assertEqual('<strong>Text</strong>', $p->purify('<strong>Text</strong>', CODEX_PURIFIER_LIGHT));
    }

    function testStripLightTidy() {
        $p =& CodeX_HTMLPurifier::instance();
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

}
?>

<?php
/**
 *
 * Originally written by Nicolas TERRAY, 2008.
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

require_once('common/include/CodeX_HTTPPurifier.class.php');


/**
 * Tests the class CodeXHTMLPurifier
 */
class CodeX_HTTPPurifierTest extends UnitTestCase {

    function UnitTestCase($name = 'CodeX_HTTPPurifier test') {
        $this->UnitTestCase($name);
    }

    function testPurify() {
        $p =& CodeX_HTTPPurifier::instance();
        $this->assertEqual('a', $p->purify("a"));
        $this->assertEqual('a', $p->purify("a\n"));
        $this->assertEqual('a', $p->purify("a\nb"));
        $this->assertEqual('a', $p->purify("a\r"));
        $this->assertEqual('a', $p->purify("a\rb"));
        $this->assertEqual('a', $p->purify("a\r\nb"));
        $this->assertEqual('a', $p->purify("a\0b"));
        $this->assertEqual('', $p->purify("\rabc"));
        $this->assertEqual('', $p->purify("\nabc"));
        $this->assertEqual('', $p->purify("\r\nabc"));
        $this->assertEqual('', $p->purify("\0abc"));
        
    }
}
?>

<?php
/**
 * Copyright (c) STMicroelectronics 2011. All rights reserved
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

require_once('common/html/HTML_Element_Selectbox_Array.class.php');
require_once('common/include/Codendi_HTMLPurifier.class.php');
require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

class HTML_Element_Selectbox_ArrayTest extends UnitTestCase {

    function setup() {
        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Language']->setReturnValue('getText', 'none');
    }

    function tearDown() {
        unset($GLOBALS['Language']);
    }

    function testblah() {
        $selectbox = new HTML_Element_Selectbox_Array(array('one' => '1', 'two' => '2'), 'id', 'label', 'name', 'value', false, '', '', 'two', false);
        $this->assertEqual('<select id="id" name="name" ><option value="one" >1</option><option value="two" selected="selected">2</option></select>', $selectbox->renderValue());
    }

    function testblah2() {
        $selectbox = new HTML_Element_Selectbox_Array(array('one' => '1', 'two' => '2'), 'id', 'label', 'name', 'value', false, '', '', 'one', true);
        $this->assertEqual('<select id="id" name="name" ><option value="1" >1</option><option value="2" >2</option></select>', $selectbox->renderValue());
    }

    function testblah3() {
        $selectbox = new HTML_Element_Selectbox_Array(array('one' => '1', 'two' => '2'), 'id', 'label', 'name', 'value', true, '', '', 'two', false);
        $this->assertEqual('<select id="id" name="name" ><option value="" >none</option><option value="one" >1</option><option value="two" selected="selected">2</option></select>', $selectbox->renderValue());
    }

}

?>
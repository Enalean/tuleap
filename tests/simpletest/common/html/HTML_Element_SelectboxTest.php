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

Mock::generate('BaseLanguage');

class HTML_Element_SelectboxTest extends TuleapTestCase
{

    public function setup()
    {
        parent::setUp();
        $GLOBALS['Language']->setReturnValue('getText', 'none');
    }

    function testWithNone()
    {
        $selectbox = new HTML_Element_Selectbox('label', 'name', 'value', true);
        $this->assertEqual('<select id="customfield_0" name="name" ><option value="" >none</option></select>', $selectbox->renderValue());
    }

    function testSetId()
    {
        $selectbox = new HTML_Element_Selectbox('label', 'name', 'value', false);
        $selectbox->setId('id');
        $this->assertEqual('<select id="id" name="name" ></select>', $selectbox->renderValue());
    }

    function testAddMultipleOptions()
    {
        $selectbox = new HTML_Element_Selectbox('label', 'name', 'value', false);
        $selectbox->addMultipleOptions(array('one' => '1', 'two' => '2'), 'two');
         $this->assertEqual('<select id="customfield_3" name="name" ><option value="one" >1</option><option value="two" selected="selected">2</option></select>', $selectbox->renderValue());
    }
}

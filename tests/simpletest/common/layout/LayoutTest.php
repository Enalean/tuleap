<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

require_once 'common/layout/Layout.class.php';

require_once 'common/event/EventManager.class.php';
Mock::generate('EventManager');

/**
 * For all tests we have to use partial mock because there are sessions related stuff in Respone class.
 */
class LayoutTest extends UnitTestCase {

    function setUp() {
        $GLOBALS['sys_user_theme']     = 'Stuff';
        $GLOBALS['sys_user_font_size'] = 2;
    }

    function tearDown() {
        unset($GLOBALS['sys_user_theme']);
        unset($GLOBALS['sys_user_font_size']);
    }

    function testAddStyleSheet() {
        $l = TestHelper::getPartialMock('Layout', array());
        $l->addStylesheet('/theme/css/style.css');
        $this->assertEqual($l->getAllStyleSheets(), array('/theme/css/style.css'));
    }

    function testAddedStyleSheetShouldBeRenderedInPageHeaders() {
        $l = TestHelper::getPartialMock('Layout', array('getEventManager', 'getFontSizeName', 'getStylesheetTheme'));
        $l->setReturnValue('getEventManager', new MockEventManager());

        $css = '/viewvc-static/styles.css';

        $l->addStylesheet($css);
        ob_start();
        $l->displayStylesheetElements(array());
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertTrue(strpos($content, '<link rel="stylesheet" type="text/css" href="'.$css.'" />'), "There should be a custom css here.");
    }
}

?>
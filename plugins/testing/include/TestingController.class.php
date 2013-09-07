<?php
/**
* Copyright Enalean (c) 2013. All rights reserved.
* Tuleap and Enalean names and logos are registrated trademarks owned by
* Enalean SAS. All other trademarks or names are properties of their respective
* owners.
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

require_once 'common/mvc2/PluginController.class.php';

/**
 * Abstract controller which render itself in the `testing` service
 */
abstract class TestingController extends MVC2_PluginController {

    protected function render($template, $renderer) {
        $service = $this->getProject()->getService('plugin_testing');
        $service->displayHeader($GLOBALS['Language']->getText('plugin_testing', 'descriptor_name'), array(), array());
        echo '<div id="plugin_testing">';
        echo '<h1>Test management</h1>';

        parent::render($template, $renderer);

        echo '</div>';
        $service->displayFooter();
    }
}

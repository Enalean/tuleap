<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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

require_once('mvc/PluginView.class.php');
require_once('common/include/HTTPRequest.class.php');

/**
 * RequestHelpViews
 */
class CodeReviewViews extends PluginView {

    /**
     * Display header
     *
     * @return void
     */
    function header() {
        $title = 'codereview';
        $GLOBALS['HTML']->header(array('title'=>'codereview','group' => $this->getController()->getRequest()->get('group_id'), 'toptab' => 'my'));
    }

    /**
     * Display footer
     *
     * @return void
     */
    function footer() {
        $GLOBALS['HTML']->footer(array());
    }

    // {{{ Views
    /**
    * Displays Review board frame
    *
    * @return void
    */
    function displayFrame() {
        $pluginInfo = PluginManager::instance()->getPluginByName('codereview')->getPluginInfo();
        echo '<iframe src="'.$pluginInfo->getPropertyValueForName('reviewboard_site').'" width="900" height="500"
              frameborder="0" scrolling="auto" name="reviewboard">
              TEXT FOR NON-COMPATIBLE BROWSERS HERE</iframe>';
    }
}

?>
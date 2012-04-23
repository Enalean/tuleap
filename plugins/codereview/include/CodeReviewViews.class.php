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

require_once('common/mvc/Views.class.php');
require_once('common/include/HTTPRequest.class.php');

/**
 * CodeReviewViews
 */
class CodeReviewViews extends Views {

    /**
     *
     * @var PluginController
     */
    protected $controller;

    /**
     *
     * @var HTTPRequest
     */
    protected $request;

    /**
     * Class constructor
     *
     * @return Void
     */
    public function __construct($controller) {
        $this->controller = $controller;
        $this->request    = $controller->getRequest();
    }

    /**
     * Display header
     *
     * @return void
     */
    function header() {
        $title = 'codereview';
        $GLOBALS['HTML']->header(array('title' => $this->_getTitle(), 'group' => $this->request->get('group_id'), 'toptab' => 'my'));
    }

    /**
     * Display footer
     *
     * @return void
     */
    function footer() {
        $GLOBALS['HTML']->footer(array());
    }

    /**
     * Retrieve plugin title
     *
     * @return string
     */
    function _getTitle() {
        return $GLOBALS['Language']->getText('plugin_codereview', 'title');
    }

    // {{{ Views
    /**
    * Displays Review board frame
    *
    * @return void
    */
    function displayFrame() {
        $pluginInfo = PluginManager::instance()->getPluginByName('codereview')->getPluginInfo();
        $url        = $pluginInfo->getPropertyValueForName('reviewboard_site');
        echo '<div id="codereview_iframe_div">';
        $GLOBALS['HTML']->iframe($url, array('id' => 'codereview_iframe', 'class' => 'iframe_service'));
        echo '</div>';
    }
}

?>
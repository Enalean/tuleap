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

require_once 'common/plugin/Plugin.class.php';

/**
 * CodeReviewPlugin
 */
class CodeReviewPlugin extends Plugin {

    /**
     * Plugin constructor
     *
     * @return Void
     */
    function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
    }

    public function process(Codendi_Request $request) {
        $GLOBALS['HTML']->header(array('title'=>'codereview','group' => $request->get('group_id'), 'toptab' => 'codereview'));
        echo '<iframe src="http://localhost/reviews/" width="900" height="500"
              frameborder="0" scrolling="auto" name="reviewboard">
              TEXT FOR NON-COMPATIBLE BROWSERS HERE</iframe>';
        $GLOBALS['HTML']->footer(array());
    }

    /**
     * Obtain plugin info
     *
     * @return CodeReviewPluginInfo
     */
    public function getPluginInfo() {
        if (!$this->pluginInfo) {
            include_once 'CodeReviewPluginInfo.class.php';
            $this->pluginInfo = new CodeReviewPluginInfo($this);
        }
        return $this->pluginInfo;
    }
}

?>

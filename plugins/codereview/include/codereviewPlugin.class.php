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
require_once('CodeReview.class.php');

/**
 * CodeReviewPlugin
 */
class CodeReviewPlugin extends Plugin {

    function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
    }

    public function process(Codendi_Request $request) {
        $controler = new CodeReview();
        $controler->process();
    }

    /**
     * Obtain plugin info
     *
     * @return CodeReviewPluginInfo
     */
    function getPluginInfo() {
        if (!$this->pluginInfo instanceof CodeReviewPluginInfo) {
            include_once('CodeReviewPluginInfo.class.php');
            $this->pluginInfo = new CodeReviewPluginInfo($this);
        }
        return $this->pluginInfo;
    }
}
?>
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

require_once('mvc/PluginControler.class.php');
require_once('CodeReviewViews.class.php');

/**
 * codereview */
class CodeReview extends PluginControler {

    protected $plugin;

    function CodeReview() {
        parent::__construct();
        $this->plugin = PluginFactory::instance()->getPluginByName('codereview');
    }

    /**
     * Compute the request
     *
     * @return void
     */
    function request() {
        $request = $this->getRequest();
        if ($this->getUser()->isLoggedIn()) {
            $vAction = new Valid_WhiteList('action', array('submit_ticket'));
            $vAction->required();
            $action = $request->getValidated('action', $vAction, false);
            switch ($action) {
                case 'add_review':
                    //put some actions here
                    $this->addview('reviewSubmission');
                    break;
                default:
                    $this->addview('displayFrame');
                    break;
            }
        } else {
            $this->addview('displayFrame');
        }
    }
}

?>
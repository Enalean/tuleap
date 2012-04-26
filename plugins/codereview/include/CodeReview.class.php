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

require_once('common/mvc/Controler.class.php');
require_once('CodeReviewViews.class.php');
require_once('RepositoryManager.class.php');

/**
 * codereview
 */
class CodeReview extends Controler {

    protected $plugin;

    /**
     * Class constructor
     *
     * @param codeReviewPlugin $plugin Instance of the plugin
     *
     * @return Void
     */
    function __construct($plugin) {
        $this->plugin  = $plugin;
        $this->request = HTTPRequest::instance();
        $this->user    = UserManager::instance()->getCurrentUser();
    }

    /**
     * Retrieve request
     *
     * @return HTTPRequest
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * Retrieve current user
     *
     * @return User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * Compute the request
     *
     * @return void
     */
    function request() {
        $request = $this->getRequest();
        if ($this->getUser()->isLoggedIn()) {
            $repositoryManager = new RepositoryManager($this->plugin);
            $repositoryManager->addRepository($request);
            $vAction = new Valid_WhiteList('action', array('add_review'));
            $vAction->required();
            $action = $request->getValidated('action', $vAction, false);
            switch ($action) {
            case 'add_review':
                // TODO: put some actions here
                $this->view = 'reviewSubmission';
                break;
            default:
                $this->view = 'displayFrame';
                break;
            }
        } else {
            $this->view = 'displayFrame';
        }
    }
}

?>
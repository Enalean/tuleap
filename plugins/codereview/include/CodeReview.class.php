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
require_once('CodeReviewActions.class.php');
require_once('RepositoryManager.class.php');
require_once('RbUserManager.class.php');

/**
 * codereview
 */
class CodeReview extends Controler {

    public $plugin;

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
        $user=$this->getUser();
        $username=$user->getUserName();
        $userpwd=$user->getUserPw();
        $pluginInfo = PluginManager::instance()->getPluginByName('codereview')->getPluginInfo();
        $url=$pluginInfo->getPropertyValueForName('reviewboard_site');
        $rbuser=$pluginInfo->getPropertyValueForName('admin_user');
        $rbpass=$pluginInfo->getPropertyValueForName('admin_pwd');
        /******check if the Tuleap User is registred in reviewboard******/
        $rbusermanager = new RbUserManager();
        $exist         = $rbusermanager->searchUser($url."/api/users/", false, $rbuser, $rbpass, null, $username);
        if (!$exist) {
         $curl   = new TuleapCurl();
         $create = $curl->execute($url."/api/users/", false, $username, $userpwd, null);
        }
        if ($this->getUser()->isLoggedIn()) {
            if($username == "admin") {
                $this->view = 'displayFrameAdmin';
            } else {
                $repositoryManager = new RepositoryManager($this->plugin, $request);
                $repositoryManager->addRepository($request);
                $vAction = new Valid_WhiteList('action', array('add_review', 'dashboard', 'submit_review', 'login', 'publish_review', 'submit_publish', 'submit_login'));
                $vAction->required();
                $action = $request->getValidated('action', $vAction, false);
                switch ($action) {
                case 'add_review':
                    $this->view = 'reviewSubmission';
                    break;
                case 'submit_review':
                    // TODO: put some actions here
                    $this->action = 'createReviewRequest';
                    break;
                case 'publish_review':
                    $this->view = 'reviewPublishing';
                    break;
                case 'submit_publish':
                    // TODO: put some actions here
                    $this->action = 'publishReviewRequest';
                    $this->view ='displayFramePublish';
                    break;  
                case 'login':
                    $this->view = 'loginSubmission';
                    break;
                
                case 'dashboard':
                    $this->view = 'displayFrame';
                    break;
                default:
                    $this->view = 'displayFirstFrame';
                    break;
                }
            }
        } else {
            $this->view = 'displayFrame';
        }
    }
}

?>
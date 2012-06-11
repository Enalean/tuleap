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

require_once('common/mvc/Actions.class.php');
require_once('common/curl/TuleapCurl.class.php');
require_once('exceptions/CodeReviewException.class.php');
require_once('RbUserManager.class.php');

/**
 * codereview
 */
class CodeReviewActions extends Actions {

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
     *
     * @var Id Review Request
     */
    private $idreviewrequest;

    /**
     * Class constructor
     *
     * @param CodeReview $controller Plugin controller
     *
     * @return Void
     */
    function __construct($controller, $view=null) {
        parent::Actions($controller);
        $this->controller = $controller;
        $this->request    = $controller->getRequest();
    }

    /**
    * Validate request values
    *
    * @return Array
    */
    function validateRequest() {
        $status  = true;
        $invalid = array();
        
        $user     = UserManager::instance()->getCurrentUser();
        $username = $user->getUserName();
        $project  = ProjectManager::instance()->getProject($this->request->get('group_id'));
        //var_dump("verifiy if the current user is a project admin or not : ");
        if(!$project->userIsAdmin()){
        $msg = "The user '".$username."' has not the right to create a review request.";
                $GLOBALS['Response']->addFeedBack('error', $msg);
                $this->controller->view = 'displayFrame';}

        $valid  = new Valid_String('codereview_server_url');
        $server = trim($this->request->get('codereview_server_url'));
        if ($this->request->valid($valid) && $server != '') {
            $params['server'] = $server;
        } else {
            $status    = false;
            $invalid[] = 'server';
        }

        $valid      = new Valid_String('codereview_repository_url');
        $repository = trim($this->request->get('codereview_repository_url'));
        if ($this->request->valid($valid) && $repository != '') {
            $params['repository'] = $repository;
        } else {
            $status    = false;
            $invalid[] = 'repository';
        }

        $valid   = new Valid_String('codereview_summary');
        $summary = trim($this->request->get('codereview_summary'));
        if ($this->request->valid($valid) && $summary != '') {
            $params['summary'] = $summary;
        } else {
            $status    = false;
            $invalid[] = 'Description';
        }

/*        $valid     = new Valid_String('codereview_revision_range');
        $revisions = trim($this->request->get('codereview_revision_range'));
        if ($this->request->valid($valid) && $revisions != '') {
            $params['revisions'] = $revisions;
        } else {
            $status    = false;
            $invalid[] = 'revisions';
        }

        $valid       = new Valid_String('codereview_description');
        $description = trim($this->request->get('codereview_description'));
        if ($this->request->valid($valid) && $description != '') {
            $params['description'] = $description;
        } else {
            $status    = false;
            $invalid[] = 'description';
        }
*/
        $valid         = new Valid_String('codereview_target_people');
        $target_people = trim($this->request->get('codereview_target_people'));
        if ($this->request->valid($valid) && $target_people != '') {
            $params['target_people'] = $target_people;
            $reviewers               = explode(",", $target_people);
            $check                   = true;
            $nbr                     = count($reviewers);
            for($i = 0;$i<$nbr && $check;$i++){
                $username = $reviewers[$i];
                $check    = $this->isUGroup($username);
                if ($check){
                    $pluginInfo = PluginManager::instance()->getPluginByName('codereview')->getPluginInfo();
                    $url=$pluginInfo->getPropertyValueForName('reviewboard_site');
                    $rbuser=$pluginInfo->getPropertyValueForName('admin_user');
                    $rbpass=$pluginInfo->getPropertyValueForName('admin_pwd');
                    $rbusermanager    = new RbUserManager();
                    $exist            = $rbusermanager->searchUser($url."/api/users/", false, $rbuser, $rbpass, null,$username);
                    if(!$exist){
                        $user    = UserManager::instance()->getUserByUserName($username);
                        $userpwd = $user->getUserPw();
                        $curl    = new TuleapCurl();
                        $create  = $curl->execute($url."/api/users/", false, $username, $userpwd, null);
                    }
                }
            }
            if (!$check){
                $msg = "The user '".$username."' is not a member of your project.";
                $GLOBALS['Response']->addFeedBack('error', $msg);
                $this->controller->view = 'displayFrame';
                $status                 = false;
                $invalid[]              = 'target_people';
            }
        } else {
            $status    = false;
            $invalid[] = 'target_people';
        }

/*         $valid     = new Valid_String('codereview_testing_done');
        $testingDone = trim($this->request->get('codereview_testing_done'));
        if ($this->request->valid($valid) && $testingDone != '') {
            $params['testing_done'] = $testingDone;
        } else {
            $status    = false;
            $invalid[] = 'testing_done';
        }

       $valid     = new Valid_String('codereview_submit_as');
        $submitAs = trim($this->request->get('codereview_submit_as'));
        if ($this->request->valid($valid) && $submitAs != '') {
            $params['submit_as'] = $submitAs;
        } else {
            $status    = false;
            $invalid[] = 'submit_as';
        }*/


        $valid     = new Valid_String('codereview_rb_user');
        $rbUser = trim($this->request->get('codereview_rb_user'));
        if ($this->request->valid($valid) && $rbUser != '') {
            $params['rb_user'] = $rbUser;
        } else {
            $status    = false;
            $invalid[] = 'rb_user';
        }

        $valid  = new Valid_String('codereview_rb_password');
        $rbPass = trim($this->request->get('codereview_rb_password'));
        if ($this->request->valid($valid) && $rbPass != '') {
            $params['rb_password'] = $rbPass;
        } else {
            $status    = false;
            $invalid[] = 'rb_password';
        }

        $valid   = new Valid_String('codereview_base_dir');
        $baseDir = trim($this->request->get('codereview_base_dir'));
        if ($this->request->valid($valid) && $baseDir != '') {
            $params['base_dir'] = $baseDir;
        } else {
            $status    = false;
            $invalid[] = 'base_dir';
        }

        $valid     = new Valid_String('codereview_diff_path');
        $diffPath = trim($this->request->get('codereview_diff_path'));
        if ($this->request->valid($valid) && $diffPath != '') {
            $params['diff_path'] = $diffPath;
        } else {
            $status    = false;
            $invalid[] = 'diff_path';
        }

        return array('status' => $status, 'params' => $params, 'invalid' => $invalid);
}

function validateRequest2() {
        $status  = true;
        $invalid = array();

        $valid  = new Valid_String('codereview_server_url');
        $server = trim($this->request->get('codereview_server_url'));
        if ($this->request->valid($valid) && $server != '') {
            $params['server'] = $server;
        } else {
            $status    = false;
            $invalid[] = 'server';
        }

        $valid     = new Valid_String('codereview_rb_user');
        $rbUser = trim($this->request->get('codereview_rb_user'));
        if ($this->request->valid($valid) && $rbUser != '') {
            $params['rb_user'] = $rbUser;
        } else {
            $status    = false;
            $invalid[] = 'rb_user';
        }

        $valid  = new Valid_String('codereview_rb_password');
        $rbPass = trim($this->request->get('codereview_rb_password'));
        if ($this->request->valid($valid) && $rbPass != '') {
            $params['rb_password'] = $rbPass;
        } else {
            $status    = false;
            $invalid[] = 'rb_password';
        }

        $valid   = new Valid_String('review_id');
        $review_id = trim($this->request->get('review_id'));
        if ($this->request->valid($valid) && $review_id != '') {
            $params['review_id'] = $review_id;
        } else {
            $status    = false;
            $invalid[] = 'review_id';
        }
        return array('status' => $status, 'params' => $params, 'invalid' => $invalid);
}

    /**
    * Creates a new review request
    *
    * @return void
    */
    function createReviewRequest() {
        $reviewRessources = $this->validateRequest();
        if ($reviewRessources['status']) {
            $server          = $reviewRessources['params']['server'];
            $repository      = $reviewRessources['params']['repository'];
            $rb_user         = $reviewRessources['params']['rb_user'];
            $rb_password     = $reviewRessources['params']['rb_password'];
            $reviewSubmitter = '';//$reviewRessources['params']['submit_as'];
            $testing_done    = '';//$reviewRessources['params']['testing_done'];
            $summary         = $reviewRessources['params']['summary'];
            $target_people   = $reviewRessources['params']['target_people'];
            $description     = '';//$reviewRessources['params']['description'];
            $baseDir         = $reviewRessources['params']['base_dir'];
            $path            = "@".$reviewRessources['params']['diff_path'];
            try {
                $reviewRequestId = $this->postEmptyReview($server, $repository, $rb_user, $rb_password, $reviewSubmitter);
                $idreviewrequest=$reviewRequestId;
                $this->updateEmptyReview($server, $reviewRequestId, $rb_user, $rb_password, $testing_done, $summary, $target_people, $description);
                $this->CreateNewDiff($server, $reviewRequestId, $rb_user, $rb_password, $baseDir, $path);
                $this->publishReviewRequestDraft($server, $reviewRequestId, $rb_user, $rb_password);
            } catch(CodeReviewException $exception) {
                $GLOBALS['Response']->addFeedBack('error', $exception->getMessage());
                $this->controller->view = 'displayFrame';
            }
        }
    }

    /**
    * Publish a new review request
    *
    * @return void
    */
    function publishReviewRequest() {
        $reviewRessources = $this->validateRequest2();
        if ($reviewRessources['status']) {
            $server          = $reviewRessources['params']['server'];
            $rb_user         = $reviewRessources['params']['rb_user'];
            $rb_password     = $reviewRessources['params']['rb_password'];
            $review_id         = $reviewRessources['params']['review_id'];
            try {
                $this->publishReviewRequestDraft($server, $review_id, $rb_user, $rb_password);
            } catch(CodeReviewException $exception) {
                $GLOBALS['Response']->addFeedBack('error', $exception->getMessage());
                $this->controller->view = 'displayFrame';
            }
        }
    }

    /**
     * Creates a review request
     *
     * @param String  $server
     * @param String  $repository
     * @param String  $rb_user
     * @param String  $rb_password
     * @param String  $reviewSubmitter
     *
     * @return Integer
     */
    function postEmptyReview($server, $repository, $rb_user, $rb_password, $reviewSubmitter = null) {
        $data = array('repository' => $repository,
                      'submit_as'  => $reviewSubmitter);
        $curl    = new TuleapCurl();
        $request = $curl->execute($server."/api/review-requests/", false, $rb_user, $rb_password, $data);
        if ($request['return']) {
            if ($request['return']['stat'] == "ok") {
                return $request['return']['review_request']['id'];
            } else {
                $msg = "Request status: ".$request['status'].' => ';
                $msg .= __METHOD__." - ".$request['return']['stat'].': '.$request['return']['err']['msg'];
                $code = $request['return']['err']['code'];
                throw new CodeReviewException($msg, $code);
            }
        } else {
            $msg = "Request status: ".$request['status'];
            throw new CodeReviewException($msg);
        }
    }

    /**
     * Update a given review request
     *
     * @param String  $server
     * @param Integer $reviewRequestId
     * @param String  $rb_user
     * @param String  $rb_password
     * @param String  $testing_done
     * @param String  $summary
     * @param String  $target_people
     * @param String  $description
     *
     * @return void
     */
    function updateEmptyReview($server, $reviewRequestId, $rb_user, $rb_password, $testing_done , $summary, $target_people, $description) {
        $data = array('testing_done'   => $testing_done,
                      'target_people'  => $target_people,
                      'description'    => $description,
                      'summary'        => $summary);
        $curl    = new TuleapCurl();
        $request = $curl->execute($server."/api/review-requests/".$reviewRequestId."/draft/", false, $rb_user, $rb_password, $data);
    }

    function publishReviewRequestDraft($server, $reviewRequestId, $rb_user, $rb_password) {
        $data = array('public' => 'true');
        $curl    = new TuleapCurl();
        $request = $curl->execute($server."/api/review-requests/".$reviewRequestId."/draft/", false, $rb_user, $rb_password, $data);
    }

    function CreateNewDiff($server, $reviewRequestId, $rb_user, $rb_password, $baseDir, $path) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1 ); 
        curl_setopt($ch, CURLOPT_USERPWD, $rb_user.":".$rb_password);  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $server."/api/review-requests/".$reviewRequestId."/diffs/");
        $post_array = array(
            "basedir"=> $baseDir,
            "path"=> $path
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_array);
        $request = curl_exec($ch);
        $result = json_decode($request, true);
        if ($result["stat"] == "ok") {
            $diffFileName = $result["diff"]["name"];
            $diffLink     = $result["diff"]["links"];
        } else {
            $msg = "Create new diff failure. Your review request is already created without any diff.";
            throw new CodeReviewException($msg);
        }
        $error = curl_error($ch);
        curl_close($ch);
    }

    /**
     * Check if the reviewer is an uGroup
     *
     * @param String  $username
     *
     * @return Boolean
     */
    function isUGroup($username){
        $project = ProjectManager::instance()->getProject($this->request->get('group_id'));
        $members = $project->getMembersUserNames();
        foreach ($members as $member) {
            if ($member['user_name'] == $username) {
                return true;
            }
        }
        return false;
    }
}
?>
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
     * @var PluginController
     */
    protected $controller;

    /**
     * @var HTTPRequest
     */
    protected $request;

    /**
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
        $status            = true;
        $invalid           = array();

        // @TODO: put the check of the user permission outside this method
        $plugin            = PluginManager::instance()->getPluginByName('codereview');
        $repositoryManager = new RepositoryManager($plugin, $this->request);

        $svnpath           = $repositoryManager->getSvnPath();
        $user              = UserManager::instance()->getCurrentUser();
        $username          = $user->getUserName();
        // @TODO: validate group_id
        $project           = ProjectManager::instance()->getProject($this->request->get('group_id'));
        $projectname       = $project->getPublicName();
        if (!svn_utils_check_write_access($username, $projectname, $svnpath)) {
            $status = false;
            // @TODO: i18n
            $msg    = "The user '".$username."' has not the right to create a review request.";
            $GLOBALS['Response']->addFeedBack('error', $msg);
            $this->controller->view = 'displayFrame';
        }

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
            $invalid[] = 'description';
        }

        $valid        = new Valid_String('codereview_target_people');
        $targetPeople = trim($this->request->get('codereview_target_people'));
        if ($this->request->valid($valid) && $targetPeople != '') {
            $params['target_people'] = $targetPeople;
        }
        /*$check        = true;
        if ($this->request->valid($valid) && $targetPeople != '') {
            $params['target_people'] = $targetPeople;
            $reviewers               = explode(",", $targetPeople);
            foreach ($reviewers as $username) {
                // @TODO: Check if we realy restrict that exactly to project members
                if ($this->isProjectMember($username)) {
                    $pluginInfo    = PluginManager::instance()->getPluginByName('codereview')->getPluginInfo();
                    $url           = $pluginInfo->getPropertyValueForName('reviewboard_site');
                    $rbuser        = $pluginInfo->getPropertyValueForName('admin_user');
                    $rbpass        = $pluginInfo->getPropertyValueForName('admin_pwd');
                    $rbusermanager = new RbUserManager();
                    $exist         = $rbusermanager->searchUser($url."/api/users/", false, $rbuser, $rbpass, null,$username);
                    // @TODO: Handle errors
                    if(!$exist) {
                        $user    = UserManager::instance()->getUserByUserName($username);
                        $userpwd = $user->getUserPw();
                        $curl    = new TuleapCurl();
                        $create  = $curl->execute($url."/api/users/", $username, $userpwd, null, false);
                        // @TODO: handle errors
                    }
                } else {
                    $check = false;
                    // @TODO: i18n
                    $msg   = "The user '".$username."' is not a member of your project.";
                    $GLOBALS['Response']->addFeedBack('error', $msg);
                    $this->controller->view = 'displayFrame';
                }
            }
        } else {
            $check = false;
        }
        if (!$check) {
            $status    = false;
            $invalid[] = 'target_people';
        }*/

        $valid    = new Valid_String('codereview_submit_as');
        $submitAs = trim($this->request->get('codereview_submit_as'));
        if ($this->request->valid($valid) && $submitAs != '') {
            $params['submit_as'] = $submitAs;
        } else {
            $status    = false;
            $invalid[] = 'submit_as';
        }


        $valid  = new Valid_String('codereview_rb_user');
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

        $valid    = new Valid_String('codereview_diff_path');
        $diffPath = trim($this->request->get('codereview_diff_path'));
        if ($this->request->valid($valid) && $diffPath != '') {
            $params['diff_path'] = $diffPath;
        } else {
            $status    = false;
            $invalid[] = 'diff_path';
        }

        return array('status' => $status, 'params' => $params, 'invalid' => $invalid);
    }

    /**
     * Validate request pubish review values
     *
     * @return Array
     */
    function validatePublishRequest() {
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

            $valid  = new Valid_String('codereview_rb_user');
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

            $valid     = new Valid_String('review_id');
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
     * Validate request patch values
     *
     * @return Array
     */
    function validateRequestPatch() {
            $status  = true;
            $invalid = array();

            $valid     = new Valid_String('first_revision');
            $frevision = trim($this->request->get('first_revision'));
            if ($this->request->valid($valid) && $frevision != '') {
                $params['frevision'] = $frevision;
            } else {
                $status    = false;
                $invalid[] = 'frevision';
            }

            $valid     = new Valid_String('second_revision');
            $srevision = trim($this->request->get('second_revision'));
            if ($this->request->valid($valid) && $srevision != '') {
                $params['srevision'] = $srevision;
            } else {
                $status    = false;
                $invalid[] = 'srevision';
            }

            $valid     = new Valid_String('target_directory');
            $directory = trim($this->request->get('target_directory'));
            if ($this->request->valid($valid) && $directory != '') {
                $params['Directory'] = $directory;
            } else {
                $status    = false;
                $invalid[] = 'Directory';
            }

            $valid      = new Valid_String('patch_path');
            $patch_path = trim($this->request->get('patch_path'));
            if ($this->request->valid($valid) && $patch_path != '') {
                $params['patch_path'] = $patch_path;
            } else {
                $status    = false;
                $invalid[] = 'patch_path';
            }
            /**********check the order**********/
            /*$f = exec ('sh /usr/share/codendi/plugins/codereview/bin/firstrev.sh '.$directory.' '.$patch_path);
            $l = exec ('sh /usr/share/codendi/plugins/codereview/bin/lastrev.sh '.$directory.' '.$patch_path);
            if ($frevision > $srevision){
                $status    = false;
                $invalid[] = 'srevision';
                $msg       = $GLOBALS['Language']->getText('plugin_codereview', 'request_patch_invalid_second_revision');
                $GLOBALS['Response']->addFeedBack('error', $msg);
                $this->controller->view = 'createPatchFile';
            }
            if (!(($frevision >= $f) && ($frevision <= $l))){
                $status = false;
                $msg    = $GLOBALS['Language']->getText('plugin_codereview', 'request_patch_first_revision_not_found', array($frevision));
                $GLOBALS['Response']->addFeedBack('error', $msg);
                $this->controller->view = 'createPatchFile';
            }

        if (!(($srevision >= $f) && ($srevision <= $l))){
            $status = false;
            $msg    = $GLOBALS['Language']->getText('plugin_codereview', 'request_patch_second_revision_not_found', array($srevision));
            $GLOBALS['Response']->addFeedBack('error', $msg);
            $this->controller->view = 'createPatchFile';
        }*/
            return array('status' => $status, 'params' => $params, 'invalid' => $invalid);
    }
    /**
     * Creates a patch file
     *
     * @return Void
     */
    function creatPatchFile() {
        //@TODO give meaningful var names
        $reviewRessources = $this->validateRequestPatch();
        //if ($reviewRessources['status']) {
            $frevision  = $reviewRessources['params']['frevision'];
            $srevision  = $reviewRessources['params']['srevision'];
            $directory  = $reviewRessources['params']['Directory'];
            $patch_path = $reviewRessources['params']['patch_path'];
        //}
        $diff = exec('sh /usr/share/codendi/plugins/codereview/bin/svndiff.sh '.$frevision.' '.$srevision.' '.$patch_path.' '.$directory);
        return $diff;
    }


    /**
     * Creates a new review request
     *
     * @return Void
     */
    function createReviewRequest() {
        $reviewRessources = $this->validateRequest();
        if ($reviewRessources['status']) {
            $server          = $reviewRessources['params']['server'];
            $repository      = $reviewRessources['params']['repository'];
            $rb_user         = $reviewRessources['params']['rb_user'];
            $rb_password     = $reviewRessources['params']['rb_password'];
            $reviewSubmitter = $reviewRessources['params']['submit_as'];
            $testing_done    = '';
            $summary         = $reviewRessources['params']['summary'];
            $target_people   = $reviewRessources['params']['target_people'];
            $description     = '';
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
     * Publish a  review request submitted by an other person
     *
     * @return Void
     */
    function publishReviewRequest() {
        $reviewRessources = $this->validatePublishRequest();
        if ($reviewRessources['status']) {
            $server      = $reviewRessources['params']['server'];
            $rb_user     = $reviewRessources['params']['rb_user'];
            $rb_password = $reviewRessources['params']['rb_password'];
            $review_id   = $reviewRessources['params']['review_id'];
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
     * @param String  $server          The path of reviewboard
     * @param String  $repository      The repository name
     * @param String  $rb_user         Reviewboard user name
     * @param String  $rb_password     Reviewboard user password
     * @param String  $reviewSubmitter The submitter name
     *
     * @return Void
     */
    function postEmptyReview($server, $repository, $rb_user, $rb_password, $reviewSubmitter = null) {
        $data = array('repository' => $repository,
                      'submit_as'  => $reviewSubmitter);
        $curl    = new TuleapCurl();
        $request = $curl->execute($server."/api/review-requests/", $rb_user, $rb_password, $data, false);
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
     * @param String  $server          The path of reviewboard
     * @param Integer $reviewRequestId Review request Id
     * @param String  $rb_user         Reviewboard user name
     * @param String  $rb_password     Reviewboard user password
     * @param String  $testing_done    The testing done text for the review request
     * @param String  $summary         The summuary of review request
     * @param String  $target_people   The reviewers
     * @param String  $description     The descreption of review request
     *
     * @return Void
     */
    function updateEmptyReview($server, $reviewRequestId, $rb_user, $rb_password, $testing_done , $summary, $target_people, $description) {
        $data = array('testing_done'   => $testing_done,
                      'target_people'  => $target_people,
                      'description'    => $description,
                      'summary'        => $summary);
        $curl    = new TuleapCurl();
        $request = $curl->execute($server."/api/review-requests/".$reviewRequestId."/draft/", $rb_user, $rb_password, $data, false);
    }

    /**
     * Publish a draft review request
     *
     * @param String  $server          The path of reviewboard
     * @param Integer $reviewRequestId Review request Id
     * @param String  $rb_user         Reviewboard user name
     * @param String  $rb_password     Reviewboard user password
     *
     * @return Void
     */
    function publishReviewRequestDraft($server, $reviewRequestId, $rb_user, $rb_password) {
        $data = array('public' => 'true');
        $curl    = new TuleapCurl();
        $request = $curl->execute($server."/api/review-requests/".$reviewRequestId."/draft/", $rb_user, $rb_password, $data, false);
    }

    /**
     * Create a new diff
     *
     * @param String  $server          The path of reviewboard
     * @param Integer $reviewRequestId Review request Id
     * @param String  $rb_user         Reviewboard user name
     * @param String  $rb_password     Reviewboard user password
     * @param String  $baseDir         Reposytory name
     * @param String  $path            Reposytory path
     *
     * @return Void
     */
    function CreateNewDiff($server, $reviewRequestId, $rb_user, $rb_password, $baseDir, $path) {
        $ch = curl_init();
        // When the certificate is self signed we must set the following option to get around it
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
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
        $result  = json_decode($request, true);
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
     * Check if the reviewer is a project member
     *
     * @param String  $username The user name
     *
     * @return Boolean
     */
    function isProjectMember($username) {
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
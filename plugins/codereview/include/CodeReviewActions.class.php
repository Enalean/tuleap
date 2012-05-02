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

        $valid     = new Valid_String('codereview_revision_range');
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

        $valid       = new Valid_String('codereview_target_people');
        $target_people = trim($this->request->get('codereview_target_people'));
        if ($this->request->valid($valid) && $target_people != '') {
            $params['target_people'] = $target_people;
        } else {
            $status    = false;
            $invalid[] = 'target_people';
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
            $rb_user         = 'codendiadm';
            $rb_password     = 'welcome0';
            $reviewSubmitter = 'codendiadm';
            //$testing_done    = $reviewRessources['params']['testing_done'];
            $testing_done    = 'testing_done';
            $summary         = $reviewRessources['params']['summary'];
            $target_people   = $reviewRessources['params']['target_people'];
            $description     = $reviewRessources['params']['description'];
            $reviewRequestId = $this->postEmptyReview($server, $repository, $rb_user, $rb_password, $reviewSubmitter);
            if (!empty($reviewRequestId)) {
                $this->updateEmptyReview($server, $reviewRequestId, $rb_user, $rb_password, $testing_done, $summary, $target_people, $description);
                $this->CreateNewDiff($server, $reviewRequestId, $rb_user, $rb_password);
                $this->publishReviewRequestDraft($server, $reviewRequestId, $rb_user, $rb_password);
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
    function postEmptyReview($server, $repository, $rb_user, $rb_password, $reviewSubmitter) {
        $data = array('repository' => $repository,
                      'submit_as'  => $reviewSubmitter);
        $curl    = new TuleapCurl();
        $request = $curl->execute($server."/api/review-requests/", false, $rb_user, $rb_password, $data);
        if ($request['return']) {
            if ($request['return']['stat'] == "ok") {
                return $request['return']['review_request']['id'];
            } else {
                return $request['return']['return'];
            }
        } else {
            return $request['status'];
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
    function updateEmptyReview($server, $reviewRequestId, $rb_user, $rb_password, $testing_done, $summary, $target_people, $description) {
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

    function CreateNewDiff($server, $reviewRequestId, $rb_user, $rb_password) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1 ); 
        curl_setopt($ch, CURLOPT_USERPWD, $rb_user.":".$rb_password);  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $server."/api/review-requests/".$reviewRequestId."/diffs/");
        $post_array = array(
            //The absolute path in the repository the diff was generated in.
            "basedir"=>"http://svn.codex-cc.codex.cro.st.com/svnroot/codex-cc/contrib/st/enhancement/114983_sttrunk_tracker_followup_html",
            //The path to the diff file.
            "path"=>"@/usr/share/codendi/plugins/codereview/diff.diff"
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_array);
        $request = curl_exec($ch);
        var_dump($request);
        $error = curl_error($ch);
        curl_close($ch);
    }
}
?>
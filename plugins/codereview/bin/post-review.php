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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * This script is called when a tuleap user wants to submit a new review request to a given review board server from codereview plugin.
 *
 * Usage: php post-review.php --repository="http://svn.codex-cc.codex.cro.st.com/svnroot/codex-cc" --rb_login="codendiadm" --rb_pass="welcome0" --submit_as="codendiadm" --testing_done="klo" --summary="This is my summary." --target_people="codendiadm" --description="This is my description."
 */

require_once dirname(__FILE__).'/../../../src/common/curl/TuleapCurl.class.php';

// Check script parameters
/*if ($argc != 8) {
    error("Wrong number of arguments");
}*/

$params = array();
foreach ($argv as $arg) {
    if (preg_match('/^--(.*)=(.*)$/', $arg, $matches)) {
        $params[$matches[1]] = $matches[2];
    }
}

$repository      = $params['repository'];
$rb_user         = $params['rb_login'];
$rb_password     = $params['rb_pass'];
$reviewSubmitter = $params['submit_as'];

$testing_done    = $params['testing_done'];
$summary         = $params['summary'];
$target_people   = $params['target_people'];
$description     = $params['description'];

$reviewRequestId = postEmptyReview($repository, $rb_user, $rb_password, $reviewSubmitter);
if (!empty($reviewRequestId)) {
    //Don't publish review request before adding the diff ressource, otherwise, its status is reset to draft
    updateEmptyReview($reviewRequestId, $rb_user, $rb_password, $testing_done, $summary, $target_people, $description);
    CreateNewDiff($reviewRequestId, $rb_user, $rb_password);
    publishReviewRequestDraft($reviewRequestId, $rb_user, $rb_password);
} else {
    error("Crate review request Failure");
}

/**
 * Pint an error then exit
 *
 * @param String $msg Error message to display
 *
 * @return void
 */
function error($msg) {
    echo "*** Error: $msg".PHP_EOL;
    exit(1);
}

/**
 * Creates a review request
 *
 * @param String  $repository
 * @param String  $rb_user
 * @param String $rb_password
 * @param String  $reviewSubmitter
 *
 * @return Integer
 */
function postEmptyReview($repository, $rb_user, $rb_password, $reviewSubmitter) {
    $data = array('repository' => $repository,
                  'submit_as'  => $reviewSubmitter);
    $curl    = new TuleapCurl();
    $request = $curl->execute('http://localhost/api/review-requests/', false, $rb_user, $rb_password, $data);
    if ($request['return']) {
        if ($request['return']['stat'] == "ok") {
            return $request['return']['review_request']['id'];
        } else {
            error("Something went wrong!");
        }
    }
}

/**
 * Update a given review request
 *
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
function updateEmptyReview($reviewRequestId, $rb_user, $rb_password, $testing_done, $summary, $target_people, $description) {
    $data = array('testing_done'   => $testing_done,
                  'target_people'  => $target_people,
                  'description'    => $description,
                  'summary'        => $summary);
    $curl    = new TuleapCurl();
    $request = $curl->execute("http://localhost/api/review-requests/".$reviewRequestId."/draft/", false, $rb_user, $rb_password, $data);
}

function publishReviewRequestDraft($reviewRequestId, $rb_user, $rb_password) {
    $data = array('public' => 'true');
    $curl    = new TuleapCurl();
    $request = $curl->execute("http://localhost/api/review-requests/".$reviewRequestId."/draft/", false, $rb_user, $rb_password, $data);
}

function CreateNewDiff($reviewRequestId, $rb_user, $rb_password) {
    //@TODO: remove hardocoded post vars and keep in mind that the data will be usually sent as part of a multipart/form-data mimetype
    // basedir: The absolute path in the repository the diff was generated in.
    // path:    The path to the diff file.
    $data = array('basedir' => 'http://svn.codex-cc.codex.cro.st.com/svnroot/codex-cc/contrib/st/enhancement/114983_sttrunk_tracker_followup_html',
                  'path'    => '@/usr/share/codendi/plugins/codereview/diff.diff');
    $curl    = new TuleapCurl();
    $request = $curl->execute("http://localhost/api/review-requests/".$reviewRequestId."/diffs/", false, $rb_user, $rb_password, $data);
}

?>
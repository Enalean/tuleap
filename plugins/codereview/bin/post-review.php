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
    updateEmptyReview($reviewRequestId, $rb_user, $rb_password, $testing_done, $summary, $target_people, $description);
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
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
    curl_setopt($ch, CURLOPT_USERPWD, $rb_user.":".$rb_password); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "repository=".$repository."&submit_as=".$reviewSubmitter);
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/reviews/api/review-requests/');
    $request = json_decode(curl_exec($ch), true);
    $error = curl_error($ch);
    curl_close($ch);
    if ($request) {
        if ($request['stat'] == "ok") {
            return $request['review_request']['id'];
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
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
    curl_setopt($ch, CURLOPT_USERPWD, "codendiadm:welcome0"); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "testing_done=".$testing_done."&target_people=".$target_people."&description=".$description."&summary=".$summary);
    curl_setopt($ch, CURLOPT_URL, "http://localhost/reviews/api/review-requests/".$reviewRequestId."/draft/");
    $request = json_decode(curl_exec($ch), true);
    $error = curl_error($ch);
    curl_close($ch);

}
?>
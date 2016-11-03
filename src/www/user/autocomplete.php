<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('pre.php');

//
// Input treatment
//
$vUserName = new Valid_String('name');
$vUserName->required();
if($request->valid($vUserName)) {
    $userName = $request->get('name');
} else {
    // Finish script, no output
    exit;
}

$codendiUserOnly = false;
$vCodendiUserOnly = new Valid_UInt('codendi_user_only');
if($request->valid($vCodendiUserOnly)) {
    if($request->get('codendi_user_only') == 1) {
        $codendiUserOnly = true;
    }
}

$json_format = false;
if ($request->get('return_type') === 'json_for_select_2') {
    $json_format = true;
}

// Number of user to display
$limit = 15;
$userList = array();

// Raise an evt
$pluginAnswered = false;
$has_more = false;
$evParams = array('searchToken'     => $userName,
                  'limit'           => $limit,
                  'codendiUserOnly' => $codendiUserOnly,
                  'json_format'     => $json_format,
                  'userList'        => &$userList,
                  'has_more'        => &$has_more,
                  'pluginAnswered'  => &$pluginAnswered);
$em = EventManager::instance();
$em->processEvent("ajax_search_user", $evParams);

// If no plugin answered, search in DB.
if(!$pluginAnswered) {
    // search user dao
    $userDao = new UserDao(CodendiDataAccess::instance());
    $dar = $userDao->searchUserNameLike($userName, $limit);
    while($dar->valid()) {
        $row = $dar->current();
        $userList[] = $row['realname']." (".$row['user_name'].")";
        $dar->next();
    }
    $has_more = $userDao->foundRows() > $limit;
}

//
// Display
//
if ($json_format) {
    $json_entries = array();
    foreach ($userList as $username) {
        $json_entries[] = array(
            'id'   => $username,
            'text' => $username
        );
    }

    $output       = array(
        'results' => $json_entries,
        'pagination' => array(
            'more' => $has_more
        )
    );

    echo json_encode($output);
} else {
    $purifier = Codendi_HTMLPurifier::instance();
    echo "<ul>\n";
    foreach ($userList as $user) {
        echo '<li>' . $purifier->purify($user) . '</li>';
    }
    echo "</ul>\n";
}

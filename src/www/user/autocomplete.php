<?php
/**
 * Copyright (c) Enalean, 2015 - 2017. All Rights Reserved.
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

use Tuleap\User\RequestFromAutocompleter;

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
        $userList[] = array(
            'display_name' => $row['realname']." (".$row['user_name'].")",
            'login'        => $row['user_name'],
            'has_avatar'   => $row['has_avatar']
        );
        $dar->next();
    }
    $has_more = $userDao->foundRows() > $limit;
}

//
// Display
//
if ($json_format) {
    $json_entries = array();
    $with_groups_of_user_in_project_id = $request->get('with-groups-of-user-in-project-id');
    if ($with_groups_of_user_in_project_id) {
        $ugroup_dao = new UGroupDao();
        $ugroups_dar = $ugroup_dao->searchUgroupsUserIsMemberInProject(
            $current_user->getId(),
            $with_groups_of_user_in_project_id
        );

        foreach ($ugroups_dar as $row) {
            if ($row['ugroup_id'] > 100
                || in_array($row['ugroup_id'], array(ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN))
            ) {
                $ugroup = new ProjectUGroup($row);
                $id     = $ugroup->getNormalizedName();
                $text   = $ugroup->getTranslatedName();

                if (mb_stripos($text, $userName) !== false
                    || mb_stripos($id, $userName) !== false
                ) {
                    $json_entries[] = array(
                        'type' => 'group',
                        'id'   => RequestFromAutocompleter::UGROUP_PREFIX . $id,
                        'text' => $text
                    );
                }
            }
        }
    }

    foreach ($userList as $user) {
        $json_entries[] = array(
            'type'       => 'user',
            'id'         => $user['display_name'],
            'text'       => $user['display_name'],
            'login'      => $user['login'],
            'has_avatar' => (bool)$user['has_avatar']
        );
    }

    $output = array(
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
        echo '<li>' . $purifier->purify($user['display_name']) . '</li>';
    }
    echo "</ul>\n";
}

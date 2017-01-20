<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
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

require_once('pre.php');

$expl_pathinfo = explode('/', $request->getFromServer('REQUEST_URI'));

//project name wasn't passed in the URL

if (!$expl_pathinfo[2]) {
	exit_error('Error','No User Name Provided');
}

$default_content_type = 'text/html';


//get the user_id based on the user_name in the URL
$user = UserManager::instance()->getUserByUserName($expl_pathinfo[2]);

$default_avatar_path = ForgeConfig::get('sys_urlroot'). '/themes/common/images/avatar_default.png';

if (! $user) {
    if (isset($expl_pathinfo[3]) && $expl_pathinfo[3] === 'avatar.png') {
        header('Content-type: image/png');
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        readfile($default_avatar_path);
        exit;
    }
    exit_error("Invalid User","That user does not exist.");
} else {
    // default HTML view

    $user_id = $user->getId();
    if (isset($expl_pathinfo[3]) && $expl_pathinfo[3] === 'avatar.png') {
        $path = $default_avatar_path;
        if ($user->hasAvatar()) {
            $avatar_path = ForgeConfig::get('sys_avatar_path', ForgeConfig::get('sys_data_dir') .'/user/avatar/');
            $path = $avatar_path .DIRECTORY_SEPARATOR.
                    substr($user_id, -2, 1) . DIRECTORY_SEPARATOR . substr($user_id, -1, 1) .
                    DIRECTORY_SEPARATOR . $user_id . DIRECTORY_SEPARATOR . 'avatar';
            if (! is_file($path)) {
                $path = $default_avatar_path;
            }
        }
        header('Content-type: image/png');
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        readfile($path);
        exit;
    } else {
        //now show the user page
        require_once('user_home.php');
    }
}

// Local Variables:
// mode: php
// End:
?>

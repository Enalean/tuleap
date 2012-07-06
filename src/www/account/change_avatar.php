<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
require_once('account.php');
require_once('common/include/CSRFSynchronizerToken.class.php');

$user_manager = UserManager::instance();
$user = $user_manager->getCurrentUser();
if ($user->isAnonymous()) {
    session_redirect("/account/");
} 
$csrf = new CSRFSynchronizerToken('/account/change_avatar.php');

if ($request->isPost() && isset($_FILES['avatar']['tmp_name']) && ( ! $_FILES['avatar']['error']) && Config::get('sys_enable_avatars')) {
    $csrf->check();
    $filename = $_FILES['avatar']['tmp_name'];
    if ($size = getimagesize($filename)) {
        $user_id = (string)$user->getId();
        $path = $GLOBALS['sys_avatar_path'] .DIRECTORY_SEPARATOR. 
                               substr($user_id, -2, 1) .DIRECTORY_SEPARATOR. 
                               substr($user_id, -1, 1) .DIRECTORY_SEPARATOR.
                               $user_id .DIRECTORY_SEPARATOR.
                               'avatar';
        $avatar_witdh = 50;
        $avatar_height = 50;
        $thumbnail_width  = $size[0];
        $thumbnail_height = $size[1];
        if ($thumbnail_width > $avatar_witdh || $thumbnail_height > $avatar_height) { 
            if ($thumbnail_width / $avatar_witdh < $thumbnail_height / $avatar_height) {
                //keep the height
                $thumbnail_width  = $thumbnail_width * $avatar_height / $thumbnail_height;
                $thumbnail_height = $avatar_height;
            } else {
                //keep the width
                $thumbnail_height = $thumbnail_height * $avatar_witdh / $thumbnail_width;
                $thumbnail_width  = $avatar_witdh;
            }
        }
        $source = null;
        switch ($size[2]) {
            case IMAGETYPE_GIF:
                $source      = imagecreatefromgif($filename);
                //imagepalettecopy($destination, $source);
                //$store       = 'imagegif';
                break;
            case IMAGETYPE_JPEG:
                $source      = imagecreatefromjpeg($filename);
                //$store       = 'imagejpeg';
                break;
            case IMAGETYPE_PNG:
                $source      = imagecreatefrompng($filename);
                //$store       = 'imagepng';
                break;
        }
        if ($source) {
            if ( ! is_file($path) ) {
                mkdir(dirname($path), 0700, true);
            }
            $destination = imagecreatetruecolor((int)$thumbnail_width, (int)$thumbnail_height);
            imagecopyresized($destination, $source, 0, 0, 0, 0, (int)$thumbnail_width, (int)$thumbnail_height, $size[0], $size[1]);
            imagepng($destination, $path);
            imagedestroy($source);
            imagedestroy($destination);
            $user->sethasAvatar();
            $user_manager->updateDb($user);
        }
    }
}

$title = $Language->getText('account_change_realname', 'title');
$HTML->header(array('title' => $title));

echo '<h2>'. $title .'</h2>';
echo '<form action="/account/change_avatar.php" method="POST" enctype="multipart/form-data">';
echo '<input type="hidden" name="MAX_FILE_SIZE" value="300000" />';
echo $csrf->fetchHTMLInput();
echo $user->fetchHtmlAvatar();
echo '<input type="file" name="avatar" />';
echo '<p><input type="submit" value="'. $Language->getText('global', 'btn_update') .'" /></p>';
echo '</form>';
$HTML->footer(array());

?>

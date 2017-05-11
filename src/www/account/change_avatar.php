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
require_once('common/include/lib/Upload.class.php');

$user_manager = UserManager::instance();
$user = $user_manager->getCurrentUser();
if ($user->isAnonymous()) {
    session_redirect("/account/");
}
$csrf = new CSRFSynchronizerToken('/account/change_avatar.php');

if (isset($_FILES['avatar'])) {
    $handle = new Upload($_FILES['avatar']);
    list($width, $height) = getimagesize($_FILES['avatar']['tmp_name']);
    $max_size = 100;
    if ($width > $max_size || $height > $max_size) {
        $handle->image_resize     = true;
        $handle->image_ratio_crop = 'L';
        $handle->image_y          = $max_size;
        $handle->image_x          = $max_size;
    }
    $handle->image_background_color = '#FFFFFF';
    $handle->image_convert          = 'png';
    $handle->file_new_name_body     = 'avatar';
    $handle->file_safe_name         = false;
    $handle->file_force_extension   = false;
    $handle->file_new_name_ext      = '';
    $handle->allowed                = 'image/*';
    $handle->file_overwrite         = true;

    if ($handle->uploaded && ForgeConfig::get('sys_enable_avatars', true)) {
        $csrf->check();

        $user_id     = (string)$user->getId();
        $avatar_path = ForgeConfig::get('sys_avatar_path', ForgeConfig::get('sys_data_dir') .'/user/avatar/');
        $path        =  "$avatar_path/". substr($user_id, -2, 1) .'/'. substr($user_id, -1, 1) ."/$user_id";
        $handle->process($path);
        if ($handle->processed) {
            $user->sethasAvatar();
            $user_manager->updateDb($user);
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('account_change_avatar', 'success'));
            $GLOBALS['Response']->redirect('/account/');
        } else {
            $GLOBALS['Response']->addFeedback('error', $handle->error);
        }
    }
}

$title = $Language->getText('account_change_avatar', 'title');
$HTML->header(array('title' => $title));

echo '<h2>'. $title .'</h2>';
echo $user->fetchHtmlAvatar();
echo '<form action="/account/change_avatar.php" method="POST" enctype="multipart/form-data">';
echo '<input type="hidden" name="MAX_FILE_SIZE" value="300000" />';
echo $csrf->fetchHTMLInput();
echo '<input type="file" name="avatar" /><br>';
echo '<input class="btn btn-primary" type="submit" value="'. $Language->getText('global', 'btn_update') .'" /></p>';
echo '</form>';
$HTML->footer(array());

?>

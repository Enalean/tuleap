<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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

$user_manager = UserManager::instance();
$user = $user_manager->getCurrentUser();
if ($user->isAnonymous()) {
    session_redirect("/account/");
}
$csrf = new CSRFSynchronizerToken('/account/index.php');
$csrf->check();

$request = HTTPRequest::instance();

if ($request->get('use-default-avatar')) {
    $user->setHasAvatar(false);
    if ($user_manager->updateDb($user)) {
        $level    = Feedback::INFO;
        $feedback = $GLOBALS['Language']->getText('account_change_avatar', 'success');
    } else {
        $level    = Feedback::ERROR;
        $feedback = $GLOBALS['Language']->getText('account_change_avatar', 'error');
    }
    $GLOBALS['Response']->addFeedback($level, $feedback);
} elseif (isset($_FILES['avatar'])) {
    if ($_FILES['avatar']['error']) {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            _('An error occured with your upload. Please try again or choose another image.')
        );
    } else {
        $handle = new upload($_FILES['avatar']);
        list($width, $height) = getimagesize($_FILES['avatar']['tmp_name']);
        $max_size = 100;
        //always resize in order to generate a background color
        $handle->image_resize = true;
        $handle->image_y = $height;
        $handle->image_x = $width;
        if ($width > $max_size || $height > $max_size) {
            $handle->image_ratio_crop = true;
            $handle->image_y = $max_size;
            $handle->image_x = $max_size;
        }
        $handle->image_background_color = '#FFFFFF';
        $handle->image_convert = 'png';
        $handle->png_compression = 9;
        $handle->file_new_name_body = 'avatar';
        $handle->file_safe_name = false;
        $handle->file_force_extension = false;
        $handle->file_new_name_ext = '';
        $handle->allowed = 'image/*';
        $handle->file_overwrite = true;

        if ($handle->uploaded && ForgeConfig::get('sys_enable_avatars', true)) {
            $user_id = (string)$user->getId();
            $avatar_path = ForgeConfig::get('sys_avatar_path', ForgeConfig::get('sys_data_dir') . '/user/avatar/');
            $path = "$avatar_path/" . substr($user_id, -2, 1) . '/' . substr($user_id, -1, 1) . "/$user_id";
            $handle->process($path);
            if ($handle->processed) {
                $user->sethasAvatar();
                $user_manager->updateDb($user);
                $GLOBALS['Response']->addFeedback('info',
                    $GLOBALS['Language']->getText('account_change_avatar', 'success'));
            } else {
                $GLOBALS['Response']->addFeedback('error', $handle->error);
            }
        }
    }
}

$GLOBALS['Response']->redirect('/account/');

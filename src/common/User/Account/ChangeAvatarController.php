<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\User\Account;

use CSRFSynchronizerToken;
use Feedback;
use Gumlet\ImageResize;
use Gumlet\ImageResizeException;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class ChangeAvatarController implements DispatchableWithRequest
{
    const AVATAR_MAX_SIZE = 100;

    /**
     * @var \UserManager
     */
    private $user_manager;

    public function __construct(\UserManager $user_manager)
    {
        $this->user_manager = $user_manager;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param HTTPRequest $request
     * @param BaseLayout $layout
     * @param array $variables
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $user = $request->getCurrentUser();
        if ($user->isAnonymous()) {
            $layout->redirect('/');
        }

        $csrf = new CSRFSynchronizerToken('/account/index.php');
        $csrf->check();

        if ($request->get('use-default-avatar')) {
            $user->setHasAvatar(false);
            if ($this->user_manager->updateDb($user)) {
                $layout->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('account_change_avatar', 'success'));
            } else {
                $layout->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('account_change_avatar', 'error'));
            }
        } elseif (isset($_FILES['avatar'])) {
            if ($_FILES['avatar']['error']) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    _('An error occured with your upload. Please try again or choose another image.')
                );
                $layout->redirect('/account');
            }
            try {
                $image = new ImageResize($_FILES['avatar']['tmp_name']);
                $image->quality_truecolor = false;
                $image->crop(self::AVATAR_MAX_SIZE, self::AVATAR_MAX_SIZE);
                // Replace transparent background by white color to avoid strange rendering in Tuleap.
                $image->addFilter(function ($imageDesc) {
                    $x = imagesx($imageDesc);
                    $y = imagesy($imageDesc);
                    $dst_im = imagecreatetruecolor($x, $y);
                    $background_color = imagecolorallocate($dst_im, 255, 255, 255);
                    imagefilledrectangle($dst_im, 0, 0, $x, $y, $background_color);

                    imagecopy($dst_im, $imageDesc, 0, 0, 0, 0, $x, $y);

                    imagecopy($imageDesc, $dst_im, 0, 0, 0, 0, $x, $y);

                    imagedestroy($dst_im);
                });
                $image->save($user->getAvatarFilePath(), IMAGETYPE_PNG, 9, 0640);
                $user->setHasAvatar();
                $this->user_manager->updateDb($user);
                $layout->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('account_change_avatar', 'success'));
            } catch (ImageResizeException $exception) {
                $layout->addFeedback(Feedback::ERROR, $exception->getMessage());
            }
        }
        $layout->redirect('/account');
    }
}

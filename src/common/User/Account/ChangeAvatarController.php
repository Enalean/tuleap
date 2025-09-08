<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
use Gumlet\ImageResizeException;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\User\Avatar\AvatarHashStorageDeletor;

class ChangeAvatarController implements DispatchableWithRequest
{
    /**
     * @var UserAvatarSaver
     */
    private $user_avatar_saver;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf;
    /**
     * @var \UserManager
     */
    private $user_manager;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        UserAvatarSaver $user_avatar_saver,
        \UserManager $user_manager,
        private readonly AvatarHashStorageDeletor $user_avatar_hash_storage,
    ) {
        $this->csrf              = $csrf;
        $this->user_avatar_saver = $user_avatar_saver;
        $this->user_manager      = $user_manager;
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $user = $request->getCurrentUser();
        if ($user->isAnonymous()) {
            $layout->redirect('/');
        }

        $this->csrf->check();

        if ($request->get('use-default-avatar')) {
            $avatar_path = $user->getAvatarFilePath();
            if (is_file($avatar_path)) {
                unlink($avatar_path);
            }
            $this->user_avatar_hash_storage->delete($user);
            $user->setHasCustomAvatar(false);
            $this->user_manager->updateDb($user);
            $layout->addFeedback(Feedback::INFO, _('Avatar changed!'));
        } elseif (isset($_FILES['avatar'])) {
            if ($_FILES['avatar']['error']) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    _('An error occurred with your upload. Please try again or choose another image.')
                );
                $layout->redirect(DisplayAccountInformationController::URL);
            }
            try {
                $this->user_avatar_saver->saveAvatar($user, $_FILES['avatar']['tmp_name']);

                $layout->addFeedback(Feedback::INFO, _('Avatar changed!'));
            } catch (ImageResizeException $exception) {
                $layout->addFeedback(Feedback::ERROR, $exception->getMessage());
            }
        }
        $layout->redirect(DisplayAccountInformationController::URL);
    }
}

<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\User\Account;

use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use UserManager;

class UpdateAppearancePreferences implements DispatchableWithRequest
{
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var \BaseLanguage
     */
    private $language;

    public function __construct(CSRFSynchronizerToken $csrf_token, UserManager $user_manager, \BaseLanguage $language)
    {
        $this->csrf_token   = $csrf_token;
        $this->user_manager = $user_manager;
        $this->language     = $language;
    }

    /**
     * @inheritDoc
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $user = $request->getCurrentUser();
        if ($user->isAnonymous()) {
            throw new ForbiddenException();
        }

        $this->csrf_token->check(DisplayAppearanceController::URL);

        $needs_update = $this->setNewLanguage($request, $layout, $user);
        if (! $needs_update) {
            $layout->addFeedback(Feedback::INFO, _('Nothing changed'));
        } elseif ($this->user_manager->updateDb($user)) {
            $layout->addFeedback(Feedback::INFO, _('User preferences successfully updated'));
        } else {
            $layout->addFeedback(Feedback::ERROR, _('Unable to update user preferences'));
        }

        $layout->redirect(DisplayAppearanceController::URL);
    }

    private function setNewLanguage(HTTPRequest $request, BaseLayout $layout, \PFUser $user): bool
    {
        $language_id = (string) $request->get('language_id');
        if (! $language_id) {
            return false;
        }

        if (! $this->language->isLanguageSupported($language_id)) {
            $layout->addFeedback(Feedback::ERROR, _('The submitted language is not supported.'));
            return false;
        }

        if ($language_id === $user->getLanguageID()) {
            return false;
        }

        $user->setLanguageID($language_id);
        return true;
    }
}

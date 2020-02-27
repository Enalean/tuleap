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

final class UpdateSessionPreferencesController implements DispatchableWithRequest
{
    public const URL = '/account/security/session';

    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var \UserManager
     */
    private $user_manager;

    public function __construct(CSRFSynchronizerToken $csrf_token, \UserManager $user_manager)
    {
        $this->csrf_token = $csrf_token;
        $this->user_manager = $user_manager;
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

        $this->csrf_token->check(DisplaySecurityController::URL);

        if ($request->get('account-remember-me') === '1') {
            $user->setStickyLogin(1);
        } else {
            $user->setStickyLogin(0);
        }

        if (! $this->user_manager->updateDb($user)) {
            $layout->addFeedback(Feedback::ERROR, _('Unable to update user preferences'));
        } else {
            $layout->addFeedback(Feedback::INFO, _('User preferences successfully updated'));
            $layout->addFeedback(Feedback::WARN, _('You need to logout & login again for this to be taken into account'));
        }

        $layout->redirect(DisplaySecurityController::URL);
    }
}

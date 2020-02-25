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

class UpdateExperimentalPreferences implements DispatchableWithRequest
{
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(CSRFSynchronizerToken $csrf_token)
    {
        $this->csrf_token = $csrf_token;
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

        $this->csrf_token->check(DisplayExperimentalController::URL);

        $user_wants_lab_features = $request->get('lab_features') === '1';
        if ($user_wants_lab_features === $user->useLabFeatures()) {
            $layout->addFeedback(Feedback::INFO, _('Nothing changed'));
        } elseif ($user->setLabFeatures($user_wants_lab_features)) {
            $layout->addFeedback(Feedback::INFO, _('User preferences successfully updated'));
        } else {
            $layout->addFeedback(Feedback::ERROR, _('Unable to update user preferences'));
        }

        $layout->redirect(DisplayExperimentalController::URL);
    }
}

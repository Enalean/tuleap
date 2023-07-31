<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\WebAuthn\Controllers;

use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\CSRFSynchronizerTokenInterface;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\User\ProvideCurrentUser;

final class PostSwitchPasswordlessAuthenticationController implements DispatchableWithRequest
{
    public const  URL          = '/webauthn/switch-passwordless';
    private const REDIRECT_URL = '/plugins/webauthn/account';

    public function __construct(
        private readonly ProvideCurrentUser $user_manager,
        private readonly CSRFSynchronizerTokenInterface $synchronizer_token,
    ) {
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $current_user = $this->user_manager->getCurrentUser();
        if ($current_user->isAnonymous()) {
            throw new ForbiddenException();
        }

        $this->synchronizer_token->check(
            self::REDIRECT_URL,
            $request
        );

        $switch = $request->get('passwordless-only-toggle');
        if (! is_string($switch)) {
            $layout->addFeedback(\Feedback::ERROR, _('"passwordless-only-toggle" field is missing from the form'));
            $layout->redirect(self::REDIRECT_URL);
        }

        $layout->addFeedback(\Feedback::ERROR, sprintf(_('Not implemented: switch passwordless only to %s'), $switch));

        $layout->redirect(self::REDIRECT_URL);
    }
}

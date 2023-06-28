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

namespace Tuleap\User\SSHKey;

use CSRFSynchronizerToken;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\NeverThrow\Result;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\User\Account\DisplayKeysTokensController;
use Tuleap\WebAuthn\Authentication\WebAuthnAuthentication;
use UserManager;

final class SSHKeyCreateController implements DispatchableWithRequest
{
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(
        CSRFSynchronizerToken $csrf_token,
        UserManager $user_manager,
        private readonly WebAuthnAuthentication $web_authn_authentication,
    ) {
        $this->user_manager = $user_manager;
        $this->csrf_token   = $csrf_token;
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

        $this->csrf_token->check(DisplayKeysTokensController::URL);

        $result = $this->web_authn_authentication->checkKeyResult(
            $user,
            $request->get('webauthn_result') ?: ''
        );
        if (Result::isErr($result)) {
            $layout->addFeedback(\Feedback::ERROR, (string) $result->error);
            $layout->redirect(DisplayKeysTokensController::URL);
        }

        $this->user_manager->addSSHKeys($user, $request->get('ssh-key'));

        $layout->redirect(DisplayKeysTokensController::URL);
    }
}

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

use Feedback;
use HTTPRequest;
use TemplateRenderer;
use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\NeverThrow\Result;
use Tuleap\Request\CSRFSynchronizerTokenInterface;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\WebAuthn\Authentication\WebAuthnAuthentication;

final class LoginController implements DispatchableWithRequestNoAuthz, DispatchableWithBurningParrot
{
    public const URL = '/plugins/webauthn/login';

    public function __construct(
        private readonly TemplateRenderer $renderer,
        private readonly IncludeViteAssets $assets,
        private readonly CSRFSynchronizerTokenInterface $synchronizer_token,
        private readonly \UserManager $user_manager,
        private readonly WebAuthnAuthentication $authentication,
    ) {
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $current_user = $request->getCurrentUser();
        if (! $current_user->isAnonymous()) {
            $layout->redirect($request->get('return_to') ?? '');
        }

        if ($request->isPost()) {
            $this->handlePost($request, $layout);
        }

        $layout->addJavascriptAsset(new JavascriptViteAsset($this->assets, 'src/login.ts'));

        $layout->header(
            HeaderConfigurationBuilder::get(dgettext('tuleap-webauthn', 'Passwordless connection'))
                ->withBodyClass(['login'])
                ->build()
        );
        $this->renderer->renderToPage('login', [
            'csrf_token' => CSRFSynchronizerTokenPresenter::fromToken(new \CSRFSynchronizerToken(self::URL)),
        ]);
        $layout->footer([]);
    }

    private function handlePost(HTTPRequest $request, BaseLayout $layout): void
    {
        $this->synchronizer_token->check(self::URL, $request);

        $username   = $request->get('username');
        $key_result = $request->get('webauthn_result');
        if (! is_string($username) || ! is_string($key_result)) {
            $layout->addFeedback(Feedback::ERROR, dgettext('tuleap-webauthn', 'Submitted form is not valid'));
            return;
        }

        $user = $this->user_manager->getUserByUserName($username);
        if ($user === null) {
            $layout->addFeedback(Feedback::ERROR, sprintf(dgettext('tuleap-webauthn', 'User %s not found'), $username));
            return;
        }

        $authentication_result = $this->authentication->checkKeyResult($user, $key_result);
        if (Result::isOk($authentication_result)) {
            $this->user_manager->openSessionForUser($user);
            $layout->redirect($request->get('return_to') ?? '');
        } else {
            $layout->addFeedback(Feedback::ERROR, dgettext('tuleap-webauthn', 'Failed to authenticate you'));
        }
    }
}

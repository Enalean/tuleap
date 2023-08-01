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
use Psr\EventDispatcher\EventDispatcherInterface;
use TemplateRenderer;
use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\FooterConfiguration;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\User\Account\AccountTabPresenterCollection;
use Tuleap\User\Account\UserPreferencesHeader;
use Tuleap\User\RetrievePasswordlessOnlyState;
use Tuleap\WebAuthn\Source\AuthenticatorPresenter;
use Tuleap\WebAuthn\Source\WebAuthnCredentialSource;
use Tuleap\WebAuthn\Source\WebAuthnCredentialSourceDao;

final class AccountController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public const URL = '/plugins/webauthn/account';

    public function __construct(
        private readonly TemplateRenderer $renderer,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly IncludeViteAssets $vite_assets,
        private readonly WebAuthnCredentialSourceDao $source_dao,
        private readonly RetrievePasswordlessOnlyState $passwordless_only_state,
    ) {
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $user = $request->getCurrentUser();
        if ($user->isAnonymous()) {
            throw new ForbiddenException();
        }

        $tabs = $this->dispatcher->dispatch(new AccountTabPresenterCollection($user, self::URL));

        $sources = $this->source_dao->getAllByUserId((int) $user->getId());

        $presenter = new AccountPresenter(
            $tabs,
            array_map(
                fn(WebAuthnCredentialSource $source) => new AuthenticatorPresenter($source, $user),
                $sources
            ),
            $this->passwordless_only_state->isPasswordlessOnly($user),
            CSRFSynchronizerTokenPresenter::fromToken(new \CSRFSynchronizerToken(PostRegistrationController::URL)),
            CSRFSynchronizerTokenPresenter::fromToken(new \CSRFSynchronizerToken(DeleteSourceController::URL)),
            CSRFSynchronizerTokenPresenter::fromToken(new \CSRFSynchronizerToken(PostSwitchPasswordlessAuthenticationController::URL))
        );

        $layout->addJavascriptAsset(new JavascriptViteAsset($this->vite_assets, 'src/account.ts'));

        (new UserPreferencesHeader())->display(dgettext('tuleap-webauthn', 'Passkeys'), $layout);
        $this->renderer->renderToPage('account', $presenter);
        $layout->footer(FooterConfiguration::withoutContent());
    }
}

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
use EventManager;
use HTTPRequest;
use Psr\EventDispatcher\EventDispatcherInterface;
use TemplateRenderer;
use TemplateRendererFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Password\PasswordSanityChecker;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\User\Password\PasswordValidatorPresenter;
use UserManager;

final class DisplaySecurityController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public const URL = '/account/security';
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var TemplateRenderer
     */
    private $renderer;
    /**
     * @var PasswordSanityChecker
     */
    private $password_sanity_checker;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        TemplateRendererFactory $renderer_factory,
        CSRFSynchronizerToken $csrf_token,
        PasswordSanityChecker $password_sanity_checker,
        UserManager $user_manager,
    ) {
        $this->dispatcher              = $dispatcher;
        $this->renderer                = $renderer_factory->getRenderer(__DIR__ . '/templates');
        $this->csrf_token              = $csrf_token;
        $this->password_sanity_checker = $password_sanity_checker;
        $this->user_manager            = $user_manager;
    }

    public static function buildSelf(): self
    {
        return new self(
            EventManager::instance(),
            TemplateRendererFactory::build(),
            self::getCSRFToken(),
            PasswordSanityChecker::build(),
            UserManager::instance(),
        );
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $user = $request->getCurrentUser();
        if ($user->isAnonymous()) {
            throw new ForbiddenException();
        }

        $layout->addJavascriptAsset(
            new JavascriptAsset(
                new IncludeAssets(__DIR__ . '/../../../scripts/account/frontend-assets', '/assets/core/account'),
                'security.js'
            )
        );

        $tabs = $this->dispatcher->dispatch(new AccountTabPresenterCollection($user, self::URL));

        $password_pre_update = $this->dispatcher->dispatch(new PasswordPreUpdateEvent($user));

        $purifier             = \Codendi_HTMLPurifier::instance();
        $passwords_validators = [];
        foreach ($this->password_sanity_checker->getValidators() as $key => $validator) {
            $passwords_validators[] = new PasswordValidatorPresenter(
                'password_validator_msg_' . $purifier->purify($key),
                $purifier->purify($key, CODENDI_PURIFIER_JS_QUOTE),
                $purifier->purify($validator->description())
            );
        }

        (new UserPreferencesHeader())->display(_('Security'), $layout);
        $this->renderer->renderToPage(
            'security',
            new SecurityPresenter(
                $tabs,
                $this->csrf_token,
                $user,
                $password_pre_update,
                $passwords_validators,
                $this->user_manager->getUserAccessInfo($user),
            )
        );
        $layout->footer([]);
    }

    public static function getCSRFToken(): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(self::URL);
    }
}

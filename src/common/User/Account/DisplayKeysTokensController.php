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
use HTTPRequest;
use Psr\EventDispatcher\EventDispatcherInterface;
use TemplateRenderer;
use TemplateRendererFactory;
use Tuleap\Cryptography\Exception\InvalidCiphertextException;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

final class DisplayKeysTokensController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public const URL = '/account/keys-tokens';

    /**
     * @var TemplateRenderer
     */
    private $renderer;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var AccessKeyPresenterBuilder
     */
    private $access_key_presenter_builder;
    /**
     * @var SVNTokensPresenterBuilder
     */
    private $svn_tokens_presenter_builder;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        TemplateRendererFactory $renderer_factory,
        CSRFSynchronizerToken $csrf_token,
        AccessKeyPresenterBuilder $access_key_presenter_builder,
        SVNTokensPresenterBuilder $svn_tokens_presenter_builder,
    ) {
        $this->dispatcher                   = $dispatcher;
        $this->renderer                     = $renderer_factory->getRenderer(__DIR__ . '/templates');
        $this->csrf_token                   = $csrf_token;
        $this->access_key_presenter_builder = $access_key_presenter_builder;
        $this->svn_tokens_presenter_builder = $svn_tokens_presenter_builder;
    }

    /**
     * @inheritDoc
     * @throws InvalidCiphertextException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $user = $request->getCurrentUser();
        if ($user->isAnonymous()) {
            throw new ForbiddenException();
        }
        assert($_SESSION !== null);

        $layout->addJavascriptAsset(
            new JavascriptAsset(
                new IncludeAssets(__DIR__ . '/../../../scripts/account/frontend-assets', '/assets/core/account'),
                'keys-tokens.js'
            )
        );

        $tabs = $this->dispatcher->dispatch(new AccountTabPresenterCollection($user, self::URL));

        (new UserPreferencesHeader())->display(_('Keys & Tokens'), $layout, ['user-preferences-frame-wide']);
        $this->renderer->renderToPage(
            'keys-tokens',
            new KeysTokensPresenter(
                $this->csrf_token,
                $tabs,
                SSHKeysPresenter::fromUser($user),
                $this->access_key_presenter_builder->getForUser($user, $_SESSION),
                $this->svn_tokens_presenter_builder->getForUser($user, $_SESSION),
            )
        );
        $layout->footer([]);
    }

    public static function getCSRFToken(): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(self::URL);
    }
}

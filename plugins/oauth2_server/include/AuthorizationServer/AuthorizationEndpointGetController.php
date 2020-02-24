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
 */

declare(strict_types=1);

namespace Tuleap\OAuth2Server\AuthorizationServer;

use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\IncludeAssets;
use Tuleap\OAuth2Server\App\AppDao;
use Tuleap\OAuth2Server\App\AppFactory;
use Tuleap\OAuth2Server\App\ClientIdentifier;
use Tuleap\OAuth2Server\App\InvalidClientIdentifierKey;
use Tuleap\OAuth2Server\App\OAuth2AppNotFoundException;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

final class AuthorizationEndpointGetController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /**
     * @var \TemplateRenderer
     */
    private $renderer;
    /**
     * @var AppFactory
     */
    private $app_factory;
    /**
     * @var \URLRedirect
     */
    private $redirect;

    public function __construct(
        \TemplateRendererFactory $renderer_factory,
        AppFactory $app_factory,
        \URLRedirect $redirect
    ) {
        $this->renderer    = $renderer_factory->getRenderer(__DIR__ . '/../../templates');
        $this->app_factory = $app_factory;
        $this->redirect    = $redirect;
    }

    public static function buildSelf(): self
    {
        return new self(
            \TemplateRendererFactory::build(),
            new AppFactory(new AppDao(), \ProjectManager::instance()),
            new \URLRedirect(\EventManager::instance())
        );
    }

    /**
     * @inheritDoc
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $user = $request->getCurrentUser();
        if ($user->isAnonymous()) {
            $this->redirect->redirectToLogin();
            return;
        }
        $client_id = $request->get('client_id');
        if (! is_string($client_id)) {
            throw new ForbiddenException();
        }
        try {
            $client_identifier = ClientIdentifier::fromClientId($client_id);
            $client_app        = $this->app_factory->getAppMatchingClientId($client_identifier);
        } catch (InvalidClientIdentifierKey | OAuth2AppNotFoundException $exception) {
            throw new ForbiddenException();
        }

        $presenter = new AuthorizationFormPresenter($client_app);
        $layout->addCssAsset(
            new CssAsset(
                new IncludeAssets(__DIR__ . '/../../../../src/www/assets/oauth2_server', '/assets/oauth2_server'),
                'authorization-form'
            )
        );
        $layout->header(
            [
                'title'        => dgettext('tuleap-oauth2_server', 'Authorize application'),
                'main_classes' => ['tlp-framed']
            ]
        );
        $this->renderer->renderToPage('authorization-form', $presenter);
        $layout->footer([]);
    }
}

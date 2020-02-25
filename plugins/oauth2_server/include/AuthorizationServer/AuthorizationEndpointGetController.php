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

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\IncludeAssets;
use Tuleap\OAuth2Server\App\AppDao;
use Tuleap\OAuth2Server\App\AppFactory;
use Tuleap\OAuth2Server\App\ClientIdentifier;
use Tuleap\OAuth2Server\App\InvalidClientIdentifierKey;
use Tuleap\OAuth2Server\App\OAuth2AppNotFoundException;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\ForbiddenException;

final class AuthorizationEndpointGetController extends DispatchablePSR15Compatible implements DispatchableWithBurningParrot
{
    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var StreamFactoryInterface
     */
    private $stream_factory;
    /**
     * @var \TemplateRenderer
     */
    private $renderer;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var AppFactory
     */
    private $app_factory;
    /**
     * @var \URLRedirect
     */
    private $redirect;

    public function __construct(
        ResponseFactoryInterface $response_factory,
        StreamFactoryInterface $stream_factory,
        \TemplateRendererFactory $renderer_factory,
        \UserManager $user_manager,
        AppFactory $app_factory,
        \URLRedirect $redirect,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->response_factory = $response_factory;
        $this->stream_factory   = $stream_factory;
        $this->renderer         = $renderer_factory->getRenderer(__DIR__ . '/../../templates');
        $this->user_manager     = $user_manager;
        $this->app_factory      = $app_factory;
        $this->redirect         = $redirect;
    }

    public static function buildSelf(
        ResponseFactoryInterface $response_factory,
        StreamFactoryInterface $stream_factory,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack
    ): self {
        return new self(
            $response_factory,
            $stream_factory,
            \TemplateRendererFactory::build(),
            \UserManager::instance(),
            new AppFactory(new AppDao(), \ProjectManager::instance()),
            new \URLRedirect(\EventManager::instance()),
            $emitter,
            ...$middleware_stack
        );
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $user = $this->user_manager->getCurrentUser();
        if ($user->isAnonymous()) {
            return $this->response_factory->createResponse(302)
                ->withHeader('Location', $this->redirect->buildReturnToLogin($request->getServerParams()));
        }

        $query_params = $request->getQueryParams();
        $client_id    = (string) ($query_params['client_id'] ?? '');
        try {
            $client_identifier = ClientIdentifier::fromClientId($client_id);
            $client_app        = $this->app_factory->getAppMatchingClientId($client_identifier);
        } catch (InvalidClientIdentifierKey | OAuth2AppNotFoundException $exception) {
            throw new ForbiddenException();
        }

        $layout = $request->getAttribute(BaseLayout::class);
        assert($layout instanceof BaseLayout);

        $presenter = new AuthorizationFormPresenter($client_app);
        $layout->addCssAsset(
            new CssAsset(
                new IncludeAssets(__DIR__ . '/../../../../src/www/assets/oauth2_server', '/assets/oauth2_server'),
                'authorization-form'
            )
        );

        ob_start();
        $layout->header(
            [
                'title'        => dgettext('tuleap-oauth2_server', 'Authorize application'),
                'main_classes' => ['tlp-framed']
            ]
        );
        $this->renderer->renderToPage('authorization-form', $presenter);
        $layout->footer([]);

        return $this->response_factory->createResponse()->withBody(
            $this->stream_factory->createStream((string) ob_get_clean())
        );
    }
}

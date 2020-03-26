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

namespace Tuleap\OAuth2Server\User\Account;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\ForbiddenException;
use Tuleap\User\Account\UserPreferencesHeader;

final class AccountAppsController extends DispatchablePSR15Compatible implements DispatchableWithBurningParrot
{
    public const URL = '/plugins/oauth2_server/account/apps';

    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var StreamFactoryInterface
     */
    private $stream_factory;
    /**
     * @var AppsPresenterBuilder
     */
    private $presenter_builder;
    /**
     * @var \TemplateRenderer
     */
    private $renderer;
    /**
     * @var \UserManager
     */
    private $user_manager;

    public function __construct(
        ResponseFactoryInterface $response_factory,
        StreamFactoryInterface $stream_factory,
        AppsPresenterBuilder $presenter_builder,
        \TemplateRendererFactory $renderer_factory,
        \UserManager $user_manager,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->response_factory  = $response_factory;
        $this->stream_factory    = $stream_factory;
        $this->presenter_builder = $presenter_builder;
        $this->renderer          = $renderer_factory->getRenderer(__DIR__ . '/../../../templates');
        $this->user_manager      = $user_manager;
    }

    public static function getCSRFToken(): \CSRFSynchronizerToken
    {
        return new \CSRFSynchronizerToken(self::URL);
    }

    /**
     * @throws ForbiddenException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->user_manager->getCurrentUser();
        if ($user->isAnonymous()) {
            throw new ForbiddenException();
        }

        $layout = $request->getAttribute(BaseLayout::class);
        assert($layout instanceof BaseLayout);


        $assets = new IncludeAssets(__DIR__ . '/../../../../../src/www/assets/oauth2_server', '/assets/oauth2_server');
        $layout->addJavascriptAsset(new JavascriptAsset($assets, 'user-preferences.js'));
        $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($assets, 'user-preferences-style'));
        $presenter = $this->presenter_builder->build(
            $user,
            CSRFSynchronizerTokenPresenter::fromToken(self::getCSRFToken())
        );
        ob_start();
        (new UserPreferencesHeader())->display(dgettext('tuleap-oauth2_server', 'OAuth2 Apps'), $layout, ['user-preferences-frame-wide']);
        $this->renderer->renderToPage('account-apps', $presenter);
        $layout->footer([]);

        return $this->response_factory->createResponse()->withBody(
            $this->stream_factory->createStream((string) ob_get_clean())
        );
    }
}

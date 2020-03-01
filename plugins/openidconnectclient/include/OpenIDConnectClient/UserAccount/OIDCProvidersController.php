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

namespace Tuleap\OpenIDConnectClient\UserAccount;

use CSRFSynchronizerToken;
use HTTPRequest;
use Psr\EventDispatcher\EventDispatcherInterface;
use TemplateRendererFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\User\Account\AccountTabPresenterCollection;
use Tuleap\User\Account\UserPreferencesHeader;

final class OIDCProvidersController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public const URL = '/plugins/openidconnectclient/account';

    /**
     * @var \TemplateRenderer
     */
    private $renderer;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var UserMappingManager
     */
    private $user_mapping_manager;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var bool
     */
    private $unique_provider;
    /**
     * @var IncludeAssets
     */
    private $oidc_assets;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        TemplateRendererFactory $renderer_factory,
        CSRFSynchronizerToken $csrf_token,
        UserMappingManager $user_mapping_manager,
        bool $unique_provider,
        IncludeAssets $oidc_assets
    ) {
        $this->dispatcher = $dispatcher;
        $this->renderer = $renderer_factory->getRenderer(__DIR__ . '/templates');
        $this->csrf_token = $csrf_token;
        $this->user_mapping_manager = $user_mapping_manager;
        $this->unique_provider = $unique_provider;
        $this->oidc_assets = $oidc_assets;
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

        $user_mappings_usage = $this->user_mapping_manager->getUsageByUser($user);

        $layout->addCssAsset(
            new CssAssetWithoutVariantDeclinaisons(
                $this->oidc_assets,
                'user-account-style'
            )
        );

        $tabs = $this->dispatcher->dispatch(new AccountTabPresenterCollection($user, self::URL));
        assert($tabs instanceof AccountTabPresenterCollection);

        (new UserPreferencesHeader())->display(dgettext('tuleap-openidconnectclient', 'OpenID Connect providers'), $layout);
        $this->renderer->renderToPage(
            'oidc-providers',
            new OIDCProvidersPresenter(
                $tabs,
                $this->csrf_token,
                $user_mappings_usage,
                $this->unique_provider
            )
        );
        $layout->footer([]);
    }

    public static function getCSRFToken(): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(self::URL);
    }
}

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
use Psr\EventDispatcher\EventDispatcherInterface;
use TemplateRendererFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssViteAsset;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\OpenIDConnectClient\UserMapping\CanRemoveUserMappingChecker;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\User\Account\AccountTabPresenterCollection;
use Tuleap\User\Account\UserPreferencesHeader;

final readonly class OIDCProvidersController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public const string URL = '/plugins/openidconnectclient/account';

    private \TemplateRenderer $renderer;

    public function __construct(
        private EventDispatcherInterface $dispatcher,
        TemplateRendererFactory $renderer_factory,
        private CSRFSynchronizerToken $csrf_token,
        private UserMappingManager $user_mapping_manager,
        private bool $unique_provider,
        private CanRemoveUserMappingChecker $can_unlink_provider_from_account_checker,
        private IncludeViteAssets $oidc_assets,
    ) {
        $this->renderer = $renderer_factory->getRenderer(__DIR__ . '/templates');
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function process(\Tuleap\HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $user = $request->getCurrentUser();
        if ($user->isAnonymous()) {
            throw new ForbiddenException();
        }

        $user_mappings_usage = $this->user_mapping_manager->getUsageByUser($user);

        $layout->addCssAsset(CssViteAsset::fromFileName($this->oidc_assets, 'themes/Account/style.scss'));

        $tabs = $this->dispatcher->dispatch(new AccountTabPresenterCollection($user, self::URL));

        (new UserPreferencesHeader())->display(dgettext('tuleap-openidconnectclient', 'OpenID Connect providers'), $layout);
        $this->renderer->renderToPage(
            'oidc-providers',
            new OIDCProvidersPresenter(
                $tabs,
                $this->csrf_token,
                $user_mappings_usage,
                $this->unique_provider,
                $this->can_unlink_provider_from_account_checker->canAUserMappingBeRemoved($user_mappings_usage)
            )
        );
        $layout->footer([]);
    }

    public static function getCSRFToken(): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(self::URL);
    }
}

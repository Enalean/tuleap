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

namespace Tuleap\OAuth2Server\Administration\SiteAdmin;

use HTTPRequest;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\OAuth2Server\Administration\AdminOAuth2AppsPresenterBuilder;
use Tuleap\Project\ServiceInstrumentation;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use UserManager;

final class SiteAdminListAppsController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public const URL = '/plugins/oauth2_server/admin';

    /**
     * @var AdminPageRenderer
     */
    private $admin_page_renderer;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var AdminOAuth2AppsPresenterBuilder
     */
    private $presenter_builder;
    /**
     * @var IncludeAssets
     */
    private $assets;
    /**
     * @var \CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(
        AdminPageRenderer $admin_page_renderer,
        UserManager $user_manager,
        AdminOAuth2AppsPresenterBuilder $presenter_builder,
        IncludeAssets $assets,
        \CSRFSynchronizerToken $csrf_token
    ) {
        $this->admin_page_renderer = $admin_page_renderer;
        $this->user_manager        = $user_manager;
        $this->presenter_builder   = $presenter_builder;
        $this->assets              = $assets;
        $this->csrf_token          = $csrf_token;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        ServiceInstrumentation::increment(\oauth2_serverPlugin::SERVICE_NAME_INSTRUMENTATION);

        $current_user = $this->user_manager->getCurrentUser();
        if (! $current_user->isSuperUser()) {
            throw new ForbiddenException();
        }

        $layout->includeFooterJavascriptFile($this->assets->getFileURL('administration.js'));
        $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($this->assets, 'administration-style'));

        $this->admin_page_renderer->renderAPresenter(
            dgettext('tuleap-oauth2_server', 'OAuth2 Server'),
            __DIR__ . '/../../../templates/',
            'site-admin',
            $this->presenter_builder->buildSiteAdministration($this->csrf_token)
        );
    }
}

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

namespace Tuleap\OAuth2Server\SiteAdmin;

use HTTPRequest;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\OAuth2Server\App\AppFactory;
use Tuleap\OAuth2Server\App\ClientIdentifier;
use Tuleap\OAuth2Server\ProjectAdmin\AppPresenter;
use Tuleap\Project\ServiceInstrumentation;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use UserManager;

final class SiteAdminListAppsController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /**
     * @var AdminPageRenderer
     */
    private $admin_page_renderer;
    /**
     * @var AppFactory
     */
    private $app_factory;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(AdminPageRenderer $admin_page_renderer, UserManager $user_manager, AppFactory $app_factory)
    {
        $this->admin_page_renderer = $admin_page_renderer;
        $this->app_factory         = $app_factory;
        $this->user_manager        = $user_manager;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        ServiceInstrumentation::increment(\oauth2_serverPlugin::SERVICE_NAME_INSTRUMENTATION);

        $current_user = $this->user_manager->getCurrentUser();
        if (! $current_user->isSuperUser()) {
            throw new ForbiddenException();
        }

        $this->admin_page_renderer->renderAPresenter(
            dgettext('tuleap-oauth2_server', 'OAuth2 Server'),
            __DIR__ . '/../../templates/',
            'site-admin',
            [
                'apps' => $this->getAppsPresenter()
            ]
        );
    }

    /**
     * @return AppPresenter[]
     */
    private function getAppsPresenter(): array
    {
        $apps       = $this->app_factory->getSiteLevelApps();
        $presenters = [];
        foreach ($apps as $app) {
            $presenters[] = new AppPresenter(
                $app->getId(),
                $app->getName(),
                $app->getRedirectEndpoint(),
                ClientIdentifier::fromOAuth2App($app)->toString(),
                $app->isUsingPKCE()
            );
        }

        return $presenters;
    }
}

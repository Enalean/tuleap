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

namespace Tuleap\OAuth2Server\Administration\ProjectAdmin;

use HTTPRequest;
use TemplateRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\OAuth2Server\Administration\AdminOAuth2AppsPresenterBuilder;
use Tuleap\Project\Admin\Navigation\NavigationPresenterBuilder;
use Tuleap\Project\Admin\Routing\AdministrationLayoutHelper;
use Tuleap\Project\Admin\Routing\LayoutHelper;
use Tuleap\Project\ServiceInstrumentation;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;

final class ListAppsController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /** @var LayoutHelper */
    private $layout_helper;
    /** @var TemplateRenderer */
    private $renderer;
    /** @var AdminOAuth2AppsPresenterBuilder */
    private $presenter_builder;
    /** @var IncludeAssets */
    private $assets;
    /** @var \CSRFSynchronizerToken */
    private $csrf_token;

    public function __construct(
        LayoutHelper $layout_helper,
        TemplateRenderer $renderer,
        AdminOAuth2AppsPresenterBuilder $presenter_builder,
        IncludeAssets $assets,
        \CSRFSynchronizerToken $csrf_token
    ) {
        $this->layout_helper     = $layout_helper;
        $this->renderer          = $renderer;
        $this->presenter_builder = $presenter_builder;
        $this->assets            = $assets;
        $this->csrf_token        = $csrf_token;
    }

    public static function buildSelf(): self
    {
        return new self(
            AdministrationLayoutHelper::buildSelf(),
            \TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../../templates'),
            AdminOAuth2AppsPresenterBuilder::buildSelf(),
            new IncludeAssets(__DIR__ . '/../../../../../src/www/assets/oauth2_server', '/assets/oauth2_server'),
            new \CSRFSynchronizerToken(\oauth2_serverPlugin::CSRF_TOKEN_APP_EDITION)
        );
    }

    public static function getUrl(\Project $project): string
    {
        return sprintf('/plugins/oauth2_server/project/%d/admin', $project->getID());
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        ServiceInstrumentation::increment(\oauth2_serverPlugin::SERVICE_NAME_INSTRUMENTATION);
        $layout->includeFooterJavascriptFile($this->assets->getFileURL('administration.js'));
        $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($this->assets, 'administration-style'));
        $callback = function (\Project $project, \PFUser $user): void {
            $this->renderer->renderToPage(
                'project-admin',
                $this->presenter_builder->buildProjectAdministration($this->csrf_token, $project)
            );
        };
        $this->layout_helper->renderInProjectAdministrationLayout(
            $request,
            $variables['project_id'],
            dgettext('tuleap-oauth2_server', 'OAuth2 Apps'),
            NavigationPresenterBuilder::OTHERS_ENTRY_SHORTNAME,
            $callback
        );
    }
}

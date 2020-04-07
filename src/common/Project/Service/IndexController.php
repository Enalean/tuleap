<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Service;

use CSRFSynchronizerToken;
use EventManager;
use HTTPRequest;
use Project;
use ServiceManager;
use TemplateRendererFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\Routing\AdministrationLayoutHelper;
use Tuleap\Project\Admin\Routing\LayoutHelper;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;

class IndexController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    private const CSRF_TOKEN = 'project_admin_services';

    /**
     * @var LayoutHelper
     */
    private $layout_helper;
    /**
     * @var ServicesPresenterBuilder
     */
    private $presenter_builder;
    /**
     * @var \TemplateRenderer
     */
    private $renderer;
    /**
     * @var IncludeAssets
     */
    private $include_assets;

    public function __construct(
        LayoutHelper $layout_helper,
        ServicesPresenterBuilder $presenter_builder,
        \TemplateRenderer $renderer,
        IncludeAssets $include_assets
    ) {
        $this->layout_helper     = $layout_helper;
        $this->presenter_builder = $presenter_builder;
        $this->renderer          = $renderer;
        $this->include_assets    = $include_assets;
    }

    public static function buildSelf(): self
    {
        return new self(
            AdministrationLayoutHelper::buildSelf(),
            new ServicesPresenterBuilder(ServiceManager::instance(), EventManager::instance()),
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../../templates/project/admin/services/'),
            new IncludeAssets(__DIR__ . '/../../../www/assets/core', '/assets/core')
        );
    }

    /**
     * @throws \Tuleap\Request\ForbiddenException
     * @throws \Tuleap\Request\NotFoundException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $title = $GLOBALS['Language']->getText('project_admin_servicebar', 'edit_s_bar');

        $callback = function (Project $project, \PFUser $current_user) use ($layout): void {
            $javascript_file_name = 'project-admin-services.js';
            if ($current_user->isSuperUser()) {
                $javascript_file_name = 'site-admin-services.js';
            }
            $layout->includeFooterJavascriptFile($this->include_assets->getFileURL($javascript_file_name));
            $presenter = $this->presenter_builder->build($project, self::getCSRFTokenSynchronizer(), $current_user);
            $this->renderer->renderToPage('services', $presenter);
        };

        $this->layout_helper->renderInProjectAdministrationLayout(
            $request,
            $variables['id'],
            $title,
            'services',
            $callback
        );
    }

    public static function getCSRFTokenSynchronizer(): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(self::CSRF_TOKEN);
    }

    public static function getUrl(Project $project): string
    {
        return sprintf('/project/%s/admin/services', urlencode((string) $project->getID()));
    }
}

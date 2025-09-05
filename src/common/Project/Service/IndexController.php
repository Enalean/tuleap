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

declare(strict_types=1);

namespace Tuleap\Project\Service;

use CSRFSynchronizerToken;
use HTTPRequest;
use PFUser;
use Project;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\JavascriptAssetGeneric;
use Tuleap\Project\Admin\Navigation\NavigationPresenterBuilder;
use Tuleap\Project\Admin\Routing\LayoutHelper;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;

final readonly class IndexController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    private const string CSRF_TOKEN = 'project_admin_services';

    public function __construct(
        private LayoutHelper $layout_helper,
        private ServicesPresenterBuilder $presenter_builder,
        private \TemplateRenderer $renderer,
        private JavascriptAssetGeneric $project_admin_assets,
        private JavascriptAssetGeneric $site_admin_assets,
    ) {
    }

    /**
     * @throws \Tuleap\Request\ForbiddenException
     * @throws \Tuleap\Request\NotFoundException
     */
    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $title = $GLOBALS['Language']->getText('project_admin_servicebar', 'edit_s_bar');

        $current_user = $request->getCurrentUser();
        if ($current_user->isSuperUser()) {
            $layout->addJavascriptAsset($this->site_admin_assets);
        } else {
            $layout->addJavascriptAsset($this->project_admin_assets);
        }

        $this->layout_helper->renderInProjectAdministrationLayout(
            $request,
            $variables['project_id'],
            $title,
            NavigationPresenterBuilder::OTHERS_ENTRY_SHORTNAME,
            $this->renderServices(...)
        );
    }

    private function renderServices(Project $project, PFUser $current_user): void
    {
        $presenter = $this->presenter_builder->build($project, self::getCSRFTokenSynchronizer(), $current_user);
        $this->renderer->renderToPage('services', $presenter);
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

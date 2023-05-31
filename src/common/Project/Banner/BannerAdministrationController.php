<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Project\Banner;

use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\JavascriptAssetGeneric;
use Tuleap\Project\Admin\Navigation\NavigationPresenterBuilder;
use Tuleap\Project\Admin\Routing\LayoutHelper;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;

final class BannerAdministrationController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public function __construct(
        private readonly LayoutHelper $layout_helper,
        private readonly \TemplateRenderer $renderer,
        private readonly JavascriptAssetGeneric $ckeditor_assets,
        private readonly JavascriptAssetGeneric $banner_assets,
        private readonly BannerRetriever $banner_retriever,
    ) {
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $layout->addJavascriptAsset($this->ckeditor_assets);
        $layout->addJavascriptAsset($this->banner_assets);

        $callback = function (\Project $project) use ($layout): void {
            $banner = $this->banner_retriever->getBannerForProject($project);
            $this->renderer->renderToPage(
                'administration',
                [
                    'message'    => $banner === null ? '' : $banner->getMessage(),
                    'project_id' => $project->getID(),
                ]
            );
        };
        $this->layout_helper->renderInProjectAdministrationLayout(
            $request,
            $variables['project_id'],
            _('Project banner'),
            NavigationPresenterBuilder::OTHERS_ENTRY_SHORTNAME,
            $callback
        );
    }
}

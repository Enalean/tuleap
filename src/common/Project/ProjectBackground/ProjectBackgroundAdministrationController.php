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

namespace Tuleap\Project\ProjectBackground;

use HTTPRequest;
use TemplateRendererFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Project\Admin\Navigation\NavigationPresenterBuilder;
use Tuleap\Project\Admin\Routing\AdministrationLayoutHelper;
use Tuleap\Project\Admin\Routing\LayoutHelper;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;

final class ProjectBackgroundAdministrationController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /**
     * @var LayoutHelper
     */
    private $layout_helper;
    /**
     * @var \TemplateRenderer
     */
    private $renderer;
    /**
     * @var IncludeAssets
     */
    private $assets;
    /**
     * @var ProjectBackgroundRetriever
     */
    private $background_retriever;

    public function __construct(
        LayoutHelper $layout_helper,
        \TemplateRenderer $renderer,
        IncludeAssets $assets,
        ProjectBackgroundRetriever $background_retriever,
    ) {
        $this->layout_helper        = $layout_helper;
        $this->renderer             = $renderer;
        $this->assets               = $assets;
        $this->background_retriever = $background_retriever;
    }

    public static function buildSelf(): self
    {
        return new self(
            AdministrationLayoutHelper::buildSelf(),
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../../templates/project/admin/background/'),
            new \Tuleap\Layout\IncludeCoreAssets(),
            new ProjectBackgroundRetriever(new ProjectBackgroundConfiguration(new ProjectBackgroundDao()))
        );
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $layout->addJavascriptAsset(new JavascriptAsset($this->assets, 'project/header-background-admin.js'));

        $callback = function (\Project $project, \PFUser $current_user) use ($request): void {
            $backgrounds = $this->background_retriever->getBackgrounds($project);
            $this->renderer->renderToPage(
                'administration',
                new ProjectBackgroundAdministrationPresenter($backgrounds, (int) $project->getID()),
            );
        };
        $this->layout_helper->renderInProjectAdministrationLayout(
            $request,
            $variables['project_id'],
            _('Project background'),
            NavigationPresenterBuilder::OTHERS_ENTRY_SHORTNAME,
            $callback
        );
    }
}

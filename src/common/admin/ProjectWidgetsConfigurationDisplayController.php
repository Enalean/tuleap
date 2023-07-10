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

namespace Tuleap\Admin;

use CSRFSynchronizerToken;
use HTTPRequest;
use Tuleap\Dashboard\Project\DisabledProjectWidgetsChecker;
use Tuleap\Dashboard\Project\DisabledProjectWidgetsDao;
use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Widget\WidgetFactory;

class ProjectWidgetsConfigurationDisplayController implements DispatchableWithRequest
{
    public const TAB_NAME = 'widgets';

    /**
     * @var WidgetFactory
     */
    private $widget_factory;

    public function __construct(WidgetFactory $widget_factory)
    {
        $this->widget_factory = $widget_factory;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        $csrf_token = new CSRFSynchronizerToken('/admin/project-creation/widgets');

        $project_widgets = $this->widget_factory->getWidgetsForOwnerType(ProjectDashboardController::DASHBOARD_TYPE);

        $presenter = new ProjectWidgetsConfigurationPresenter(
            new ProjectCreationNavBarPresenter(self::TAB_NAME),
            $csrf_token,
            $this->buildWidgetPresenters($project_widgets)
        );

        $layout->addJavascriptAsset(
            new \Tuleap\Layout\JavascriptAsset(
                new \Tuleap\Layout\IncludeAssets(__DIR__ . '/../../scripts/site-admin/frontend-assets', '/assets/core/site-admin'),
                'site-admin-project-widgets.js'
            )
        );

        $admin_renderer = new AdminPageRenderer();
        $admin_renderer->renderANoFramedPresenter(
            _('Widgets availability'),
            __DIR__ . '/../../templates/admin/projects',
            'project-widgets-configuration-pane',
            $presenter
        );
    }

    private function buildWidgetPresenters(array $project_widgets): array
    {
        $disabled_widget_checker = new DisabledProjectWidgetsChecker(new DisabledProjectWidgetsDao());
        $widget_presenters       = [];
        foreach ($project_widgets as $project_widget) {
            $widget_presenters[] = new ProjectWidgetsPresenter($project_widget, $disabled_widget_checker);
        }

        return $widget_presenters;
    }
}

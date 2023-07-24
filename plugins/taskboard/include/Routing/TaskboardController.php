<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Routing;

use HTTPRequest;
use TemplateRenderer;
use Tuleap\AgileDashboard\Milestone\AllBreadCrumbsForMilestoneBuilder;
use Tuleap\AgileDashboard\Milestone\HeaderOptionsProvider;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssViteAsset;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\NotFoundException;
use Tuleap\Taskboard\Board\BoardPresenterBuilder;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;

class TaskboardController implements DispatchableWithRequestNoAuthz, DispatchableWithBurningParrot
{
    /**
     * @var MilestoneExtractor
     */
    private $milestone_extractor;
    /**
     * @var TemplateRenderer
     */
    private $renderer;
    /**
     * @var AllBreadCrumbsForMilestoneBuilder
     */
    private $bread_crumbs_builder;
    /**
     * @var IncludeViteAssets
     */
    private $taskboard_assets;
    /**
     * @var BoardPresenterBuilder
     */
    private $presenter_builder;
    /**
     * @var VisitRecorder
     */
    private $visit_recorder;
    /**
     * @var HeaderOptionsProvider
     */
    private $header_options_provider;

    public function __construct(
        MilestoneExtractor $milestone_extractor,
        TemplateRenderer $renderer,
        AllBreadCrumbsForMilestoneBuilder $bread_crumbs_builder,
        BoardPresenterBuilder $presenter_builder,
        IncludeViteAssets $taskboard_assets,
        VisitRecorder $visit_recorder,
        HeaderOptionsProvider $header_options_provider,
    ) {
        $this->milestone_extractor     = $milestone_extractor;
        $this->renderer                = $renderer;
        $this->bread_crumbs_builder    = $bread_crumbs_builder;
        $this->presenter_builder       = $presenter_builder;
        $this->taskboard_assets        = $taskboard_assets;
        $this->visit_recorder          = $visit_recorder;
        $this->header_options_provider = $header_options_provider;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        \Tuleap\Project\ServiceInstrumentation::increment(\taskboardPlugin::NAME);

        $user      = $request->getCurrentUser();
        $milestone = $this->milestone_extractor->getMilestone($user, $variables);

        $project = $milestone->getProject();
        $service = $project->getService('plugin_agiledashboard');
        if (! $service) {
            throw new NotFoundException(
                $GLOBALS['Language']->getText(
                    'project_service',
                    'service_not_used',
                    dgettext('tuleap-agiledashboard', 'Agile Dashboard')
                )
            );
        }

        $this->visit_recorder->record($user, $milestone->getArtifact());

        $layout->addJavascriptAsset(
            new JavascriptViteAsset(
                $this->taskboard_assets,
                'src/index.ts'
            )
        );

        $layout->addCssAsset(CssViteAsset::fromFileName($this->taskboard_assets, 'themes/taskboard.scss'));

        $service->displayHeader(
            $milestone->getArtifactTitle() . ' - ' . dgettext('tuleap-taskboard', "Taskboard"),
            $this->bread_crumbs_builder->getBreadcrumbs($user, $project, $milestone),
            [],
            $this->getHeaderOptions($user, $milestone)
        );
        $this->renderer->renderToPage('taskboard', $this->presenter_builder->getPresenter($milestone, $user));
        $service->displayFooter();
    }

    private function getHeaderOptions(\PFUser $user, \Planning_Milestone $milestone): array
    {
        $header_options = $this->header_options_provider->getHeaderOptions($user, $milestone, 'taskboard');
        if (! isset($header_options['main_classes'])) {
            $header_options['main_classes'] = [];
        }
        if (! in_array('fluid-main', $header_options['main_classes'], true)) {
            $header_options['main_classes'][] = 'fluid-main';
        }

        $header_options['body_class'][] = 'reduce-help-button';

        return $header_options;
    }
}

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
use Tuleap\Cardwall\Agiledashboard\CardwallPaneInfo;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\IncludeAssets;
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
     * @var IncludeAssets
     */
    private $agiledashboard_assets;
    /**
     * @var IncludeAssets
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

    public function __construct(
        MilestoneExtractor $milestone_extractor,
        TemplateRenderer $renderer,
        AllBreadCrumbsForMilestoneBuilder $bread_crumbs_builder,
        BoardPresenterBuilder $presenter_builder,
        IncludeAssets $agiledashboard_assets,
        IncludeAssets $taskboard_assets,
        VisitRecorder $visit_recorder
    ) {
        $this->milestone_extractor   = $milestone_extractor;
        $this->renderer              = $renderer;
        $this->bread_crumbs_builder  = $bread_crumbs_builder;
        $this->presenter_builder     = $presenter_builder;
        $this->agiledashboard_assets = $agiledashboard_assets;
        $this->taskboard_assets      = $taskboard_assets;
        $this->visit_recorder        = $visit_recorder;
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
                    $GLOBALS['Language']->getText('plugin_agiledashboard', 'service_lbl_key')
                )
            );
        }
        if ($request->getBrowser()->isIE11()) {
            $layout->redirect(
                '/plugins/agiledashboard/?' . http_build_query(
                    [
                        'group_id'    => $project->getID(),
                        'planning_id' => $milestone->getPlanningId(),
                        'action'      => 'show',
                        'aid'         => $milestone->getArtifactId(),
                        'pane'        => CardwallPaneInfo::IDENTIFIER
                    ]
                )
            );
            return;
        }

        $this->visit_recorder->record($user, $milestone->getArtifact());

        $layout->includeFooterJavascriptFile($this->agiledashboard_assets->getFileURL('scrum-header.js'));
        $layout->includeFooterJavascriptFile($this->taskboard_assets->getFileURL('taskboard.js'));

        $layout->addCssAsset(new CssAsset($this->taskboard_assets, 'taskboard'));

        $service->displayHeader(
            $milestone->getArtifactTitle() . ' - ' . dgettext('tuleap-taskboard', "Taskboard"),
            $this->bread_crumbs_builder->getBreadcrumbs($user, $project, $milestone),
            [],
            ['main_classes' => ['fluid-main']]
        );
        $this->renderer->renderToPage('taskboard', $this->presenter_builder->getPresenter($milestone, $user));
        $service->displayFooter();
    }
}

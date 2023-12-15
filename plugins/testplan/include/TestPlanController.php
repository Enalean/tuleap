<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\TestPlan;

use AgileDashboardPlugin;
use HTTPRequest;
use TemplateRenderer;
use Tuleap\AgileDashboard\Milestone\AllBreadCrumbsForMilestoneBuilder;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;

final class TestPlanController implements DispatchableWithRequestNoAuthz, DispatchableWithBurningParrot
{
    /**
     * @var TemplateRenderer
     */
    private $renderer;
    /**
     * @var AllBreadCrumbsForMilestoneBuilder
     */
    private $bread_crumbs_builder;
    /**
     * @var TestPlanPaneDisplayable
     */
    private $testplan_pane_displayable;
    /**
     * @var VisitRecorder
     */
    private $visit_recorder;
    /**
     * @var \Planning_MilestoneFactory
     */
    private $milestone_factory;
    /**
     * @var TestPlanPresenterBuilder
     */
    private $presenter_builder;
    /**
     * @var IncludeAssets
     */
    private $testplan_assets;
    /**
     * @var TestPlanHeaderOptionsProvider
     */
    private $header_options_provider;

    public function __construct(
        TemplateRenderer $renderer,
        AllBreadCrumbsForMilestoneBuilder $bread_crumbs_builder,
        IncludeAssets $testplan_assets,
        TestPlanPaneDisplayable $testplan_pane_displayable,
        VisitRecorder $visit_recorder,
        \Planning_MilestoneFactory $milestone_factory,
        TestPlanPresenterBuilder $presenter_builder,
        TestPlanHeaderOptionsProvider $header_options_provider,
    ) {
        $this->renderer                  = $renderer;
        $this->bread_crumbs_builder      = $bread_crumbs_builder;
        $this->testplan_assets           = $testplan_assets;
        $this->testplan_pane_displayable = $testplan_pane_displayable;
        $this->visit_recorder            = $visit_recorder;
        $this->milestone_factory         = $milestone_factory;
        $this->presenter_builder         = $presenter_builder;
        $this->header_options_provider   = $header_options_provider;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        \Tuleap\Project\ServiceInstrumentation::increment('testplan');

        $user = $request->getCurrentUser();

        $milestone = $this->milestone_factory->getBareMilestoneByArtifactId($user, (int) $variables['id']);
        if (! $milestone instanceof \Planning_ArtifactMilestone) {
            throw new NotFoundException(dgettext('tuleap-testplan', "Milestone not found."));
        }

        $project = $milestone->getProject();
        if ((string) $project->getUnixNameMixedCase() !== (string) $variables['project_name']) {
            throw new NotFoundException(dgettext('tuleap-testplan', "Milestone not found."));
        }

        $service = $project->getService(AgileDashboardPlugin::PLUGIN_SHORTNAME);
        if (! $service) {
            throw new NotFoundException(dgettext('tuleap-testplan', "Project does not use agile dashboard."));
        }

        if (! $this->testplan_pane_displayable->isTestPlanPaneDisplayable($project, $user)) {
            throw new NotFoundException(
                dgettext(
                    'tuleap-testplan',
                    "Test plan cannot be displayed. Maybe you don't have enough rights to view it."
                )
            );
        }

        $this->visit_recorder->record($user, $milestone->getArtifact());

        $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($this->testplan_assets, 'testplan-style'));

        $title = dgettext('tuleap-testmanagement', "Tests") . ' - ' . $milestone->getArtifactTitle();
        $service->displayHeader(
            $title,
            $this->bread_crumbs_builder->getBreadcrumbs($user, $project, $milestone),
            [],
            HeaderConfigurationBuilder::get($title)
                ->inProjectWithActivePromotedItem(
                    $project,
                    AgileDashboardPlugin::PLUGIN_SHORTNAME,
                    $milestone->getPromotedMilestoneId(),
                )
                ->withBodyClass(['agiledashboard-body'])
                ->withMainClass(['fluid-main'])
                ->withNewDropdownLinkSection($this->header_options_provider->getCurrentContextSection($user, $milestone)->unwrapOr(null))
                ->build()
        );

        $expand_backlog_item_id       = (int) ($variables['backlog_item_id'] ?? 0);
        $highlight_test_definition_id = (int) ($variables['test_definition_id'] ?? 0);

        $presenter = $this->presenter_builder->getPresenter(
            $milestone,
            $user,
            $expand_backlog_item_id,
            $highlight_test_definition_id
        );
        $layout->includeFooterJavascriptFile($this->testplan_assets->getFileURL('testplan.js'));
        $this->renderer->renderToPage('test-plan', $presenter);
        $service->displayFooter();
    }
}

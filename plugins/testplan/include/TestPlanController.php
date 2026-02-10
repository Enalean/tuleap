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
use TemplateRenderer;
use Tuleap\AgileDashboard\Milestone\AllBreadCrumbsForMilestoneBuilder;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssViteAsset;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;

final readonly class TestPlanController implements DispatchableWithRequestNoAuthz, DispatchableWithBurningParrot
{
    public function __construct(
        private TemplateRenderer $renderer,
        private AllBreadCrumbsForMilestoneBuilder $bread_crumbs_builder,
        private IncludeViteAssets $testplan_assets,
        private TestPlanPaneDisplayable $testplan_pane_displayable,
        private VisitRecorder $visit_recorder,
        private \Planning_MilestoneFactory $milestone_factory,
        private TestPlanPresenterBuilder $presenter_builder,
        private TestPlanHeaderOptionsProvider $header_options_provider,
    ) {
    }

    #[\Override]
    public function process(\Tuleap\HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        \Tuleap\Project\ServiceInstrumentation::increment('testplan');

        $user = $request->getCurrentUser();

        $milestone = $this->milestone_factory->getBareMilestoneByArtifactId($user, (int) $variables['id']);
        if (! $milestone instanceof \Planning_ArtifactMilestone) {
            throw new NotFoundException(dgettext('tuleap-testplan', 'Milestone not found.'));
        }

        $project = $milestone->getProject();
        if ((string) $project->getUnixNameMixedCase() !== (string) $variables['project_name']) {
            throw new NotFoundException(dgettext('tuleap-testplan', 'Milestone not found.'));
        }

        $service = $project->getService(AgileDashboardPlugin::PLUGIN_SHORTNAME);
        if (! $service) {
            throw new NotFoundException(dgettext('tuleap-testplan', 'Project does not use agile dashboard.'));
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

        $layout->addCssAsset(CssViteAsset::fromFileName($this->testplan_assets, 'themes/testplan.scss'));
        $layout->addJavascriptAsset(new JavascriptViteAsset($this->testplan_assets, 'scripts/test-plan/index.ts'));

        $title = dgettext('tuleap-testmanagement', 'Tests') . ' - ' . $milestone->getArtifactTitle();
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
        $this->renderer->renderToPage('test-plan', $presenter);
        $service->displayFooter();
    }
}

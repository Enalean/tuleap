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

namespace Tuleap\TestManagement\TestPlan;

use AgileDashboardPlugin;
use HTTPRequest;
use TemplateRenderer;
use Tuleap\AgileDashboard\Milestone\AllBreadCrumbsForMilestoneBuilder;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;

class TestPlanController implements DispatchableWithRequestNoAuthz, DispatchableWithBurningParrot
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
     * @var IncludeAssets
     */
    private $agiledashboard_assets;
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
    private $testmanagement_assets;
    /**
     * @var \Browser
     */
    private $browser;

    public function __construct(
        TemplateRenderer $renderer,
        AllBreadCrumbsForMilestoneBuilder $bread_crumbs_builder,
        IncludeAssets $agiledashboard_assets,
        IncludeAssets $testmanagement_assets,
        VisitRecorder $visit_recorder,
        \Planning_MilestoneFactory $milestone_factory,
        TestPlanPresenterBuilder $presenter_builder,
        \Browser $browser
    ) {
        $this->renderer              = $renderer;
        $this->bread_crumbs_builder  = $bread_crumbs_builder;
        $this->agiledashboard_assets = $agiledashboard_assets;
        $this->testmanagement_assets = $testmanagement_assets;
        $this->visit_recorder        = $visit_recorder;
        $this->milestone_factory     = $milestone_factory;
        $this->presenter_builder     = $presenter_builder;
        $this->browser               = $browser;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        \Tuleap\Project\ServiceInstrumentation::increment(\testmanagementPlugin::NAME);

        $user = $request->getCurrentUser();

        $milestone = $this->milestone_factory->getBareMilestoneByArtifactId($user, (int) $variables['id']);
        if (! $milestone) {
            throw new NotFoundException(dgettext('tuleap-testmanagement', "Milestone not found."));
        }

        $project = $milestone->getProject();
        if ((string) $project->getUnixNameMixedCase() !== (string) $variables['project_name']) {
            throw new NotFoundException(dgettext('tuleap-testmanagement', "Milestone not found."));
        }

        $service = $project->getService(AgileDashboardPlugin::PLUGIN_SHORTNAME);
        if (! $service || ! $project->getService(\testmanagementPlugin::SERVICE_SHORTNAME)) {
            throw new NotFoundException(dgettext('tuleap-testmanagement', "Milestone not found."));
        }

        $this->visit_recorder->record($user, $milestone->getArtifact());

        $layout->includeFooterJavascriptFile($this->agiledashboard_assets->getFileURL('scrum-header.js'));
        $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($this->testmanagement_assets, 'test-plan'));

        $service->displayHeader(
            dgettext('tuleap-testmanagement', "Tests") . ' - ' . $milestone->getArtifactTitle(),
            $this->bread_crumbs_builder->getBreadcrumbs($user, $project, $milestone),
            [],
            ['main_classes' => ['fluid-main']]
        );
        if ($this->browser->isIE11()) {
            $this->renderer->renderToPage(
                'test-plan-unsupported-browser',
                $this->presenter_builder->getPresenter($milestone)
            );
        } else {
            $this->renderer->renderToPage(
                'test-plan',
                $this->presenter_builder->getPresenter($milestone)
            );
        }
        $service->displayFooter();
    }
}

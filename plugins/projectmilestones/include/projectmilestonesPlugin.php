<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

use Tuleap\admin\ProjectEdit\ProjectStatusUpdate;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Widget\Event\ConfigureAtXMLImport;
use Tuleap\Widget\Event\GetProjectWidgetList;
use Tuleap\Widget\Event\GetWidget;
use Tuleap\Request\CurrentPage;
use Tuleap\Widget\Event\GetUserWidgetList;
use Tuleap\ProjectMilestones\Widget\MyProjectMilestones;
use Tuleap\ProjectMilestones\Widget\DashboardProjectMilestones;
use Tuleap\ProjectMilestones\Widget\ProjectMilestonesWidgetRetriever;
use Tuleap\ProjectMilestones\Milestones\ProjectMilestonesDao;
use Tuleap\Request\ProjectRetriever;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\ProjectMilestones\Widget\ProjectMilestonesPresenterBuilder;

require_once __DIR__ . '/../vendor/autoload.php';

class projectmilestonesPlugin extends Plugin // phpcs:ignore
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);

        bindtextdomain('tuleap-projectmilestones', __DIR__ . '/../site-content');
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES)]
    public function burningParrotGetJavascriptFiles(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $assets = new JavascriptViteAsset(
            new IncludeViteAssets(
                __DIR__ . '/../scripts/projectmilestones-preferences/frontend-assets',
                '/assets/projectmilestones/projectmilestones-preferences'
            ),
            "src/index.ts"
        );

        if ($this->isInDashboard()) {
            $params['javascript_files'][] = $assets->getFileURL();
        }
    }

    /**
     * @return PluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new Tuleap\ProjectMilestones\Plugin\PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getProjectWidgetList(GetProjectWidgetList $event): void
    {
        $event->addWidget(DashboardProjectMilestones::NAME);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getUserWidgetList(GetUserWidgetList $event): void
    {
        $event->addWidget(MyProjectMilestones::NAME);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function projectStatusUpdate(ProjectStatusUpdate $event): void
    {
        if ($event->status === \Project::STATUS_DELETED) {
            $milestone_dao = new ProjectMilestonesDao();
            $milestone_dao->deleteAllPluginWithProject((int) $event->project->getID());
        }
    }

    /**
     * Hook: event raised when widget are instanciated
     *
     */
    #[\Tuleap\Plugin\ListeningToEventClass]
    public function widgetInstance(GetWidget $get_widget_event): void
    {
        $project_milestones_widget_retriever = new ProjectMilestonesWidgetRetriever(
            new ProjectAccessChecker(new RestrictedUserCanAccessProjectVerifier(), \EventManager::instance()),
            ProjectManager::instance(),
            new ProjectMilestonesDao(),
            $this->getRenderer(),
            ProjectMilestonesPresenterBuilder::build()
        );

        if ($get_widget_event->getName() === DashboardProjectMilestones::NAME) {
            $project = HTTPRequest::instance()->getProject();

            $agiledashboard_plugin = PluginManager::instance()->getPluginByName('agiledashboard');
            if (! $agiledashboard_plugin || ! $agiledashboard_plugin->isAllowed($project->getID())) {
                return;
            }

            $get_widget_event->setWidget(new DashboardProjectMilestones(
                $project_milestones_widget_retriever,
                new ProjectMilestonesDao(),
                new ProjectRetriever(ProjectManager::instance()),
                PlanningFactory::build(),
                HTTPRequest::instance(),
                new CSRFSynchronizerToken('/project/')
            ));
        }

        if ($get_widget_event->getName() === MyProjectMilestones::NAME) {
            $get_widget_event->setWidget(new MyProjectMilestones(
                $project_milestones_widget_retriever,
                new ProjectMilestonesDao(),
                new ProjectRetriever(ProjectManager::instance()),
                PlanningFactory::build(),
                HTTPRequest::instance(),
                new CSRFSynchronizerToken('/my/')
            ));
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function configureAtXMLImport(ConfigureAtXMLImport $event): void
    {
        (new Tuleap\ProjectMilestones\Widget\ConfigureAtXMLImport())($event);
    }

    public function getDependencies()
    {
        return ['agiledashboard'];
    }

    private function isInDashboard(): bool
    {
        $current_page = new CurrentPage();

        return $current_page->isDashboard();
    }

    private function getRenderer(): TemplateRenderer
    {
        return TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates');
    }
}

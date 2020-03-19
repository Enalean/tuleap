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

use Tuleap\Widget\Event\GetProjectWidgetList;
use Tuleap\Widget\Event\GetWidget;
use Tuleap\Layout\IncludeAssets;
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

    public function getHooksAndCallbacks()
    {
        $this->addHook(GetWidget::NAME);
        $this->addHook(GetProjectWidgetList::NAME);
        $this->addHook(GetUserWidgetList::NAME);
        $this->addHook(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES);
        $this->addHook('project_is_deleted');

        return parent::getHooksAndCallbacks();
    }

    public function burning_parrot_get_javascript_files(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $include_assets = new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/projectmilestones',
            '/assets/projectmilestones'
        );

        if ($this->isInDashboard()) {
            $params['javascript_files'][] = $include_assets->getFileURL('projectmilestones-preferences.js');
        }
    }

    /**
     * @return PluginInfo
     */
    public function getPluginInfo()
    {
        if (!$this->pluginInfo) {
            $this->pluginInfo = new Tuleap\ProjectMilestones\Plugin\PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    public function getProjectWidgetList(GetProjectWidgetList $event)
    {
        $event->addWidget(DashboardProjectMilestones::NAME);
    }

    public function getUserWidgetList(GetUserWidgetList $event)
    {
        $event->addWidget(MyProjectMilestones::NAME);
    }

    public function project_is_deleted($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (! empty($params['group_id'])) {
            $milestone_dao = new ProjectMilestonesDao();
            $milestone_dao->deleteAllPluginWithProject((int) $params['group_id']);
        }
    }
    /**
     * Hook: event raised when widget are instanciated
     *
     */
    public function widgetInstance(GetWidget $get_widget_event)
    {
        $project = HTTPRequest::instance()->getProject();

        if (! PluginManager::instance()->getPluginByName('agiledashboard')->isAllowed($project->getID())) {
            return;
        }

        $project_milestones_widget_retriever = new ProjectMilestonesWidgetRetriever(
            new ProjectAccessChecker(PermissionsOverrider_PermissionsOverriderManager::instance(), new RestrictedUserCanAccessProjectVerifier(), \EventManager::instance()),
            ProjectManager::instance(),
            new ProjectMilestonesDao(),
            $this->getRenderer(),
            ProjectMilestonesPresenterBuilder::build()
        );

        if ($get_widget_event->getName() === DashboardProjectMilestones::NAME) {
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

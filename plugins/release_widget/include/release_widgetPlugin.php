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

use Tuleap\Layout\ServiceUrlCollector;
use Tuleap\ReleaseWidget\Widget\ProjectReleaseWidget;
use Tuleap\Widget\Event\GetProjectWidgetList;
use Tuleap\Widget\Event\GetWidget;

require_once __DIR__ . '/../vendor/autoload.php';

class release_widgetPlugin extends Plugin // phpcs:ignore
{

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);

        bindtextdomain('tuleap-release_widget', __DIR__ . '/../site-content');
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook(GetWidget::NAME);
        $this->addHook(GetProjectWidgetList::NAME);
        return parent::getHooksAndCallbacks();
    }

    /**
     * @return PluginInfo
     */
    public function getPluginInfo()
    {
        if (!$this->pluginInfo) {
            $this->pluginInfo = new Tuleap\ReleaseWidget\Plugin\PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    public function getProjectWidgetList(GetProjectWidgetList $event)
    {
        $event->addWidget(ProjectReleaseWidget::NAME);
    }

    /**
     * Hook: event raised when widget are instanciated
     *
     * @param \Tuleap\Widget\Event\GetWidget $get_widget_event
     */
    public function widgetInstance(GetWidget $get_widget_event)
    {
        $project = HTTPRequest::instance()->getProject();

        if (! $this->rootPlanningExists($project)) {
            return;
        }

        if (! PluginManager::instance()->getPluginByName('agiledashboard')->isAllowed($project->getID())) {
            return;
        }

        if ($get_widget_event->getName() === ProjectReleaseWidget::NAME) {
            $get_widget_event->setWidget(new ProjectReleaseWidget());
        }
    }

    public function getDependencies()
    {
        return ['agiledashboard'];
    }

    private function rootPlanningExists(Project $project): bool
    {
        $user = HTTPRequest::instance()->getCurrentUser();

        $planning_factory = new PlanningFactory(
            new PlanningDao(),
            TrackerFactory::instance(),
            new PlanningPermissionsManager()
        );

        return $planning_factory->getRootPlanning(
            $user,
            $project->getID()
        ) instanceof Planning;
    }
}

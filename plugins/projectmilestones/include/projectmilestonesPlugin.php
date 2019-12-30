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

use Tuleap\ProjectMilestones\Widget\ProjectMilestones;
use Tuleap\Widget\Event\GetProjectWidgetList;
use Tuleap\Widget\Event\GetWidget;

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
        return parent::getHooksAndCallbacks();
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
        $event->addWidget(ProjectMilestones::NAME);
    }

    /**
     * Hook: event raised when widget are instanciated
     *
     * @param \Tuleap\Widget\Event\GetWidget $get_widget_event
     */
    public function widgetInstance(GetWidget $get_widget_event)
    {
        $project = HTTPRequest::instance()->getProject();

        if (! PluginManager::instance()->getPluginByName('agiledashboard')->isAllowed($project->getID())) {
            return;
        }

        if ($get_widget_event->getName() === ProjectMilestones::NAME) {
            $get_widget_event->setWidget(new ProjectMilestones());
        }
    }

    public function getDependencies()
    {
        return ['agiledashboard'];
    }
}

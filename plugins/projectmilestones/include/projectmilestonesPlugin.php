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
use Tuleap\Layout\IncludeAssets;

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
        $this->addHook(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES);

        return parent::getHooksAndCallbacks();
    }

    public function burning_parrot_get_javascript_files(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $include_assets = new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/projectmilestones',
            '/assets/projectmilestones'
        );

        $params['javascript_files'][] = $include_assets->getFileURL('projectmilestones-preferences.js');
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

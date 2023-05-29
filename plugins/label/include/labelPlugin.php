<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

use Tuleap\Dashboard\Project\ProjectDashboardIsDisplayed;
use Tuleap\Label\REST\ResourcesInjector;
use Tuleap\Label\Widget\Dao;
use Tuleap\Label\Widget\ProjectLabeledItems;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Project\Label\MergeLabels;
use Tuleap\Project\Label\RemoveLabel;

require_once __DIR__ . '/../vendor/autoload.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class labelPlugin extends Plugin
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);

        bindtextdomain('tuleap-label', __DIR__ . '/../site-content');
    }

    /**
     * @return Tuleap\Label\Plugin\PluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new Tuleap\Label\Plugin\PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getProjectWidgetList(\Tuleap\Widget\Event\GetProjectWidgetList $event): void
    {
        $event->addWidget(ProjectLabeledItems::NAME);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function widgetInstance(\Tuleap\Widget\Event\GetWidget $get_widget_event): void
    {
        switch ($get_widget_event->getName()) {
            case ProjectLabeledItems::NAME:
                $get_widget_event->setWidget(new ProjectLabeledItems());
                break;
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::REST_RESOURCES)]
    public function restResources(array $params): void
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::REST_PROJECT_RESOURCES)]
    public function restProjectResources(array $params): void
    {
        $injector = new ResourcesInjector();
        $injector->declareProjectResource($params['resources'], $params['project']);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function removeLabel(RemoveLabel $event): void
    {
        $this->getDao()->removeLabelById($event->getLabelToDeleteId());
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function mergeLabel(MergeLabels $merge_labels): void
    {
        $this->getDao()->mergeLabelInTransaction(
            $merge_labels->getLabelToEditId(),
            $merge_labels->getLabelIdsToMerge()
        );
    }

    /**
     * @return Dao
     */
    private function getDao()
    {
        return new Dao();
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function projectDashboardIsDisplayed(ProjectDashboardIsDisplayed $project_dashboard_is_displayed): void
    {
        $project_dashboard_is_displayed->getLayout()->addJavascriptAsset(
            new \Tuleap\Layout\JavascriptViteAsset($this->getAssets(), 'src/index.js')
        );
    }

    private function getAssets(): IncludeViteAssets
    {
        return new IncludeViteAssets(
            __DIR__ . '/../scripts/configure-widget/frontend-assets',
            '/assets/label/configure-widget'
        );
    }
}

<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use Tuleap\Config\GetConfigKeys;
use Tuleap\CrossTracker\Report\CrossTrackerArtifactReportFactory;
use Tuleap\CrossTracker\Report\ReportInheritanceHandler;
use Tuleap\CrossTracker\REST\ResourcesInjector;
use Tuleap\CrossTracker\Widget\WidgetPermissionChecker;
use Tuleap\CrossTracker\Widget\CrossTrackerWidgetDao;
use Tuleap\CrossTracker\Widget\CrossTrackerSearchWidget;
use Tuleap\Plugin\ListeningToEventClass;
use Tuleap\Plugin\ListeningToEventName;
use Tuleap\Widget\Event\GetProjectWidgetList;
use Tuleap\Widget\Event\GetUserWidgetList;
use Tuleap\Widget\Event\GetWidget;

require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class crosstrackerPlugin extends Plugin
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);

        bindtextdomain('tuleap-crosstracker', __DIR__ . '/../site-content');
    }

    public function getDependencies(): array
    {
        return ['tracker'];
    }

    public function getPluginInfo(): \Tuleap\CrossTracker\Plugin\PluginInfo
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new Tuleap\CrossTracker\Plugin\PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    #[ListeningToEventClass]
    public function getUserWidgetList(GetUserWidgetList $event): void
    {
        $event->addWidget(CrossTrackerSearchWidget::NAME);
    }

    #[ListeningToEventClass]
    public function getProjectWidgetList(GetProjectWidgetList $event): void
    {
        $event->addWidget(CrossTrackerSearchWidget::NAME);
    }

    #[ListeningToEventClass]
    public function widgetInstance(GetWidget $get_widget_event): void
    {
        if ($get_widget_event->getName() === CrossTrackerSearchWidget::NAME) {
            $widget_dao = new CrossTrackerWidgetDao();
            $get_widget_event->setWidget(
                new CrossTrackerSearchWidget(
                    $widget_dao,
                    new ReportInheritanceHandler(
                        $widget_dao,
                        $widget_dao,
                        $this->getBackendLogger()
                    ),
                    new WidgetPermissionChecker($widget_dao, \ProjectManager::instance())
                )
            );
        }
    }

    public function uninstall(): void
    {
        $this->removeOrphanWidgets([CrossTrackerSearchWidget::NAME]);
    }

    #[ListeningToEventName(Event::REST_RESOURCES)]
    public function restResources(array $params): void
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    #[ListeningToEventClass]
    public function getConfigKeys(GetConfigKeys $config_keys): void
    {
        $config_keys->addConfigClass(CrossTrackerArtifactReportFactory::class);
        $config_keys->addConfigClass(CrossTrackerSearchWidget::class);
    }
}

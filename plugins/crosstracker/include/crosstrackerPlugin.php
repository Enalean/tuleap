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
use Tuleap\CrossTracker\Query\CrossTrackerArtifactQueryFactory;
use Tuleap\CrossTracker\Query\CrossTrackerQueryDao;
use Tuleap\CrossTracker\Query\CrossTrackerQueryFactory;
use Tuleap\CrossTracker\Query\QueryCreator;
use Tuleap\CrossTracker\REST\ResourcesInjector;
use Tuleap\CrossTracker\Widget\CrossTrackerSearchWidget;
use Tuleap\CrossTracker\Widget\CrossTrackerWidgetCreator;
use Tuleap\CrossTracker\Widget\CrossTrackerWidgetDao;
use Tuleap\CrossTracker\Widget\WidgetCrossTrackerWidgetXMLExporter;
use Tuleap\CrossTracker\Widget\WidgetCrossTrackerXMLImporter;
use Tuleap\CrossTracker\Widget\WidgetInheritanceHandler;
use Tuleap\CrossTracker\Widget\WidgetPermissionChecker;
use Tuleap\Dashboard\XML\XMLColumn;
use Tuleap\Dashboard\XML\XMLDashboard;
use Tuleap\Dashboard\XML\XMLLine;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Plugin\ListeningToEventClass;
use Tuleap\Plugin\ListeningToEventName;
use Tuleap\Project\Registration\Template\IssuesTemplateDashboardDefinition;
use Tuleap\Widget\Event\ConfigureAtXMLImport;
use Tuleap\Widget\Event\GetProjectWidgetList;
use Tuleap\Widget\Event\GetUserWidgetList;
use Tuleap\Widget\Event\GetWidget;
use Tuleap\Widget\XML\XMLPreference;
use Tuleap\Widget\XML\XMLPreferenceValue;
use Tuleap\Widget\XML\XMLWidget;

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

    public function getPluginInfo(): PluginInfo
    {
        if (! $this->pluginInfo) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(new PluginDescriptor(
                dgettext('tuleap-crosstracker', 'Cross trackers search'),
                dgettext('tuleap-crosstracker', 'Search artifacts that are in different trackers'),
            ));
            $this->pluginInfo = $plugin_info;
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
            $query_dao  = new CrossTrackerQueryDao();
            $executor   = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());

            $get_widget_event->setWidget(
                new CrossTrackerSearchWidget(
                    new WidgetInheritanceHandler(
                        $widget_dao,
                        $widget_dao,
                        $this->getBackendLogger()
                    ),
                    new WidgetPermissionChecker($widget_dao, ProjectManager::instance()),
                    $widget_dao,
                    new WidgetCrossTrackerWidgetXMLExporter(new CrossTrackerQueryFactory(new CrossTrackerQueryDao())),
                    new CrossTrackerWidgetCreator(
                        $widget_dao,
                        new QueryCreator(
                            $executor,
                            $query_dao,
                            $query_dao
                        ),
                        $executor
                    )
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
    public function configureAtXMLImport(ConfigureAtXMLImport $event): void
    {
        if ($event->getWidget()->getId() === CrossTrackerSearchWidget::NAME) {
            $xml_import = new WidgetCrossTrackerXMLImporter();
            $xml_import->configureWidget($event);
        }
    }

    #[ListeningToEventClass]
    public function getConfigKeys(GetConfigKeys $config_keys): void
    {
        $config_keys->addConfigClass(CrossTrackerArtifactQueryFactory::class);
        $config_keys->addConfigClass(CrossTrackerSearchWidget::class);
    }

    #[ListeningToEventClass]
    public function issuesTemplateDashboardDefinition(IssuesTemplateDashboardDefinition $dashboard_definition): void
    {
        $dashboard_definition->enforceUniqueDashboard(
            (new XMLDashboard('Dashboard'))
                ->withLine(
                    XMLLine::withLayout('two-columns-small-big')
                        ->withColumn((new XMLColumn())
                            ->withWidget(new XMLWidget('projectheartbeat')))
                        ->withColumn((new XMLColumn())
                            ->withWidget((new XMLWidget(CrossTrackerSearchWidget::NAME))
                                ->withPreference(
                                    $this->getQueryAsXml(
                                        'All open artifacts',
                                        <<<EOS
                                            SELECT @pretty_title, @tracker.name, @status, @last_update_date, @submitted_by
                                            FROM @project = 'self'
                                            WHERE @status = OPEN()
                                            ORDER BY @last_update_date DESC
                                            EOS,
                                        true,
                                    )
                                )
                                ->withPreference(
                                    $this->getQueryAsXml(
                                        'Open artifacts assigned to me',
                                        <<<EOS
                                            SELECT @pretty_title, @tracker.name, @status, @last_update_date, @submitted_by
                                            FROM @project = 'self'
                                            WHERE @status = OPEN() AND @assigned_to = MYSELF()
                                            ORDER BY @last_update_date DESC
                                            EOS,
                                        false,
                                    )
                                )))
                )
        );
    }

    private function getQueryAsXml(string $title, string $tql, bool $is_default): XMLPreference
    {
        return (new XMLPreference(WidgetCrossTrackerWidgetXMLExporter::PREFERENCE_QUERY))
            ->withValue(
                XMLPreferenceValue::text(
                    WidgetCrossTrackerWidgetXMLExporter::PREFERENCE_QUERY_IS_DEFAULT,
                    $is_default ? '1' : '0',
                )
            )
            ->withValue(
                XMLPreferenceValue::text(WidgetCrossTrackerWidgetXMLExporter::PREFERENCE_QUERY_TITLE, $title)
            )
            ->withValue(
                XMLPreferenceValue::text(WidgetCrossTrackerWidgetXMLExporter::PREFERENCE_QUERY_DESCRIPTION, '')
            )
            ->withValue(
                XMLPreferenceValue::text(WidgetCrossTrackerWidgetXMLExporter::PREFERENCE_QUERY_TQL, $tql)
            );
    }
}

<?php
/**
 *  Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Dashboard\Project;

use PFUser;
use Project;
use Codendi_Request;
use Tuleap\Dashboard\Dashboard;
use Tuleap\Dashboard\NameDashboardAlreadyExistsException;
use Tuleap\Dashboard\NameDashboardDoesNotExistException;
use Tuleap\Widget\Event\ConfigureAtXMLImport;
use Tuleap\Widget\Event\GetProjectWidgetList;
use Tuleap\Widget\WidgetFactory;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\XML\MappingsRegistry;

class ProjectDashboardXMLImporter
{
    /**
     * @var ProjectDashboardSaver
     */
    private $project_dashboard_saver;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var WidgetFactory
     */
    private $widget_factory;
    /**
     * @var DashboardWidgetDao
     */
    private $widget_dao;
    /**
     * @var \EventManager
     */
    private $event_manager;

    /**
     * @var DisabledProjectWidgetsChecker
     */
    private $disabled_project_widgets_checker;

    public function __construct(
        ProjectDashboardSaver $project_dashboard_saver,
        WidgetFactory $widget_factory,
        DashboardWidgetDao $widget_dao,
        \Psr\Log\LoggerInterface $logger,
        \EventManager $event_manager,
        DisabledProjectWidgetsChecker $disabled_project_widgets_checker
    ) {
        $this->project_dashboard_saver          = $project_dashboard_saver;
        $this->widget_factory                   = $widget_factory;
        $this->widget_dao                       = $widget_dao;
        $this->logger                           = new \WrapperLogger($logger, 'Dashboards');
        $this->event_manager                    = $event_manager;
        $this->disabled_project_widgets_checker = $disabled_project_widgets_checker;
    }

    public function import(\SimpleXMLElement $xml_element, PFUser $user, Project $project, MappingsRegistry $mapping_registry)
    {
        $this->logger->info('Start import');
        if ($xml_element->dashboards) {
            foreach ($xml_element->dashboards->dashboard as $dashboard_xml) {
                try {
                    $dashboard_name = trim((string) $dashboard_xml["name"]);
                    $this->logger->info("Create dashboard $dashboard_name");
                    $dashboard_id = $this->project_dashboard_saver->save($user, $project, $dashboard_name);
                    $dashboard = new Dashboard($dashboard_id, $dashboard_name);
                    $this->importWidgets($dashboard, $project, $dashboard_xml, $mapping_registry);
                } catch (UserCanNotUpdateProjectDashboardException $e) {
                    $this->logger->warning($e->getMessage());
                } catch (NameDashboardDoesNotExistException $e) {
                    $this->logger->warning($e->getMessage());
                } catch (NameDashboardAlreadyExistsException $e) {
                    $this->logger->warning($e->getMessage());
                }
            }
        }
        $this->logger->info('Import completed');
    }

    private function importWidgets(Dashboard $dashboard, Project $project, \SimpleXMLElement $dashboard_xml, MappingsRegistry $mapping_registry)
    {
        $this->logger->info("Import widgets");
        if (! isset($dashboard_xml->line)) {
            return;
        }

        $line_rank = 1;
        $all_widgets = [];
        foreach ($dashboard_xml->line as $line) {
            $this->createLine($line, $project, $dashboard, $line_rank, $all_widgets, $mapping_registry);
            $line_rank++;
        }
        $this->logger->info("Import of widgets: Done");
    }

    private function createLine(\SimpleXMLElement $line, Project $project, Dashboard $dashboard, $line_rank, array &$all_widgets, MappingsRegistry $mapping_registry)
    {
        $line_id = -1;
        $column_rank = 1;
        foreach ($line->column as $column) {
            $this->createColumn($column, $project, $dashboard, $line_id, $line_rank, $column_rank, $all_widgets, $mapping_registry);
            $column_rank++;
        }
        $nb_columns = $column_rank - 1;
        $layout = '';
        if (isset($line['layout'])) {
            $layout = (string) $line['layout'];
            if (! $dashboard->isLayoutValid($layout, $nb_columns)) {
                $layout = '';
                $this->logger->warning("Invalid layout $layout for $nb_columns columns");
            }
        }
        if ($layout !== '') {
            $this->widget_dao->updateLayout($line_id, $layout);
        } elseif ($column_rank > 2) {
            $this->widget_dao->adjustLayoutAccordinglyToNumberOfWidgets($nb_columns, $line_id);
        }
    }

    private function createColumn(\SimpleXMLElement $column, Project $project, Dashboard $dashboard, &$line_id, $line_rank, $column_rank, array &$all_widgets, MappingsRegistry $mapping_registry)
    {
        $column_id = -1;
        $widget_rank = 1;
        foreach ($column->widget as $widget_xml) {
            try {
                list($widget, $content_id) = $this->getWidget($project, $widget_xml, $all_widgets, $mapping_registry);
                if (! $this->isWidgetCreated($widget, $content_id)) {
                    continue;
                }
                if (! $this->isLineCreated($line_id)) {
                    $line_id = $this->widget_dao->createLine($dashboard->getId(), ProjectDashboardController::DASHBOARD_TYPE, $line_rank);
                }
                if (! $this->isColumnCreated($line_id, $column_id)) {
                    $column_id = $this->widget_dao->createColumn($line_id, $column_rank);
                }
                if ($column_id) {
                    $this->widget_dao->insertWidgetInColumnWithRank($widget->getId(), $content_id, $column_id, $widget_rank);
                    $all_widgets[$widget->getId()] = true;
                } else {
                    $this->logger->warning("Impossible to create line or column, widget {$widget->getId()} not added");
                }
                $widget_rank++;
            } catch (\Exception $exception) {
                $this->logger->warning("Impossible to create widget: " . $exception->getMessage());
            }
        }
    }

    private function isWidgetCreated($widget, $content_id)
    {
        return $widget !== null && $content_id !== null;
    }

    private function isLineCreated($line_id)
    {
        return $line_id !== -1;
    }

    private function isColumnCreated($line_id, $column_id)
    {
        return $line_id && $column_id !== -1;
    }

    /**
     * @return array
     */
    private function getWidget(Project $project, \SimpleXMLElement $widget_xml, array $all_widgets, MappingsRegistry $mapping_registry)
    {
        $widget_name = trim((string) $widget_xml['name']);
        $this->logger->info("Import widget $widget_name");
        $widget = $this->widget_factory->getInstanceByWidgetName($widget_name);
        if ($widget === null) {
            $this->logger->error("Impossible to instantiate widget named '" . $widget_name . "'.  Widget skipped");
            return [null, null];
        }

        if ($this->disabled_project_widgets_checker->isWidgetDisabled($widget, ProjectDashboardController::DASHBOARD_TYPE)) {
            $this->logger->error("The widget named '" . $widget_name . "' is disabled. Widget skipped");
            return [null, null];
        }

        $widget->setOwner($project->getID(), ProjectDashboardController::LEGACY_DASHBOARD_TYPE);
        if ($widget->isUnique() && isset($all_widgets[$widget->getId()])) {
            $this->logger->warning("Impossible to instantiate twice widget named '" . $widget_name . "'.  Widget skipped");
            return [null, null];
        }
        $event = new ConfigureAtXMLImport($widget, $widget_xml, $mapping_registry);
        $this->event_manager->processEvent($event);
        if ($event->isWidgetConfigured()) {
            return [$widget, $event->getContentId()];
        }
        if (! in_array($widget->getId(), GetProjectWidgetList::CORE_WIDGETS)) {
            $this->logger->error("Widget named '" . $widget_name . "' is not supported at import.  Widget skipped");
            return [null, null];
        }
        $content_id = $this->configureWidget($widget, $widget_xml);
        if ($content_id === false) {
            $this->logger->error("Impossible to create content for widget $widget_name. Widget skipped");
            return [null, null];
        }
        return [$widget, (int) $content_id];
    }

    /**
     *
     * @return null|false|int
     */
    private function configureWidget(\Widget $widget, \SimpleXMLElement $widget_xml)
    {
        $params = [];
        if (isset($widget_xml->preference)) {
            foreach ($widget_xml->preference as $preference) {
                $preference_name = trim((string) $preference['name']);
                foreach ($preference->value as $value) {
                    $key = trim((string) $value['name']);
                    $val = trim((string) $value);
                    $params[$preference_name][$key] = $val;
                }
            }
        }
        return $widget->create(new Codendi_Request($params));
    }
}

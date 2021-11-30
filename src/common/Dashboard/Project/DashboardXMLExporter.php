<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Dashboard\Project;

use Psr\Log\LoggerInterface;
use Tuleap\Dashboard\Dashboard;
use Tuleap\Dashboard\Widget\DashboardWidget;
use Tuleap\Dashboard\Widget\DashboardWidgetColumn;
use Tuleap\Dashboard\Widget\DashboardWidgetLine;
use Tuleap\Dashboard\Widget\IRetrieveDashboardWidgets;
use Tuleap\Dashboard\Widget\OwnerInfo;
use Tuleap\Widget\IBuildInstanceOfWidgets;

class DashboardXMLExporter
{
    private IRetrieveDashboards $retriever;
    private IRetrieveDashboardWidgets $widgets_retriever;
    private IBuildInstanceOfWidgets $widget_factory;
    private LoggerInterface $logger;

    public function __construct(
        IRetrieveDashboards $dashboards_retriever,
        IRetrieveDashboardWidgets $widgets_retriever,
        IBuildInstanceOfWidgets $widget_factory,
        LoggerInterface $logger,
    ) {
        $this->retriever         = $dashboards_retriever;
        $this->widgets_retriever = $widgets_retriever;
        $this->widget_factory    = $widget_factory;
        $this->logger            = $logger;
    }

    public function exportDashboards(\Project $project, \SimpleXMLElement $xml_element): void
    {
        $dashboards = $this->retriever->getAllProjectDashboards($project);
        if (empty($dashboards)) {
            $this->logger->debug("The project does not have any dashboards to export");

            return;
        }

        $this->logger->debug(sprintf("Found %d dashboards to export", count($dashboards)));
        $dashboards_element = $xml_element->addChild('dashboards');
        foreach ($dashboards as $dashboard) {
            $this->exportOneDashboard($dashboard, $dashboards_element, OwnerInfo::createForProject($project));
        }
    }

    private function exportOneDashboard(Dashboard $dashboard, \SimpleXMLElement $dashboards_element, OwnerInfo $owner_info): void
    {
        $this->logger->debug("Exporting dashboard " . $dashboard->getName());
        $dashboard_element = $dashboards_element->addChild('dashboard');
        $dashboard_element->addAttribute('name', $dashboard->getName());

        $this->exportWidgets($dashboard, $dashboard_element, $owner_info);
    }

    private function exportWidgets(Dashboard $dashboard, \SimpleXMLElement $dashboard_element, OwnerInfo $owner_info): void
    {
        $widget_lines = $this->widgets_retriever->getAllWidgets(
            (int) $dashboard->getId(),
            ProjectDashboardController::DASHBOARD_TYPE
        );
        foreach ($widget_lines as $line) {
            $this->exportLine($line, $dashboard_element, $owner_info);
        }
    }

    private function exportLine(DashboardWidgetLine $line, \SimpleXMLElement $dashboard_element, OwnerInfo $owner_info): void
    {
        $columns = $this->getColumnsAsXML($line, $owner_info);
        if ($columns) {
            $line_element = $dashboard_element->addChild('line');
            $line_element->addAttribute('layout', $line->getLayout());
            $line_dom = $this->getDOMElement($line_element);
            if (! $line_dom->ownerDocument) {
                throw new \RuntimeException('ownerDocument should not be null for line XML element');
            }
            foreach ($columns as $column) {
                $column_dom = $this->getDOMElement($column);
                $line_dom->appendChild($line_dom->ownerDocument->importNode($column_dom, true));
            }
        }
    }

    /**
     * @return \SimpleXMLElement[]
     */
    private function getColumnsAsXML(DashboardWidgetLine $line, OwnerInfo $owner_info): array
    {
        $columns = [];
        foreach ($line->getWidgetColumns() as $column) {
            $widgets = $this->getWidgetsAsXML($column, $owner_info);
            if ($widgets) {
                $column_element = new \SimpleXMLElement('<column />');
                $column_dom     = $this->getDOMElement($column_element);
                if (! $column_dom->ownerDocument) {
                    throw new \RuntimeException('ownerDocument should not be null for column XML element');
                }
                foreach ($widgets as $widget) {
                    $widget_dom = $this->getDOMElement($widget);
                    $column_dom->appendChild($column_dom->ownerDocument->importNode($widget_dom, true));
                }
                $columns[] = $column_element;
            }
        }

        return $columns;
    }

    private function getDOMElement(\SimpleXMLElement $element): \DOMElement
    {
        $dom = dom_import_simplexml($element);
        if (! $dom) {
            throw new \RuntimeException("Unable to get DOMElement from SimpleXMLElement");
        }

        return $dom;
    }

    /**
     * @return \SimpleXMLElement[]
     */
    private function getWidgetsAsXML(DashboardWidgetColumn $column, OwnerInfo $owner_info): array
    {
        $widgets = [];
        foreach ($column->getWidgets() as $dashboard_widget) {
            $widget = $this->getWidgetAsXML($dashboard_widget, $owner_info);
            if ($widget) {
                $widgets[] = $widget;
            }
        }

        return $widgets;
    }

    private function getWidgetAsXML(DashboardWidget $dashboard_widget, OwnerInfo $owner_info): ?\SimpleXMLElement
    {
        $widget = $this->widget_factory->getInstanceByWidgetName($dashboard_widget->getName());
        if (! $widget) {
            return null;
        }

        $widget->owner_id   = $owner_info->getId();
        $widget->owner_type = $owner_info->getType();
        $widget->loadContent($dashboard_widget->getContentId());

        return $widget->exportAsXML();
    }
}

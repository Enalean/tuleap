<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Dashboard\Project;

use Project;
use Tuleap\Dashboard\Widget\DashboardWidgetColumn;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Dashboard\Widget\DashboardWidgetLine;
use Tuleap\Dashboard\Widget\DashboardWidgetRetriever;
use Tuleap\Dashboard\Widget\WidgetDashboardController;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Project\MappingRegistry;
use Tuleap\Widget\WidgetFactory;

class ProjectDashboardDuplicator
{
    /**
     * @var ProjectDashboardDao
     */
    private $dao;

    /**
     * @var ProjectDashboardRetriever
     */
    private $retriever;

    /**
     * @var DashboardWidgetDao
     */
    private $widget_dao;

    /**
     * @var DashboardWidgetRetriever
     */
    private $widget_retriever;

    /**
     * @var WidgetFactory
     */
    private $widget_factory;

    /**
     * @var DisabledProjectWidgetsChecker
     */
    private $disabled_project_widgets_checker;

    public function __construct(
        ProjectDashboardDao $dao,
        ProjectDashboardRetriever $retriever,
        DashboardWidgetDao $widget_dao,
        DashboardWidgetRetriever $widget_retriever,
        WidgetFactory $widget_factory,
        DisabledProjectWidgetsChecker $disabled_project_widgets_checker,
        private readonly DBTransactionExecutor $transaction_executor,
    ) {
        $this->dao                              = $dao;
        $this->retriever                        = $retriever;
        $this->widget_dao                       = $widget_dao;
        $this->widget_retriever                 = $widget_retriever;
        $this->widget_factory                   = $widget_factory;
        $this->disabled_project_widgets_checker = $disabled_project_widgets_checker;
    }

    public function duplicate(Project $template_project, Project $new_project, MappingRegistry $mapping_registry): void
    {
        $this->transaction_executor->execute(function () use ($template_project, $new_project, $mapping_registry): void {
            $template_dashboards = $this->retriever->getAllProjectDashboards($template_project);
            foreach ($template_dashboards as $template_dashboard) {
                $new_dashboard_id = $this->dao->duplicateDashboard(
                    $template_project->getID(),
                    $new_project->getID(),
                    $template_dashboard->getId()
                );

                $this->duplicateDashboardContent(
                    $template_project,
                    $new_project,
                    $template_dashboard,
                    $new_dashboard_id,
                    $mapping_registry,
                );
            }
        });
    }

    private function duplicateDashboardContent(
        Project $template_project,
        Project $new_project,
        ProjectDashboard $template_dashboard,
        $new_dashboard_id,
        MappingRegistry $mapping_registry,
    ) {
        $template_dashboard_id = $template_dashboard->getId();

        $template_lines = $this->widget_retriever->getAllWidgets(
            $template_dashboard_id,
            WidgetDashboardController::PROJECT_DASHBOARD_TYPE
        );

        foreach ($template_lines as $template_line) {
            $new_line_id = $this->widget_dao->duplicateLine(
                $template_dashboard_id,
                $new_dashboard_id,
                $template_line->getId(),
                WidgetDashboardController::PROJECT_DASHBOARD_TYPE
            );

            $this->duplicateColumns($template_project, $new_project, $template_line, $new_line_id, $mapping_registry);
        }
    }

    private function duplicateColumns(
        Project $template_project,
        Project $new_project,
        DashboardWidgetLine $template_line,
        $new_line_id,
        MappingRegistry $mapping_registry,
    ) {
        foreach ($template_line->getWidgetColumns() as $template_column) {
            $new_column_id = $this->widget_dao->duplicateColumn($template_line->getId(), $new_line_id, $template_column->getId());

            $this->duplicateWidgets(
                $template_project,
                $new_project,
                $template_column,
                $new_column_id,
                $mapping_registry
            );
        }
    }

    private function duplicateWidgets(
        Project $template_project,
        Project $new_project,
        DashboardWidgetColumn $template_column,
        $new_column_id,
        MappingRegistry $mapping_registry,
    ) {
        foreach ($template_column->getWidgets() as $template_widget) {
            $widget = $this->widget_factory->getInstanceByWidgetName($template_widget->getName());

            if (! $widget || $this->disabled_project_widgets_checker->isWidgetDisabled($widget, ProjectDashboardController::DASHBOARD_TYPE)) {
                continue;
            }

            $widget->setOwner($template_project->getID(), ProjectDashboardController::LEGACY_DASHBOARD_TYPE);
            $new_content_id = $widget->cloneContent(
                $template_project,
                $new_project,
                $template_widget->getContentId(),
                $new_project->getID(),
                ProjectDashboardController::LEGACY_DASHBOARD_TYPE,
                $mapping_registry,
            );

            $this->widget_dao->duplicateWidget(
                $new_column_id,
                $template_widget->getId(),
                $new_content_id
            );
        }
    }
}

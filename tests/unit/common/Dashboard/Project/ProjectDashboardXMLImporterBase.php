<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Dashboard\Project;

use ColinODell\PsrTestLogger\TestLogger;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Dashboard\Widget\WidgetCreator;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Widget\WidgetFactory;
use Tuleap\XML\MappingsRegistry;

class ProjectDashboardXMLImporterBase extends \Tuleap\Test\PHPUnit\TestCase
{
    protected ProjectDashboardSaver $project_dashboard_saver;
    protected ProjectDashboardDao&MockObject $dao;
    protected TestLogger $logger;
    protected \Project $project;
    protected ProjectDashboardXMLImporter $project_dashboard_importer;
    protected WidgetCreator&MockObject $widget_creator;
    protected WidgetFactory&MockObject $widget_factory;
    protected DashboardWidgetDao&MockObject $widget_dao;
    protected MappingsRegistry $mappings_registry;
    protected \EventManager&MockObject $event_manager;
    protected DisabledProjectWidgetsChecker&MockObject $disabled_widgets_checker;

    protected function setUp(): void
    {
        $this->dao                      = $this->createMock(ProjectDashboardDao::class);
        $this->project_dashboard_saver  = new ProjectDashboardSaver(
            $this->dao,
            $this->createStub(DeleteVisitByDashboardId::class),
            new DBTransactionExecutorPassthrough()
        );
        $this->widget_creator           = $this->createMock(WidgetCreator::class);
        $this->widget_factory           = $this->createMock(WidgetFactory::class);
        $this->widget_dao               = $this->createMock(DashboardWidgetDao::class);
        $this->event_manager            = $this->createMock(\EventManager::class);
        $this->disabled_widgets_checker = $this->createMock(DisabledProjectWidgetsChecker::class);

        $this->logger                     = new TestLogger();
        $this->project_dashboard_importer = new ProjectDashboardXMLImporter(
            $this->project_dashboard_saver,
            $this->widget_factory,
            $this->widget_dao,
            $this->logger,
            $this->event_manager,
            $this->disabled_widgets_checker
        );

        $this->mappings_registry = new MappingsRegistry();

        $this->project = ProjectTestBuilder::aProject()
            ->withId(101)
            ->withAccessPrivate()
            ->build();
    }
}

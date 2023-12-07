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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Widget\WidgetFactory;
use Tuleap\XML\MappingsRegistry;

class ProjectDashboardXMLImporterBase extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ProjectDashboardSaver
     */
    protected $project_dashboard_saver;
    /**
     * @var ProjectDashboardDao
     */
    protected $dao;
    /**
     * @var \Logger
     */
    protected $logger;
    /**
     * @var \Project
     */
    protected $project;

    /**
     * @var \PFUser
     */
    protected $user;

    /**
     * @var ProjectDashboardXMLImporter
     */
    protected $project_dashboard_importer;
    /**
     * @var \Tuleap\Dashboard\Widget\WidgetCreator
     */
    protected $widget_creator;
    /**
     * @var WidgetFactory
     */
    protected $widget_factory;
    /**
     * @var DashboardWidgetDao
     */
    protected $widget_dao;
    /**
     * @var MappingsRegistry
     */
    protected $mappings_registry;
    /**
     * @var \EventManager
     */
    protected $event_manager;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|DisabledProjectWidgetsChecker
     */
    protected $disabled_widgets_checker;

    protected function setUp(): void
    {
        $this->dao                      = \Mockery::spy(\Tuleap\Dashboard\Project\ProjectDashboardDao::class);
        $this->project_dashboard_saver  = new ProjectDashboardSaver(
            $this->dao,
            $this->createStub(DeleteVisitByDashboardId::class),
        );
        $this->widget_creator           = \Mockery::spy(\Tuleap\Dashboard\Widget\WidgetCreator::class);
        $this->widget_factory           = \Mockery::spy(\Tuleap\Widget\WidgetFactory::class);
        $this->widget_dao               = \Mockery::spy(\Tuleap\Dashboard\Widget\DashboardWidgetDao::class);
        $this->event_manager            = \Mockery::spy(\EventManager::class);
        $this->disabled_widgets_checker = \Mockery::mock(DisabledProjectWidgetsChecker::class);

        $this->logger                     = \Mockery::spy(\Psr\Log\LoggerInterface::class);
        $this->project_dashboard_importer = new ProjectDashboardXMLImporter(
            $this->project_dashboard_saver,
            $this->widget_factory,
            $this->widget_dao,
            $this->logger,
            $this->event_manager,
            $this->disabled_widgets_checker
        );

        $this->mappings_registry = new MappingsRegistry();

        $this->user    = \Mockery::spy(\PFUser::class);
        $this->project = \Mockery::spy(\Project::class, ['getID' => 101, 'getUnixName' => false, 'isPublic' => false]);
    }
}

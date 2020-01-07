<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\ExplicitBacklog;

use Codendi_Request;
use MilestoneReportCriterionDao;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

class ConfigurationUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration, GlobalResponseMock;

    /**
     * @var ConfigurationUpdater
     */
    private $updater;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    /**
     * @var MilestoneReportCriterionDao|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $milestone_report_criterion_dao;

    /**
     * @var DBTransactionExecutorPassthrough
     */
    private $db_transaction_executor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->explicit_backlog_dao           = Mockery::mock(ExplicitBacklogDao::class);
        $this->milestone_report_criterion_dao = Mockery::mock(MilestoneReportCriterionDao::class);
        $this->db_transaction_executor        = new DBTransactionExecutorPassthrough();

        $this->updater = new ConfigurationUpdater(
            $this->explicit_backlog_dao,
            $this->milestone_report_criterion_dao,
            $this->db_transaction_executor
        );
    }

    public function testItDoesNothingIfOptionNotProvidedInRequest()
    {
        $request = new Codendi_Request([
            'group_id' => '101'
        ]);

        $this->explicit_backlog_dao->shouldNotReceive('setProjectIsNoMoreUsingExplicitBacklog');
        $this->explicit_backlog_dao->shouldNotReceive('setProjectUsingExplicitBacklog');
        $this->milestone_report_criterion_dao->shouldNotReceive('updateAllUnplannedValueToAnyInProject');

        $this->updater->updateScrumConfiguration($request);
    }

    public function testItDoesNothingIfStillActivated()
    {
        $request = new Codendi_Request([
            'use-explicit-top-backlog' => '1',
            'group_id' => '101'
        ]);

        $this->explicit_backlog_dao->shouldNotReceive('setProjectIsNoMoreUsingExplicitBacklog');
        $this->explicit_backlog_dao->shouldNotReceive('setProjectUsingExplicitBacklog');
        $this->milestone_report_criterion_dao->shouldNotReceive('updateAllUnplannedValueToAnyInProject');

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->with(101)
            ->andReturnTrue();

        $this->updater->updateScrumConfiguration($request);
    }

    public function testItDoesNothingIfStillDeactivated()
    {
        $request = new Codendi_Request([
            'use-explicit-top-backlog' => '0',
            'group_id' => '101'
        ]);

        $this->explicit_backlog_dao->shouldNotReceive('setProjectIsNoMoreUsingExplicitBacklog');
        $this->explicit_backlog_dao->shouldNotReceive('setProjectUsingExplicitBacklog');
        $this->milestone_report_criterion_dao->shouldNotReceive('updateAllUnplannedValueToAnyInProject');

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->with(101)
            ->andReturnFalse();

        $this->updater->updateScrumConfiguration($request);
    }

    public function testItActivatesExplicitBacklogManagement()
    {
        $request = new Codendi_Request([
            'use-explicit-top-backlog' => '1',
            'group_id' => '101'
        ]);

        $this->explicit_backlog_dao->shouldNotReceive('setProjectIsNoMoreUsingExplicitBacklog');
        $this->explicit_backlog_dao->shouldReceive('setProjectIsUsingExplicitBacklog')->once();
        $this->milestone_report_criterion_dao->shouldNotReceive('updateAllUnplannedValueToAnyInProject');

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->with(101)
            ->andReturnFalse();

        $this->updater->updateScrumConfiguration($request);
    }

    public function testItDeactivatesExplicitBacklogManagement()
    {
        $request = new Codendi_Request([
            'use-explicit-top-backlog' => '0',
            'group_id' => '101'
        ]);

        $this->explicit_backlog_dao->shouldReceive('setProjectIsNoMoreUsingExplicitBacklog')->once();
        $this->explicit_backlog_dao->shouldNotReceive('setProjectIsUsingExplicitBacklog');
        $this->milestone_report_criterion_dao->shouldReceive('updateAllUnplannedValueToAnyInProject')->once();

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->with(101)
            ->andReturnTrue();

        $this->updater->updateScrumConfiguration($request);
    }
}

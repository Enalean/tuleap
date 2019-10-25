<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

declare(strict_types = 1);

namespace Tuleap\AgileDashboard\REST\v1\Milestone;

use Mockery;
use ParagonIE\EasyDB\EasyDB;
use PHPUnit\Framework\TestCase;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\REST\v1\ResourcesPatcher;
use Tuleap\DB\DBConnection;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

class MilestoneElementAdderTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|DBConnection
     */
    private $db_connection;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|DBTransactionExecutorWithConnection
     */
    private $transaction_executor;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ArtifactsInExplicitBacklogDao
     */
    private $artifacts_in_explicit_backlog_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExplicitBacklogDao
     */
    private $explicit_backlog_dao;
    /**
     * @var MilestoneElementAdder
     */
    private $adder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ResourcesPatcher
     */
    private $resources_patcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resources_patcher                 = Mockery::mock(ResourcesPatcher::class);
        $this->explicit_backlog_dao              = Mockery::mock(ExplicitBacklogDao::class);
        $this->artifacts_in_explicit_backlog_dao = Mockery::mock(ArtifactsInExplicitBacklogDao::class);
        $this->db_connection                     = Mockery::mock(DBConnection::class);

        $this->transaction_executor = new DBTransactionExecutorPassthrough();

        $this->adder = new MilestoneElementAdder(
            $this->explicit_backlog_dao,
            $this->artifacts_in_explicit_backlog_dao,
            $this->resources_patcher,
            $this->transaction_executor
        );
    }

    public function testItAddsElementToMilestoneInExplicitMode(): void
    {
        $user    = Mockery::mock(\PFUser::class);
        $add     = [["id" => 112]];
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getGroupId')->once()->andReturn(102);

        $this->artifacts_in_explicit_backlog_dao->shouldReceive('addArtifactToProjectBacklog')->once();
        $this->resources_patcher->shouldReceive('removeArtifactFromSource')->once();

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->withArgs([102])
            ->andReturnTrue();

        $this->adder->addElementToBacklog($project, $add, $user);
    }

    public function testItAddsElementToMilestoneInStandardMode(): void
    {
        $user    = Mockery::mock(\PFUser::class);
        $add     = [["id" => 112]];
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getGroupId')->once()->andReturn(102);

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->withArgs([102])
            ->andReturnFalse();

        $this->resources_patcher->shouldReceive('removeArtifactFromSource')
            ->once()
            ->withArgs([$user, $add]);

        $this->adder->addElementToBacklog($project, $add, $user);
    }
}

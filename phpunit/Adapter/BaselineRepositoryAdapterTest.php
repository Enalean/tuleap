<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Baseline\Adapter;

require_once __DIR__ . '/../bootstrap.php';

use DateTime;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use ParagonIE\EasyDB\EasyDB;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Tracker_ArtifactFactory;
use Tuleap\Baseline\Baseline;
use Tuleap\Baseline\Factory\MilestoneFactory;
use Tuleap\GlobalLanguageMock;
use UserManager;

class BaselineRepositoryAdapterTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /** @var BaselineRepositoryAdapter */
    private $repository;

    /** @var EasyDB|MockInterface */
    private $db;

    /** @var UserManager|MockInterface */
    private $user_manager;

    /** @var Tracker_ArtifactFactory|MockInterface */
    private $artifact_factory;

    /** @before */
    public function createInstance()
    {
        $this->db               = Mockery::mock(EasyDB::class);
        $this->user_manager     = Mockery::mock(UserManager::class);
        $this->artifact_factory = Mockery::mock(Tracker_ArtifactFactory::class);

        $this->repository = new BaselineRepositoryAdapter(
            $this->db,
            $this->user_manager,
            $this->artifact_factory
        );
    }

    public function testFindByProject()
    {
        $milestone = MilestoneFactory::one()->build();
        $this->artifact_factory
            ->shouldReceive('getArtifactById')
            ->with(10)
            ->andReturn($milestone);

        $user = new PFUser();
        $this->user_manager
            ->shouldReceive('getUserById')
            ->with(22)
            ->andReturn($user);

        $this->db
            ->shouldReceive('safeQuery')
            ->with(Mockery::type('string'), [102, 10, 3])
            ->andReturn(
                [
                    [
                        "id"            => 1,
                        "name"          => "Persisted baseline",
                        "artifact_id"   => 10,
                        "user_id"       => 22,
                        "snapshot_date" => 1553176023,
                    ]
                ]
            );

        $project = Mockery::mock(Project::class)
            ->shouldReceive('getID')
            ->andReturn(102)
            ->getMock();

        $baselines = $this->repository->findByProject($project, 10, 3);

        $expected_baselines = [new Baseline(
            1,
            "Persisted baseline",
            $milestone,
            DateTime::createFromFormat('Y-m-d H:i:s', '2019-03-21 14:47:03'),
            $user
        )];
        $this->assertEquals($expected_baselines, $baselines);
    }

    public function testCountByProject()
    {
        $project = Mockery::mock(Project::class)
            ->shouldReceive('getID')
            ->andReturn(102)
            ->getMock();
        $this->db
            ->shouldReceive('safeQuery')
            ->with(Mockery::type('string'), [102])
            ->andReturn([[BaselineRepositoryAdapter::SQL_COUNT_ALIAS => 233]]);

        $count = $this->repository->countByProject($project);

        $this->assertEquals(233, $count);
    }
}

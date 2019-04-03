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

declare(strict_types=1);

namespace Tuleap\Baseline\Adapter;

require_once __DIR__ . '/../bootstrap.php';

use DateTimeImmutable;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use ParagonIE\EasyDB\EasyDB;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\Baseline\Baseline;
use Tuleap\Baseline\BaselineArtifactRepository;
use Tuleap\Baseline\Factory\BaselineArtifactFactory;
use Tuleap\Baseline\Support\DateTimeFactory;
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

    /** @var BaselineArtifactRepository|MockInterface */
    private $baseline_artifact_repository;

    /** @var AdapterPermissions|MockInterface */
    private $adapter_permissions;

    /** @var ClockAdapter|MockInterface */
    private $clock;

    /** @before */
    public function createInstance()
    {
        $this->db                           = Mockery::mock(EasyDB::class);
        $this->user_manager                 = Mockery::mock(UserManager::class);
        $this->baseline_artifact_repository = Mockery::mock(BaselineArtifactRepository::class);
        $this->adapter_permissions          = Mockery::mock(AdapterPermissions::class);
        $this->clock                        = Mockery::mock(ClockAdapter::class);
        $this->clock->allows(['at' => DateTimeFactory::one()]);

        $this->repository = new BaselineRepositoryAdapter(
            $this->db,
            $this->user_manager,
            $this->baseline_artifact_repository,
            $this->adapter_permissions,
            $this->clock
        );
    }

    /** @var PFUser */
    private $current_user;

    /** @before */
    public function createCurrentUser()
    {
        $this->current_user = new PFUser();
    }

    public function testFindById()
    {
        $artifact = BaselineArtifactFactory::one()->build();
        $this->baseline_artifact_repository
            ->shouldReceive('findById')
            ->with($this->current_user, 10)
            ->andReturn($artifact);

        $user = new PFUser();
        $this->user_manager
            ->shouldReceive('getUserById')
            ->with(22)
            ->andReturn($user);

        $this->db
            ->shouldReceive('safeQuery')
            ->with(Mockery::type('string'), [1])
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

        $this->adapter_permissions
            ->shouldReceive('canUserReadBaselineOnProject')
            ->andReturn(true);

        $baseline = $this->repository->findById($this->current_user, 1);

        $expected_baseline = new Baseline(
            1,
            "Persisted baseline",
            $artifact,
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2019-03-21 14:47:03'),
            $user
        );
        $this->assertEquals($expected_baseline, $baseline);
    }

    public function testFindByIdReturnsNullWhenNotFound()
    {
        $this->db
            ->shouldReceive('safeQuery')
            ->with(Mockery::type('string'), [1])
            ->andReturn([]);

        $baseline = $this->repository->findById($this->current_user, 1);

        $this->assertNull($baseline);
    }

    public function testFindByIdReturnsNullWhenGivenUserCannotReadBaselineOnProjetOfFoundBaseline()
    {
        $artifact = BaselineArtifactFactory::one()->build();
        $this->baseline_artifact_repository
            ->shouldReceive('findById')
            ->with($this->current_user, 10)
            ->andReturn($artifact);

        $user = new PFUser();
        $this->user_manager
            ->shouldReceive('getUserById')
            ->with(22)
            ->andReturn($user);

        $this->db
            ->shouldReceive('safeQuery')
            ->with(Mockery::type('string'), [1])
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

        $this->adapter_permissions
            ->shouldReceive('canUserReadBaselineOnProject')
            ->with($this->current_user, $artifact->getProject())
            ->andReturn(false);

        $baseline = $this->repository->findById($this->current_user, 1);

        $this->assertNull($baseline);
    }

    public function testFindByIdReturnsNullWhenBaselineArtifactIsNotFound()
    {
        $this->baseline_artifact_repository
            ->shouldReceive('findById')
            ->with($this->current_user, 10)
            ->andReturn(null);

        $user = new PFUser();
        $this->user_manager
            ->shouldReceive('getUserById')
            ->with(22)
            ->andReturn($user);

        $this->db
            ->shouldReceive('safeQuery')
            ->with(Mockery::type('string'), [1])
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

        $baseline = $this->repository->findById($this->current_user, 1);

        $this->assertNull($baseline);
    }

    public function testFindByProject()
    {
        $artifact = BaselineArtifactFactory::one()->build();
        $this->baseline_artifact_repository
            ->shouldReceive('findById')
            ->with($this->current_user, 10)
            ->andReturn($artifact);

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

        $this->adapter_permissions
            ->shouldReceive('canUserReadBaselineOnProject')
            ->andReturn(true);

        $baselines = $this->repository->findByProject($this->current_user, $project, 10, 3);

        $expected_baselines = [new Baseline(
            1,
            "Persisted baseline",
            $artifact,
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2019-03-21 14:47:03'),
            $user
        )];
        $this->assertEquals($expected_baselines, $baselines);
    }

    public function testFindByProjectIgnoresBaselinesWhereArtifactIsNotFound()
    {
        $this->baseline_artifact_repository
            ->shouldReceive('findById')
            ->with($this->current_user, 10)
            ->andReturn(null);

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

        $this->adapter_permissions
            ->shouldReceive('canUserReadBaselineOnProject')
            ->andReturn(true);

        $baselines = $this->repository->findByProject($this->current_user, $project, 10, 3);

        $this->assertEquals([], $baselines);
    }

    public function testCountByProject()
    {
        $project = Mockery::mock(Project::class)
            ->shouldReceive('getID')
            ->andReturn(102)
            ->getMock();
        $this->db
            ->shouldReceive('single')
            ->with(Mockery::type('string'), [102])
            ->andReturn(233);

        $count = $this->repository->countByProject($project);

        $this->assertEquals(233, $count);
    }
}

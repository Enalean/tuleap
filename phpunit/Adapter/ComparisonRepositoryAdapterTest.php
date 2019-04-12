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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use ParagonIE\EasyDB\EasyDB;
use PHPUnit\Framework\TestCase;
use Tuleap\Baseline\BaselineRepository;
use Tuleap\Baseline\Comparison;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\Baseline\Factory\ProjectFactory;
use Tuleap\Baseline\Factory\TransientComparisonFactory;
use Tuleap\Baseline\NotAuthorizedException;
use Tuleap\Baseline\Support\CurrentUserContext;

class ComparisonRepositoryAdapterTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use CurrentUserContext;

    /** @var ComparisonRepositoryAdapter */
    private $repository;

    /** @var EasyDB|MockInterface */
    private $db;

    /** @var AdapterPermissions|MockInterface */
    private $adapter_permissions;

    /** @var BaselineRepository|MockInterface */
    private $baseline_repository;

    /** @before */
    public function createInstance()
    {
        $this->db                  = Mockery::mock(EasyDB::class);
        $this->adapter_permissions = Mockery::mock(AdapterPermissions::class);
        $this->adapter_permissions->allows(['canUserAdministrateBaselineOnProject' => true])
            ->byDefault();
        $this->baseline_repository = Mockery::mock(BaselineRepository::class);

        $this->repository = new ComparisonRepositoryAdapter(
            $this->db,
            $this->adapter_permissions,
            $this->baseline_repository
        );
    }

    public function testAddReturnsComparisonBasedOnGivenTransientComparison()
    {
        $this->db
            ->allows('insertReturnId')
            ->andReturn(10);

        $transient_comparison = TransientComparisonFactory::one()->build();

        $comparison = $this->repository->add(
            $transient_comparison,
            $this->current_user
        );

        $this->assertEquals($transient_comparison->getName(), $comparison->getName());
        $this->assertEquals($transient_comparison->getComment(), $comparison->getComment());
        $this->assertEquals($transient_comparison->getBaseBaseline(), $comparison->getBaseBaseline());
        $this->assertEquals($transient_comparison->getComparedToBaseline(), $comparison->getComparedToBaseline());
    }

    public function testAddReturnsComparisonWithDatabaseId()
    {
        $this->db
            ->allows('insertReturnId')
            ->andReturn(10);

        $comparison = $this->repository->add(
            TransientComparisonFactory::one()->build(),
            $this->current_user
        );

        $this->assertEquals(10, $comparison->getId());
    }

    public function testAddThrowsIfGivenUserIsNotAuthorized()
    {
        $this->expectException(NotAuthorizedException::class);
        $project = ProjectFactory::one();

        $this->adapter_permissions
            ->allows('canUserAdministrateBaselineOnProject')
            ->with($this->current_user, $project)
            ->andReturn(false);

        $this->repository->add(
            TransientComparisonFactory::fromProject($project)->build(),
            $this->current_user
        );
    }

    public function testFindById()
    {
        $base_baseline = BaselineFactory::one()->build();
        $this->baseline_repository
            ->shouldReceive('findById')
            ->with($this->current_user, 1)
            ->andReturn($base_baseline);

        $compared_to_baseline = BaselineFactory::one()->build();
        $this->baseline_repository
            ->shouldReceive('findById')
            ->with($this->current_user, 2)
            ->andReturn($compared_to_baseline);

        $this->db
            ->shouldReceive('safeQuery')
            ->with(Mockery::type('string'), [1])
            ->andReturn(
                [
                    [
                        "id"                      => 1,
                        "name"                    => "Persisted comparison",
                        "comment"                 => null,
                        "base_baseline_id"        => 1,
                        "compared_to_baseline_id" => 2,
                    ]
                ]
            );

        $this->adapter_permissions
            ->shouldReceive('canUserReadBaselineOnProject')
            ->andReturn(true);

        $comparison = $this->repository->findById($this->current_user, 1);

        $expected_comparison = new Comparison(
            1,
            "Persisted comparison",
            null,
            $base_baseline,
            $compared_to_baseline
        );
        $this->assertEquals($expected_comparison, $comparison);
    }

    public function testFindByIdReturnsNullWhenNotFound()
    {
        $this->db
            ->shouldReceive('safeQuery')
            ->with(Mockery::type('string'), [1])
            ->andReturn([]);

        $comparison = $this->repository->findById($this->current_user, 1);

        $this->assertNull($comparison);
    }

    public function testFindByIdReturnsNullWhenGivenUserCannotReadBaselineOnProjetOfFoundBaseline()
    {
        $base_baseline = BaselineFactory::one()->build();
        $this->baseline_repository
            ->shouldReceive('findById')
            ->with($this->current_user, 1)
            ->andReturn($base_baseline);

        $compared_to_baseline = BaselineFactory::one()->build();
        $this->baseline_repository
            ->shouldReceive('findById')
            ->with($this->current_user, 2)
            ->andReturn($compared_to_baseline);

        $this->db
            ->shouldReceive('safeQuery')
            ->with(Mockery::type('string'), [1])
            ->andReturn(
                [
                    [
                        "id"                      => 1,
                        "name"                    => "Persisted comparison",
                        "comment"                 => null,
                        "base_baseline_id"        => 1,
                        "compared_to_baseline_id" => 2,
                    ]
                ]
            );

        $this->adapter_permissions
            ->shouldReceive('canUserReadBaselineOnProject')
            ->with($this->current_user, $base_baseline->getProject())
            ->andReturn(false);

        $baseline = $this->repository->findById($this->current_user, 1);

        $this->assertNull($baseline);
    }

    public function testFindByIdReturnsNullWhenBaseBaselineIsNotFound()
    {
        $this->baseline_repository
            ->shouldReceive('findById')
            ->with($this->current_user, 1)
            ->andReturn(null);

        $this->db
            ->shouldReceive('safeQuery')
            ->with(Mockery::type('string'), [1])
            ->andReturn(
                [
                    [
                        "id"                      => 1,
                        "name"                    => "Persisted comparison",
                        "comment"                 => null,
                        "base_baseline_id"        => 1,
                        "compared_to_baseline_id" => 2,
                    ]
                ]
            );

        $baseline = $this->repository->findById($this->current_user, 1);

        $this->assertNull($baseline);
    }

    public function testFindByIdReturnsNullWhenComparedToBaselineIsNotFound()
    {
        $this->baseline_repository
            ->shouldReceive('findById')
            ->with($this->current_user, 1)
            ->andReturn(BaselineFactory::one()->build());

        $this->baseline_repository
            ->shouldReceive('findById')
            ->with($this->current_user, 2)
            ->andReturn(null);

        $this->db
            ->shouldReceive('safeQuery')
            ->with(Mockery::type('string'), [1])
            ->andReturn(
                [
                    [
                        "id"                      => 1,
                        "name"                    => "Persisted comparison",
                        "comment"                 => null,
                        "base_baseline_id"        => 1,
                        "compared_to_baseline_id" => 2,
                    ]
                ]
            );

        $baseline = $this->repository->findById($this->current_user, 1);

        $this->assertNull($baseline);
    }
}

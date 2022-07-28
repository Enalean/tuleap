<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
use DateTimeInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use ParagonIE\EasyDB\EasyDB;
use PFUser;
use Tuleap\Baseline\Domain\Authorizations;
use Tuleap\Baseline\Domain\AuthorizationsImpl;
use Tuleap\Baseline\Domain\BaselineRepository;
use Tuleap\Baseline\Domain\Comparison;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\Baseline\Factory\ProjectFactory;
use Tuleap\Baseline\Factory\TransientComparisonFactory;
use Tuleap\Baseline\Support\CurrentUserContext;
use Tuleap\Baseline\Support\DateTimeFactory;
use UserManager;

class ComparisonRepositoryAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use CurrentUserContext;

    /** @var ComparisonRepositoryAdapter */
    private $repository;

    /** @var EasyDB|MockInterface */
    private $db;

    /** @var BaselineRepository|MockInterface */
    private $baseline_repository;

    /** @var UserManager|MockInterface */
    private $user_manager;

    /** @var Authorizations|MockInterface */
    private $authorizations;

    /** @var ClockAdapter|MockInterface */
    private $clock;

    /** @var DateTimeInterface */
    private $now;

    /** @before */
    public function createInstance()
    {
        $this->now = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2019-05-17 09:33:22');

        $this->db                  = Mockery::mock(EasyDB::class);
        $this->baseline_repository = Mockery::mock(BaselineRepository::class);
        $this->user_manager        = Mockery::mock(UserManager::class);
        $this->authorizations      = Mockery::mock(AuthorizationsImpl::class);
        $this->clock               = Mockery::mock(ClockAdapter::class);
        $this->clock->allows(['now' => $this->now]);

        $this->repository = new ComparisonRepositoryAdapter(
            $this->db,
            $this->baseline_repository,
            $this->user_manager,
            $this->authorizations,
            $this->clock
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
        $this->assertEquals($this->current_user, $comparison->getAuthor());
        $this->assertEquals($this->now, $comparison->getCreationDate());
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
                        "user_id"                 => 9,
                        "creation_date"           => 1553176023,
                    ],
                ]
            );

        $author = new PFUser();
        $this->user_manager
            ->shouldReceive('getUserById')
            ->with(9)
            ->andReturn($author);

        $creation_date = DateTimeFactory::one();
        $this->clock
            ->shouldReceive('at')
            ->with(1553176023)
            ->andReturn($creation_date);

        $this->authorizations
            ->shouldReceive('canReadComparison')
            ->andReturn(true);

        $comparison = $this->repository->findById($this->current_user, 1);

        $expected_comparison = new Comparison(
            1,
            "Persisted comparison",
            null,
            $base_baseline,
            $compared_to_baseline,
            $author,
            $creation_date
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

    public function testFindByIdReturnsNullWhenGivenUserCannotReadFoundBaseline()
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
                        "user_id"                 => 9,
                        "creation_date"           => 1553176023,
                    ],
                ]
            );

        $this->user_manager
            ->shouldReceive('getUserById')
            ->with(9)
            ->andReturn(new PFUser());

        $this->clock
            ->shouldReceive('at')
            ->with(1553176023)
            ->andReturn(DateTimeFactory::one());

        $this->authorizations
            ->shouldReceive('canReadComparison')
            ->andReturn(false);

        $comparison = $this->repository->findById($this->current_user, 1);

        $this->assertNull($comparison);
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
                        "user_id"                 => 9,
                        "creation_date"           => 1553176023,
                    ],
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
                        "user_id"                 => 9,
                        "creation_date"           => 1553176023,
                    ],
                ]
            );

        $baseline = $this->repository->findById($this->current_user, 1);

        $this->assertNull($baseline);
    }

    public function testFindByProject()
    {
        $this->db->allows()
            ->safeQuery(Mockery::type('string'), [102, 10, 3])
            ->andReturn(
                [
                    [
                        "id"                      => 1,
                        "name"                    => "Persisted comparison",
                        "comment"                 => null,
                        "base_baseline_id"        => 1,
                        "compared_to_baseline_id" => 2,
                        "user_id"                 => 22,
                        "creation_date"           => 1553176023,
                    ],
                ]
            );

        $base_baseline = BaselineFactory::one()->build();
        $this->baseline_repository->allows()
            ->findById($this->current_user, 1)
            ->andReturn($base_baseline);

        $compared_to_baseline = BaselineFactory::one()->build();
        $this->baseline_repository->allows()
            ->findById($this->current_user, 2)
            ->andReturn($compared_to_baseline);

        $user = new PFUser();
        $this->user_manager->allows()
            ->getUserById(22)
            ->andReturn($user);

        $creation_date = DateTimeFactory::one();
        $this->clock->allows()
            ->at(1553176023)
            ->andReturn($creation_date);

        $project = ProjectFactory::oneWithId(102);

        $baselines = $this->repository->findByProject($this->current_user, $project, 10, 3);

        $expected_baselines = [new Comparison(
            1,
            "Persisted comparison",
            null,
            $base_baseline,
            $compared_to_baseline,
            $user,
            $creation_date
        ),
        ];
        $this->assertEquals($expected_baselines, $baselines);
    }

    public function testFindByProjectIgnoresBaselinesWhereBaseBaselineIsNotFound()
    {
        $this->db->allows()
            ->safeQuery(Mockery::type('string'), [102, 10, 3])
            ->andReturn(
                [
                    [
                        "id"                      => 1,
                        "name"                    => "Persisted comparison",
                        "comment"                 => null,
                        "base_baseline_id"        => 1,
                        "compared_to_baseline_id" => 2,
                        "user_id"                 => 22,
                        "creation_date"           => 1553176023,
                    ],
                ]
            );

        $this->baseline_repository->allows()
            ->findById($this->current_user, 1)
            ->andReturn(null);

        $user = new PFUser();
        $this->user_manager->allows()
            ->getUserById(22)
            ->andReturn($user);


        $project = ProjectFactory::oneWithId(102);

        $baselines = $this->repository->findByProject($this->current_user, $project, 10, 3);

        $this->assertEquals([], $baselines);
    }

    public function testCountByProject()
    {
        $project = ProjectFactory::oneWithId(102);
        $this->db->allows()
            ->single(Mockery::type('string'), [102])
            ->andReturn(233);

        $count = $this->repository->countByProject($project);

        $this->assertEquals(233, $count);
    }
}

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
use ParagonIE\EasyDB\EasyDB;
use Tuleap\Baseline\Domain\Authorizations;
use Tuleap\Baseline\Domain\BaselineRepository;
use Tuleap\Baseline\Domain\Comparison;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\Baseline\Factory\ProjectFactory;
use Tuleap\Baseline\Factory\TransientComparisonFactory;
use Tuleap\Baseline\Support\CurrentUserContext;
use Tuleap\Baseline\Support\DateTimeFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use UserManager;

class ComparisonRepositoryAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use CurrentUserContext;

    /** @var ComparisonRepositoryAdapter */
    private $repository;

    /** @var EasyDB&\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var BaselineRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $baseline_repository;

    /** @var UserManager&\PHPUnit\Framework\MockObject\MockObject */
    private $user_manager;

    /** @var Authorizations&\PHPUnit\Framework\MockObject\MockObject */
    private $authorizations;

    /** @var ClockAdapter&\PHPUnit\Framework\MockObject\MockObject */
    private $clock;

    /** @var DateTimeInterface */
    private $now;

    /** @before */
    public function createInstance(): void
    {
        $now = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2019-05-17 09:33:22');
        self::assertInstanceOf(DateTimeImmutable::class, $now);
        $this->now = $now;

        $this->db                  = $this->createMock(EasyDB::class);
        $this->baseline_repository = $this->createMock(BaselineRepository::class);
        $this->user_manager        = $this->createMock(UserManager::class);
        $this->authorizations      = $this->createMock(AuthorizationsImpl::class);
        $this->clock               = $this->createMock(ClockAdapter::class);
        $this->clock->method('now')->willReturn($this->now);

        $this->repository = new ComparisonRepositoryAdapter(
            $this->db,
            $this->baseline_repository,
            $this->user_manager,
            $this->authorizations,
            $this->clock
        );
    }

    public function testAddReturnsComparisonBasedOnGivenTransientComparison(): void
    {
        $this->db->method('insertReturnId')->willReturn(10);

        $transient_comparison = TransientComparisonFactory::one()->build();

        $comparison = $this->repository->add(
            $transient_comparison,
            $this->current_user
        );

        self::assertEquals($transient_comparison->getName(), $comparison->getName());
        self::assertEquals($transient_comparison->getComment(), $comparison->getComment());
        self::assertEquals($transient_comparison->getBaseBaseline(), $comparison->getBaseBaseline());
        self::assertEquals($transient_comparison->getComparedToBaseline(), $comparison->getComparedToBaseline());
        self::assertEquals($this->current_user, $comparison->getAuthor());
        self::assertEquals($this->now, $comparison->getCreationDate());
    }

    public function testAddReturnsComparisonWithDatabaseId(): void
    {
        $this->db->method('insertReturnId')->willReturn(10);

        $comparison = $this->repository->add(
            TransientComparisonFactory::one()->build(),
            $this->current_user
        );

        self::assertEquals(10, $comparison->getId());
    }

    public function testFindById(): void
    {
        $base_baseline        = BaselineFactory::one()->build();
        $compared_to_baseline = BaselineFactory::one()->build();

        $this->baseline_repository
            ->method('findById')
            ->with()
            ->willReturnMap([
                [$this->current_user, 1, $base_baseline],
                [$this->current_user, 2, $compared_to_baseline],
            ]);

        $this->db
            ->method('safeQuery')
            ->with(self::isType('string'), [1])
            ->willReturn(
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

        $author = UserTestBuilder::buildWithId(9);
        $this->user_manager
            ->method('getUserById')
            ->with(9)
            ->willReturn($author);

        $creation_date = DateTimeFactory::one();
        $this->clock
            ->method('at')
            ->with(1553176023)
            ->willReturn($creation_date);

        $this->authorizations
            ->method('canReadComparison')
            ->willReturn(true);

        $comparison = $this->repository->findById($this->current_user, 1);

        $expected_comparison = new Comparison(
            1,
            "Persisted comparison",
            null,
            $base_baseline,
            $compared_to_baseline,
            UserProxy::fromUser($author),
            $creation_date
        );
        self::assertEquals($expected_comparison, $comparison);
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $this->db
            ->method('safeQuery')
            ->with(self::isType('string'), [1])
            ->willReturn([]);

        $comparison = $this->repository->findById($this->current_user, 1);

        self::assertNull($comparison);
    }

    public function testFindByIdReturnsNullWhenGivenUserCannotReadFoundBaseline(): void
    {
        $base_baseline        = BaselineFactory::one()->build();
        $compared_to_baseline = BaselineFactory::one()->build();

        $this->baseline_repository
            ->method('findById')
            ->with()
            ->willReturnMap([
                [$this->current_user, 1, $base_baseline],
                [$this->current_user, 2, $compared_to_baseline],
            ]);

        $this->db
            ->method('safeQuery')
            ->with(self::isType('string'), [1])
            ->willReturn(
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
            ->method('getUserById')
            ->with(9)
            ->willReturn(UserTestBuilder::aUser()->build());

        $this->clock
            ->method('at')
            ->with(1553176023)
            ->willReturn(DateTimeFactory::one());

        $this->authorizations
            ->method('canReadComparison')
            ->willReturn(false);

        $comparison = $this->repository->findById($this->current_user, 1);

        self::assertNull($comparison);
    }

    public function testFindByIdReturnsNullWhenBaseBaselineIsNotFound(): void
    {
        $this->baseline_repository
            ->method('findById')
            ->with($this->current_user, 1)
            ->willReturn(null);

        $this->db
            ->method('safeQuery')
            ->with(self::isType('string'), [1])
            ->willReturn(
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

        self::assertNull($baseline);
    }

    public function testFindByIdReturnsNullWhenComparedToBaselineIsNotFound(): void
    {
        $this->baseline_repository
            ->method('findById')
            ->with()
            ->willReturnMap([
                [$this->current_user, 1, BaselineFactory::one()->build()],
                [$this->current_user, 2, null],
            ]);

        $this->db
            ->method('safeQuery')
            ->with(self::isType('string'), [1])
            ->willReturn(
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

        self::assertNull($baseline);
    }

    public function testFindByProject(): void
    {
        $this->db->method('safeQuery')
            ->with(self::isType('string'), [102, 10, 3])
            ->willReturn(
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

        $base_baseline        = BaselineFactory::one()->build();
        $compared_to_baseline = BaselineFactory::one()->build();

        $this->baseline_repository
            ->method('findById')
            ->with()
            ->willReturnMap([
                [$this->current_user, 1, $base_baseline],
                [$this->current_user, 2, $compared_to_baseline],
            ]);

        $user = UserTestBuilder::buildWithId(22);
        $this->user_manager->method('getUserById')
            ->with(22)
            ->willReturn($user);

        $creation_date = DateTimeFactory::one();
        $this->clock->method('at')
            ->with(1553176023)
            ->willReturn($creation_date);

        $project = ProjectFactory::oneWithId(102);

        $baselines = $this->repository->findByProject($this->current_user, $project, 10, 3);

        $expected_baselines = [new Comparison(
            1,
            "Persisted comparison",
            null,
            $base_baseline,
            $compared_to_baseline,
            UserProxy::fromUser($user),
            $creation_date
        ),
        ];
        self::assertEquals($expected_baselines, $baselines);
    }

    public function testFindByProjectIgnoresBaselinesWhereBaseBaselineIsNotFound(): void
    {
        $this->db->method('safeQuery')
            ->with(self::isType('string'), [102, 10, 3])
            ->willReturn(
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

        $this->baseline_repository
            ->method('findById')
            ->with($this->current_user, 1)
            ->willReturn(null);

        $user = UserTestBuilder::aUser()->build();
        $this->user_manager->method('getUserById')
            ->with(22)
            ->willReturn($user);

        $project = ProjectFactory::oneWithId(102);

        $baselines = $this->repository->findByProject($this->current_user, $project, 10, 3);

        self::assertEquals([], $baselines);
    }

    public function testCountByProject(): void
    {
        $project = ProjectFactory::oneWithId(102);
        $this->db->method('single')
            ->with(self::isType('string'), [102])
            ->willReturn(233);

        $count = $this->repository->countByProject($project);

        self::assertEquals(233, $count);
    }
}

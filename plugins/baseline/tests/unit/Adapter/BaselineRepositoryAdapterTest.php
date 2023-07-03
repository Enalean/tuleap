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
use ParagonIE\EasyDB\EasyDB;
use Tuleap\Baseline\Domain\Authorizations;
use Tuleap\Baseline\Domain\Baseline;
use Tuleap\Baseline\Domain\BaselineArtifactRepository;
use Tuleap\Baseline\Domain\UserIdentifier;
use Tuleap\Baseline\Factory\BaselineArtifactFactory;
use Tuleap\Baseline\Factory\ProjectFactory;
use Tuleap\Baseline\Support\DateTimeFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use UserManager;

final class BaselineRepositoryAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /** @var BaselineRepositoryAdapter */
    private $repository;

    /** @var EasyDB&\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var UserManager&\PHPUnit\Framework\MockObject\MockObject */
    private $user_manager;

    /** @var BaselineArtifactRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $baseline_artifact_repository;

    /** @var Authorizations&\PHPUnit\Framework\MockObject\MockObject */
    private $authorizations;

    /** @var ClockAdapter&\PHPUnit\Framework\MockObject\MockObject */
    private $clock;
    private UserIdentifier $current_user;
    private \PFUser $user;

    /** @before */
    public function createInstance(): void
    {
        $this->db                           = $this->createMock(EasyDB::class);
        $this->user_manager                 = $this->createMock(UserManager::class);
        $this->baseline_artifact_repository = $this->createMock(BaselineArtifactRepository::class);
        $this->authorizations               = $this->createMock(AuthorizationsImpl::class);
        $this->clock                        = $this->createMock(ClockAdapter::class);
        $this->clock->method('at')->willReturn(DateTimeFactory::one());

        $this->repository = new BaselineRepositoryAdapter(
            $this->db,
            $this->user_manager,
            $this->baseline_artifact_repository,
            $this->authorizations,
            $this->clock
        );

        $this->user         = UserTestBuilder::aUser()->build();
        $this->current_user = UserProxy::fromUser($this->user);
    }

    public function testFindById(): void
    {
        $artifact = BaselineArtifactFactory::one()->build();
        $this->baseline_artifact_repository
            ->method('findById')
            ->with($this->current_user, 10)
            ->willReturn($artifact);

        $user = UserTestBuilder::aUser()->build();
        $this->user_manager
            ->method('getUserById')
            ->with()
            ->willReturnMap([
                [$this->current_user->getId(), $this->user],
                [22, $user],
            ]);

        $this->db
            ->method('safeQuery')
            ->with(self::isType('string'), [1])
            ->willReturn(
                [
                    [
                        "id"            => 1,
                        "name"          => "Persisted baseline",
                        "artifact_id"   => 10,
                        "user_id"       => 22,
                        "snapshot_date" => 1553176023,
                    ],
                ]
            );

        $this->authorizations->method('canReadBaseline')->willReturn(true);

        $baseline = $this->repository->findById($this->current_user, 1);

        $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2019-03-21 14:47:03');
        self::assertInstanceOf(DateTimeImmutable::class, $date);

        $expected_baseline = new Baseline(
            1,
            "Persisted baseline",
            $artifact,
            $date,
            UserProxy::fromUser($user),
        );

        self::assertEquals($expected_baseline, $baseline);
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $this->db
            ->method('safeQuery')
            ->with(self::isType('string'), [1])
            ->willReturn([]);

        $baseline = $this->repository->findById($this->current_user, 1);

        self::assertNull($baseline);
    }

    public function testFindByIdReturnsNullWhenGivenUserCannotReadFoundBaseline(): void
    {
        $artifact = BaselineArtifactFactory::one()->build();
        $this->baseline_artifact_repository
            ->method('findById')
            ->with($this->current_user, 10)
            ->willReturn($artifact);

        $user = UserTestBuilder::aUser()->build();
        $this->user_manager
            ->method('getUserById')
            ->with()
            ->willReturnMap([
                [$this->current_user->getId(), $this->user],
                [22, $user],
            ]);

        $this->db
            ->method('safeQuery')
            ->with(self::isType('string'), [1])
            ->willReturn(
                [
                    [
                        "id"            => 1,
                        "name"          => "Persisted baseline",
                        "artifact_id"   => 10,
                        "user_id"       => 22,
                        "snapshot_date" => 1553176023,
                    ],
                ]
            );

        $this->authorizations->method('canReadBaseline')->willReturn(false);

        $baseline = $this->repository->findById($this->current_user, 1);

        self::assertNull($baseline);
    }

    public function testFindByIdReturnsNullWhenBaselineArtifactIsNotFound(): void
    {
        $this->baseline_artifact_repository
            ->method('findById')
            ->with($this->current_user, 10)
            ->willReturn(null);

        $user = UserTestBuilder::aUser()->build();
        $this->user_manager
            ->method('getUserById')
            ->with()
            ->willReturnMap([
                [$this->current_user->getId(), $this->user],
                [22, $user],
            ]);

        $this->db
            ->method('safeQuery')
            ->with(self::isType('string'), [1])
            ->willReturn(
                [
                    [
                        "id"            => 1,
                        "name"          => "Persisted baseline",
                        "artifact_id"   => 10,
                        "user_id"       => 22,
                        "snapshot_date" => 1553176023,
                    ],
                ]
            );

        $baseline = $this->repository->findById($this->current_user, 1);

        self::assertNull($baseline);
    }

    public function testFindByProject(): void
    {
        $artifact = BaselineArtifactFactory::one()->build();
        $this->baseline_artifact_repository
            ->method('findById')
            ->with($this->current_user, 10)
            ->willReturn($artifact);

        $user = UserTestBuilder::aUser()->build();
        $this->user_manager
            ->method('getUserById')
            ->with()
            ->willReturnMap([
                [$this->current_user->getId(), $this->user],
                [22, $user],
            ]);

        $this->db
            ->method('safeQuery')
            ->with(self::isType('string'), [102, 10, 3])
            ->willReturn(
                [
                    [
                        "id"            => 1,
                        "name"          => "Persisted baseline",
                        "artifact_id"   => 10,
                        "user_id"       => 22,
                        "snapshot_date" => 1553176023,
                    ],
                ]
            );

        $project = ProjectFactory::oneWithId(102);

        $baselines = $this->repository->findByProject($this->current_user, $project, 10, 3);

        $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2019-03-21 14:47:03');
        self::assertInstanceOf(DateTimeImmutable::class, $date);

        $expected_baselines = [
            new Baseline(
                1,
                "Persisted baseline",
                $artifact,
                $date,
                UserProxy::fromUser($user),
            ),
        ];
        $this->assertEquals($expected_baselines, $baselines);
    }

    public function testFindByProjectIgnoresBaselinesWhereArtifactIsNotFound(): void
    {
        $this->baseline_artifact_repository
            ->method('findById')
            ->with($this->current_user, 10)
            ->willReturn(null);

        $user = UserTestBuilder::aUser()->build();
        $this->user_manager
            ->method('getUserById')
            ->with()
            ->willReturnMap([
                [$this->current_user->getId(), $this->user],
                [22, $user],
            ]);

        $this->db
            ->method('safeQuery')
            ->with(self::isType('string'), [102, 10, 3])
            ->willReturn(
                [
                    [
                        "id"            => 1,
                        "name"          => "Persisted baseline",
                        "artifact_id"   => 10,
                        "user_id"       => 22,
                        "snapshot_date" => 1553176023,
                    ],
                ]
            );

        $project = ProjectFactory::oneWithId(102);

        $baselines = $this->repository->findByProject($this->current_user, $project, 10, 3);

        self::assertEquals([], $baselines);
    }

    public function testCountByProject(): void
    {
        $project = ProjectFactory::oneWithId(102);
        $this->db
            ->method('single')
            ->with(self::isType('string'), [102])
            ->willReturn(233);

        $count = $this->repository->countByProject($project);

        self::assertEquals(233, $count);
    }
}

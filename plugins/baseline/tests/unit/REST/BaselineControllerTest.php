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

namespace Tuleap\Baseline\REST;

require_once __DIR__ . "/../bootstrap.php";

use DateTimeImmutable;
use Tuleap\Baseline\Adapter\UserProxy;
use Tuleap\Baseline\Domain\BaselineArtifact;
use Tuleap\Baseline\Domain\BaselineArtifactRepository;
use Tuleap\Baseline\Domain\BaselineService;
use Tuleap\Baseline\Domain\CurrentUserProvider;
use Tuleap\Baseline\Factory\BaselineArtifactFactory;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\Baseline\Domain\NotAuthorizedException;
use Tuleap\Baseline\REST\Exception\ForbiddenRestException;
use Tuleap\Baseline\REST\Exception\NotFoundRestException;
use Tuleap\REST\I18NRestException;
use Tuleap\Test\Builders\UserTestBuilder;

final class BaselineControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private BaselineController $controller;

    /**
     * @var CurrentUserProvider&\PHPUnit\Framework\MockObject\MockObject
     */
    private $current_user_provider;

    /**
     * @var BaselineArtifactRepository&\PHPUnit\Framework\MockObject\MockObject
     */
    private $baseline_artifact_repository;

    /**
     * @var BaselineService&\PHPUnit\Framework\MockObject\MockObject
     */
    private $baseline_service;
    private UserProxy $current_user;

    /**
     * @before
     */
    public function createInstance(): void
    {
        $this->current_user = UserProxy::fromUser(UserTestBuilder::aUser()->build());

        $this->current_user_provider = $this->createMock(CurrentUserProvider::class);

        $this->baseline_artifact_repository = $this->createMock(BaselineArtifactRepository::class);
        $this->baseline_service             = $this->createMock(BaselineService::class);

        $this->controller = new BaselineController(
            $this->current_user_provider,
            $this->baseline_service,
            $this->baseline_artifact_repository
        );
    }

    private BaselineArtifact $an_artifact;

    /** @before */
    public function createAnArtifact(): void
    {
        $this->an_artifact = BaselineArtifactFactory::one()->build();
    }

    public function testPostCreatesNewBaseline(): void
    {
        $this->mockDefaultGetCurrentUser();

        $this->baseline_artifact_repository
            ->method('findById')
            ->with($this->current_user, 3)
            ->willReturn($this->an_artifact);

        $this->baseline_service
            ->expects(self::atLeast(1))
            ->method('create')
            ->willReturn(BaselineFactory::one()->build());

        $this->controller->post('new baseline', 3, null);
    }

    public function testPostReturnsRepresentationOfCreatedBaseline(): void
    {
        $this->mockDefaultGetCurrentUser();

        $artifact = BaselineArtifactFactory::one()
            ->id(3)
            ->build();

        $this->baseline_artifact_repository
            ->method('findById')
            ->with($this->current_user, 3)
            ->willReturn($artifact);

        $this->baseline_service
            ->method('create')
            ->willReturn(
                BaselineFactory::one()
                    ->id(11)
                    ->name('first baseline')
                    ->artifact($artifact)
                    ->author(UserProxy::fromUser(UserTestBuilder::aUser()->withId(99)->build()))
                    ->build()
            );

        $representation = $this->controller->post('first baseline', 3, null);

        self::assertEquals(11, $representation->id);
        self::assertEquals('first baseline', $representation->name);
        self::assertEquals(3, $representation->artifact_id);
        self::assertEquals(99, $representation->author_id);
    }

    public function testPostReturnsRepresentationOfBaselineSnapshotDateWithUserTimeZone(): void
    {
        $current_user = UserProxy::fromUser(
            UserTestBuilder::aUser()
                ->withId(99)
                ->withTimezone('GMT+2')
                ->build()
        );

        $this->current_user_provider
            ->method('getUser')
            ->willReturn($current_user);

        $this->baseline_artifact_repository
            ->method('findById')
            ->with($current_user, 3)
            ->willReturn($this->an_artifact);

        $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2019-03-21 14:47:03');
        self::assertInstanceOf(DateTimeImmutable::class, $date);

        $this->baseline_service
            ->method('create')
            ->willReturn(
                BaselineFactory::one()
                    ->snapshotDate($date)
                    ->build()
            );

        $representation = $this->controller->post('first baseline', 3, null);

        self::assertEquals('2019-03-21T14:47:03+01:00', $representation->snapshot_date);
    }

    public function testPostThrows403WhenNotAuthorized(): void
    {
        $this->mockDefaultGetCurrentUser();

        $this->expectException(ForbiddenRestException::class);

        $this->baseline_artifact_repository
            ->method('findById')
            ->willReturn($this->an_artifact);

        $this->baseline_service
            ->method('create')
            ->willThrowException(new NotAuthorizedException('not authorized'));

        $this->controller->post('new baseline', 3, null);
    }

    public function testPostThrows400WhenGivenDateIsMalFormed(): void
    {
        $this->mockDefaultGetCurrentUser();

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->baseline_artifact_repository
            ->method('findById')
            ->willReturn($this->an_artifact);

        $this->controller->post('new baseline', 3, 'not a date');
    }

    public function testGetById(): void
    {
        $this->mockDefaultGetCurrentUser();

        $baseline = BaselineFactory::one()
            ->id(1)
            ->name('found baseline')
            ->artifact(BaselineArtifactFactory::one()->id(3)->build())
            ->author(UserProxy::fromUser(UserTestBuilder::aUser()->withId(99)->build()))
            ->build();
        $this->baseline_service
            ->method('findById')
            ->with($this->current_user, 1)
            ->willReturn($baseline);

        $representation = $this->controller->getById(1);

        self::assertEquals(1, $representation->id);
        self::assertEquals('found baseline', $representation->name);
        self::assertEquals(3, $representation->artifact_id);
        self::assertEquals(99, $representation->author_id);
    }

    public function testGetByIdThrows404WhenNoBaselineFound(): void
    {
        $this->mockDefaultGetCurrentUser();

        $this->expectException(NotFoundRestException::class);

        $this->baseline_service
            ->method('findById')
            ->willReturn(null);

        $this->controller->getById(1);
    }

    public function testDelete(): void
    {
        $this->mockDefaultGetCurrentUser();

        $baseline = BaselineFactory::one()
            ->id(2)
            ->build();

        $this->baseline_service
            ->method('findById')
            ->willReturn($baseline);

        $this->baseline_service
            ->expects(self::atLeast(1))
            ->method('delete')
            ->with($this->current_user, $baseline);

        $this->controller->delete(2);
    }

    public function testDeleteThrows404WhenBaselineNotFound(): void
    {
        $this->mockDefaultGetCurrentUser();

        $this->expectException(NotFoundRestException::class);

        $this->baseline_service
            ->method('findById')
            ->willReturn(null);

        $this->controller->delete(2);
    }

    public function testDeleteThrows403WhenNotAllowed(): void
    {
        $this->mockDefaultGetCurrentUser();

        $this->expectException(ForbiddenRestException::class);

        $this->baseline_service
            ->method('findById')
            ->willReturn(
                BaselineFactory::one()->build()
            );

        $this->baseline_service
            ->method('delete')
            ->willThrowException(new NotAuthorizedException('not allowed'));

        $this->controller->delete(2);
    }

    private function mockDefaultGetCurrentUser(): void
    {
        $this->current_user_provider->method('getUser')->willReturn($this->current_user);
    }
}

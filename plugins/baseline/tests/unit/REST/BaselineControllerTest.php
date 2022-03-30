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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PFUser;
use Tuleap\Baseline\Domain\BaselineArtifact;
use Tuleap\Baseline\Domain\BaselineArtifactRepository;
use Tuleap\Baseline\Domain\BaselineService;
use Tuleap\Baseline\Domain\CurrentUserProvider;
use Tuleap\Baseline\Factory\BaselineArtifactFactory;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\Baseline\Domain\NotAuthorizedException;
use Tuleap\Baseline\REST\Exception\ForbiddenRestException;
use Tuleap\Baseline\REST\Exception\NotFoundRestException;
use Tuleap\Baseline\Support\CurrentUserContext;
use Tuleap\REST\I18NRestException;

class BaselineControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use CurrentUserContext;

    /**
     * @var BaselineController
     */
    private $controller;

    /**
     * @var CurrentUserProvider|MockInterface
     */
    private $current_user_provider;

    /**
     * @var BaselineArtifactRepository|MockInterface
     */
    private $baseline_artifact_repository;

    /**
     * @var BaselineService|MockInterface
     */
    private $baseline_service;

    /**
     * @before
     */
    public function createInstance()
    {
        $this->current_user_provider = Mockery::mock(CurrentUserProvider::class);
        $this->current_user_provider
            ->allows(['getUser' => $this->current_user])
            ->byDefault();
        $this->baseline_artifact_repository = Mockery::mock(BaselineArtifactRepository::class);
        $this->baseline_service             = Mockery::mock(BaselineService::class);

        $this->controller = new BaselineController(
            $this->current_user_provider,
            $this->baseline_service,
            $this->baseline_artifact_repository
        );
    }

    /** @var BaselineArtifact */
    private $an_artifact;

    /** @before */
    public function createAnArtifact(): void
    {
        $this->an_artifact = BaselineArtifactFactory::one()->build();
    }

    public function testPostCreatesNewBaseline()
    {
        $this->baseline_artifact_repository
            ->shouldReceive('findById')
            ->with($this->current_user, 3)
            ->andReturn($this->an_artifact);

        $this->baseline_service
            ->shouldReceive('create')
            ->andReturn(BaselineFactory::one()->build());

        $this->controller->post('new baseline', 3, null);
    }

    public function testPostReturnsRepresentationOfCreatedBaseline()
    {
        $artifact = BaselineArtifactFactory::one()
            ->id(3)
            ->build();

        $this->baseline_artifact_repository
            ->shouldReceive('findById')
            ->with($this->current_user, 3)
            ->andReturn($artifact);

        $this->baseline_service
            ->shouldReceive('create')
            ->andReturn(
                BaselineFactory::one()
                    ->id(11)
                    ->name('first baseline')
                    ->artifact($artifact)
                    ->author(new PFUser(['user_id' => 99]))
                    ->build()
            );

        $representation = $this->controller->post('first baseline', 3, null);

        $this->assertEquals(11, $representation->id);
        $this->assertEquals('first baseline', $representation->name);
        $this->assertEquals(3, $representation->artifact_id);
        $this->assertEquals(99, $representation->author_id);
    }

    public function testPostReturnsRepresentationOfBaselineSnapshotDateWithUserTimeZone()
    {
        $current_user = new PFUser(['user_id' => 99, 'timezone' => 'GMT+2']);
        $this->current_user_provider
            ->shouldReceive('getUser')
            ->andReturn($current_user);

        $this->baseline_artifact_repository
            ->shouldReceive('findById')
            ->with($current_user, 3)
            ->andReturn($this->an_artifact);

        $this->baseline_service
            ->shouldReceive('create')
            ->andReturn(
                BaselineFactory::one()
                    ->snapshotDate(DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2019-03-21 14:47:03'))
                    ->build()
            );

        $representation = $this->controller->post('first baseline', 3, null);

        $this->assertEquals('2019-03-21T14:47:03+01:00', $representation->snapshot_date);
    }

    public function testPostThrows403WhenNotAuthorized()
    {
        $this->expectException(ForbiddenRestException::class);

        $this->baseline_artifact_repository
            ->shouldReceive('findById')
            ->andReturn($this->an_artifact);

        $this->baseline_service
            ->shouldReceive('create')
            ->andThrow(new NotAuthorizedException('not authorized'));

        $this->controller->post('new baseline', 3, null);
    }

    public function testPostThrows400WhenGivenDateIsMalFormed()
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->baseline_artifact_repository
            ->shouldReceive('findById')
            ->andReturn($this->an_artifact);

        $this->controller->post('new baseline', 3, 'not a date');
    }

    public function testGetById()
    {
        $baseline = BaselineFactory::one()
            ->id(1)
            ->name('found baseline')
            ->artifact(BaselineArtifactFactory::one()->id(3)->build())
            ->author(new PFUser(['user_id' => 99]))
            ->build();
        $this->baseline_service
            ->shouldReceive('findById')
            ->with($this->current_user, 1)
            ->andReturn($baseline);

        $representation = $this->controller->getById(1);

        $this->assertEquals(1, $representation->id);
        $this->assertEquals('found baseline', $representation->name);
        $this->assertEquals(3, $representation->artifact_id);
        $this->assertEquals(99, $representation->author_id);
    }

    public function testGetByIdThrows404WhenNoBaselineFound()
    {
        $this->expectException(NotFoundRestException::class);

        $this->baseline_service
            ->shouldReceive('findById')
            ->andReturn(null);

        $this->controller->getById(1);
    }

    public function testDelete()
    {
        $baseline = BaselineFactory::one()
            ->id(2)
            ->build();

        $this->baseline_service
            ->shouldReceive('findById')
            ->andReturn($baseline);

        $this->baseline_service
            ->shouldReceive('delete')
            ->with($this->current_user, $baseline);

        $this->controller->delete(2);
    }

    public function testDeleteThrows404WhenBaselineNotFound()
    {
        $this->expectException(NotFoundRestException::class);

        $this->baseline_service
            ->shouldReceive('findById')
            ->andReturn(null);

        $this->controller->delete(2);
    }

    public function testDeleteThrows403WhenNotAllowed()
    {
        $this->expectException(ForbiddenRestException::class);

        $this->baseline_service
            ->shouldReceive('findById')
            ->andReturn(
                BaselineFactory::one()->build()
            );

        $this->baseline_service
            ->shouldReceive('delete')
            ->andThrow(new NotAuthorizedException('not allowed'));

        $this->controller->delete(2);
    }
}

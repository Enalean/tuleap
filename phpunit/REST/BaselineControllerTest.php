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

namespace Tuleap\Baseline\REST;

require_once __DIR__ . "/../bootstrap.php";

use DateTime;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact;
use Tuleap\Baseline\BaselineService;
use Tuleap\Baseline\CurrentUserProvider;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\Baseline\MilestoneRepository;
use Tuleap\Baseline\NotAuthorizedException;
use Tuleap\GlobalLanguageMock;
use Tuleap\REST\I18NRestException;

class BaselineControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var BaselineController
     */
    private $controller;

    /**
     * @var CurrentUserProvider|MockInterface
     */
    private $current_user_provider;

    /**
     * @var MilestoneRepository|MockInterface
     */
    private $milestone_repository;

    /**
     * @var BaselineService|MockInterface
     */
    private $baseline_service;

    /** @var Tracker_Artifact|MockInterface */
    private $a_milestone;

    /** @var PFUser */
    private $current_user;

    /**
     * @before
     */
    public function createInstance()
    {
        $this->current_user_provider = Mockery::mock(CurrentUserProvider::class);
        $this->milestone_repository  = Mockery::mock(MilestoneRepository::class);
        $this->baseline_service      = Mockery::mock(BaselineService::class);

        $this->controller = new BaselineController(
            $this->current_user_provider,
            $this->milestone_repository,
            $this->baseline_service
        );

        $this->current_user = new PFUser(['user_id' => 99]);
        $this->current_user_provider
            ->shouldReceive('getUser')
            ->andReturn($this->current_user)
            ->byDefault();
    }

    /** @before */
    public function createAMilestone(): void
    {
        $this->a_milestone = Mockery::mock(Tracker_Artifact::class);
    }

    public function testPostCreatesNewBaseline()
    {
        $this->milestone_repository
            ->shouldReceive('findById')
            ->with(3)
            ->andReturn($this->a_milestone);

        $this->baseline_service
            ->shouldReceive('create')
            ->andReturn(BaselineFactory::one()->build());

        $this->controller->post('new baseline', 3);
    }

    public function testPostReturnsRepresentationOfCreatedBaseline()
    {
        $this->milestone_repository
            ->shouldReceive('findById')
            ->with(3)
            ->andReturn($this->a_milestone);

        $this->a_milestone
            ->shouldReceive('getId')
            ->andReturn(3)
            ->getMock();

        $this->baseline_service
            ->shouldReceive('create')
            ->andReturn(
                BaselineFactory::one()
                    ->id(11)
                    ->name('first baseline')
                    ->milestone($this->a_milestone)
                    ->author(new PFUser(['user_id' => 99]))
                    ->build()
            );

        $representation = $this->controller->post('first baseline', 3);

        $this->assertEquals(11, $representation->id);
        $this->assertEquals('first baseline', $representation->name);
        $this->assertEquals(3, $representation->milestone_id);
        $this->assertEquals(99, $representation->author_id);
    }

    public function testPostReturnsRepresentationOfBaselineCreationDateWithUserTimeZone()
    {
        $current_user = new PFUser(['user_id' => 99, 'timezone' => 'GMT+2']);
        $this->current_user_provider
            ->shouldReceive('getUser')
            ->andReturn($current_user);

        $this->milestone_repository
            ->shouldReceive('findById')
            ->with(3)
            ->andReturn($this->a_milestone);

        $this->baseline_service
            ->shouldReceive('create')
            ->andReturn(
                BaselineFactory::one()
                    ->creationDate(DateTime::createFromFormat('Y-m-d H:i:s', '2019-03-21 14:47:03'))
                    ->build()
            );

        $representation = $this->controller->post('first baseline', 3);

        $this->assertEquals('2019-03-21T14:47:03+01:00', $representation->creation_date);
    }

    public function testPostThrows403WhenNotAuthorized()
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(403);

        $this->milestone_repository
            ->shouldReceive('findById')
            ->andReturn($this->a_milestone);

        $this->baseline_service
            ->shouldReceive('create')
            ->andThrow(new NotAuthorizedException('not authorized'));

        $this->controller->post('new baseline', 3);
    }
}

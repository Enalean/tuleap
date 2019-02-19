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

namespace Tuleap\Baseline;

use DateTime;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact;
use Tuleap\GlobalLanguageMock;
use Tuleap\REST\UserManager;

class BaselineServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /** @var BaselineService|MockInterface */
    private $service;

    /** @var UserManager|MockInterface */
    private $user_manager;

    /** @var Clock|MockInterface */
    private $clock;

    /** @var BaselineRepository|MockInterface */
    private $baseline_repository;

    /** @var PFUser */
    private $current_user;

    /** @var DateTime */
    private $now;

    /**
     * @before
     */
    public function createInstanceAndMockDefaultBehaviors()
    {
        $this->user_manager        = Mockery::mock(UserManager::class);
        $this->clock               = Mockery::mock(Clock::class);
        $this->baseline_repository = Mockery::mock(BaselineRepository::class);

        $this->service = new BaselineService(
            $this->user_manager,
            $this->clock,
            $this->baseline_repository
        );

        $this->current_user = $this->buildAUser();
        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->andReturn($this->current_user);

        $this->now = $this->buildADateTime();
        $this->clock
            ->shouldReceive('now')
            ->andReturn($this->now);
    }

    public function testCreate()
    {
        $transient_baseline = new TransientBaseline('name', $this->buildARelease());

        $created_baseline = $this->buildABaseline();
        $this->baseline_repository
            ->shouldReceive('create')
            ->with($transient_baseline, $this->current_user, $this->now)
            ->andReturn($created_baseline);

        $baseline = $this->service->create($transient_baseline);

        $this->assertEquals($baseline, $created_baseline);
    }

    private function buildADateTime(): DateTime
    {
        return DateTime::createFromFormat('Y-m-d H:i:s', '2019-03-15 15:16:17');
    }

    private function buildABaseline(): Baseline
    {
        return new Baseline(
            1,
            'Release startup',
            $this->buildARelease(),
            $this->buildAUser(),
            $this->buildADateTime()
        );
    }

    private function buildAUser(): PFUser
    {
        return new PFUser();
    }

    /**
     * @return Tracker_Artifact|MockInterface
     */
    private function buildARelease()
    {
        return Mockery::mock(Tracker_Artifact::class);
    }
}

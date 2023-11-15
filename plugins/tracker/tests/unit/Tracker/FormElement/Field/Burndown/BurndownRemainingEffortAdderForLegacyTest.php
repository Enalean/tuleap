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
 */

namespace Tuleap\Tracker\FormElement\Field\Burndown;

use DateTime;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Tracker_Chart_Data_Burndown;
use Tracker_FormElement_Field_Burndown;
use Tracker_UserWithReadAllPermission;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;
use Tuleap\Tracker\UserWithReadAllPermissionBuilder;

class BurndownRemainingEffortAdderForLegacyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var BurndownRemainingEffortAdderForLegacy
     */
    private $adder;

    private $field_retriever;
    private $user_builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->field_retriever = Mockery::mock(ChartConfigurationFieldRetriever::class);
        $this->user_builder    = Mockery::mock(UserWithReadAllPermissionBuilder::class);

        $this->adder = new BurndownRemainingEffortAdderForLegacy(
            $this->field_retriever,
            $this->user_builder
        );
    }

    public function testItAddsRemainingEffortDataForLegacy()
    {
        $capacity    = 10;
        $duration    = 4;
        $date        = new DateTime('2019-06-17');
        $date_period = DatePeriodWithoutWeekEnd::buildFromDuration($date->getTimestamp(), $duration);

        $burndown_data = new Tracker_Chart_Data_Burndown($date_period, $capacity);

        $artifact = Mockery::mock(Artifact::class);
        $user     = Mockery::mock(PFUser::class);

        $burndown_field = Mockery::mock(Tracker_FormElement_Field_Burndown::class);

        $this->field_retriever->shouldReceive('getBurndownRemainingEffortField')
            ->with($artifact, $user)
            ->once()
            ->andReturn($burndown_field);

        $all_perms_user = Mockery::mock(Tracker_UserWithReadAllPermission::class);
        $this->user_builder->shouldReceive('buildUserWithReadAllPermission')
            ->with($user)
            ->andReturn($all_perms_user)
            ->times(5);

        $burndown_field->shouldReceive('getCachedValue')
            ->with($all_perms_user, $artifact, 1560808799)
            ->once()
            ->andReturn(9);

        $burndown_field->shouldReceive('getCachedValue')
            ->with($all_perms_user, $artifact, 1560895199)
            ->once()
            ->andReturn(7);

        $burndown_field->shouldReceive('getCachedValue')
            ->with($all_perms_user, $artifact, 1560981599)
            ->once()
            ->andReturn(4);

        $burndown_field->shouldReceive('getCachedValue')
            ->with($all_perms_user, $artifact, 1561067999)
            ->once()
            ->andReturn(2);

        $burndown_field->shouldReceive('getCachedValue')
            ->with($all_perms_user, $artifact, 1561154399)
            ->once()
            ->andReturn(0);

        $this->adder->addRemainingEffortDataForLegacy(
            $burndown_data,
            $artifact,
            $user
        );

        $this->assertCount(5, $burndown_data->getRemainingEffortsAtDate());

        $expected_efforts = [
            1560722400 => 9,
            1560808800 => 7,
            1560895200 => 4,
            1560981600 => 2,
            1561068000 => 0,
        ];

        $this->assertSame($expected_efforts, $burndown_data->getRemainingEffortsAtDate());
    }

    public function testItDoesNotAddRemainingEffortDataForLegacyWhenMilesetoneIsInTheFuture()
    {
        $capacity    = 10;
        $duration    = 4;
        $now         = new DateTime();
        $date_period = DatePeriodWithoutWeekEnd::buildFromDuration($now->getTimestamp() + 10000, $duration);

        $burndown_data = new Tracker_Chart_Data_Burndown($date_period, $capacity);

        $artifact = Mockery::mock(Artifact::class);
        $user     = Mockery::mock(PFUser::class);

        $burndown_field = Mockery::mock(Tracker_FormElement_Field_Burndown::class);

        $this->field_retriever->shouldReceive('getBurndownRemainingEffortField')
            ->with($artifact, $user)
            ->once()
            ->andReturn($burndown_field);

        $this->user_builder->shouldReceive('buildUserWithReadAllPermission')->never();
        $burndown_field->shouldReceive('getCachedValue')->never();

        $this->adder->addRemainingEffortDataForLegacy(
            $burndown_data,
            $artifact,
            $user
        );

        $this->assertCount(0, $burndown_data->getRemainingEffortsAtDate());
    }

    public function testItDoesNotAddRemainingEffortDataForLegacyWhenNoBurndownField()
    {
        $capacity    = 10;
        $duration    = 5;
        $now         = new DateTime();
        $date_period = DatePeriodWithoutWeekEnd::buildFromDuration($now->getTimestamp() - 1000, $duration);

        $burndown_data = new Tracker_Chart_Data_Burndown($date_period, $capacity);

        $artifact = Mockery::mock(Artifact::class);
        $user     = Mockery::mock(PFUser::class);

        $this->field_retriever->shouldReceive('getBurndownRemainingEffortField')
            ->with($artifact, $user)
            ->once()
            ->andReturnNull();

        $this->user_builder->shouldReceive('buildUserWithReadAllPermission')->never();

        $this->adder->addRemainingEffortDataForLegacy(
            $burndown_data,
            $artifact,
            $user
        );

        $this->assertCount(0, $burndown_data->getRemainingEffortsAtDate());
    }
}

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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\Burndown;

use DateTime;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Chart_Data_Burndown;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedField;
use Tracker_UserWithReadAllPermission;
use Tuleap\Date\DatePeriodWithOpenDays;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\UserWithReadAllPermissionBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class BurndownRemainingEffortAdderForLegacyTest extends TestCase
{
    private BurndownRemainingEffortAdderForLegacy $adder;
    private ChartConfigurationFieldRetriever&MockObject $field_retriever;
    private UserWithReadAllPermissionBuilder&MockObject $user_builder;

    #[\Override]
    protected function setUp(): void
    {
        $this->field_retriever = $this->createMock(ChartConfigurationFieldRetriever::class);
        $this->user_builder    = $this->createMock(UserWithReadAllPermissionBuilder::class);
        $this->adder           = new BurndownRemainingEffortAdderForLegacy($this->field_retriever, $this->user_builder);
    }

    public function testItAddsRemainingEffortDataForLegacy(): void
    {
        $capacity    = 10;
        $duration    = 4;
        $date        = new DateTime('2019-06-17');
        $date_period = DatePeriodWithOpenDays::buildFromDuration($date->getTimestamp(), $duration);

        $burndown_data = new Tracker_Chart_Data_Burndown($date_period, $capacity);

        $artifact = ArtifactTestBuilder::anArtifact(3541)->build();
        $user     = UserTestBuilder::buildWithDefaults();

        $computed_field = $this->createMock(ComputedField::class);

        $this->field_retriever->expects($this->once())->method('getBurndownRemainingEffortField')
            ->with($artifact, $user)->willReturn($computed_field);

        $all_perms_user = new Tracker_UserWithReadAllPermission($user);
        $this->user_builder->expects($this->exactly(5))->method('buildUserWithReadAllPermission')
            ->with($user)->willReturn($all_perms_user);

        $computed_field->expects($this->exactly(5))->method('getCachedValue')
            ->with($all_perms_user, $artifact, self::anything())
            ->willReturnCallback(static fn(PFUser $user, Artifact $artifact, ?int $timestamp) => match ($timestamp) {
                1560808799 => 9,
                1560895199 => 7,
                1560981599 => 4,
                1561067999 => 2,
                1561154399 => 0,
            });

        $this->adder->addRemainingEffortDataForLegacy($burndown_data, $artifact, $user);

        self::assertCount(5, $burndown_data->getRemainingEffortsAtDate());

        $expected_efforts = [
            1560722400 => 9,
            1560808800 => 7,
            1560895200 => 4,
            1560981600 => 2,
            1561068000 => 0,
        ];

        self::assertSame($expected_efforts, $burndown_data->getRemainingEffortsAtDate());
    }

    public function testItDoesNotAddRemainingEffortDataForLegacyWhenMilesetoneIsInTheFuture(): void
    {
        $capacity    = 10;
        $duration    = 4;
        $now         = new DateTime();
        $date_period = DatePeriodWithOpenDays::buildFromDuration($now->getTimestamp() + 10000, $duration);

        $burndown_data = new Tracker_Chart_Data_Burndown($date_period, $capacity);

        $artifact = ArtifactTestBuilder::anArtifact(3542)->build();
        $user     = UserTestBuilder::buildWithDefaults();

        $computed_field = $this->createMock(ComputedField::class);

        $this->field_retriever->expects($this->once())->method('getBurndownRemainingEffortField')
            ->with($artifact, $user)->willReturn($computed_field);

        $this->user_builder->expects($this->never())->method('buildUserWithReadAllPermission');
        $computed_field->expects($this->never())->method('getCachedValue');

        $this->adder->addRemainingEffortDataForLegacy($burndown_data, $artifact, $user);

        self::assertCount(0, $burndown_data->getRemainingEffortsAtDate());
    }

    public function testItDoesNotAddRemainingEffortDataForLegacyWhenNoBurndownField(): void
    {
        $capacity    = 10;
        $duration    = 5;
        $now         = new DateTime();
        $date_period = DatePeriodWithOpenDays::buildFromDuration($now->getTimestamp() - 1000, $duration);

        $burndown_data = new Tracker_Chart_Data_Burndown($date_period, $capacity);

        $artifact = ArtifactTestBuilder::anArtifact(6142)->build();
        $user     = UserTestBuilder::buildWithDefaults();

        $this->field_retriever->expects($this->once())->method('getBurndownRemainingEffortField')
            ->with($artifact, $user)->willReturn(null);

        $this->user_builder->expects($this->never())->method('buildUserWithReadAllPermission');

        $this->adder->addRemainingEffortDataForLegacy($burndown_data, $artifact, $user);

        self::assertCount(0, $burndown_data->getRemainingEffortsAtDate());
    }
}

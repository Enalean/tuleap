<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use ForgeConfig;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Psr\Log\NullLogger;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedField;
use Tuleap\Config\ConfigurationVariables;
use Tuleap\Date\DatePeriodWithOpenDays;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\REST\JsonCast;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\TimezoneRetriever;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;
use Tuleap\Tracker\FormElement\ChartConfigurationValueRetriever;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\UserWithReadAllPermissionBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class BurndownDataBuilderForLegacyTest extends TestCase
{
    use ForgeConfigSandbox;
    use GlobalLanguageMock;

    private string $original_timezone;
    private PFUser $user;
    private Artifact $artifact;
    private BurndownDataBuilderForLegacy $burndown_data_builder;

    #[\Override]
    protected function setUp(): void
    {
        ForgeConfig::set(ConfigurationVariables::SERVER_TIMEZONE, 'Europe/Paris');

        $timezone_retriever      = new TimezoneRetriever();
        $this->original_timezone = $timezone_retriever::getServerTimezone();

        $field_retriever = $this->createMock(ChartConfigurationFieldRetriever::class);
        $field_retriever->method('doesCapacityFieldExist')->willReturn(false);

        $field = $this->createMock(ComputedField::class);
        $field_retriever->method('getBurndownRemainingEffortField')->willReturn($field);
        $field->method('getCachedValue')->willReturn(1);

        $cache_checker = $this->createMock(BurndownCacheGenerationChecker::class);
        $cache_checker->method('isBurndownUnderCalculationBasedOnServerTimezone')->willReturn(false);

        $this->burndown_data_builder = new BurndownDataBuilderForLegacy(
            new NullLogger(),
            $field_retriever,
            $this->createStub(ChartConfigurationValueRetriever::class),
            $cache_checker,
            new BurndownRemainingEffortAdderForLegacy($field_retriever, new UserWithReadAllPermissionBuilder())
        );

        $this->artifact = ArtifactTestBuilder::anArtifact(101)->inTracker(TrackerTestBuilder::aTracker()->build())->build();
        $this->user     = UserTestBuilder::anActiveUser()->build();
    }

    #[\Override]
    protected function tearDown(): void
    {
        date_default_timezone_set($this->original_timezone);
    }

    public function testStartDateDoesNotShiftForUsersLocatedInUTCNegative(): void
    {
        $this->user->setTimezone('America/Los_Angeles');

        $start_date  = strtotime('2018-11-01');
        $duration    = 5;
        $date_period = DatePeriodWithOpenDays::buildFromDuration($start_date, $duration);

        $user_burndown_data = $this->burndown_data_builder->build($this->artifact, $this->user, $date_period);

        $shifted_start_date = 1541026800;
        self::assertEquals($shifted_start_date, $user_burndown_data->getDatePeriod()->getStartDate());
    }

    public function testStartDateDoesNotShiftForUsersLocatedInUTCPositive(): void
    {
        $this->user->setTimezone('Asia/Tokyo');

        $start_date  = strtotime('2018-11-01');
        $duration    = 5;
        $date_period = DatePeriodWithOpenDays::buildFromDuration($start_date, $duration);

        $user_burndown_data = $this->burndown_data_builder->build($this->artifact, $this->user, $date_period);

        $shifted_start_date = 1541026800;
        self::assertEquals($shifted_start_date, $user_burndown_data->getDatePeriod()->getStartDate());
    }

    public function testRemainingEffortAreNotShiftedUsersLocatedInUTCNegative(): void
    {
        $this->user->setTimezone('America/Los_Angeles');

        $start_date  = strtotime('2018-11-01');
        $duration    = 2;
        $date_period = DatePeriodWithOpenDays::buildFromDuration($start_date, $duration);

        $second_day = strtotime('2018-11-02');
        $third_day  = strtotime('2018-11-03');

        $user_burndown_data = $this->burndown_data_builder->build($this->artifact, $this->user, $date_period);

        self::assertEquals(JsonCast::toDate($start_date), $user_burndown_data->getRESTRepresentation()->points_with_date[0]->date);
        self::assertEquals(JsonCast::toDate($second_day), $user_burndown_data->getRESTRepresentation()->points_with_date[1]->date);
        self::assertEquals(JsonCast::toDate($third_day), $user_burndown_data->getRESTRepresentation()->points_with_date[2]->date);
    }

    public function testRemainingEffortAreNotShiftedUsersLocatedInUTCPositive(): void
    {
        $this->user->setTimezone('Asia/Tokyo');

        $start_date  = strtotime('2018-11-01');
        $duration    = 2;
        $date_period = DatePeriodWithOpenDays::buildFromDuration($start_date, $duration);

        $second_day = strtotime('2018-11-02');
        $third_day  = strtotime('2018-11-03');

        $user_burndown_data = $this->burndown_data_builder->build($this->artifact, $this->user, $date_period);

        self::assertEquals(JsonCast::toDate($start_date), $user_burndown_data->getRESTRepresentation()->points_with_date[0]->date);
        self::assertEquals(JsonCast::toDate($second_day), $user_burndown_data->getRESTRepresentation()->points_with_date[1]->date);
        self::assertEquals(JsonCast::toDate($third_day), $user_burndown_data->getRESTRepresentation()->points_with_date[2]->date);
    }
}

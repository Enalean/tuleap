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

use DateTime;
use ForgeConfig;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Tracker_FormElement_Field_Computed;
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
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDao;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class BurndownDataBuilderForRESTTest extends TestCase
{
    use ForgeConfigSandbox;
    use GlobalLanguageMock;

    private const FIELD_ID = 10;

    private string $original_timezone;
    private PFUser $user;
    private Artifact $artifact;
    private BurndownDataBuilderForREST $burndown_data_builder_for_d3;
    private ComputedFieldDao&MockObject $computed_cache;


    protected function setUp(): void
    {
        ForgeConfig::set(ConfigurationVariables::SERVER_TIMEZONE, 'Europe/Paris');
        $this->original_timezone = TimezoneRetriever::getServerTimezone();

        $logger = new NullLogger();

        $field_retriever = $this->createMock(ChartConfigurationFieldRetriever::class);
        $field_retriever->method('doesCapacityFieldExist')->willReturn(false);

        $field = $this->createMock(Tracker_FormElement_Field_Computed::class);
        $field_retriever->method('getBurndownRemainingEffortField')->willReturn($field);
        $field->method('getCachedValue')->willReturn(1);
        $field->method('getId')->willReturn(self::FIELD_ID);

        $cache_checker = $this->createMock(BurndownCacheGenerationChecker::class);
        $cache_checker->method('isBurndownUnderCalculationBasedOnServerTimezone')->willReturn(false);

        $this->computed_cache               = $this->createMock(ComputedFieldDao::class);
        $this->burndown_data_builder_for_d3 = new BurndownDataBuilderForREST(
            $logger,
            new BurndownRemainingEffortAdderForREST($field_retriever, $this->computed_cache),
            new BurndownCommonDataBuilder(
                $logger,
                $field_retriever,
                $this->createStub(ChartConfigurationValueRetriever::class),
                $cache_checker
            )
        );

        $this->artifact = ArtifactTestBuilder::anArtifact(101)->inTracker(TrackerTestBuilder::aTracker()->build())->build();
        $this->user     = UserTestBuilder::anActiveUser()->build();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->original_timezone);
    }

    public function testStartDateDoesNotShiftForUsersLocatedInUTCNegative(): void
    {
        $this->user->setTimezone('America/Los_Angeles');

        $start_date = strtotime('2018-11-01');
        $duration   = 5;

        $date_period = DatePeriodWithOpenDays::buildFromDuration($start_date, $duration);

        $this->computed_cache->method('searchCachedDays')->willReturn([]);

        $user_burndown_data = $this->burndown_data_builder_for_d3->build($this->artifact, $this->user, $date_period);

        $shifted_start_date = 1541026800;
        self::assertEquals($user_burndown_data->getDatePeriod()->getStartDate(), $shifted_start_date);
    }

    public function testStartDateDoesNotShiftForUsersLocatedInUTCPositive(): void
    {
        $this->user->setTimezone('Asia/Tokyo');

        $start_date = strtotime('2018-11-01');
        $duration   = 5;

        $date_period = DatePeriodWithOpenDays::buildFromDuration($start_date, $duration);

        $this->computed_cache->method('searchCachedDays')->willReturn([]);

        $user_burndown_data = $this->burndown_data_builder_for_d3->build($this->artifact, $this->user, $date_period);

        $shifted_start_date = 1541026800;
        self::assertEquals($user_burndown_data->getDatePeriod()->getStartDate(), $shifted_start_date);
    }

    public function testRemainingEffortAreNotShiftedUsersLocatedInUTCNegative(): void
    {
        $this->user->setTimezone('America/Los_Angeles');

        $start_date = strtotime('2018-11-01');
        $second_day = strtotime('2018-11-02');
        $third_day  = strtotime('2018-11-03');

        $duration = 2;

        $this->computed_cache->method('searchCachedDays')->willReturn([
            [
                'artifact_id' => $this->artifact->getId(),
                'field_id'    => self::FIELD_ID,
                'timestamp'   => $start_date,
                'value'       => 10,
            ],
            [
                'artifact_id' => $this->artifact->getId(),
                'field_id'    => self::FIELD_ID,
                'timestamp'   => $second_day,
                'value'       => 10,
            ],
            [
                'artifact_id' => $this->artifact->getId(),
                'field_id'    => self::FIELD_ID,
                'timestamp'   => $third_day,
                'value'       => 10,
            ],
        ]);

        $date_period = DatePeriodWithOpenDays::buildFromDuration($start_date, $duration);

        $user_burndown_data = $this->burndown_data_builder_for_d3->build($this->artifact, $this->user, $date_period);

        self::assertEquals($user_burndown_data->getRESTRepresentation()->points_with_date[0]->date, JsonCast::toDate($start_date));
        self::assertEquals($user_burndown_data->getRESTRepresentation()->points_with_date[1]->date, JsonCast::toDate($second_day));
        self::assertEquals($user_burndown_data->getRESTRepresentation()->points_with_date[2]->date, JsonCast::toDate($third_day));
    }

    public function testRemainingEffortAreNotShiftedUsersLocatedInUTCPositive(): void
    {
        $this->user->setTimezone('Asia/Tokyo');

        $start_date = strtotime('2018-11-01');
        $second_day = strtotime('2018-11-02');
        $third_day  = strtotime('2018-11-03');

        $this->computed_cache->method('searchCachedDays')->willReturn([
            [
                'artifact_id' => $this->artifact->getId(),
                'field_id'    => self::FIELD_ID,
                'timestamp'   => $start_date,
                'value'       => 10,
            ],
            [
                'artifact_id' => $this->artifact->getId(),
                'field_id'    => self::FIELD_ID,
                'timestamp'   => $second_day,
                'value'       => 10,
            ],
            [
                'artifact_id' => $this->artifact->getId(),
                'field_id'    => self::FIELD_ID,
                'timestamp'   => $third_day,
                'value'       => 10,
            ],
        ]);

        $duration = 2;

        $date_period = DatePeriodWithOpenDays::buildFromDuration($start_date, $duration);

        $user_burndown_data = $this->burndown_data_builder_for_d3->build($this->artifact, $this->user, $date_period);

        self::assertEquals($user_burndown_data->getRESTRepresentation()->points_with_date[0]->date, JsonCast::toDate($start_date));
        self::assertEquals($user_burndown_data->getRESTRepresentation()->points_with_date[1]->date, JsonCast::toDate($second_day));
        self::assertEquals($user_burndown_data->getRESTRepresentation()->points_with_date[2]->date, JsonCast::toDate($third_day));
    }

    public function testItReturnsAnEmptyArrayWhenTimePeriodIsInFuture(): void
    {
        $this->user->setTimezone('Europe/London');

        $this->computed_cache->method('searchCachedDays')->willReturn([]);

        $duration = 2;

        $start_date  = new DateTime('+1d');
        $date_period = DatePeriodWithOpenDays::buildFromDuration($start_date->getTimestamp(), $duration);

        $user_burndown_data = $this->burndown_data_builder_for_d3->build($this->artifact, $this->user, $date_period);
        self::assertSame([0 => null], $user_burndown_data->getRemainingEffort());
    }
}

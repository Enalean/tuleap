<?php
/**
 * Copyright (c) Enalean, 2017 - Present All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use TimePeriodWithoutWeekEnd;
use Tracker_FormElement_Chart_Field_Exception;

require_once __DIR__ . '/../../bootstrap.php';

final class ChartConfigurationValueCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Tracker_FormElement_Field_Integer
     */
    public $duration_field;

    /**
     * @var \Tracker_Artifact_Changeset
     */
    private $new_changeset;

    /**
     * @var \Tracker_Artifact_ChangesetValue_Integer
     */
    private $duration_changeset;

    /**
     * @var \Tracker_Artifact_ChangesetValue_Date
     */
    private $start_date_changeset;

    /**
     * @var \PFUser
     */
    private $user;

    /**
     * @var \Tuleap\Tracker\Artifact\Artifact
     */
    private $artifact;

    /**
     * @var \Tracker_FormElement_Field
     */
    private $start_date_field;

    /**
     * @var ChartConfigurationFieldRetriever
     */
    private $configuration_field_retriever;

    /**
     * @var ChartConfigurationValueRetriever
     */
    private $configuration_value_retriever;

    /**
     * @var ChartConfigurationValueChecker
     */
    private $chart_configuration_value_checker;

    /**
     * @var int
     */
    private $duration_value;

    /**
     * @var int
     */
    private $start_date_timestamp;

    /**
     * @var \Tracker
     */
    private $tracker;
    /**
     * @var Mockery\MockInterface|\Tracker_FormElement_Field_Date
     */
    private $end_date_field;
    /**
     * @var Mockery\MockInterface|\Tracker_Artifact_ChangesetValue_Date
     */
    private $end_date_changeset;

    protected function setUp(): void
    {
        $this->configuration_field_retriever     = \Mockery::mock(\Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever::class);
        $this->configuration_value_retriever     = Mockery::mock(ChartConfigurationValueRetriever::class);
        $this->chart_configuration_value_checker = new ChartConfigurationValueChecker(
            $this->configuration_field_retriever,
            $this->configuration_value_retriever
        );

        $this->tracker              = \Mockery::mock(\Tracker::class);
        $this->start_date_field     = \Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $this->end_date_field       = \Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $this->duration_field       = \Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $this->artifact             = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->user                 = \Mockery::mock(\PFUser::class);
        $this->start_date_changeset = \Mockery::mock(\Tracker_Artifact_ChangesetValue_Date::class);
        $this->end_date_changeset   = \Mockery::mock(\Tracker_Artifact_ChangesetValue_Date::class);
        $this->duration_changeset   = \Mockery::mock(\Tracker_Artifact_ChangesetValue_Integer::class);
        $this->new_changeset        = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $this->duration_value       = 10;
        $this->start_date_timestamp = 1488470204;

        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);
    }

    public function testItReturnsFalseWhenChartDontHaveAStartDateField(): void
    {
        $this->configuration_field_retriever->shouldReceive('getStartDateField')
            ->with($this->tracker, $this->user)
            ->andThrow(new Tracker_FormElement_Chart_Field_Exception());

        $this->assertFalse(
            $this->chart_configuration_value_checker->hasStartDate($this->artifact, $this->user)
        );
    }

    public function testItReturnsFalseWhenStartDateFieldIsNeverDefined(): void
    {
        $this->configuration_field_retriever->shouldReceive('getStartDateField')
            ->with($this->tracker, $this->user)
            ->andReturn($this->start_date_field);

        $this->artifact->shouldReceive('getValue')->with($this->start_date_field)->andReturnNull();

        $this->assertFalse(
            $this->chart_configuration_value_checker->hasStartDate($this->artifact, $this->user)
        );
    }

    public function testItReturnsFalseWhenStartDateFieldIsEmpty(): void
    {
        $this->configuration_field_retriever->shouldReceive('getStartDateField')
            ->with($this->tracker, $this->user)
            ->andReturn($this->start_date_field);

        $this->artifact->shouldReceive('getValue')
            ->with($this->start_date_field)
            ->andReturn($this->start_date_changeset);

        $this->start_date_changeset->shouldReceive('getTimestamp')->andReturnNull();

        $this->assertFalse(
            $this->chart_configuration_value_checker->hasStartDate($this->artifact, $this->user)
        );
    }

    public function testItReturnsTrueWhenChartHasAStartDateAndStartDateIsFiled(): void
    {
        $this->configuration_field_retriever->shouldReceive('getStartDateField')
            ->with($this->tracker, $this->user)
            ->andReturn($this->start_date_field);

        $this->artifact->shouldReceive('getValue')
            ->with($this->start_date_field)
            ->andReturn($this->start_date_changeset);

        $this->start_date_changeset->shouldReceive('getTimestamp')->andReturn($this->start_date_timestamp);

        $this->assertTrue(
            $this->chart_configuration_value_checker->hasStartDate($this->artifact, $this->user)
        );
    }

    public function testItReturnsConfigurationIsNotCorrectlySetWhenStartDateIsMissing(): void
    {
        $this->configuration_value_retriever->shouldReceive('getTimePeriod')
            ->with($this->artifact, $this->user)
            ->andReturn(TimePeriodWithoutWeekEnd::buildFromDuration(null, $this->duration_value));

        $this->assertFalse(
            $this->chart_configuration_value_checker->areBurndownFieldsCorrectlySet($this->artifact, $this->user)
        );
    }

    public function testItReturnsConfigurationIsNotCorrectlySetWhenDurationIsMissing(): void
    {
        $this->configuration_value_retriever->shouldReceive('getTimePeriod')
            ->with($this->artifact, $this->user)
            ->andReturn(TimePeriodWithoutWeekEnd::buildFromDuration($this->start_date_timestamp, null));

        $this->assertFalse(
            $this->chart_configuration_value_checker->areBurndownFieldsCorrectlySet($this->artifact, $this->user)
        );
    }

    public function testItReturnsConfigurationIsNotCorrectlySetWhenExceptionIsThrownAtTimePeriodCreation(): void
    {
        $this->configuration_value_retriever->shouldReceive('getTimePeriod')
            ->with($this->artifact, $this->user)
            ->andThrow(Tracker_FormElement_Chart_Field_Exception::class);

        $this->assertFalse(
            $this->chart_configuration_value_checker->areBurndownFieldsCorrectlySet($this->artifact, $this->user)
        );
    }

    public function testItReturnsConfigurationIsCorrectlySetWhenBurndownHasAStartDateAndADuration(): void
    {
        $this->configuration_value_retriever->shouldReceive('getTimePeriod')
            ->with($this->artifact, $this->user)
            ->andReturn(
                TimePeriodWithoutWeekEnd::buildFromDuration($this->start_date_timestamp, $this->duration_value)
            );

        $this->assertTrue(
            $this->chart_configuration_value_checker->areBurndownFieldsCorrectlySet($this->artifact, $this->user)
        );
    }

    public function testItReturnsFalseWhenDurationIsNotSet(): void
    {
        $this->start_date_changeset->shouldReceive('getTimestamp')->andReturn($this->start_date_timestamp);

        $this->configuration_value_retriever->shouldReceive('getTimePeriod')
            ->andReturn(TimePeriodWithoutWeekEnd::buildFromDuration(12345678, null));

        $this->assertFalse(
            $this->chart_configuration_value_checker->areBurndownFieldsCorrectlySet($this->artifact, $this->user)
        );
    }

    public function testItReturnsFalseWhenStartDateAndDurationDontHaveChanged(): void
    {
        $this->configuration_field_retriever->shouldReceive('getDurationField')
            ->with($this->tracker, $this->user)
            ->andReturn($this->duration_field);

        $this->configuration_field_retriever->shouldReceive('getStartDateField')
            ->with($this->tracker, $this->user)
            ->andReturn($this->start_date_field);

        $this->configuration_field_retriever->shouldReceive('doesEndDateFieldExist')
            ->with($this->tracker, $this->user)
            ->andReturns(false);

        $this->configuration_value_retriever->shouldReceive('getTimePeriod')
            ->andReturn(TimePeriodWithoutWeekEnd::buildFromDuration(12345678, 5));

        $this->new_changeset->shouldReceive('getValue')
            ->with($this->start_date_field)
            ->andReturn($this->start_date_changeset);

        $this->new_changeset->shouldReceive('getValue')
            ->with($this->duration_field)
            ->andReturnFalse();

        $this->start_date_changeset->shouldReceive('hasChanged')->andReturnFalse();
        $this->duration_changeset->shouldReceive('hasChanged')->andReturnFalse();

        $this->assertFalse(
            $this->chart_configuration_value_checker->hasConfigurationChange(
                $this->artifact,
                $this->user,
                $this->new_changeset
            )
        );
    }

    public function testItReturnsTrueWhenStartDateHaveChanged(): void
    {
        $this->configuration_field_retriever->shouldReceive('getDurationField')
            ->with($this->tracker, $this->user)
            ->andReturn($this->duration_field);

        $this->configuration_field_retriever->shouldReceive('getStartDateField')
            ->with($this->tracker, $this->user)
            ->andReturn($this->start_date_field);

        $this->configuration_field_retriever->shouldReceive('getEndDateField')
            ->with($this->tracker, $this->user)
            ->andThrows(Tracker_FormElement_Chart_Field_Exception::class);

        $this->new_changeset->shouldReceive('getValue')
            ->with($this->start_date_field)
            ->andReturn($this->start_date_changeset);

        $this->new_changeset->shouldReceive('getValue')
            ->with($this->duration_field)
            ->andReturn($this->duration_changeset);

        $this->configuration_value_retriever->shouldReceive('getTimePeriod')
            ->andReturn(TimePeriodWithoutWeekEnd::buildFromDuration(12345678, 5));

        $this->start_date_changeset->shouldReceive('hasChanged')->andReturnTrue();
        $this->duration_changeset->shouldReceive('hasChanged')->andReturnFalse();

        $this->assertTrue(
            $this->chart_configuration_value_checker->hasConfigurationChange(
                $this->artifact,
                $this->user,
                $this->new_changeset
            )
        );
    }

    public function testItReturnsTrueWhenDurationHaveChanged(): void
    {
        $this->configuration_field_retriever->shouldReceive('getDurationField')
            ->with($this->tracker, $this->user)
            ->andReturn($this->duration_field);

        $this->configuration_field_retriever->shouldReceive('getStartDateField')
            ->with($this->tracker, $this->user)
            ->andReturn($this->start_date_field);

        $this->configuration_value_retriever->shouldReceive('getTimePeriod')
            ->andReturn(TimePeriodWithoutWeekEnd::buildFromDuration(12345678, 5));

        $this->configuration_field_retriever->shouldReceive('doesEndDateFieldExist')
            ->with($this->tracker, $this->user)
            ->andReturns(false);

        $this->new_changeset->shouldReceive('getValue')
            ->with($this->start_date_field)
            ->andReturn($this->start_date_changeset);

        $this->new_changeset->shouldReceive('getValue')
            ->with($this->duration_field)
            ->andReturn($this->duration_changeset);

        $this->start_date_changeset->shouldReceive('hasChanged')->andReturnFalse();
        $this->duration_changeset->shouldReceive('hasChanged')->andReturnTrue();

        $this->assertTrue(
            $this->chart_configuration_value_checker->hasConfigurationChange(
                $this->artifact,
                $this->user,
                $this->new_changeset
            )
        );
    }

    public function testItReturnsTrueWhenEndDateHasChanged(): void
    {
        $this->configuration_field_retriever->shouldReceive('getStartDateField')
            ->with($this->tracker, $this->user)
            ->andReturn($this->start_date_field);

        $this->configuration_field_retriever->shouldReceive('doesEndDateFieldExist')
            ->with($this->tracker, $this->user)
            ->andReturn(true);

        $this->configuration_value_retriever->shouldReceive('getTimePeriod')
            ->andReturn(TimePeriodWithoutWeekEnd::buildFromDuration(12345678, 5));

        $this->configuration_field_retriever->shouldReceive('getEndDateField')
            ->with($this->tracker, $this->user)
            ->andReturn($this->end_date_field);

        $this->new_changeset->shouldReceive('getValue')
            ->with($this->start_date_field)
            ->andReturn($this->start_date_changeset);

        $this->new_changeset->shouldReceive('getValue')
            ->with($this->end_date_field)
            ->andReturn($this->end_date_changeset);

        $this->start_date_changeset->shouldReceive('hasChanged')->andReturnFalse();
        $this->end_date_changeset->shouldReceive('hasChanged')->andReturnTrue();

        $this->assertTrue(
            $this->chart_configuration_value_checker->hasConfigurationChange(
                $this->artifact,
                $this->user,
                $this->new_changeset
            )
        );
    }
}

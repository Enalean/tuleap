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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement;

use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Date;
use Tracker_Artifact_ChangesetValue_Integer;
use Tracker_FormElement_Chart_Field_Exception;
use Tracker_FormElement_Field_Date;
use Tuleap\Date\DatePeriodWithOpenDays;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\Integer\IntegerField;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueDateTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[DisableReturnValueGenerationForTestDoubles]
final class ChartConfigurationValueCheckerTest extends TestCase
{
    private const DURATION_VALUE       = 10;
    private const START_DATE_TIMESTAMP = 1488470204;

    public IntegerField $duration_field;
    private Tracker_Artifact_Changeset $new_changeset;
    private Tracker_Artifact_ChangesetValue_Integer&MockObject $duration_changeset;
    private Tracker_Artifact_ChangesetValue_Date&MockObject $start_date_changeset;
    private PFUser $user;
    private Artifact $artifact;
    private Tracker_FormElement_Field_Date $start_date_field;
    private ChartConfigurationFieldRetriever&MockObject $configuration_field_retriever;
    private ChartConfigurationValueRetriever&MockObject $configuration_value_retriever;
    private ChartConfigurationValueChecker $chart_configuration_value_checker;
    private Tracker $tracker;
    private Tracker_FormElement_Field_Date $end_date_field;
    private Tracker_Artifact_ChangesetValue_Date $end_date_changeset;

    protected function setUp(): void
    {
        $this->configuration_field_retriever     = $this->createMock(ChartConfigurationFieldRetriever::class);
        $this->configuration_value_retriever     = $this->createMock(ChartConfigurationValueRetriever::class);
        $this->chart_configuration_value_checker = new ChartConfigurationValueChecker(
            $this->configuration_field_retriever,
            $this->configuration_value_retriever
        );

        $this->tracker              = TrackerTestBuilder::aTracker()->build();
        $this->start_date_field     = DateFieldBuilder::aDateField(6541)->build();
        $this->end_date_field       = DateFieldBuilder::aDateField(6542)->build();
        $this->duration_field       = IntegerFieldBuilder::anIntField(6543)->build();
        $this->new_changeset        = ChangesetTestBuilder::aChangeset(1786)->build();
        $this->artifact             = ArtifactTestBuilder::anArtifact(9874)->inTracker($this->tracker)->withChangesets($this->new_changeset)->build();
        $this->user                 = UserTestBuilder::buildWithDefaults();
        $this->start_date_changeset = $this->createMock(Tracker_Artifact_ChangesetValue_Date::class);
        $this->end_date_changeset   = ChangesetValueDateTestBuilder::aValue(2, $this->new_changeset, $this->end_date_field)->build();
        $this->duration_changeset   = $this->createMock(Tracker_Artifact_ChangesetValue_Integer::class);
    }

    public function testItReturnsFalseWhenChartDontHaveAStartDateField(): void
    {
        $this->configuration_field_retriever->method('getStartDateField')
            ->with($this->tracker, $this->user)
            ->willThrowException(new Tracker_FormElement_Chart_Field_Exception());

        self::assertFalse($this->chart_configuration_value_checker->hasStartDate($this->artifact, $this->user));
    }

    public function testItReturnsFalseWhenStartDateFieldIsNeverDefined(): void
    {
        $this->configuration_field_retriever->method('getStartDateField')
            ->with($this->tracker, $this->user)->willReturn($this->start_date_field);

        $this->new_changeset->setFieldValue($this->start_date_field, null);

        self::assertFalse($this->chart_configuration_value_checker->hasStartDate($this->artifact, $this->user));
    }

    public function testItReturnsFalseWhenStartDateFieldIsEmpty(): void
    {
        $this->configuration_field_retriever->method('getStartDateField')
            ->with($this->tracker, $this->user)->willReturn($this->start_date_field);

        $this->new_changeset->setFieldValue($this->start_date_field, $this->start_date_changeset);

        $this->start_date_changeset->method('getTimestamp')->willReturn(null);

        self::assertFalse($this->chart_configuration_value_checker->hasStartDate($this->artifact, $this->user));
    }

    public function testItReturnsTrueWhenChartHasAStartDateAndStartDateIsFiled(): void
    {
        $this->configuration_field_retriever->method('getStartDateField')
            ->with($this->tracker, $this->user)->willReturn($this->start_date_field);

        $this->new_changeset->setFieldValue($this->start_date_field, $this->start_date_changeset);

        $this->start_date_changeset->method('getTimestamp')->willReturn(self::START_DATE_TIMESTAMP);

        self::assertTrue($this->chart_configuration_value_checker->hasStartDate($this->artifact, $this->user));
    }

    public function testItReturnsConfigurationIsNotCorrectlySetWhenStartDateIsMissing(): void
    {
        $this->configuration_value_retriever->method('getDatePeriod')
            ->with($this->artifact, $this->user)
            ->willReturn(DatePeriodWithOpenDays::buildFromDuration(null, self::DURATION_VALUE));

        self::assertFalse($this->chart_configuration_value_checker->areBurndownFieldsCorrectlySet($this->artifact, $this->user));
    }

    public function testItReturnsConfigurationIsNotCorrectlySetWhenDurationIsMissing(): void
    {
        $this->configuration_value_retriever->method('getDatePeriod')
            ->with($this->artifact, $this->user)
            ->willReturn(DatePeriodWithOpenDays::buildFromDuration(self::START_DATE_TIMESTAMP, null));

        self::assertFalse($this->chart_configuration_value_checker->areBurndownFieldsCorrectlySet($this->artifact, $this->user));
    }

    public function testItReturnsConfigurationIsNotCorrectlySetWhenExceptionIsThrownAtDatePeriodCreation(): void
    {
        $this->configuration_value_retriever->method('getDatePeriod')
            ->with($this->artifact, $this->user)->willThrowException(new Tracker_FormElement_Chart_Field_Exception());

        self::assertFalse($this->chart_configuration_value_checker->areBurndownFieldsCorrectlySet($this->artifact, $this->user));
    }

    public function testItReturnsConfigurationIsCorrectlySetWhenBurndownHasAStartDateAndADuration(): void
    {
        $this->configuration_value_retriever->method('getDatePeriod')
            ->with($this->artifact, $this->user)
            ->willReturn(DatePeriodWithOpenDays::buildFromDuration(self::START_DATE_TIMESTAMP, self::DURATION_VALUE));

        self::assertTrue($this->chart_configuration_value_checker->areBurndownFieldsCorrectlySet($this->artifact, $this->user));
    }

    public function testItReturnsFalseWhenDurationIsNotSet(): void
    {
        $this->start_date_changeset->method('getTimestamp')->willReturn(self::START_DATE_TIMESTAMP);

        $this->configuration_value_retriever->method('getDatePeriod')
            ->willReturn(DatePeriodWithOpenDays::buildFromDuration(12345678, null));

        self::assertFalse($this->chart_configuration_value_checker->areBurndownFieldsCorrectlySet($this->artifact, $this->user));
    }

    public function testItReturnsFalseWhenStartDateAndDurationDontHaveChanged(): void
    {
        $this->configuration_field_retriever->method('getDurationField')
            ->with($this->tracker, $this->user)->willReturn($this->duration_field);

        $this->configuration_field_retriever->method('getStartDateField')
            ->with($this->tracker, $this->user)->willReturn($this->start_date_field);

        $this->configuration_field_retriever->method('doesEndDateFieldExist')
            ->with($this->tracker, $this->user)->willReturn(false);

        $this->configuration_value_retriever->method('getDatePeriod')
            ->willReturn(DatePeriodWithOpenDays::buildFromDuration(12345678, 5));

        $this->new_changeset->setFieldValue($this->start_date_field, $this->start_date_changeset);
        $this->new_changeset->setFieldValue($this->duration_field, null);

        $this->start_date_changeset->method('hasChanged')->willReturn(false);
        $this->duration_changeset->method('hasChanged')->willReturn(false);

        self::assertFalse($this->chart_configuration_value_checker->hasConfigurationChange($this->artifact, $this->user, $this->new_changeset));
    }

    public function testItReturnsTrueWhenStartDateHaveChanged(): void
    {
        $this->configuration_field_retriever->method('getDurationField')
            ->with($this->tracker, $this->user)->willReturn($this->duration_field);

        $this->configuration_field_retriever->method('getStartDateField')
            ->with($this->tracker, $this->user)->willReturn($this->start_date_field);

        $this->configuration_field_retriever->method('getEndDateField')
            ->with($this->tracker, $this->user)->willThrowException(new Tracker_FormElement_Chart_Field_Exception());

        $this->new_changeset->setFieldValue($this->start_date_field, $this->start_date_changeset);
        $this->new_changeset->setFieldValue($this->duration_field, $this->duration_changeset);

        $this->configuration_value_retriever->method('getDatePeriod')
            ->willReturn(DatePeriodWithOpenDays::buildFromDuration(12345678, 5));

        $this->start_date_changeset->method('hasChanged')->willReturn(true);
        $this->duration_changeset->method('hasChanged')->willReturn(false);

        self::assertTrue($this->chart_configuration_value_checker->hasConfigurationChange($this->artifact, $this->user, $this->new_changeset));
    }

    public function testItReturnsTrueWhenDurationHaveChanged(): void
    {
        $this->configuration_field_retriever->method('getDurationField')
            ->with($this->tracker, $this->user)->willReturn($this->duration_field);

        $this->configuration_field_retriever->method('getStartDateField')
            ->with($this->tracker, $this->user)->willReturn($this->start_date_field);

        $this->configuration_value_retriever->method('getDatePeriod')
            ->willReturn(DatePeriodWithOpenDays::buildFromDuration(12345678, 5));

        $this->configuration_field_retriever->method('doesEndDateFieldExist')
            ->with($this->tracker, $this->user)->willReturn(false);

        $this->new_changeset->setFieldValue($this->start_date_field, $this->start_date_changeset);
        $this->new_changeset->setFieldValue($this->duration_field, $this->duration_changeset);

        $this->start_date_changeset->method('hasChanged')->willReturn(false);
        $this->duration_changeset->method('hasChanged')->willReturn(true);

        self::assertTrue($this->chart_configuration_value_checker->hasConfigurationChange($this->artifact, $this->user, $this->new_changeset));
    }

    public function testItReturnsTrueWhenEndDateHasChanged(): void
    {
        $this->configuration_field_retriever->method('getStartDateField')
            ->with($this->tracker, $this->user)->willReturn($this->start_date_field);

        $this->configuration_field_retriever->method('doesEndDateFieldExist')
            ->with($this->tracker, $this->user)->willReturn(true);

        $this->configuration_value_retriever->method('getDatePeriod')
            ->willReturn(DatePeriodWithOpenDays::buildFromDuration(12345678, 5));

        $this->configuration_field_retriever->method('getEndDateField')
            ->with($this->tracker, $this->user)->willReturn($this->end_date_field);

        $this->new_changeset->setFieldValue($this->start_date_field, $this->start_date_changeset);
        $this->new_changeset->setFieldValue($this->end_date_field, $this->end_date_changeset);

        $this->start_date_changeset->method('hasChanged')->willReturn(false);

        self::assertTrue($this->chart_configuration_value_checker->hasConfigurationChange($this->artifact, $this->user, $this->new_changeset));
    }
}

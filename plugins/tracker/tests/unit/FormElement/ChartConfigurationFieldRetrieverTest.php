<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
use Psr\Log\NullLogger;
use Tracker_FormElement_Chart_Field_Exception;
use Tracker_FormElement_Field_Date;
use Tracker_FormElementFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\Integer\IntegerField;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeNotConfigured;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithDuration;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Tracker;

#[DisableReturnValueGenerationForTestDoubles]
final class ChartConfigurationFieldRetrieverTest extends TestCase
{
    private Tracker_FormElementFactory&MockObject $form_element_field_factoy;
    private Tracker&MockObject $tracker;
    private ChartConfigurationFieldRetriever $configuration_retriever;
    private Artifact $artifact;
    private PFUser $user;
    private IntegerField $field_duration;
    private IntegerField $field_capacity;
    private IntegerField $field_remaining_effort;
    private SemanticTimeframeBuilder&MockObject $semantic_timeframe_builder;
    private Tracker_FormElement_Field_Date $field_start_date;

    protected function setUp(): void
    {
        $this->form_element_field_factoy = $this->createPartialMock(
            Tracker_FormElementFactory::class,
            ['getNumericFieldByNameForUser', 'getNumericFieldByName', 'getUsedFieldByName'],
        );

        $this->tracker = $this->createMock(Tracker::class);
        $this->tracker->method('getId')->willReturn(101);
        $this->tracker->method('getName')->willReturn('Scrum');

        $this->artifact = ArtifactTestBuilder::anArtifact(65431)->inTracker($this->tracker)->build();
        $this->user     = UserTestBuilder::anActiveUser()->build();

        $this->field_duration         = IntegerFieldBuilder::anIntField(65413)->withName('duration')->build();
        $this->field_capacity         = IntegerFieldBuilder::anIntField(65414)->withName('capacity')->build();
        $this->field_remaining_effort = IntegerFieldBuilder::anIntField(65415)->withName('remaining_effort')->build();
        $this->field_start_date       = DateFieldBuilder::aDateField(65416)->withName('start_date')->build();

        $this->semantic_timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);

        $this->configuration_retriever = new ChartConfigurationFieldRetriever(
            $this->form_element_field_factoy,
            $this->semantic_timeframe_builder,
            new NullLogger(),
        );
    }

    public function testItThrowsAnExceptionWhenDurationFieldDoesNotExist(): void
    {
        $this->tracker->method('hasFormElementWithNameAndType')->with(
            'duration',
            ['int', 'float', 'computed']
        )->willReturn(true);

        $this->form_element_field_factoy->method('getNumericFieldByNameForUser')->with(
            $this->tracker,
            $this->user,
            'duration'
        )->willReturn(null);

        $this->semantic_timeframe_builder->method('getSemantic')
            ->willReturn(new SemanticTimeframe($this->tracker, new TimeframeNotConfigured()));

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);
        $this->expectExceptionMessage(
            "The tracker doesn't have a \"duration\" Integer field or you don't have the permission to access it."
        );

        $this->configuration_retriever->getDurationField($this->tracker, $this->user);
    }

    public function testItThrowsAnExceptionWhenDurationFieldExistsButUserCannotReadIt(): void
    {
        $this->tracker->method('hasFormElementWithNameAndType')->with(
            'duration',
            ['int', 'float', 'computed']
        )->willReturn(true);

        $this->form_element_field_factoy->method('getNumericFieldByNameForUser')->with(
            $this->tracker,
            $this->user,
            'duration'
        )->willReturn(null);

        $this->semantic_timeframe_builder->method('getSemantic')->willReturn(
            new SemanticTimeframe(
                $this->tracker,
                new TimeframeWithDuration(
                    $this->field_start_date,
                    $this->field_duration,
                )
            )
        );

        $this->field_duration->setUserCanRead($this->user, false);

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);

        $this->expectExceptionMessage(
            "The tracker doesn't have a \"duration\" Integer field or you don't have the permission to access it."
        );

        $this->configuration_retriever->getDurationField($this->tracker, $this->user);
    }

    public function testItReturnsDurationFieldWhenDurationFieldExistsAnUserCanReadIt(): void
    {
        $this->tracker->method('hasFormElementWithNameAndType')->with(
            'duration',
            ['int', 'float', 'computed']
        )->willReturn(true);

        $this->form_element_field_factoy->method('getNumericFieldByNameForUser')->with(
            $this->tracker,
            $this->user,
            'duration'
        )->willReturn($this->field_duration);

        $this->semantic_timeframe_builder->method('getSemantic')->willReturn(
            new SemanticTimeframe(
                $this->tracker,
                new TimeframeWithDuration(
                    $this->field_start_date,
                    $this->field_duration,
                )
            )
        );

        $this->field_duration->setUserCanRead($this->user, true);

        self::assertSame(
            $this->configuration_retriever->getDurationField($this->tracker, $this->user),
            $this->field_duration
        );
    }

    public function testItThrowsAnExceptionWhenStartDateFieldDoesNotExist(): void
    {
        $this->tracker->method('hasFormElementWithNameAndType')->with(
            'start_date',
            ['date']
        )->willReturn(true);

        $this->semantic_timeframe_builder->method('getSemantic')->willReturn(
            new SemanticTimeframe(
                $this->tracker,
                new TimeframeNotConfigured()
            )
        );

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);

        $this->expectExceptionMessage(
            "The tracker doesn't have a \"start_date\" Date field or you don't have the permission to access it."
        );

        $this->configuration_retriever->getStartDateField($this->tracker, $this->user);
    }

    public function testItThrowsAnExceptionWhenStartDateFieldExistsButUserCannotReadIt(): void
    {
        $this->tracker->method('hasFormElementWithNameAndType')->with(
            'start_date',
            ['date']
        )->willReturn(true);

        $this->semantic_timeframe_builder->method('getSemantic')->willReturn(
            new SemanticTimeframe(
                $this->tracker,
                new TimeframeWithDuration(
                    $this->field_start_date,
                    $this->field_duration,
                )
            )
        );

        $this->field_start_date->setUserCanRead($this->user, false);

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);

        $this->expectExceptionMessage(
            "The tracker doesn't have a \"start_date\" Date field or you don't have the permission to access it."
        );

        $this->configuration_retriever->getStartDateField($this->tracker, $this->user);
    }

    public function testItReturnsStartDateFieldWhenStartDateFieldExistsAnUserCanReadIt(): void
    {
        $this->tracker->method('hasFormElementWithNameAndType')->with(
            'start_date',
            ['date']
        )->willReturn(true);

        $this->semantic_timeframe_builder->method('getSemantic')->willReturn(
            new SemanticTimeframe(
                $this->tracker,
                new TimeframeWithDuration(
                    $this->field_start_date,
                    $this->field_duration,
                )
            )
        );

        $this->field_start_date->setUserCanRead($this->user, true);

        self::assertSame(
            $this->field_start_date,
            $this->configuration_retriever->getStartDateField($this->tracker, $this->user)
        );
    }

    public function testItThrowsAnExceptionWhenCapacityFieldDoesNotExist(): void
    {
        $this->tracker->method('hasFormElementWithNameAndType')->with(
            'capacity',
            ['int', 'float', 'computed']
        )->willReturn(false);

        $this->form_element_field_factoy->method('getNumericFieldByName')->with(
            $this->tracker,
            'capacity'
        )->willReturn(null);

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);

        $this->expectExceptionMessage(
            'The tracker doesn\'t have a "capacity" Integer or Float or Computed field or you don\'t have the permission to access it.'
        );

        $this->configuration_retriever->getCapacityField($this->tracker);
    }

    public function testItReturnsCapacityFieldWhenFieldExist(): void
    {
        $this->tracker->method('hasFormElementWithNameAndType')->with(
            'capacity',
            ['int', 'float', 'computed']
        )->willReturn(true);

        $this->form_element_field_factoy->method('getNumericFieldByName')->with(
            $this->tracker,
            'capacity'
        )->willReturn($this->field_capacity);

        self::assertSame(
            $this->configuration_retriever->getCapacityField($this->tracker),
            $this->field_capacity
        );
    }

    public function testItReturnsNullWhenRemainingEffortFieldDoesNotExist(): void
    {
        $form_element_factory = Tracker_FormElementFactory::instance();

        $configuration_retriever = new ChartConfigurationFieldRetriever(
            $form_element_factory,
            $this->semantic_timeframe_builder,
            new NullLogger(),
        );

        $this->tracker->method('hasFormElementWithNameAndType')->with(
            'remaining_effort',
            ['int', 'float', 'computed']
        )->willReturn(false);

        $this->assertNull($configuration_retriever->getBurndownRemainingEffortField($this->artifact, $this->user));
    }

    public function testItReturnsNullWhenRemainingEffortFieldExistsAndUserCanNotReadIt(): void
    {
        $this->tracker->method('hasFormElementWithNameAndType')->with(
            'remaining_effort',
            ['int', 'float', 'computed']
        )->willReturn(true);

        $this->form_element_field_factoy->method('getNumericFieldByName')->with(
            $this->tracker,
            'remaining_effort'
        )->willReturn($this->field_remaining_effort);

        $this->form_element_field_factoy->method('getNumericFieldByNameForUser')->willReturn(null);

        $this->form_element_field_factoy->method('getUsedFieldByName')->with(
            $this->tracker->getId(),
            'remaining_effort'
        )->willReturn($this->field_remaining_effort);

        $this->field_remaining_effort->setUserCanRead($this->user, false);

        $this->assertNull($this->configuration_retriever->getBurndownRemainingEffortField($this->artifact, $this->user));
    }

    public function testItReturnsFieldWhenRemainingEffortFieldExistsAndUserCanReadIt(): void
    {
        $this->tracker->method('hasFormElementWithNameAndType')->with(
            'remaining_effort',
            ['int', 'float', 'computed']
        )->willReturn(true);

        $this->form_element_field_factoy->method('getNumericFieldByName')->with(
            $this->tracker,
            'remaining_effort'
        )->willReturn($this->field_remaining_effort);

        $this->form_element_field_factoy->method('getNumericFieldByNameForUser')->willReturn($this->field_remaining_effort);

        $this->form_element_field_factoy->method('getUsedFieldByName')->with(
            $this->tracker->getId(),
            'remaining_effort'
        )->willReturn($this->field_remaining_effort);

        $this->field_remaining_effort->setUserCanRead($this->user, true);

        self::assertSame(
            $this->field_remaining_effort,
            $this->configuration_retriever->getBurndownRemainingEffortField($this->artifact, $this->user)
        );
    }
}

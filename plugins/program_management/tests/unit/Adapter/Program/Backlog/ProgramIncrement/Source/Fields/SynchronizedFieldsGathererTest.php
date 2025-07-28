<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Fields;

use PHPUnit\Framework\MockObject\Stub;
use Tracker_FormElement_Field_Date;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\DurationFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\EndDateFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\MissingTimeFrameFieldException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\NoArtifactLinkFieldException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\TitleFieldHasIncorrectTypeException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementTrackerIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullArtifactLinkFieldStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatusFactory;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeNotConfigured;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithDuration;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithEndDate;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Semantic\Description\RetrieveSemanticDescriptionFieldStub;
use Tuleap\Tracker\Test\Stub\Semantic\Title\RetrieveSemanticTitleFieldStub;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SynchronizedFieldsGathererTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const int PROGRAM_INCREMENT_TRACKER_ID = 37;
    private Stub&TrackerSemanticStatusFactory $status_factory;
    private Stub&SemanticTimeframeBuilder $timeframe_builder;
    private RetrieveFullArtifactLinkFieldStub $artifact_link_retriever;
    private Tracker $tracker;
    private ProgramIncrementTrackerIdentifier $tracker_identifier;
    private RetrieveSemanticTitleFieldStub $retrieve_semantic_title_field;
    private RetrieveSemanticDescriptionFieldStub $retrieve_semantic_description_field;

    #[\Override]
    protected function setUp(): void
    {
        $this->status_factory          = $this->createStub(\Tuleap\Tracker\Semantic\Status\TrackerSemanticStatusFactory::class);
        $this->timeframe_builder       = $this->createStub(SemanticTimeframeBuilder::class);
        $this->artifact_link_retriever = RetrieveFullArtifactLinkFieldStub::withNoField();

        $this->tracker_identifier = ProgramIncrementTrackerIdentifierBuilder::buildWithId(
            self::PROGRAM_INCREMENT_TRACKER_ID
        );
        $this->tracker            = TrackerTestBuilder::aTracker()
            ->withId(self::PROGRAM_INCREMENT_TRACKER_ID)
            ->withProject(ProjectTestBuilder::aProject()->build())
            ->build();

        $this->retrieve_semantic_title_field       = RetrieveSemanticTitleFieldStub::build();
        $this->retrieve_semantic_description_field = RetrieveSemanticDescriptionFieldStub::build();
    }

    private function getGatherer(): SynchronizedFieldsGatherer
    {
        return new SynchronizedFieldsGatherer(
            RetrieveFullTrackerStub::withTracker($this->tracker),
            $this->retrieve_semantic_title_field,
            $this->retrieve_semantic_description_field,
            $this->status_factory,
            $this->timeframe_builder,
            $this->artifact_link_retriever
        );
    }

    public function testItThrowsWhenTitleFieldCantBeFound(): void
    {
        $this->expectException(FieldRetrievalException::class);
        $this->getGatherer()->getTitleField($this->tracker_identifier, null);
    }

    public function testItThrowsWhenTitleIsNotAString(): void
    {
        $this->retrieve_semantic_title_field->withTitleField($this->tracker, $this->getTextField(1, 'Title'));
        $this->expectException(TitleFieldHasIncorrectTypeException::class);
        $this->getGatherer()->getTitleField($this->tracker_identifier, null);
    }

    public function testItCollectsErrorWhenTitleIsNotAString(): void
    {
        $this->retrieve_semantic_title_field->withTitleField($this->tracker, $this->getTextField(1, 'Title'));
        $this->expectException(TitleFieldHasIncorrectTypeException::class);
        $errors_collector = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false);
        $this->getGatherer()->getTitleField($this->tracker_identifier, $errors_collector);
        $this->assertCount(1, $errors_collector->getTitleHasIncorrectTypeError());
    }

    public function testItReturnsTitleReference(): void
    {
        $this->retrieve_semantic_title_field->withTitleField(
            $this->tracker,
            StringFieldBuilder::aStringField(832)
                ->inTracker($this->tracker)
                ->withLabel('Semiacquaintance')
                ->build()
        );
        $title = $this->getGatherer()->getTitleField($this->tracker_identifier, null);
        self::assertSame(832, $title->getId());
        self::assertSame('Semiacquaintance', $title->getLabel());
    }

    public function testItThrowsWhenDescriptionFieldCantBeFound(): void
    {
        $this->expectException(FieldRetrievalException::class);
        $this->getGatherer()->getDescriptionField($this->tracker_identifier);
    }

    public function testItReturnsDescriptionReference(): void
    {
        $this->retrieve_semantic_description_field->withDescriptionField($this->getTextField(693, 'Smokish'));

        $description = $this->getGatherer()->getDescriptionField($this->tracker_identifier);
        self::assertSame(693, $description->getId());
        self::assertSame('Smokish', $description->getLabel());
    }

    public function testItThrowsWhenStatusFieldCantBeFound(): void
    {
        $status_semantic = new TrackerSemanticStatus($this->tracker);
        $this->status_factory->method('getByTracker')->willReturn($status_semantic);

        $this->expectException(FieldRetrievalException::class);
        $this->getGatherer()->getStatusField($this->tracker_identifier);
    }

    public function testItReturnsStatusReference(): void
    {
        $status_semantic = new TrackerSemanticStatus(
            $this->tracker,
            ListFieldBuilder::aListField(525)
                ->inTracker($this->tracker)
                ->withLabel('Kettle')
                ->build()
        );
        $this->status_factory->method('getByTracker')->willReturn($status_semantic);

        $status = $this->getGatherer()->getStatusField($this->tracker_identifier);
        self::assertSame(525, $status->getId());
        self::assertSame('Kettle', $status->getLabel());
    }

    public function testItThrowsWhenDateFieldCantBeFound(): void
    {
        $timeframe_semantic = new SemanticTimeframe($this->tracker, new TimeframeNotConfigured());
        $this->timeframe_builder->method('getSemantic')->willReturn($timeframe_semantic);

        $this->expectException(MissingTimeFrameFieldException::class);
        $this->getGatherer()->getStartDateField($this->tracker_identifier);
    }

    public function testItReturnsStartDateReference(): void
    {
        $timeframe_semantic = new SemanticTimeframe(
            $this->tracker,
            new TimeframeWithEndDate($this->getDateField(101, 'hebetate'), $this->getDateField(981, 'polyphore'))
        );
        $this->timeframe_builder->method('getSemantic')->willReturn($timeframe_semantic);

        $start_date = $this->getGatherer()->getStartDateField($this->tracker_identifier);
        self::assertSame(101, $start_date->getId());
        self::assertSame('hebetate', $start_date->getLabel());
    }

    public function testItThrowsWhenNeitherEndDateNorDurationFieldCanBeFound(): void
    {
        $timeframe_semantic = new SemanticTimeframe($this->tracker, new TimeframeNotConfigured());
        $this->timeframe_builder->method('getSemantic')->willReturn($timeframe_semantic);

        $this->expectException(MissingTimeFrameFieldException::class);
        $this->getGatherer()->getEndPeriodField($this->tracker_identifier);
    }

    public function testItReturnsDurationReference(): void
    {
        $duration_field     = IntegerFieldBuilder::anIntField(429)
            ->inTracker($this->tracker)
            ->withLabel('Maclura')
            ->build();
        $timeframe_semantic = new SemanticTimeframe(
            $this->tracker,
            new TimeframeWithDuration($this->getDateField(942, 'micher'), $duration_field)
        );
        $this->timeframe_builder->method('getSemantic')->willReturn($timeframe_semantic);

        $end_period = $this->getGatherer()->getEndPeriodField($this->tracker_identifier);
        self::assertInstanceOf(DurationFieldReference::class, $end_period);
        self::assertSame(429, $end_period->getId());
        self::assertSame('Maclura', $end_period->getLabel());
    }

    public function testItReturnsEndDateReference(): void
    {
        $timeframe_semantic = new SemanticTimeframe(
            $this->tracker,
            new TimeframeWithEndDate($this->getDateField(591, 'privy'), $this->getDateField(754, 'block'))
        );
        $this->timeframe_builder->method('getSemantic')->willReturn($timeframe_semantic);

        $end_period = $this->getGatherer()->getEndPeriodField($this->tracker_identifier);
        self::assertInstanceOf(EndDateFieldReference::class, $end_period);
        self::assertSame(754, $end_period->getId());
        self::assertSame('block', $end_period->getLabel());
    }

    public function testItThrowsWhenArtifactLinkFieldCantBeFound(): void
    {
        $this->expectException(NoArtifactLinkFieldException::class);
        $this->getGatherer()->getArtifactLinkField($this->tracker_identifier, null);
    }

    public function testItReturnsArtifactLinkReference(): void
    {
        $this->artifact_link_retriever = RetrieveFullArtifactLinkFieldStub::withField(
            ArtifactLinkFieldBuilder::anArtifactLinkField(623)
                ->withLabel('premanifest')
                ->withTrackerId(self::PROGRAM_INCREMENT_TRACKER_ID)
                ->build()
        );

        $artifact_link = $this->getGatherer()->getArtifactLinkField($this->tracker_identifier, null);
        self::assertSame(623, $artifact_link->getId());
        self::assertSame('premanifest', $artifact_link->getLabel());
    }

    private function getTextField(int $id, string $label): TextField
    {
        return TextFieldBuilder::aTextField($id)
            ->inTracker($this->tracker)
            ->withLabel($label)
            ->build();
    }

    private function getDateField(int $id, string $label): Tracker_FormElement_Field_Date
    {
        return DateFieldBuilder::aDateField($id)
            ->inTracker($this->tracker)
            ->withLabel($label)
            ->build();
    }
}

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

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\DescriptionFieldReference;
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
use Tuleap\Tracker\Semantic\Description\RetrieveSemanticDescriptionField;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeNotConfigured;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithDuration;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithEndDate;
use Tuleap\Tracker\Semantic\Title\RetrieveSemanticTitleField;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Semantic\Description\RetrieveSemanticDescriptionFieldStub;
use Tuleap\Tracker\Test\Stub\Semantic\Title\RetrieveSemanticTitleFieldStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SynchronizedFieldsGathererTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_TRACKER_ID = 37;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\Tuleap\Tracker\Semantic\Status\TrackerSemanticStatusFactory
     */
    private $status_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&SemanticTimeframeBuilder
     */
    private $timeframe_builder;
    private RetrieveFullArtifactLinkFieldStub $artifact_link_retriever;
    private \Tuleap\Tracker\Tracker $tracker;
    private ProgramIncrementTrackerIdentifier $tracker_identifier;

    protected function setUp(): void
    {
        $this->status_factory          = $this->createStub(\Tuleap\Tracker\Semantic\Status\TrackerSemanticStatusFactory::class);
        $this->timeframe_builder       = $this->createStub(SemanticTimeframeBuilder::class);
        $this->artifact_link_retriever = RetrieveFullArtifactLinkFieldStub::withNoField();

        $this->tracker_identifier = ProgramIncrementTrackerIdentifierBuilder::buildWithId(
            self::PROGRAM_INCREMENT_TRACKER_ID
        );
        $project                  = new \Project(['group_id' => 101, 'group_name' => 'My project']);
        $this->tracker            = TrackerTestBuilder::aTracker()
            ->withId(self::PROGRAM_INCREMENT_TRACKER_ID)
            ->withProject($project)
            ->build();
    }

    private function getGatherer(
        RetrieveSemanticTitleField $retrieve_semantic_title_field,
    ): SynchronizedFieldsGatherer {
        return new SynchronizedFieldsGatherer(
            RetrieveFullTrackerStub::withTracker($this->tracker),
            $retrieve_semantic_title_field,
            RetrieveSemanticDescriptionFieldStub::withNoField(),
            $this->status_factory,
            $this->timeframe_builder,
            $this->artifact_link_retriever
        );
    }

    private function getDescriptionField(RetrieveSemanticDescriptionField $retrieve_semantic_description_field): DescriptionFieldReference
    {
        $gatherer = new SynchronizedFieldsGatherer(
            RetrieveFullTrackerStub::withTracker($this->tracker),
            RetrieveSemanticTitleFieldStub::build(),
            $retrieve_semantic_description_field,
            $this->status_factory,
            $this->timeframe_builder,
            $this->artifact_link_retriever
        );
        return $gatherer->getDescriptionField($this->tracker_identifier);
    }

    public function testItThrowsWhenTitleFieldCantBeFound(): void
    {
        $this->expectException(FieldRetrievalException::class);
        $this->getGatherer(RetrieveSemanticTitleFieldStub::build())->getTitleField($this->tracker_identifier, null);
    }

    public function testItThrowsWhenTitleIsNotAString(): void
    {
        $this->expectException(TitleFieldHasIncorrectTypeException::class);
        $this->getGatherer(RetrieveSemanticTitleFieldStub::build()->withTitleField($this->tracker, $this->getTextField(1, 'Title')))->getTitleField($this->tracker_identifier, null);
    }

    public function testItCollectsErrorWhenTitleIsNotAString(): void
    {
        $this->expectException(TitleFieldHasIncorrectTypeException::class);
        $errors_collector = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false);
        $this->getGatherer(RetrieveSemanticTitleFieldStub::build()->withTitleField($this->tracker, $this->getTextField(1, 'Title')))->getTitleField($this->tracker_identifier, $errors_collector);
        $this->assertCount(1, $errors_collector->getTitleHasIncorrectTypeError());
    }

    public function testItReturnsTitleReference(): void
    {
        $title = $this->getGatherer(RetrieveSemanticTitleFieldStub::build()->withTitleField($this->tracker, $this->getStringField(832, 'Semiacquaintance')))->getTitleField($this->tracker_identifier, null);
        self::assertSame(832, $title->getId());
        self::assertSame('Semiacquaintance', $title->getLabel());
    }

    public function testItThrowsWhenDescriptionFieldCantBeFound(): void
    {
        $this->expectException(FieldRetrievalException::class);
        $this->getDescriptionField(
            RetrieveSemanticDescriptionFieldStub::withNoField(),
        );
    }

    public function testItReturnsDescriptionReference(): void
    {
        $description = $this->getDescriptionField(
            RetrieveSemanticDescriptionFieldStub::withTextField($this->getTextField(693, 'Smokish'))
        );
        self::assertSame(693, $description->getId());
        self::assertSame('Smokish', $description->getLabel());
    }

    public function testItThrowsWhenStatusFieldCantBeFound(): void
    {
        $status_semantic = new \Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus($this->tracker);
        $this->status_factory->method('getByTracker')->willReturn($status_semantic);

        $this->expectException(FieldRetrievalException::class);
        $this->getGatherer(RetrieveSemanticTitleFieldStub::build())->getStatusField($this->tracker_identifier);
    }

    public function testItReturnsStatusReference(): void
    {
        $status_semantic = new \Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus($this->tracker, $this->getSelectboxField(525, 'Kettle'));
        $this->status_factory->method('getByTracker')->willReturn($status_semantic);

        $status = $this->getGatherer(RetrieveSemanticTitleFieldStub::build())->getStatusField($this->tracker_identifier);
        self::assertSame(525, $status->getId());
        self::assertSame('Kettle', $status->getLabel());
    }

    public function testItThrowsWhenDateFieldCantBeFound(): void
    {
        $timeframe_semantic = new SemanticTimeframe($this->tracker, new TimeframeNotConfigured());
        $this->timeframe_builder->method('getSemantic')->willReturn($timeframe_semantic);

        $this->expectException(MissingTimeFrameFieldException::class);
        $this->getGatherer(RetrieveSemanticTitleFieldStub::build())->getStartDateField($this->tracker_identifier);
    }

    public function testItReturnsStartDateReference(): void
    {
        $timeframe_semantic = new SemanticTimeframe(
            $this->tracker,
            new TimeframeWithEndDate($this->getDateField(101, 'hebetate'), $this->getDateField(981, 'polyphore'))
        );
        $this->timeframe_builder->method('getSemantic')->willReturn($timeframe_semantic);

        $start_date = $this->getGatherer(RetrieveSemanticTitleFieldStub::build())->getStartDateField($this->tracker_identifier);
        self::assertSame(101, $start_date->getId());
        self::assertSame('hebetate', $start_date->getLabel());
    }

    public function testItThrowsWhenNeitherEndDateNorDurationFieldCanBeFound(): void
    {
        $timeframe_semantic = new SemanticTimeframe($this->tracker, new TimeframeNotConfigured());
        $this->timeframe_builder->method('getSemantic')->willReturn($timeframe_semantic);

        $this->expectException(MissingTimeFrameFieldException::class);
        $this->getGatherer(RetrieveSemanticTitleFieldStub::build())->getEndPeriodField($this->tracker_identifier);
    }

    public function testItReturnsDurationReference(): void
    {
        $timeframe_semantic = new SemanticTimeframe(
            $this->tracker,
            new TimeframeWithDuration($this->getDateField(942, 'micher'), $this->getIntField(429, 'Maclura'))
        );
        $this->timeframe_builder->method('getSemantic')->willReturn($timeframe_semantic);

        $end_period = $this->getGatherer(RetrieveSemanticTitleFieldStub::build())->getEndPeriodField($this->tracker_identifier);
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

        $end_period = $this->getGatherer(RetrieveSemanticTitleFieldStub::build())->getEndPeriodField($this->tracker_identifier);
        self::assertInstanceOf(EndDateFieldReference::class, $end_period);
        self::assertSame(754, $end_period->getId());
        self::assertSame('block', $end_period->getLabel());
    }

    public function testItThrowsWhenArtifactLinkFieldCantBeFound(): void
    {
        $this->expectException(NoArtifactLinkFieldException::class);
        $this->getGatherer(RetrieveSemanticTitleFieldStub::build())->getArtifactLinkField($this->tracker_identifier, null);
    }

    public function testItReturnsArtifactLinkReference(): void
    {
        $this->artifact_link_retriever = RetrieveFullArtifactLinkFieldStub::withField(
            ArtifactLinkFieldBuilder::anArtifactLinkField(623)
                ->withLabel('premanifest')
                ->withTrackerId(self::PROGRAM_INCREMENT_TRACKER_ID)
                ->build()
        );

        $artifact_link = $this->getGatherer(RetrieveSemanticTitleFieldStub::build())->getArtifactLinkField($this->tracker_identifier, null);
        self::assertSame(623, $artifact_link->getId());
        self::assertSame('premanifest', $artifact_link->getLabel());
    }

    private function getStringField(int $id, string $label): \Tuleap\Tracker\FormElement\Field\String\StringField
    {
        return new \Tuleap\Tracker\FormElement\Field\String\StringField(
            $id,
            self::PROGRAM_INCREMENT_TRACKER_ID,
            1,
            'irrelevant',
            $label,
            'Irrelevant',
            true,
            'P',
            true,
            '',
            1
        );
    }

    private function getTextField(int $id, string $label): \Tuleap\Tracker\FormElement\Field\Text\TextField
    {
        return new \Tuleap\Tracker\FormElement\Field\Text\TextField(
            $id,
            self::PROGRAM_INCREMENT_TRACKER_ID,
            1,
            'irrelevant',
            $label,
            'Irrelevant',
            true,
            'P',
            true,
            '',
            1
        );
    }

    private function getSelectboxField(int $id, string $label): \Tracker_FormElement_Field_Selectbox
    {
        return new \Tracker_FormElement_Field_Selectbox(
            $id,
            self::PROGRAM_INCREMENT_TRACKER_ID,
            1,
            'irrelevant',
            $label,
            'Irrelevant',
            true,
            'P',
            true,
            '',
            1
        );
    }

    private function getDateField(int $id, string $label): \Tracker_FormElement_Field_Date
    {
        return new \Tracker_FormElement_Field_Date(
            $id,
            self::PROGRAM_INCREMENT_TRACKER_ID,
            1,
            'irrelevant',
            $label,
            'Irrelevant',
            true,
            'P',
            true,
            '',
            1
        );
    }

    private function getIntField(int $id, string $label): \Tracker_FormElement_Field_Integer
    {
        return new \Tracker_FormElement_Field_Integer(
            $id,
            self::PROGRAM_INCREMENT_TRACKER_ID,
            1,
            'irrelevant',
            $label,
            'Irrelevant',
            true,
            'P',
            true,
            '',
            1
        );
    }
}

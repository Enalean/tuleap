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

use PHPUnit\Framework\MockObject\Stub\Stub;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\MissingTimeFrameFieldException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\NoArtifactLinkFieldException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\TitleFieldHasIncorrectTypeException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerIdentifier;
use Tuleap\ProgramManagement\Domain\TrackerNotFoundException;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeNotConfigured;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithDuration;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithEndDate;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class SynchronizedFieldsGathererTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_TRACKER_ID = 37;
    private Stub|\TrackerFactory $tracker_factory;
    private Stub|\Tracker_Semantic_TitleFactory $title_factory;
    private Stub|\Tracker_Semantic_DescriptionFactory $description_factory;
    private Stub|\Tracker_Semantic_StatusFactory $status_factory;
    private Stub|SemanticTimeframeBuilder $timeframe_builder;
    private Stub|\Tracker_FormElementFactory $form_element_factory;
    private \Tracker $tracker;
    private ?ProgramIncrementTrackerIdentifier $tracker_identifier;

    protected function setUp(): void
    {
        $this->tracker_factory      = $this->createStub(\TrackerFactory::class);
        $this->title_factory        = $this->createStub(\Tracker_Semantic_TitleFactory::class);
        $this->description_factory  = $this->createStub(\Tracker_Semantic_DescriptionFactory::class);
        $this->status_factory       = $this->createStub(\Tracker_Semantic_StatusFactory::class);
        $this->timeframe_builder    = $this->createStub(SemanticTimeframeBuilder::class);
        $this->form_element_factory = $this->createStub(\Tracker_FormElementFactory::class);

        $this->tracker_identifier = ProgramIncrementTrackerIdentifier::fromId(
            VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement(),
            self::PROGRAM_INCREMENT_TRACKER_ID
        );
        $project                  = new \Project(['group_id' => 101, 'group_name' => "My project"]);
        $this->tracker            = TrackerTestBuilder::aTracker()
            ->withId(self::PROGRAM_INCREMENT_TRACKER_ID)
            ->withProject($project)
            ->build();
    }

    private function getGatherer(): SynchronizedFieldsGatherer
    {
        return new SynchronizedFieldsGatherer(
            $this->tracker_factory,
            $this->title_factory,
            $this->description_factory,
            $this->status_factory,
            $this->timeframe_builder,
            $this->form_element_factory
        );
    }

    public function dataProviderMethodUnderTest(): array
    {
        return [
            'when getting title field'         => ['getTitleField'],
            'when getting description field'   => ['getDescriptionField'],
            'when getting status field'        => ['getStatusField'],
            'when getting start date field'    => ['getStartDateField'],
            'when getting end period field'    => ['getEndPeriodField'],
            'when getting artifact link field' => ['getArtifactLinkField'],
        ];
    }

    /**
     * @dataProvider dataProviderMethodUnderTest
     */
    public function testItThrowsWhenTrackerCantBeFound(string $method_under_test): void
    {
        $this->tracker_factory->method('getTrackerById')->willReturn(null);

        $this->expectException(TrackerNotFoundException::class);
        call_user_func([$this->getGatherer(), $method_under_test], $this->tracker_identifier, null);
    }

    public function testItThrowsWhenTitleFieldCantBeFound(): void
    {
        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);
        $title_semantic = new \Tracker_Semantic_Title($this->tracker);
        $this->title_factory->method('getByTracker')->willReturn($title_semantic);

        $this->expectException(FieldRetrievalException::class);
        $this->getGatherer()->getTitleField($this->tracker_identifier, null);
    }

    public function testItThrowsWhenTitleIsNotAString(): void
    {
        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);
        $title_semantic = new \Tracker_Semantic_Title($this->tracker, $this->getTextField(1, 'Title'));
        $this->title_factory->method('getByTracker')->willReturn($title_semantic);

        $this->expectException(TitleFieldHasIncorrectTypeException::class);
        $this->getGatherer()->getTitleField($this->tracker_identifier, null);
    }

    public function testItCollectsErrorWhenTitleIsNotAString(): void
    {
        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);
        $title_semantic = new \Tracker_Semantic_Title($this->tracker, $this->getTextField(1, 'Title'));
        $this->title_factory->method('getByTracker')->willReturn($title_semantic);

        $this->expectException(TitleFieldHasIncorrectTypeException::class);
        $errors_collector = new ConfigurationErrorsCollector(false);
        $this->getGatherer()->getTitleField($this->tracker_identifier, $errors_collector);
        $this->assertCount(1, $errors_collector->getTitleHasIncorrectTypeError());
    }

    public function testItReturnsTitleReference(): void
    {
        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);
        $title_semantic = new \Tracker_Semantic_Title($this->tracker, $this->getStringField(832, 'Semiacquaintance'));
        $this->title_factory->method('getByTracker')->willReturn($title_semantic);

        $title = $this->getGatherer()->getTitleField($this->tracker_identifier, null);
        self::assertSame(832, $title->getId());
        self::assertSame('Semiacquaintance', $title->getLabel());
    }

    public function testItThrowsWhenDescriptionFieldCantBeFound(): void
    {
        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);
        $description_semantic = new \Tracker_Semantic_Description($this->tracker);
        $this->description_factory->method('getByTracker')->willReturn($description_semantic);

        $this->expectException(FieldRetrievalException::class);
        $this->getGatherer()->getDescriptionField($this->tracker_identifier);
    }

    public function testItReturnsDescriptionReference(): void
    {
        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);
        $description_semantic = new \Tracker_Semantic_Description($this->tracker, $this->getTextField(693, 'Smokish'));
        $this->description_factory->method('getByTracker')->willReturn($description_semantic);

        $description = $this->getGatherer()->getDescriptionField($this->tracker_identifier);
        self::assertSame(693, $description->getId());
        self::assertSame('Smokish', $description->getLabel());
    }

    public function testItThrowsWhenStatusFieldCantBeFound(): void
    {
        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);
        $status_semantic = new \Tracker_Semantic_Status($this->tracker);
        $this->status_factory->method('getByTracker')->willReturn($status_semantic);

        $this->expectException(FieldRetrievalException::class);
        $this->getGatherer()->getStatusField($this->tracker_identifier);
    }

    public function testItReturnsStatusReference(): void
    {
        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);
        $status_semantic = new \Tracker_Semantic_Status($this->tracker, $this->getSelectboxField(525, 'Kettle'));
        $this->status_factory->method('getByTracker')->willReturn($status_semantic);

        $status = $this->getGatherer()->getStatusField($this->tracker_identifier);
        self::assertSame(525, $status->getId());
        self::assertSame('Kettle', $status->getLabel());
    }

    public function testItThrowsWhenDateFieldCantBeFound(): void
    {
        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);
        $timeframe_semantic = new SemanticTimeframe($this->tracker, new TimeframeNotConfigured());
        $this->timeframe_builder->method('getSemantic')->willReturn($timeframe_semantic);

        $this->expectException(MissingTimeFrameFieldException::class);
        $this->getGatherer()->getStartDateField($this->tracker_identifier);
    }

    public function testItReturnsStartDateReference(): void
    {
        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);
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
        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);
        $timeframe_semantic = new SemanticTimeframe($this->tracker, new TimeframeNotConfigured());
        $this->timeframe_builder->method('getSemantic')->willReturn($timeframe_semantic);

        $this->expectException(MissingTimeFrameFieldException::class);
        $this->getGatherer()->getEndPeriodField($this->tracker_identifier);
    }

    public function testItReturnsEndPeriodReferenceWithDuration(): void
    {
        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);
        $timeframe_semantic = new SemanticTimeframe(
            $this->tracker,
            new TimeframeWithDuration($this->getDateField(942, 'micher'), $this->getIntField(429, 'Maclura'))
        );
        $this->timeframe_builder->method('getSemantic')->willReturn($timeframe_semantic);

        $end_period = $this->getGatherer()->getEndPeriodField($this->tracker_identifier);
        self::assertSame(429, $end_period->getId());
        self::assertSame('Maclura', $end_period->getLabel());
    }

    public function testItReturnsEndPeriodReferenceWithEndDate(): void
    {
        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);
        $timeframe_semantic = new SemanticTimeframe(
            $this->tracker,
            new TimeframeWithEndDate($this->getDateField(591, 'privy'), $this->getDateField(754, 'block'))
        );
        $this->timeframe_builder->method('getSemantic')->willReturn($timeframe_semantic);

        $end_period = $this->getGatherer()->getEndPeriodField($this->tracker_identifier);
        self::assertSame(754, $end_period->getId());
        self::assertSame('block', $end_period->getLabel());
    }

    public function testItThrowsWhenArtifactLinkFieldCantBeFound(): void
    {
        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);
        $this->form_element_factory->method('getUsedArtifactLinkFields')->willReturn([]);

        $this->expectException(NoArtifactLinkFieldException::class);
        $this->getGatherer()->getArtifactLinkField($this->tracker_identifier, null);
    }

    public function testItReturnsArtifactLinkReference(): void
    {
        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);
        $this->form_element_factory->method('getUsedArtifactLinkFields')
            ->willReturn([$this->getArtifactLinkField(623, 'premanifest')]);

        $artifact_link = $this->getGatherer()->getArtifactLinkField($this->tracker_identifier, null);
        self::assertSame(623, $artifact_link->getId());
        self::assertSame('premanifest', $artifact_link->getLabel());
    }

    private function getStringField(int $id, string $label): \Tracker_FormElement_Field_String
    {
        return new \Tracker_FormElement_Field_String(
            $id,
            self::PROGRAM_INCREMENT_TRACKER_ID,
            null,
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

    private function getTextField(int $id, string $label): \Tracker_FormElement_Field_Text
    {
        return new \Tracker_FormElement_Field_Text(
            $id,
            self::PROGRAM_INCREMENT_TRACKER_ID,
            null,
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
            null,
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
            null,
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
            null,
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

    private function getArtifactLinkField(int $id, string $label): \Tracker_FormElement_Field_ArtifactLink
    {
        return new \Tracker_FormElement_Field_ArtifactLink(
            $id,
            self::PROGRAM_INCREMENT_TRACKER_ID,
            null,
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

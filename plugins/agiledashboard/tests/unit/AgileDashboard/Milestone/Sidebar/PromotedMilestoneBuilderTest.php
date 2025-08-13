<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Sidebar;

use DateTime;
use PlanningFactory;
use Psr\Log\NullLogger;
use Tracker_Artifact_ChangesetValue_Text;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeNotConfigured;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithEndDate;
use Tuleap\Tracker\Semantic\Title\RetrieveSemanticTitleField;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueDateTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Semantic\Title\RetrieveSemanticTitleFieldStub;

/**
 * @psalm-immutable
 */
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PromotedMilestoneBuilderTest extends TestCase
{
    private \PFUser $user;
    private \Project $project;
    private \Tuleap\Tracker\Tracker $tracker;
    private \Tuleap\Tracker\Artifact\Artifact $artifact;
    private \Tuleap\Tracker\FormElement\Field\String\StringField $title_field;
    private SemanticTimeframeBuilder|\PHPUnit\Framework\MockObject\MockObject $timeframe_builder;
    private \Tuleap\Tracker\FormElement\Field\Date\DateField $start_field;
    private \Tuleap\Tracker\FormElement\Field\Date\DateField $end_field;
    private \Tracker_Artifact_Changeset $changeset;
    private PlanningFactory|\PHPUnit\Framework\MockObject\MockObject $planning_factory;
    private RetrieveSemanticTitleField $retrieve_semantic_title_field;

    protected function setUp(): void
    {
        $this->changeset   = ChangesetTestBuilder::aChangeset(501)->build();
        $this->user        = UserTestBuilder::anActiveUser()->build();
        $this->project     = ProjectTestBuilder::aProject()->withId(101)->build();
        $this->tracker     = TrackerTestBuilder::aTracker()->build();
        $this->artifact    = ArtifactTestBuilder::anArtifact(1)
            ->inTracker($this->tracker)
            ->userCanView($this->user)
            ->withChangesets($this->changeset)
            ->build();
        $this->title_field = StringFieldBuilder::aStringField(301)
            ->inTracker($this->tracker)
            ->build();

        $this->timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $this->start_field       = DateFieldBuilder::aDateField(302)->build();
        $this->end_field         = DateFieldBuilder::aDateField(303)->build();

        $this->start_field->setUserCanRead($this->user, true);
        $this->end_field->setUserCanRead($this->user, true);
        $title_value = $this->createMock(Tracker_Artifact_ChangesetValue_Text::class);
        $this->changeset->setFieldValue($this->title_field, $title_value);
        $this->planning_factory = $this->createMock(PlanningFactory::class);

        $this->retrieve_semantic_title_field = RetrieveSemanticTitleFieldStub::build()->withTitleField(
            $this->title_field
        );
    }

    private function getBuilder(): PromotedMilestoneBuilder
    {
        return new PromotedMilestoneBuilder(
            $this->planning_factory,
            $this->timeframe_builder,
            $this->retrieve_semantic_title_field,
            new NullLogger(),
        );
    }

    public function testItReturnNothingOptionWhenUserCanNotViewArtifact(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(1)
            ->inTracker($this->tracker)
            ->userCannotView($this->user)
            ->build();

        self::assertTrue($this->getBuilder()->build($artifact, $this->user, $this->project)->isNothing());
    }

    public function testItReturnsNothingOptionWhenTitleFieldIsNotFound(): void
    {
        $this->retrieve_semantic_title_field = RetrieveSemanticTitleFieldStub::build();
        self::assertTrue($this->getBuilder()->build($this->artifact, $this->user, $this->project)->isNothing());
    }

    public function testItReturnsNothingOptionWhenTimeFrameSemanticIsNotDefined(): void
    {
        $this->timeframe_builder->method('getSemantic')->willReturn(new SemanticTimeframe(
            $this->tracker,
            new TimeframeNotConfigured()
        ));
        $this->changeset->setFieldValue(
            $this->start_field,
            ChangesetValueDateTestBuilder::aValue(
                1,
                $this->changeset,
                $this->start_field
            )->withTimestamp((new DateTime('-1day'))->getTimestamp())->build()
        );
        $this->changeset->setFieldValue(
            $this->end_field,
            ChangesetValueDateTestBuilder::aValue(
                2,
                $this->changeset,
                $this->end_field
            )->withTimestamp((new DateTime('+1day'))->getTimestamp())->build()
        );
        self::assertTrue($this->getBuilder()->build($this->artifact, $this->user, $this->project)->isNothing());
    }

    #[\PHPUnit\Framework\Attributes\TestWith([true, false])]
    #[\PHPUnit\Framework\Attributes\TestWith([false, true])]
    #[\PHPUnit\Framework\Attributes\TestWith([true, true])]
    public function testItReturnsNothingOptionWhenStartDateOrEndDateAreZero(bool $start_date_zero, bool $end_date_zero): void
    {
        $this->timeframe_builder->method('getSemantic')->willReturn(
            new SemanticTimeframe(
                $this->tracker,
                new TimeframeWithEndDate($this->start_field, $this->end_field)
            )
        );
        $this->changeset->setFieldValue(
            $this->start_field,
            ChangesetValueDateTestBuilder::aValue(
                1,
                $this->changeset,
                $this->start_field
            )->withTimestamp($start_date_zero ? 0 : (new DateTime('-1day'))->getTimestamp())->build()
        );
        $this->changeset->setFieldValue(
            $this->end_field,
            ChangesetValueDateTestBuilder::aValue(
                2,
                $this->changeset,
                $this->end_field
            )->withTimestamp($end_date_zero ? 0 : (new DateTime('+1day'))->getTimestamp())->build()
        );
        self::assertTrue($this->getBuilder()->build($this->artifact, $this->user, $this->project)->isNothing());
    }

    public function testItReturnsNothingOptionWhenTimeFrameSemanticIsNotCurrent(): void
    {
        $this->timeframe_builder->method('getSemantic')->willReturn(
            new SemanticTimeframe(
                $this->tracker,
                new TimeframeWithEndDate($this->start_field, $this->end_field)
            )
        );
        $this->changeset->setFieldValue(
            $this->start_field,
            ChangesetValueDateTestBuilder::aValue(
                1,
                $this->changeset,
                $this->start_field
            )->withTimestamp((new DateTime('-1month'))->getTimestamp())->build()
        );
        $this->changeset->setFieldValue(
            $this->end_field,
            ChangesetValueDateTestBuilder::aValue(
                2,
                $this->changeset,
                $this->end_field
            )->withTimestamp((new DateTime('-1day'))->getTimestamp())->build()
        );
        self::assertTrue($this->getBuilder()->build($this->artifact, $this->user, $this->project)->isNothing());
    }

    public function testItReturnsNothingOptionWhenPlanningTrackerIsNotDefined(): void
    {
        $this->changeset->setFieldValue(
            $this->start_field,
            ChangesetValueDateTestBuilder::aValue(
                1,
                $this->changeset,
                $this->start_field
            )->withTimestamp((new DateTime('-1month'))->getTimestamp())->build()
        );
        $this->changeset->setFieldValue(
            $this->end_field,
            ChangesetValueDateTestBuilder::aValue(
                2,
                $this->changeset,
                $this->end_field
            )->withTimestamp((new DateTime('+1day'))->getTimestamp())->build()
        );
        $this->timeframe_builder->method('getSemantic')->willReturn(
            new SemanticTimeframe(
                $this->tracker,
                new TimeframeWithEndDate($this->start_field, $this->end_field)
            )
        );
        $this->planning_factory->expects($this->once())->method('getPlanningByPlanningTracker')->willReturn(null);
        self::assertTrue($this->getBuilder()->build($this->artifact, $this->user, $this->project)->isNothing());
    }

    public function testItReturnsAPlanningArtifactMilestone(): void
    {
        $this->timeframe_builder->method('getSemantic')->willReturn(
            new SemanticTimeframe(
                $this->tracker,
                new TimeframeWithEndDate($this->start_field, $this->end_field)
            )
        );
        $this->changeset->setFieldValue(
            $this->start_field,
            ChangesetValueDateTestBuilder::aValue(
                1,
                $this->changeset,
                $this->start_field
            )->withTimestamp((new DateTime('-1day'))->getTimestamp())->build()
        );
        $this->changeset->setFieldValue(
            $this->end_field,
            ChangesetValueDateTestBuilder::aValue(
                2,
                $this->changeset,
                $this->end_field
            )->withTimestamp((new DateTime('+1day'))->getTimestamp())->build()
        );
        $planning = PlanningBuilder::aPlanning((int) $this->project->getID())->build();
        $this->planning_factory->method('getPlanningByPlanningTracker')->willReturn($planning);
        self::assertFalse($this->getBuilder()->build($this->artifact, $this->user, $this->project)->isNothing());
    }
}

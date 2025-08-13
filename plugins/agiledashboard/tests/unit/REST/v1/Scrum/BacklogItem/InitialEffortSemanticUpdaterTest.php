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

namespace Tuleap\AgileDashboard\REST\v1\Scrum\BacklogItem;

use AgileDashBoard_Semantic_InitialEffort;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_Changeset;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedField;
use Tuleap\AgileDashboard\Milestone\Backlog\BacklogItem;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueFloatTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueIntegerTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueListTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class InitialEffortSemanticUpdaterTest extends TestCase
{
    private InitialEffortSemanticUpdater $updater;
    private PFUser $user;
    private Artifact&MockObject $artifact;
    private BacklogItem&MockObject $backlog_item;
    private AgileDashBoard_Semantic_InitialEffort&MockObject $semantic_initial_effort;
    private Tracker_Artifact_Changeset $last_changeset;

    #[\Override]
    protected function setUp(): void
    {
        $this->updater = new InitialEffortSemanticUpdater();

        $this->user                    = UserTestBuilder::buildWithDefaults();
        $this->artifact                = $this->createMock(Artifact::class);
        $this->backlog_item            = $this->createMock(BacklogItem::class);
        $this->semantic_initial_effort = $this->createMock(AgileDashBoard_Semantic_InitialEffort::class);
        $this->last_changeset          = ChangesetTestBuilder::aChangeset(1)->ofArtifact($this->artifact)->build();

        $this->backlog_item->expects($this->once())->method('getArtifact')->willReturn($this->artifact);
    }

    public function testItSetsTheInitialEffortInTheBacklogItem(): void
    {
        $initial_effort_field = FloatFieldBuilder::aFloatField(1)
            ->withName('Initial effort')
            ->withReadPermission($this->user, true)
            ->build();
        ChangesetValueFloatTestBuilder::aValue(1, $this->last_changeset, $initial_effort_field)
            ->withValue(0.5)
            ->build();

        $this->artifact->method('getLastChangeset')->willReturn($this->last_changeset);
        $this->semantic_initial_effort->expects($this->once())->method('getField')->willReturn($initial_effort_field);

        $this->backlog_item->expects($this->once())->method('setInitialEffort')->with(0.5);

        $this->updater->updateBacklogItemInitialEffortSemantic(
            $this->user,
            $this->backlog_item,
            $this->semantic_initial_effort
        );
    }

    public function testItSetsTheInitialEffortInTheBacklogItemWhenInitialEffortFieldIsInteger(): void
    {
        $initial_effort_field = IntegerFieldBuilder::anIntField(153)
            ->withName('Initial effort')
            ->withReadPermission($this->user, true)
            ->build();
        ChangesetValueIntegerTestBuilder::aValue(555, $this->last_changeset, $initial_effort_field)
            ->withValue(5)
            ->build();

        $this->artifact->method('getLastChangeset')->willReturn($this->last_changeset);
        $this->semantic_initial_effort->expects($this->once())->method('getField')->willReturn($initial_effort_field);

        $this->backlog_item->expects($this->once())->method('setInitialEffort')->with(5);

        $this->updater->updateBacklogItemInitialEffortSemantic(
            $this->user,
            $this->backlog_item,
            $this->semantic_initial_effort
        );
    }

    public function testItSetsTheInitialEffortInTheBacklogItemWhenInitialEffortFieldIsASelectbox(): void
    {
        $initial_effort_field = ListStaticBindBuilder::aStaticBind(
            SelectboxFieldBuilder::aSelectboxField(1)
                ->withName('Initial effort')
                ->withReadPermission($this->user, true)
                ->build()
        )->withStaticValues([
            381 => '10',
        ])->build()->getField();
        $value                = ChangesetValueListTestBuilder::aListOfValue(1, $this->last_changeset, $initial_effort_field)
            ->withValues([ListStaticValueBuilder::aStaticValue('10')->withId(381)->build()])
            ->build();
        $this->last_changeset->setFieldValue($initial_effort_field, $value);

        $this->artifact->method('getLastChangeset')->willReturn($this->last_changeset);
        $this->semantic_initial_effort->expects($this->once())->method('getField')->willReturn($initial_effort_field);
        $this->artifact->method('getValue')->with($initial_effort_field, null)->willReturn($value);

        $this->backlog_item->expects($this->once())->method('setInitialEffort')->with(10);

        $this->updater->updateBacklogItemInitialEffortSemantic(
            $this->user,
            $this->backlog_item,
            $this->semantic_initial_effort
        );
    }

    public function testItSetsTheInitialEffortInTheBacklogItemWhenInitialEffortFieldIsAComputedField(): void
    {
        $initial_effort_field = $this->createMock(ComputedField::class);
        $initial_effort_field->expects($this->once())->method('userCanRead')->with($this->user)->willReturn(true);
        $initial_effort_field->expects($this->once())->method('getFullRESTValue')
            ->with($this->user, $this->last_changeset)
            ->willReturn($this->buildRESTComputedValue());
        $initial_effort_field->expects($this->once())->method('getComputedValue')
            ->with($this->user, $this->artifact)
            ->willReturn(8.0);

        $this->artifact->method('getLastChangeset')->willReturn($this->last_changeset);
        $this->semantic_initial_effort->expects($this->once())->method('getField')->willReturn($initial_effort_field);

        $this->backlog_item->expects($this->once())->method('setInitialEffort')->with(8);

        $this->updater->updateBacklogItemInitialEffortSemantic(
            $this->user,
            $this->backlog_item,
            $this->semantic_initial_effort
        );
    }

    public function testItDoesNotSetTheInitialEffortInTheBacklogItemIfUserCannotReadTheField(): void
    {
        $initial_effort_field = IntegerFieldBuilder::anIntField(1)
            ->withReadPermission($this->user, false)
            ->build();

        $this->artifact->expects($this->never())->method('getLastChangeset');
        $this->semantic_initial_effort->expects($this->once())->method('getField')->willReturn($initial_effort_field);

        $this->backlog_item->expects($this->never())->method('setInitialEffort');

        $this->updater->updateBacklogItemInitialEffortSemantic(
            $this->user,
            $this->backlog_item,
            $this->semantic_initial_effort
        );
    }

    public function testItDoesNotSetTheInitialEffortInTheBacklogItemIfFieldDoesNotHaveRESTValue(): void
    {
        $initial_effort_field = IntegerFieldBuilder::anIntField(1)
            ->withReadPermission($this->user, true)
            ->build();
        $this->last_changeset->setFieldValue($initial_effort_field, null);

        $this->artifact->method('getLastChangeset')->willReturn($this->last_changeset);
        $this->semantic_initial_effort->expects($this->once())->method('getField')->willReturn($initial_effort_field);

        $this->backlog_item->expects($this->never())->method('setInitialEffort');

        $this->updater->updateBacklogItemInitialEffortSemantic(
            $this->user,
            $this->backlog_item,
            $this->semantic_initial_effort
        );
    }

    public function testItDoesNotSetTheInitialEffortInTheBacklogItemIfArtifactDoesNotHaveLastChangeset(): void
    {
        $initial_effort_field = IntegerFieldBuilder::anIntField(1)
            ->withReadPermission($this->user, true)
            ->build();

        $this->artifact->method('getLastChangeset')->willReturn(null);
        $this->semantic_initial_effort->expects($this->once())->method('getField')->willReturn($initial_effort_field);

        $this->backlog_item->expects($this->never())->method('setInitialEffort');

        $this->updater->updateBacklogItemInitialEffortSemantic(
            $this->user,
            $this->backlog_item,
            $this->semantic_initial_effort
        );
    }

    private function buildRESTComputedValue(): ArtifactFieldValueFullRepresentation
    {
        $artifact_field_value_full_representation = new ArtifactFieldValueFullRepresentation();
        $artifact_field_value_full_representation->build(
            101,
            'computed',
            'Initial effort',
            8
        );

        return $artifact_field_value_full_representation;
    }
}

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

use AgileDashboard_Milestone_Backlog_BacklogItem;
use AgileDashBoard_Semantic_InitialEffort;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field_Computed;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueIntegerTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueListTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;

final class InitialEffortSemanticUpdaterTest extends TestCase
{
    private InitialEffortSemanticUpdater $updater;
    private PFUser $user;
    private Artifact&MockObject $artifact;
    private AgileDashboard_Milestone_Backlog_BacklogItem&MockObject $backlog_item;
    private AgileDashBoard_Semantic_InitialEffort&MockObject $semantic_initial_effort;
    private Tracker_Artifact_Changeset $last_changeset;

    protected function setUp(): void
    {
        $this->updater = new InitialEffortSemanticUpdater();

        $this->user                    = UserTestBuilder::buildWithDefaults();
        $this->artifact                = $this->createMock(Artifact::class);
        $this->backlog_item            = $this->createMock(AgileDashboard_Milestone_Backlog_BacklogItem::class);
        $this->semantic_initial_effort = $this->createMock(AgileDashBoard_Semantic_InitialEffort::class);
        $this->last_changeset          = ChangesetTestBuilder::aChangeset(1)->ofArtifact($this->artifact)->build();

        $this->backlog_item->expects(self::once())->method('getArtifact')->willReturn($this->artifact);
    }

    public function testItSetsTheInitialEffortInTheBacklogItem(): void
    {
        $initial_effort_field = IntFieldBuilder::anIntField(1)
            ->withName('Initial effort')
            ->withReadPermission($this->user, true)
            ->build();
        $this->last_changeset->setFieldValue(
            $initial_effort_field,
            ChangesetValueIntegerTestBuilder::aValue(1, $this->last_changeset, $initial_effort_field)->withValue(5)->build()
        );

        $this->artifact->method('getLastChangeset')->willReturn($this->last_changeset);
        $this->semantic_initial_effort->expects(self::once())->method('getField')->willReturn($initial_effort_field);

        $this->backlog_item->expects(self::once())->method('setInitialEffort')->with(5);

        $this->updater->updateBacklogItemInitialEffortSemantic(
            $this->user,
            $this->backlog_item,
            $this->semantic_initial_effort
        );
    }

    public function testItSetsTheInitialEffortInTheBacklogItemWhenInitialEffortFieldIsASelectbox(): void
    {
        $initial_effort_field = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(1)
                ->withName('Initial effort')
                ->withReadPermission($this->user, true)
                ->build()
        )->withStaticValues([
            381 => '10',
        ])->build()->getField();
        $value                = ChangesetValueListTestBuilder::aListOfValue(1, $this->last_changeset, $initial_effort_field)
            ->withValues([new Tracker_FormElement_Field_List_Bind_StaticValue(381, '10', '', 0, false)])
            ->build();
        $this->last_changeset->setFieldValue($initial_effort_field, $value);

        $this->artifact->method('getLastChangeset')->willReturn($this->last_changeset);
        $this->semantic_initial_effort->expects(self::once())->method('getField')->willReturn($initial_effort_field);
        $this->artifact->method('getValue')->with($initial_effort_field, null)->willReturn($value);

        $this->backlog_item->expects(self::once())->method('setInitialEffort')->with(10);

        $this->updater->updateBacklogItemInitialEffortSemantic(
            $this->user,
            $this->backlog_item,
            $this->semantic_initial_effort
        );
    }

    public function testItSetsTheInitialEffortInTheBacklogItemWhenInitialEffortFieldIsAComputedField(): void
    {
        $initial_effort_field = $this->createMock(Tracker_FormElement_Field_Computed::class);
        $initial_effort_field->expects(self::once())->method('userCanRead')->with($this->user)->willReturn(true);
        $initial_effort_field->expects(self::once())->method('getFullRESTValue')
            ->with($this->user, $this->last_changeset)
            ->willReturn($this->buildRESTComputedValue());
        $initial_effort_field->expects(self::once())->method('getComputedValue')
            ->with($this->user, $this->artifact)
            ->willReturn(8.0);

        $this->artifact->method('getLastChangeset')->willReturn($this->last_changeset);
        $this->semantic_initial_effort->expects(self::once())->method('getField')->willReturn($initial_effort_field);

        $this->backlog_item->expects(self::once())->method('setInitialEffort')->with(8);

        $this->updater->updateBacklogItemInitialEffortSemantic(
            $this->user,
            $this->backlog_item,
            $this->semantic_initial_effort
        );
    }

    public function testItDoesNotSetTheInitialEffortInTheBacklogItemIfUserCannotReadTheField(): void
    {
        $initial_effort_field = IntFieldBuilder::anIntField(1)
            ->withReadPermission($this->user, false)
            ->build();

        $this->artifact->expects(self::never())->method('getLastChangeset');
        $this->semantic_initial_effort->expects(self::once())->method('getField')->willReturn($initial_effort_field);

        $this->backlog_item->expects(self::never())->method('setInitialEffort');

        $this->updater->updateBacklogItemInitialEffortSemantic(
            $this->user,
            $this->backlog_item,
            $this->semantic_initial_effort
        );
    }

    public function testItDoesNotSetTheInitialEffortInTheBacklogItemIfFieldDoesNotHaveRESTValue(): void
    {
        $initial_effort_field = IntFieldBuilder::anIntField(1)
            ->withReadPermission($this->user, true)
            ->build();
        $this->last_changeset->setFieldValue($initial_effort_field, null);

        $this->artifact->method('getLastChangeset')->willReturn($this->last_changeset);
        $this->semantic_initial_effort->expects(self::once())->method('getField')->willReturn($initial_effort_field);

        $this->backlog_item->expects(self::never())->method('setInitialEffort');

        $this->updater->updateBacklogItemInitialEffortSemantic(
            $this->user,
            $this->backlog_item,
            $this->semantic_initial_effort
        );
    }

    public function testItDoesNotSetTheInitialEffortInTheBacklogItemIfArtifactDoesNotHaveLastChangeset(): void
    {
        $initial_effort_field = IntFieldBuilder::anIntField(1)
            ->withReadPermission($this->user, true)
            ->build();

        $this->artifact->method('getLastChangeset')->willReturn(null);
        $this->semantic_initial_effort->expects(self::once())->method('getField')->willReturn($initial_effort_field);

        $this->backlog_item->expects(self::never())->method('setInitialEffort');

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

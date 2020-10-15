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

namespace Tuleap\AgileDashboard\REST\v1\Scrum\BacklogItem;

use AgileDashboard_Milestone_Backlog_BacklogItem;
use AgileDashBoard_Semantic_InitialEffort;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field_Computed;
use Tracker_FormElement_Field_Integer;
use Tracker_FormElement_Field_Selectbox;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation;

final class InitialEffortSemanticUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var InitialEffortSemanticUpdater
     */
    private $updater;

    /**
     * @var Mockery\MockInterface|PFUser
     */
    private $user;

    /**
     * @var Mockery\MockInterface|Artifact
     */
    private $artifact;

    /**
     * @var AgileDashboard_Milestone_Backlog_BacklogItem|Mockery\MockInterface
     */
    private $backlog_item;

    /**
     * @var AgileDashBoard_Semantic_InitialEffort|Mockery\MockInterface
     */
    private $semantic_initial_effort;

    /**
     * @var Mockery\MockInterface|Tracker_Artifact_Changeset
     */
    private $last_changeset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->updater = new InitialEffortSemanticUpdater();

        $this->user                    = Mockery::mock(PFUser::class);
        $this->artifact                = Mockery::mock(Artifact::class);
        $this->backlog_item            = Mockery::mock(AgileDashboard_Milestone_Backlog_BacklogItem::class);
        $this->semantic_initial_effort = Mockery::mock(AgileDashBoard_Semantic_InitialEffort::class);
        $this->last_changeset          = Mockery::mock(Tracker_Artifact_Changeset::class);

        $this->backlog_item->shouldReceive('getArtifact')->once()->andReturn($this->artifact);
    }

    public function testItSetsTheInitialEffortInTheBacklogItem(): void
    {
        $initial_effort_field = Mockery::mock(Tracker_FormElement_Field_Integer::class);
        $initial_effort_field->shouldReceive('userCanRead')->once()->with($this->user)->andReturnTrue();
        $initial_effort_field->shouldReceive('getFullRESTValue')
            ->once()
            ->with($this->user, $this->last_changeset)
            ->andReturn($this->buildRESTIntValue());

        $this->artifact->shouldReceive('getLastChangeset')->andReturn($this->last_changeset);
        $this->semantic_initial_effort->shouldReceive('getField')->once()->andReturn($initial_effort_field);

        $this->backlog_item->shouldReceive('setInitialEffort')->once()->with(5);

        $this->updater->updateBacklogItemInitialEffortSemantic(
            $this->user,
            $this->backlog_item,
            $this->semantic_initial_effort
        );
    }

    public function testItSetsTheInitialEffortInTheBacklogItemWhenInitialEffortFieldIsASelectbox(): void
    {
        $initial_effort_field = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $initial_effort_field->shouldReceive('userCanRead')->once()->with($this->user)->andReturnTrue();
        $initial_effort_field->shouldReceive('getFullRESTValue')
            ->once()
            ->with($this->user, $this->last_changeset)
            ->andReturn($this->buildRESTListValue());
        $initial_effort_field->shouldReceive('getComputedValue')
            ->once()
            ->with($this->user, $this->artifact)
            ->andReturn(10);

        $this->artifact->shouldReceive('getLastChangeset')->andReturn($this->last_changeset);
        $this->semantic_initial_effort->shouldReceive('getField')->once()->andReturn($initial_effort_field);

        $this->backlog_item->shouldReceive('setInitialEffort')->once()->with(10);

        $this->updater->updateBacklogItemInitialEffortSemantic(
            $this->user,
            $this->backlog_item,
            $this->semantic_initial_effort
        );
    }

    public function testItSetsTheInitialEffortInTheBacklogItemWhenInitialEffortFieldIsAComputedField(): void
    {
        $initial_effort_field = Mockery::mock(Tracker_FormElement_Field_Computed::class);
        $initial_effort_field->shouldReceive('userCanRead')->once()->with($this->user)->andReturnTrue();
        $initial_effort_field->shouldReceive('getFullRESTValue')
            ->once()
            ->with($this->user, $this->last_changeset)
            ->andReturn($this->buildRESTComputedValue());
        $initial_effort_field->shouldReceive('getComputedValue')
            ->once()
            ->with($this->user, $this->artifact)
            ->andReturn(8);

        $this->artifact->shouldReceive('getLastChangeset')->andReturn($this->last_changeset);
        $this->semantic_initial_effort->shouldReceive('getField')->once()->andReturn($initial_effort_field);

        $this->backlog_item->shouldReceive('setInitialEffort')->once()->with(8);

        $this->updater->updateBacklogItemInitialEffortSemantic(
            $this->user,
            $this->backlog_item,
            $this->semantic_initial_effort
        );
    }

    public function testItDoesNotSetTheInitialEffortInTheBacklogItemIfUserCannotReadTheField(): void
    {
        $initial_effort_field = Mockery::mock(Tracker_FormElement_Field_Integer::class);
        $initial_effort_field->shouldReceive('userCanRead')->once()->with($this->user)->andReturnFalse();
        $initial_effort_field->shouldReceive('getFullRESTValue')->never();

        $this->artifact->shouldReceive('getLastChangeset')->never();
        $this->semantic_initial_effort->shouldReceive('getField')->once()->andReturn($initial_effort_field);

        $this->backlog_item->shouldReceive('setInitialEffort')->never();

        $this->updater->updateBacklogItemInitialEffortSemantic(
            $this->user,
            $this->backlog_item,
            $this->semantic_initial_effort
        );
    }

    public function testItDoesNotSetTheInitialEffortInTheBacklogItemIfFieldDoesNotHaveRESTValue(): void
    {
        $initial_effort_field = Mockery::mock(Tracker_FormElement_Field_Integer::class);
        $initial_effort_field->shouldReceive('userCanRead')->once()->with($this->user)->andReturnTrue();
        $initial_effort_field->shouldReceive('getFullRESTValue')
            ->once()
            ->with($this->user, $this->last_changeset)
            ->andReturnNull();

        $this->artifact->shouldReceive('getLastChangeset')->andReturn($this->last_changeset);
        $this->semantic_initial_effort->shouldReceive('getField')->once()->andReturn($initial_effort_field);

        $this->backlog_item->shouldReceive('setInitialEffort')->never();

        $this->updater->updateBacklogItemInitialEffortSemantic(
            $this->user,
            $this->backlog_item,
            $this->semantic_initial_effort
        );
    }

    public function testItDoesNotSetTheInitialEffortInTheBacklogItemIfArtifactDoesNotHaveLastChangeset(): void
    {
        $initial_effort_field = Mockery::mock(Tracker_FormElement_Field_Integer::class);
        $initial_effort_field->shouldReceive('userCanRead')->once()->with($this->user)->andReturnTrue();

        $this->artifact->shouldReceive('getLastChangeset')->andReturnNull();
        $this->semantic_initial_effort->shouldReceive('getField')->once()->andReturn($initial_effort_field);

        $this->backlog_item->shouldReceive('setInitialEffort')->never();

        $this->updater->updateBacklogItemInitialEffortSemantic(
            $this->user,
            $this->backlog_item,
            $this->semantic_initial_effort
        );
    }

    private function buildRESTIntValue(): ArtifactFieldValueFullRepresentation
    {
        $artifact_field_value_full_representation = new ArtifactFieldValueFullRepresentation();
        $artifact_field_value_full_representation->build(
            101,
            'int',
            'Initial effort',
            5
        );

        return $artifact_field_value_full_representation;
    }

    private function buildRESTListValue(): ArtifactFieldValueFullRepresentation
    {
        $artifact_field_value_full_representation = new ArtifactFieldValueFullRepresentation();
        $artifact_field_value_full_representation->build(
            101,
            'sb',
            'Initial effort',
            381
        );

        return $artifact_field_value_full_representation;
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

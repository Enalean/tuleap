<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Type;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\TestManagement\Config;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TypeCoveredByOverriderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Config|MockObject $config;
    private \Project $project;
    private TypeCoveredByOverrider $overrider;
    private int $artifact_id                = 123;
    private int $test_definition_tracker_id = 444;
    private int $another_tracker_id         = 445;
    private ArtifactLinksUsageDao&MockObject $dao;
    private \Tuleap\Tracker\Tracker $test_definition_tracker;
    private \Tuleap\Tracker\Tracker $another_tracker;

    public function setUp(): void
    {
        $this->test_definition_tracker = TrackerTestBuilder::aTracker()->withId($this->test_definition_tracker_id)->build();
        $this->another_tracker         = TrackerTestBuilder::aTracker()->withId($this->another_tracker_id)->build();

        $this->project = ProjectTestBuilder::aProject()->build();

        $this->config = $this->createMock(\Tuleap\TestManagement\Config::class);
        $this->dao    = $this->createMock(\Tuleap\Tracker\Admin\ArtifactLinksUsageDao::class);

        $this->overrider = new TypeCoveredByOverrider($this->config, $this->dao);

        $this->config->method('getTestDefinitionTrackerId')
            ->with($this->project)
            ->willReturn($this->test_definition_tracker_id);
    }

    public function testItGivesTheCoveredByTypeToNewLinkToTestDefinition(): void
    {
        $new_linked_artifact_ids = [$this->artifact_id];

        $artifact = ArtifactTestBuilder::anArtifact($this->artifact_id)
            ->inTracker($this->test_definition_tracker)
            ->build();

        $this->dao->method('isTypeDisabledInProject')
            ->with(101, '_covered_by')
            ->willReturn(false);

        $overriding_type = $this->overrider->getOverridingType(
            $this->project,
            $artifact,
            $new_linked_artifact_ids
        );

        $this->assertEquals(
            $overriding_type,
            TypeCoveredByPresenter::TYPE_COVERED_BY
        );
    }

    public function testItReturnsNothingWhenNotLinkingToTestDefinition(): void
    {
        $new_linked_artifact_ids = [$this->artifact_id];

        $artifact = ArtifactTestBuilder::anArtifact($this->artifact_id)
            ->inTracker($this->another_tracker)
            ->build();

        $this->dao->method('isTypeDisabledInProject')
            ->with(101, '_covered_by')
            ->willReturn(false);

        $overriding_type = $this->overrider->getOverridingType(
            $this->project,
            $artifact,
            $new_linked_artifact_ids
        );

        $this->assertNull($overriding_type);
    }

    public function testItReturnsNothingWhenUpdatingLinkToTestDefinition(): void
    {
        $new_linked_artifact_ids = [];

        $artifact = ArtifactTestBuilder::anArtifact($this->artifact_id)
            ->inTracker($this->test_definition_tracker)
            ->build();

        $this->dao->method('isTypeDisabledInProject')
            ->with(101, '_covered_by')
            ->willReturn(false);

        $overriding_type = $this->overrider->getOverridingType(
            $this->project,
            $artifact,
            $new_linked_artifact_ids
        );

        $this->assertNull($overriding_type);
    }

    public function testItReturnsNothingIfCoveredByTypeIsDisabled(): void
    {
        $new_linked_artifact_ids = [$this->artifact_id];

        $artifact = ArtifactTestBuilder::anArtifact($this->artifact_id)
            ->inTracker($this->test_definition_tracker)
            ->build();

        $this->dao->method('isTypeDisabledInProject')
            ->with(101, '_covered_by')
            ->willReturn(true);

        $overriding_type = $this->overrider->getOverridingType(
            $this->project,
            $artifact,
            $new_linked_artifact_ids
        );

        $this->assertNull($overriding_type);
    }
}

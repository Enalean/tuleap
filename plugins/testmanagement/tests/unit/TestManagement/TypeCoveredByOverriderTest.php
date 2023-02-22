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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

require_once __DIR__ . '/../bootstrap.php';

class TypeCoveredByOverriderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var TrackerArtifact */
    private $artifact;

    /** @var Config */
    private $config;

    /** @var Project */
    private $project;

    /** @var TypeCoveredByOverrider */
    private $overrider;

    private $artifact_id = 123;

    private $test_definition_tracker_id = 444;

    public function setUp(): void
    {
        parent::setUp();

        $this->artifact = Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact->shouldReceive('getId')->andReturn($this->artifact_id);

        $this->project = Mockery::spy(\Project::class);
        $this->project->shouldReceive('getID')->andReturn(101);

        $this->config = \Mockery::spy(\Tuleap\TestManagement\Config::class);
        $this->dao    = \Mockery::spy(\Tuleap\Tracker\Admin\ArtifactLinksUsageDao::class);

        $this->overrider = new TypeCoveredByOverrider($this->config, $this->dao);

        $this->config->shouldReceive('getTestDefinitionTrackerId')
            ->with($this->project)
            ->andReturn($this->test_definition_tracker_id);
    }

    public function testItGivesTheCoveredByTypeToNewLinkToTestDefinition()
    {
        $new_linked_artifact_ids = [$this->artifact_id];

        $this->artifact->shouldReceive('getTrackerId')->andReturn($this->test_definition_tracker_id);

        $this->dao->shouldReceive('isTypeDisabledInProject')
            ->with(101, '_covered_by')
            ->andReturn(false);

        $overriding_type = $this->overrider->getOverridingType(
            $this->project,
            $this->artifact,
            $new_linked_artifact_ids
        );

        $this->assertEquals(
            $overriding_type,
            TypeCoveredByPresenter::TYPE_COVERED_BY
        );
    }

    public function testItReturnsNothingWhenNotLinkingToTestDefinition()
    {
        $new_linked_artifact_ids = [$this->artifact_id];

        $this->dao->shouldReceive('isTypeDisabledInProject')
            ->with(101, '_covered_by')
            ->andReturn(false);

        $overriding_type = $this->overrider->getOverridingType(
            $this->project,
            $this->artifact,
            $new_linked_artifact_ids
        );

        $this->assertNull($overriding_type);
    }

    public function testItReturnsNothingWhenUpdatingLinkToTestDefinition()
    {
        $new_linked_artifact_ids = [];

        $this->artifact->shouldReceive('getTrackerId')->andReturn($this->test_definition_tracker_id);

        $this->dao->shouldReceive('isTypeDisabledInProject')
            ->with(101, '_covered_by')
            ->andReturn(false);

        $overriding_type = $this->overrider->getOverridingType(
            $this->project,
            $this->artifact,
            $new_linked_artifact_ids
        );

        $this->assertNull($overriding_type);
    }

    public function testItReturnsNothingIfCoveredByTypeIsDisabled()
    {
        $new_linked_artifact_ids = [$this->artifact_id];

        $this->dao->shouldReceive('isTypeDisabledInProject')
            ->with(101, '_covered_by')
            ->andReturn(true);

        $overriding_type = $this->overrider->getOverridingType(
            $this->project,
            $this->artifact,
            $new_linked_artifact_ids
        );

        $this->assertNull($overriding_type);
    }
}

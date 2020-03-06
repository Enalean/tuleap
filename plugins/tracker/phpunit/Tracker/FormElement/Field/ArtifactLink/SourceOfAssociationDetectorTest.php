<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class SourceOfAssociationDetectorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var SourceOfAssociationDetector
     */
    private $detector;
    /**
     * @var \Tracker_Artifact
     */
    private $release;
    /**
     * @var \Tracker_Artifact
     */
    private $sprint;

    protected function setUp(): void
    {
        $release_tracker = \Mockery::mock(\Tracker_Artifact::class);
        $release_tracker->shouldReceive('getId')->andReturn(123);
        $sprint_tracker  = \Mockery::mock(\Tracker_Artifact::class);
        $sprint_tracker->shouldReceive('getId')->andReturn(565);

        $this->release = \Mockery::mock(\Tracker_Artifact::class);
        $this->release->shouldReceive('getTracker')->andReturn($release_tracker);
        $this->release->shouldReceive('getTrackerId')->andReturn(123);
        $this->sprint  = \Mockery::mock(\Tracker_Artifact::class);
        $this->sprint->shouldReceive('getTracker')->andReturn($sprint_tracker);
        $this->sprint->shouldReceive('getTrackerId')->andReturn(565);

        $hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        $hierarchy_factory->shouldReceive('getChildren')->with(123)->andReturns(array($sprint_tracker));
        $hierarchy_factory->shouldReceive('getChildren')->with(565)->andReturns(array());

        $this->detector = new SourceOfAssociationDetector($hierarchy_factory);
    }

    public function testItSaysThatReleaseIsASourceOfAssociation(): void
    {
        $this->assertTrue($this->detector->isChild($this->release, $this->sprint));
    }

    public function testItSaysThatSprintIsNotASourceOfAssociation(): void
    {
        $this->assertFalse($this->detector->isChild($this->sprint, $this->release));
    }
}

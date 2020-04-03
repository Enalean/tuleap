<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\Artifact;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_Artifact;
use Tracker_Artifact_ChangesetValue;
use Tracker_ArtifactFactory;
use Tuleap\Tracker\DAO\TrackerArtifactSourceIdDao;

require_once __DIR__ . '/../../bootstrap.php';

class ExistingArtifactSourceIdFromTrackerExtractorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $tracker_artifact_factory;

    /**
     * @var ExistingArtifactSourceIdFromTrackerExtractor
     */
    private $existing_artifact_source_id;

    /**
     * @var Tracker
     */
    private $tracker;

    /**
     * @var TrackerArtifactSourceIdDao
     */
    private $artifact_source_id_dao;

    public function setUp(): void
    {
        $this->tracker_artifact_factory = \Mockery::mock(Tracker_ArtifactFactory::class);
        $this->artifact_source_id_dao = \Mockery::mock(TrackerArtifactSourceIdDao::class);

        $this->tracker = \Mockery::mock(\Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn(100);
    }

    public function testItRetrieveArtifactIdFromSource()
    {
        $artifact_existing_1 = $this->mockAnArtifact(123, 456);
        $artifact_existing_2 = $this->mockAnArtifact(789, 987);

        $this->tracker_artifact_factory->shouldReceive('getArtifactsByTrackerId')->andReturn([$artifact_existing_1, $artifact_existing_2]);

        $this->artifact_source_id_dao->shouldReceive('getSourceArtifactIds')->withArgs(["https://web/", 100])->andReturn(
            [
                [
                    "source_artifact_id" => 123,
                    "artifact_id" => 456
                ],
                [
                    "source_artifact_id" => 789,
                    "artifact_id" => 987
                ]
            ]
        );

        $this->existing_artifact_source_id = new ExistingArtifactSourceIdFromTrackerExtractor($this->artifact_source_id_dao);

        $this->assertEquals([789 => 987, 123 => 456], $this->existing_artifact_source_id->getSourceArtifactIds($this->tracker, "https://web/"));
    }

    public function testReturnNothingBecauseNoSourceIdInDatabase()
    {
        $artifact_existing_1 = $this->mockAnArtifact(null, 123);
        $artifact_existing_2 = $this->mockAnArtifact(null, 654);
        $this->tracker_artifact_factory->shouldReceive('getArtifactsByTrackerId')->andReturn([$artifact_existing_1, $artifact_existing_2]);

        $this->existing_artifact_source_id = new ExistingArtifactSourceIdFromTrackerExtractor($this->artifact_source_id_dao);

        $this->artifact_source_id_dao->shouldReceive('getSourceArtifactIds')->withArgs(["https://web/", 100])->andReturn();

        $this->assertEquals([], $this->existing_artifact_source_id->getSourceArtifactIds($this->tracker, "https://web/"));
    }

    public function testReturnNothingIfNoSourcePlatform()
    {
        $artifact_existing_1 = $this->mockAnArtifact(null, 123);
        $artifact_existing_2 = $this->mockAnArtifact(null, 654);
        $this->tracker_artifact_factory->shouldReceive('getArtifactsByTrackerId')->andReturn([$artifact_existing_1, $artifact_existing_2]);

        $this->existing_artifact_source_id = new ExistingArtifactSourceIdFromTrackerExtractor($this->artifact_source_id_dao);

        $this->assertEquals([], $this->existing_artifact_source_id->getSourceArtifactIds($this->tracker, null));
    }

    private function mockAnArtifact($value, $id)
    {
        $artifact = \Mockery::mock(Tracker_Artifact::class);
        $changeset = \Mockery::mock(Tracker_Artifact_ChangesetValue::class);
        $changeset->shouldReceive('getValue')->andReturn($value);
        $artifact->shouldReceive('getValue')->andReturn($changeset);
        $artifact->shouldReceive('getId')->andReturn($id);
        return $artifact;
    }
}

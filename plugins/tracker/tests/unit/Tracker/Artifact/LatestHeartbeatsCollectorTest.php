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

namespace Tuleap\Tracker\Artifact;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tuleap\Project\HeartbeatsEntry;
use Tuleap\Project\HeartbeatsEntryCollection;
use Tuleap\Tracker\TrackerColor;

require_once __DIR__ . '/../../bootstrap.php';

class LatestHeartbeatsCollectorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var \Tracker_ArtifactDao */
    public $dao;

    /** @var \Tracker_ArtifactFactory */
    public $factory;

    /** @var LatestHeartbeatsCollector */
    public $collector;

    /** @var \Project */
    public $project;

    /** @var \PFUser */
    public $user;

    protected function setUp(): void
    {
        parent::setUp();

        $glyph_finder = \Mockery::spy(\Tuleap\Glyph\GlyphFinder::class);
        $glyph_finder->shouldReceive('get')->andReturns(\Mockery::spy(\Tuleap\Glyph\Glyph::class));

        $this->project = \Mockery::spy(\Project::class, ['getID' => 101, 'getUnixName' => false, 'isPublic' => false]);
        $this->user    = new PFUser([
            'language_id' => 'en',
            'user_id' => 200
        ]);

        $this->dao = \Mockery::spy(\Tracker_ArtifactDao::class);
        $this->dao->shouldReceive('searchLatestUpdatedArtifactsInProject')->with(101, HeartbeatsEntryCollection::NB_MAX_ENTRIES)->andReturns(\TestHelper::arrayToDar(array('id' => 1), array('id' => 2), array('id' => 3)));

        $artifact1 = Mockery::spy(\Tracker_Artifact::class)->shouldReceive('getId')->andReturn(1)->getMock();
        $artifact2 = Mockery::spy(\Tracker_Artifact::class)->shouldReceive('getId')->andReturn(2)->getMock();
        $artifact3 = Mockery::spy(\Tracker_Artifact::class)->shouldReceive('getId')->andReturn(3)->getMock();

        $artifact1->shouldReceive('userCanView')->andReturnTrue();
        $artifact3->shouldReceive('userCanView')->andReturnTrue();

        $color   = TrackerColor::default();
        $tracker = Mockery::spy(Tracker::class)->shouldReceive('getColor')->andReturn($color)->getMock();

        $artifact1->shouldReceive('getTracker')->andReturn($tracker);
        $artifact2->shouldReceive('getTracker')->andReturn($tracker);
        $artifact3->shouldReceive('getTracker')->andReturn($tracker);

        $this->factory = \Mockery::spy(\Tracker_ArtifactFactory::class);
        $this->factory->shouldReceive('getInstanceFromRow')->with(array('id' => 1))->andReturns($artifact1);
        $this->factory->shouldReceive('getInstanceFromRow')->with(array('id' => 2))->andReturns($artifact2);
        $this->factory->shouldReceive('getInstanceFromRow')->with(array('id' => 3))->andReturns($artifact3);

        $this->collector = new LatestHeartbeatsCollector(
            $this->dao,
            $this->factory,
            $glyph_finder,
            \Mockery::spy(\UserManager::class),
            \Mockery::spy(\UserHelper::class)
        );
    }

    public function testItConvertsArtifactsIntoHeartbeats(): void
    {
        $collection = new HeartbeatsEntryCollection($this->project, $this->user);
        $this->collector->collect($collection);

        $entries = $collection->getLatestEntries();
        foreach ($entries as $entry) {
            $this->assertInstanceOf(HeartbeatsEntry::class, $entry);
        }
    }

    public function testItCollectsOnlyArtifactsUserCanView(): void
    {
        $collection = new HeartbeatsEntryCollection($this->project, $this->user);
        $this->collector->collect($collection);

        $this->assertCount(2, $collection->getLatestEntries());
    }

    public function testItInformsThatThereIsAtLeastOneActivityThatUserCannotRead(): void
    {
        $collection = new HeartbeatsEntryCollection($this->project, $this->user);
        $this->collector->collect($collection);

        $this->assertTrue($collection->areThereActivitiesUserCannotSee());
    }
}

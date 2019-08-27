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
declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\RecentlyVisited;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Tracker;
use Tracker_Artifact;
use Tracker_ArtifactFactory;
use Tuleap\Tracker\TrackerColor;
use Tuleap\User\History\HistoryEntryCollection;

class VisitRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItRetrievesHistory()
    {
        $recently_visited_dao = Mockery::mock(\Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao::class);
        $recently_visited_dao->shouldReceive('searchVisitByUserId')->andReturns(
            array(
                array('artifact_id' => 1, 'created_on' => 1),
                array('artifact_id' => 2, 'created_on' => 2),
            )
        );
        $artifact_factory = Mockery::mock(Tracker_ArtifactFactory::class);
        $artifact         = Mockery::mock(Tracker_Artifact::class);
        $artifact_factory->shouldReceive('getArtifactById')->andReturns($artifact);
        $tracker = Mockery::mock(Tracker::class);
        $artifact->shouldReceive(
            [
                'getTracker' => $tracker,
                'getXRef'    => 'bug #xxx',
                'getUri'     => '/whatever',
                'getTitle'   => 'Random title',
            ]
        );
        $tracker->shouldReceive(
            [
                'getProject' => Mockery::mock(Project::class),
                'getColor'   => TrackerColor::default(),
                'getName'    => 'bug',
                'getUri'     => 'whatever',
            ]
        );
        $glyph_finder = Mockery::mock(\Tuleap\Glyph\GlyphFinder::class);
        $glyph_finder->shouldReceive('get')->andReturns(Mockery::mock(\Tuleap\Glyph\Glyph::class));

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturn(101);
        $visit_retriever    = new VisitRetriever($recently_visited_dao, $artifact_factory, $glyph_finder);
        $max_length_history = 2;
        $collection         = new HistoryEntryCollection($user);
        $visit_retriever->getVisitHistory($collection, $max_length_history);

        $history = $collection->getEntries();
        $this->assertCount($max_length_history, $history);
        $this->assertEquals(1, $history[0]->getVisitTime());
        $this->assertEquals(2, $history[1]->getVisitTime());
    }

    public function testItExpectsBrokenHistory()
    {
        $recently_visited_dao = Mockery::mock(\Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao::class);
        $recently_visited_dao->shouldReceive('searchVisitByUserId')->andReturns(
            array(
                array('artifact_id' => 1, 'created_on' => 1),
                array('artifact_id' => 2, 'created_on' => 2),
            )
        );
        $artifact_factory = Mockery::mock(Tracker_ArtifactFactory::class);
        $artifact_factory->shouldReceive('getArtifactById')->andReturns(null);
        $glyph_finder = Mockery::mock(\Tuleap\Glyph\GlyphFinder::class);

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturn(101);
        $visit_retriever    = new VisitRetriever($recently_visited_dao, $artifact_factory, $glyph_finder);
        $max_length_history = 30;

        $collection         = new HistoryEntryCollection($user);
        $visit_retriever->getVisitHistory($collection, $max_length_history);

        $this->assertCount(0, $collection->getEntries());
    }
}

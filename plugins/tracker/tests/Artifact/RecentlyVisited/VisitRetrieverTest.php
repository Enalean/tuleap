<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\RecentlyVisited;

require_once __DIR__.'/../../bootstrap.php';

class VisitRetrieverTest extends \TuleapTestCase
{
    public function itRetrievesHistory()
    {
        $recently_visited_dao = mock('Tuleap\\Tracker\\RecentlyVisited\\RecentlyVisitedDao');
        stub($recently_visited_dao)->searchVisitByUserId()->returns(
            array(
                array('artifact_id' => 1, 'created_on' => 1),
                array('artifact_id' => 2, 'created_on' => 2),
            )
        );
        $artifact_factory = mock('Tracker_ArtifactFactory');
        $artifact         = mock('Tracker_Artifact');
        stub($artifact_factory)->getArtifactById()->returns($artifact);
        $tracker = mock('Tracker');
        stub($artifact)->getTracker()->returns($tracker);
        stub($tracker)->getProject()->returns(mock('Project'));
        $glyph_finder = mock('Tuleap\\Glyph\\GlyphFinder');
        stub($glyph_finder)->get()->returns(mock('Tuleap\\Glyph\\Glyph'));

        $visit_retriever    = new VisitRetriever($recently_visited_dao, $artifact_factory, $glyph_finder);
        $user               = mock('PFUser');
        $max_length_history = 2;
        $history            = $visit_retriever->getVisitHistory($user, $max_length_history);

        $this->assertCount($history, $max_length_history);
        $this->assertEqual($history[0]->getVisitTime(), 1);
        $this->assertEqual($history[1]->getVisitTime(), 2);
    }

    public function itExpectsBrokenHistory()
    {
        $recently_visited_dao = mock('Tuleap\\Tracker\\RecentlyVisited\\RecentlyVisitedDao');
        stub($recently_visited_dao)->searchVisitByUserId()->returns(
            array(
                array('artifact_id' => 1, 'created_on' => 1),
                array('artifact_id' => 2, 'created_on' => 2),
            )
        );
        $artifact_factory = mock('Tracker_ArtifactFactory');
        stub($artifact_factory)->getArtifactById()->returns(null);
        $glyph_finder = mock('Tuleap\\Glyph\\GlyphFinder');

        $visit_retriever    = new VisitRetriever($recently_visited_dao, $artifact_factory, $glyph_finder);
        $user               = mock('PFUser');
        $max_length_history = 30;
        $history            = $visit_retriever->getVisitHistory($user, $max_length_history);

        $this->assertArrayEmpty($history);
    }
}

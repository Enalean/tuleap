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

namespace Tuleap\Tracker\Artifact;

use Tuleap\Project\HeartbeatsEntryCollection;
use TuleapTestCase;

require_once __DIR__.'/../../bootstrap.php';

class LatestHeartbeatsCollectorTest extends TuleapTestCase
{
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

    public function setUp()
    {
        parent::setUp();

        $glyph_finder = mock('Tuleap\\Glyph\\GlyphFinder');
        stub($glyph_finder)->get()->returns(mock('Tuleap\\Glyph\\Glyph'));

        $this->project = aMockProject()->withId(101)->build();
        $this->user    = aUser()->withId(200)->build();

        $this->dao = mock('Tracker_ArtifactDao');
        stub($this->dao)
            ->searchLatestUpdatedArtifactsInProject(
                101,
                HeartbeatsEntryCollection::NB_MAX_ENTRIES
            )->returnsDar(
                array('id' => 1),
                array('id' => 2),
                array('id' => 3)
            );

        $artifact1 = aMockArtifact()->withId(1)->allUsersCanView()->build();
        $artifact2 = aMockArtifact()->withId(2)->build();
        $artifact3 = aMockArtifact()->withId(3)->allUsersCanView()->build();

        $this->factory = mock('Tracker_ArtifactFactory');
        stub($this->factory)->getInstanceFromRow(array('id' => 1))->returns($artifact1);
        stub($this->factory)->getInstanceFromRow(array('id' => 2))->returns($artifact2);
        stub($this->factory)->getInstanceFromRow(array('id' => 3))->returns($artifact3);

        $this->collector = new LatestHeartbeatsCollector(
            $this->dao,
            $this->factory,
            $glyph_finder,
            mock('UserManager'),
            mock('UserHelper')
        );
    }

    public function itConvertsArtifactsIntoHeartbeats()
    {
        $collection = new HeartbeatsEntryCollection($this->project, $this->user);
        $this->collector->collect($collection);

        $entries = $collection->getLatestEntries();
        foreach ($entries as $entry) {
            $this->assertIsA($entry, 'Tuleap\Project\HeartbeatsEntry');
        }
    }

    public function itCollectsOnlyArtifactsUserCanView()
    {
        expect($this->factory)->getInstanceFromRow()->count(3);

        $collection = new HeartbeatsEntryCollection($this->project, $this->user);
        $this->collector->collect($collection);

        $this->assertCount($collection->getLatestEntries(), 2);
    }

    public function itInformsThatThereIsAtLeastOneActivityThatUserCannotRead()
    {
        $collection = new HeartbeatsEntryCollection($this->project, $this->user);
        $this->collector->collect($collection);

        $this->assertTrue($collection->areThereActivitiesUserCannotSee());
    }
}

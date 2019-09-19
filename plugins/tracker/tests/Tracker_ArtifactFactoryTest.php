<?php
/**
 * Copyright Enalean (c) 2011 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

require_once 'bootstrap.php';

class Tracker_ArtifactFactoryTest extends TuleapTestCase
{
    /** @var Tracker_ArtifactDao */
    private $dao;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    public function setUp()
    {
        parent::setUp();
        $this->dao = mock('Tracker_ArtifactDao');
        $this->artifact_factory = partial_mock('Tracker_ArtifactFactory', array('getDao'));
        stub($this->artifact_factory)->getDao()->returns($this->dao);
    }

    public function itFetchArtifactsTitlesFromDb()
    {
        $artifacts = array(anArtifact()->withId(12)->build(), anArtifact()->withId(30)->build());

        expect($this->dao)->getTitles(array(12, 30))->once();
        stub($this->dao)->getTitles()->returnsEmptyDar();

        $this->artifact_factory->setTitles($artifacts);
    }

    public function itSetTheTitlesToTheArtifact()
    {
        $art24 = anArtifact()->withId(24)->build();
        $artifacts = array($art24);

        stub($this->dao)->getTitles()->returnsDar(array('id' => 24, 'title' => 'Zoum'));

        $this->artifact_factory->setTitles($artifacts);

        $this->assertEqual('Zoum', $art24->getTitle());
    }

    public function itSetTheTitlesWhenThereAreSeveralArtifacts()
    {
        $art24 = anArtifact()->withId(24)->build();
        $art32   = anArtifact()->withId(32)->build();
        $artifacts = array($art24, $art32);

        stub($this->dao)->getTitles()->returnsDar(array('id' => 24, 'title' => 'Zoum'), array('id' => 32, 'title' => 'Zen'));

        $this->artifact_factory->setTitles($artifacts);

        $this->assertEqual('Zoum', $art24->getTitle());
        $this->assertEqual('Zen', $art32->getTitle());
    }

    public function itSetTheTitlesWhenThereAreSeveralTimeTheSameArtifact()
    {
        $art24_1 = anArtifact()->withId(24)->build();
        $art24_2 = anArtifact()->withId(24)->build();
        $artifacts = array($art24_1, $art24_2);

        stub($this->dao)->getTitles()->returnsDar(array('id' => 24, 'title' => 'Zoum'));

        $this->artifact_factory->setTitles($artifacts);

        $this->assertEqual('Zoum', $art24_1->getTitle());
        $this->assertEqual('Zoum', $art24_2->getTitle());
    }
}

class Tracker_ArtifactFactory_GetChildrenTest extends TuleapTestCase
{
    /** @var Tracker_ArtifactDao */
    private $dao;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var PFUser */
    private $user;

    public function setUp()
    {
        parent::setUp();
        $this->dao = mock('Tracker_ArtifactDao');
        $this->artifact_factory = partial_mock('Tracker_ArtifactFactory', array('getDao', 'getInstanceFromRow'));
        stub($this->artifact_factory)->getDao()->returns($this->dao);

        $this->user = mock('PFUser');
        stub($this->user)->getId()->returns(48);
        // Needed to by pass Tracker_Artifact::userCanView
        stub($this->user)->isSuperUser()->returns(true);
    }

    public function itFetchAllChildren()
    {
        $project = \Mockery::mock(\Project::class);

        $tracker = \Mockery::mock(Tracker::class);
        $tracker->shouldReceive('userIsAdmin')->with($this->user)->andReturn(true);
        $tracker->shouldReceive('getId')->andReturn(101);
        $tracker->shouldReceive('getProject')->andReturn($project);

        $artifacts = array(
            anArtifact()->withId(11)->withTracker($tracker)->build(),
            anArtifact()->withId(12)->withTracker($tracker)->build(),
        );

        $artiafct_as_dar1 = anArtifactDar()->withId(55)->build();
        $artiafct_as_dar2 = anArtifactDar()->withId(56)->build();
        stub($this->dao)->getChildrenForArtifacts(array(11, 12))->returnsDar(
            $artiafct_as_dar1,
            $artiafct_as_dar2
        );

        $child_artifact1 = \Mockery::mock(\Tracker_Artifact::class);
        $child_artifact1->shouldReceive('userCanView')->andReturn(true);
        $child_artifact2 = \Mockery::mock(\Tracker_Artifact::class);
        $child_artifact2->shouldReceive('userCanView')->andReturn(true);

        stub($this->artifact_factory)->getInstanceFromRow($artiafct_as_dar1)->returns($child_artifact1);
        stub($this->artifact_factory)->getInstanceFromRow($artiafct_as_dar2)->returns($child_artifact2);

        $this->artifact_factory->getChildrenForArtifacts($this->user, $artifacts);
    }
}

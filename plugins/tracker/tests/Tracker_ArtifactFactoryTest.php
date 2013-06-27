<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
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

class Tracker_ArtifactFactoryTest extends TuleapTestCase {
    /** @var Tracker_ArtifactDao */
    private $dao;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    public function setUp() {
        parent::setUp();
        $this->dao = mock('Tracker_ArtifactDao');
        $this->artifact_factory = partial_mock('Tracker_ArtifactFactory', array('getDao'));
        stub($this->artifact_factory)->getDao()->returns($this->dao);
    }

    public function itFetchArtifactsTitlesFromDb() {
        $artifacts = array(anArtifact()->withId(12)->build(), anArtifact()->withId(30)->build());

        expect($this->dao)->getTitles(array(12, 30))->once();
        stub($this->dao)->getTitles()->returnsEmptyDar();

        $this->artifact_factory->setTitles($artifacts);
    }

    public function itSetTheTitlesToTheArtifact() {
        $art24 = anArtifact()->withId(24)->build();
        $artifacts = array($art24);

        stub($this->dao)->getTitles()->returnsDar(array('id' => 24, 'title' => 'Zoum'));

        $this->artifact_factory->setTitles($artifacts);

        $this->assertEqual('Zoum', $art24->getTitle());
    }

    public function itSetTheTitlesWhenThereAreSeveralArtifacts() {
        $art24 = anArtifact()->withId(24)->build();
        $art32   = anArtifact()->withId(32)->build();
        $artifacts = array($art24, $art32);

        stub($this->dao)->getTitles()->returnsDar(array('id' => 24, 'title' => 'Zoum'), array('id' => 32, 'title' => 'Zen'));

        $this->artifact_factory->setTitles($artifacts);

        $this->assertEqual('Zoum', $art24->getTitle());
        $this->assertEqual('Zen', $art32->getTitle());
    }

    public function itSetTheTitlesWhenThereAreSeveralTimeTheSameArtifact() {
        $art24_1 = anArtifact()->withId(24)->build();
        $art24_2 = anArtifact()->withId(24)->build();
        $artifacts = array($art24_1, $art24_2);

        stub($this->dao)->getTitles()->returnsDar(array('id' => 24, 'title' => 'Zoum'));

        $this->artifact_factory->setTitles($artifacts);

        $this->assertEqual('Zoum', $art24_1->getTitle());
        $this->assertEqual('Zoum', $art24_2->getTitle());
    }
}

?>

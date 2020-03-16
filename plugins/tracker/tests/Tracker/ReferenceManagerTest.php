<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

require_once __DIR__ . '/../bootstrap.php';

class Tracker_ReferenceManagerTest extends TuleapTestCase
{

    public function setUp()
    {
        $this->reference_manager = mock('ReferenceManager');
        $this->artifact_factory  = mock('Tracker_ArtifactFactory');

        $this->tracker_reference_manager = new Tracker_ReferenceManager(
            $this->reference_manager,
            $this->artifact_factory
        );

        $this->keyword     = 'art';
        $this->artifact_id = 101;
        $tracker           = aTracker()->withId(101)->withName('My tracker')->build();
        $this->artifact    = anArtifact()->withId(101)->withTracker($tracker)->build();

        $GLOBALS['Language'] = mock('BaseLanguage');
    }

    public function tearDown()
    {
        unset($GLOBALS['Language']);

        parent::tearDown();
    }

    public function itReturnsNullIfThereIsNoArtifactMatching()
    {
        stub($this->artifact_factory)->getArtifactById(101)->returns(null);

        $reference = $this->tracker_reference_manager->getReference(
            $this->keyword,
            $this->artifact_id
        );

        $this->assertNull($reference);
    }

    public function itReturnsTheTV5LinkIfIdIsMatching()
    {
        stub($this->artifact_factory)->getArtifactById(101)->returns($this->artifact);

        $reference = $this->tracker_reference_manager->getReference(
            $this->keyword,
            $this->artifact_id
        );

        $this->assertNotNull($reference);
        $this->assertIsA($reference, 'Tracker_Reference');
    }
}

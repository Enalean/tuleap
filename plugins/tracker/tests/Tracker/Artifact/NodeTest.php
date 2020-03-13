<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
require_once __DIR__ . '/../../bootstrap.php';
class Tracker_ArtifactNodeTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->artifact = anArtifact()->withId(9787)->build();
        $this->data     = array('somekey' => 'somevalue');
        $this->node     = new ArtifactNode($this->artifact, $this->data);
    }

    public function itHoldsTheArtifact()
    {
        $this->assertIdentical($this->artifact, $this->node->getArtifact());
        $this->assertIdentical($this->artifact, $this->node->getObject());
    }

    public function itCanHoldData()
    {
        $this->assertIdentical($this->data, $this->node->getData());
    }

    public function itUsesTheIdOfTheArtifact()
    {
        $this->assertEqual($this->artifact->getId(), $this->node->getId());
    }

    public function itCallsTheSuperConstructor()
    {
        $this->assertTrue(is_array($this->node->getChildren()), "getChildren should have been initialized to array()");
    }
}

<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\Tracker\TrackerColor;

require_once __DIR__ . '/../bootstrap.php';

class MockArtifactBuilder
{
    public function __construct()
    {
        $this->id       = 123;
        $this->tracker  = Mockery::spy(Tracker::class);
        $this->tracker->shouldReceive('getColor')->andReturn(TrackerColor::default());
        $this->title    = '';
        $this->artifact = Mockery::spy(Tracker_Artifact::class);
        $this->linkedArtifacts  = array();
        $this->uniqueLinkedArtifacts  = array();
        $this->allowedChildrenTypes   = array();
        $this->uri      = '';
        $this->xref     = '';
        $this->value    = null;
        $this->parent   = null;
        $this->lastChangeset = null;
        $this->userCanView = false;
    }

    /** @return \MockArtifactBuilder */
    public function withId($id)
    {
        $this->id = $id;
        return $this;
    }

    /** @return \MockArtifactBuilder */
    public function withTracker(Tracker $tracker)
    {
        $this->tracker = $tracker;
        return $this;
    }

    /** @return \MockArtifactBuilder */
    public function withTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /** @return \MockArtifactBuilder */
    public function withLinkedArtifacts($linkedArtifacts)
    {
        $this->linkedArtifacts = $linkedArtifacts;
        return $this;
    }

    /** @return \MockArtifactBuilder */
    public function withAllowedChildrenTypes(array $types)
    {
        $this->allowedChildrenTypes = $types;
        return $this;
    }

    /** @return \MockArtifactBuilder */
    public function withUniqueLinkedArtifacts($uniqueLinkedArtifacts)
    {
        $this->uniqueLinkedArtifacts = $uniqueLinkedArtifacts;
        return $this;
    }

    /** @return \MockArtifactBuilder */
    public function withUri($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /** @return \MockArtifactBuilder */
    public function withXRef($xref)
    {
        $this->xref = $xref;
        return $this;
    }

    /** @return \MockArtifactBuilder */
    public function withlastChangeset($changset)
    {
        $this->lastChangeset = $changset;
        return $this;
    }

    /**
     * @param Tracker_Artifact_ChangesetValue $value
     * @return \MockArtifactBuilder
     */
    public function withValue($value)
    {
        $this->value = $value;
        return $this;
    }

    public function withParent($parent)
    {
        if ($parent && !($parent instanceof Tracker_Artifact)) {
            throw new InvalidArgumentException('Argument 1 passed to MockArtifactBuilder::withParent() must be an object of class Tracker_Artifact');
        }
        $this->parent = $parent;
        return $this;
    }

    public function allUsersCanView()
    {
        $this->userCanView = true;
        return $this;
    }

    /** @return \Tracker_Artifact|\Mockery\MockInterface */
    public function build()
    {
        $this->artifact->shouldReceive('getId')->andReturn($this->id);
        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);
        $this->artifact->shouldReceive('getTitle')->andReturn($this->title);
        $this->artifact->shouldReceive('getUri')->andReturn($this->uri);
        $this->artifact->shouldReceive('getXRef')->andReturn($this->xref);
        $this->artifact->shouldReceive('getLinkedArtifacts')->andReturn($this->linkedArtifacts);
        $this->artifact->shouldReceive('getUniqueLinkedArtifacts')->andReturn($this->uniqueLinkedArtifacts);
        $this->artifact->shouldReceive('getAllowedChildrenTypes')->andReturn($this->allowedChildrenTypes);
        $this->artifact->shouldReceive('getValue')->andReturn($this->value);
        $this->artifact->shouldReceive('getParent')->andReturn($this->parent);
        $this->artifact->shouldReceive('userCanView')->andReturn($this->userCanView);
        $this->artifact->shouldReceive('getLastChangeset')->andReturn($this->lastChangeset);

        return $this->artifact;
    }
}

function aMockArtifact()
{
    return new MockArtifactBuilder();
}

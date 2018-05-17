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
require_once __DIR__.'/../bootstrap.php';

Mock::generate('Tracker');
Mock::generate('Tracker_Artifact');

class MockArtifactBuilder {
    public function __construct() {
        $this->id       = 123;
        $this->tracker  = new MockTracker();
        $this->title    = '';
        $this->artifact = new MockTracker_Artifact();
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
    public function withId($id) {
        $this->id = $id;
        return $this;
    }

    /** @return \MockArtifactBuilder */
    public function withTracker(Tracker $tracker) {
        $this->tracker = $tracker;
        return $this;
    }

    /** @return \MockArtifactBuilder */
    public function withTitle($title) {
        $this->title = $title;
        return $this;
    }

    /** @return \MockArtifactBuilder */
    public function withLinkedArtifacts($linkedArtifacts) {
        $this->linkedArtifacts = $linkedArtifacts;
        return $this;
    }

    /** @return \MockArtifactBuilder */
    public function withAllowedChildrenTypes(array $types) {
        $this->allowedChildrenTypes = $types;
        return $this;
    }

    /** @return \MockArtifactBuilder */
    public function withUniqueLinkedArtifacts($uniqueLinkedArtifacts) {
        $this->uniqueLinkedArtifacts = $uniqueLinkedArtifacts;
        return $this;
    }

    /** @return \MockArtifactBuilder */
    public function withUri($uri) {
        $this->uri = $uri;
        return $this;
    }

    /** @return \MockArtifactBuilder */
    public function withXRef($xref) {
        $this->xref = $xref;
        return $this;
    }

    /** @return \MockArtifactBuilder */
    public function withlastChangeset($changset) {
        $this->lastChangeset = $changset;
        return $this;
    }

    /**
     * @param Tracker_Artifact_ChangesetValue $value
     * @return \MockArtifactBuilder
     */
    public function withValue($value) {
        $this->value = $value;
        return $this;
    }

    public function withParent($parent) {
        if ($parent && !($parent instanceof Tracker_Artifact)) {
            throw new InvalidArgumentException('Argument 1 passed to MockArtifactBuilder::withParent() must be an object of class Tracker_Artifact');
        }
        $this->parent = $parent;
        return $this;
    }

    public function allUsersCanView() {
        $this->userCanView = true;
        return $this;
    }

    /** @return \Tracker_Artifact */
    public function build() {
        $this->artifact->setReturnValue('getId', $this->id);
        $this->artifact->setReturnValue('getTracker', $this->tracker);
        $this->artifact->setReturnValue('getTitle', $this->title);
        $this->artifact->setReturnValue('getUri', $this->uri);
        $this->artifact->setReturnValue('getXRef', $this->xref);
        $this->artifact->setReturnValue('getLinkedArtifacts', $this->linkedArtifacts);
        $this->artifact->setReturnValue('getUniqueLinkedArtifacts', $this->uniqueLinkedArtifacts);
        $this->artifact->setReturnValue('getAllowedChildrenTypes', $this->allowedChildrenTypes);
        $this->artifact->setReturnValue('getValue', $this->value);
        $this->artifact->setReturnValue('getParent', $this->parent);
        $this->artifact->setReturnValue('userCanView', $this->userCanView);
        $this->artifact->setReturnValue('getLastChangeset', $this->lastChangeset);

        return $this->artifact;
    }
}

function aMockArtifact() { return new MockArtifactBuilder(); }
?>

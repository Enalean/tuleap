<?php
/**
 * Copyright Enalean (c) 2014 - Present. All rights reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Tracker;

class Tracker_ArtifactLinkInfo
{
    protected $artifact_id;
    protected $keyword;
    protected $group_id;
    protected $tracker_id;
    protected $last_changeset_id;

    /**
     * @var string|null
     */
    private $type;

    /**
     * @var Artifact
     */
    private $artifact;

    /**
     * @param int $artifact_id
     * @param string  $keyword
     * @param int $group_id
     * @param int $last_changeset_id
     */
    public function __construct($artifact_id, $keyword, $group_id, $tracker_id, $last_changeset_id, ?string $type)
    {
        $this->artifact_id       = $artifact_id;
        $this->keyword           = $keyword;
        $this->group_id          = $group_id;
        $this->tracker_id        = $tracker_id;
        $this->last_changeset_id = $last_changeset_id;
        $this->type              = $type;
    }

    public static function buildFromArtifact(Artifact $artifact, string $type): self
    {
        $tracker = $artifact->getTracker();

        $changeset_id   = 0;
        $last_changeset = $artifact->getLastChangeset();
        if ($last_changeset) {
            $changeset_id = (int) $last_changeset->getId();
        }

        return (
            new Tracker_ArtifactLinkInfo(
                $artifact->getId(),
                $tracker->getItemName(),
                $tracker->getGroupId(),
                $tracker->getId(),
                $changeset_id,
                $type
            )
        )->setArtifact($artifact);
    }

    public function getArtifactId(): int
    {
        return (int) $this->artifact_id;
    }

    /**
     * @return string the keyword of the artifact link
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * @return int the group_id of the artifact link
     */
    public function getGroupId()
    {
        return $this->group_id;
    }

    public function getTrackerId(): int
    {
        return (int) $this->tracker_id;
    }

    /**
     * Returns the tracker this artifact belongs to
     *
     * @return Tracker|null The tracker this artifact belongs to
     */
    public function getTracker()
    {
        return TrackerFactory::instance()->getTrackerByid($this->tracker_id);
    }

    /**
     * @return int the last changeset_id of the artifact link
     */
    public function getLastChangesetId()
    {
        return $this->last_changeset_id;
    }

    /**
     * Get the link to the artifact link
     *
     * @return string the html code (a href) to this artifact link
     */
    public function getLink()
    {
        return '<a class="cross-reference" href="' . $this->getUrl() . '">' . Codendi_HTMLPurifier::instance()->purify($this->getLabel()) . '</a>';
    }

    public function getUrl()
    {
        return \Tuleap\ServerHostname::HTTPSUrl() . '/goto?' . http_build_query(
            [
                'key'      => $this->getKeyword(),
                'val'      => $this->getArtifactId(),
                'group_id' => $this->getGroupId(),
            ]
        );
    }

    /**
     * Get the raw value of this artifact link (bug #1234, story #9876, etc.)
     *
     * @return string the raw value of this artifact link
     */
    public function getLabel(): string
    {
        return $this->getKeyword() . ' #' . $this->getArtifactId();
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type)
    {
        $this->type = $type;
    }

    /**
     * Returns true is the current user can see the artifact
     *
     * @return bool
     */
    public function userCanView(PFUser $user)
    {
        $artifact = $this->getArtifact();

        return $artifact !== null && $artifact->userCanView($user);
    }

    public function getArtifact(): ?Artifact
    {
        if (! $this->artifact) {
            $this->artifact = Tracker_ArtifactFactory::instance()->getArtifactById($this->artifact_id);
        }
        return $this->artifact;
    }

    private function setArtifact(Artifact $artifact): self
    {
        $this->artifact    = $artifact;
        $this->artifact_id = $artifact->getId();
        return $this;
    }

    public function __toString(): string
    {
        return $this->getLabel();
    }
}

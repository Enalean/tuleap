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

class Tracker_ArtifactLinkInfo
{
    protected $artifact_id;
    protected $keyword;
    protected $group_id;
    protected $tracker_id;
    protected $last_changeset_id;
    private $nature;
    /**
     * @var Tracker_Artifact
     */
    private $artifact;

    /**
     * @param int $artifact_id
     * @param string  $keyword
     * @param int $group_id
     * @param int $last_changeset_id
     * @param string $nature
     */
    public function __construct($artifact_id, $keyword, $group_id, $tracker_id, $last_changeset_id, $nature)
    {
        $this->artifact_id       = $artifact_id;
        $this->keyword           = $keyword;
        $this->group_id          = $group_id;
        $this->tracker_id        = $tracker_id;
        $this->last_changeset_id = $last_changeset_id;
        $this->nature            = $nature;
    }

    public static function buildFromArtifact(Tracker_Artifact $artifact, string $nature): self
    {
        $tracker = $artifact->getTracker();

        $changeset_id   = 0;
        $last_changeset = $artifact->getLastChangeset();
        if ($last_changeset) {
            $changeset_id = $last_changeset->getId();
        }

        return (
            new Tracker_ArtifactLinkInfo(
                $artifact->getId(),
                $tracker->getItemName(),
                $tracker->getGroupId(),
                $tracker->getId(),
                $changeset_id,
                $nature
            )
        )->setArtifact($artifact);
    }

    /**
     * @return int the id of the artifact link
     */
    public function getArtifactId()
    {
        return $this->artifact_id;
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

    /**
     * @return int the tracker_id of the artifact link
     */
    public function getTrackerId()
    {
        return $this->tracker_id;
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
        return HTTPRequest::instance()->getServerUrl() . '/goto?' . http_build_query(
            array(
                'key'      => $this->getKeyword(),
                'val'      => $this->getArtifactId(),
                'group_id' => $this->getGroupId()
            )
        );
    }

    /**
     * Get the raw value of this artifact link (bug #1234, story #9876, etc.)
     *
     * @return string the raw value of this artifact link
     */
    public function getLabel()
    {
        return $this->getKeyword() . ' #' . $this->getArtifactId();
    }

    public function getNature()
    {
        return $this->nature;
    }

    public function setNature($nature)
    {
        $this->nature = $nature;
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

    public function getArtifact(): ?Tracker_Artifact
    {
        if (! $this->artifact) {
            $this->artifact = Tracker_ArtifactFactory::instance()->getArtifactById($this->artifact_id);
        }
        return $this->artifact;
    }

    private function setArtifact(Tracker_Artifact $artifact): self // phpcs:ignore SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedMethod
    {
        $this->artifact    = $artifact;
        $this->artifact_id = $artifact->getId();
        return $this;
    }

    public function __toString()
    {
        return $this->getLabel();
    }

    public function shouldLinkBeHidden($nature)
    {
        $hide_artifact = false;
        $params = array(
            'nature'        => $nature,
            'hide_artifact' => &$hide_artifact
        );
        EventManager::instance()->processEvent(
            Tracker_Artifact_ChangesetValue_ArtifactLinkDiff::HIDE_ARTIFACT,
            $params
        );

        return $params['hide_artifact'];
    }
}

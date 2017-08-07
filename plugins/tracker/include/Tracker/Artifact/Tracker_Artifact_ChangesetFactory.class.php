<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Tracker_Artifact_ChangesetFactory {
    /** Tracker_Artifact_ChangesetDao */
    private $dao;

    /** @var Tracker_Artifact_ChangesetJsonFormatter */
    private $json_formatter;

    public function __construct(Tracker_Artifact_ChangesetDao $dao, Tracker_Artifact_ChangesetJsonFormatter $json_formatter) {
        $this->dao = $dao;
        $this->json_formatter = $json_formatter;
    }

    /**
     * Return a changeset
     *
     * @param Tracker_Artifact $artifact
     * @param Integer $changeset_id
     * @return Tracker_Artifact_Changeset | null
     */
    public function getChangeset(Tracker_Artifact $artifact, $changeset_id) {
        $row = $this->dao->searchByArtifactIdAndChangesetId($artifact->getId(), $changeset_id)->getRow();
        if ($row) {
            return $this->getChangesetFromRow($artifact, $row);
        }
        return null;
    }

    /**
     * @return \Tracker_Artifact_Changeset|null
     */
    public function getLastChangeset(Tracker_Artifact $artifact) {
        $row = $this->dao->searchLastChangesetByArtifactId($artifact->getId())->getRow();
        if ($row) {
            return $this->getChangesetFromRow($artifact, $row);
        }
        return null;
    }

    /**
     * @return \Tracker_Artifact_Changeset|null
     */
    public function getLastChangesetWithFieldValue(Tracker_Artifact $artifact, Tracker_FormElement_Field $field) {
        $dar = $this->dao->searchLastChangesetAndValueForArtifactField($artifact->getId(), $field->getId());
        if ($dar) {
            $row       = $dar->getRow();
            $changeset = $this->getChangesetFromRow($artifact, $row);
            $value     = $field->getChangesetValue($changeset, $row['value_id'], $row['has_changed']);
            $changeset->setFieldValue($field, $value);
            return $changeset;
        }
        return null;
    }

    /**
     * Return all the changesets of an artifact
     *
     * @param Tracker_Artifact $artifact
     * @return Tracker_Artifact_Changeset[]
     */
    public function getChangesetsForArtifact(Tracker_Artifact $artifact) {
        $changesets = array();
        foreach ($this->dao->searchByArtifactId($artifact->getId()) as $row) {
            $changesets[$row['id']] = $this->getChangesetFromRow($artifact, $row);
        }
        return $changesets;
    }

    /**
     * @param Tracker_Artifact $artifact
     * @return Tracker_Artifact_Changeset[]
     * @internal param PFUser $user
     */
    public function getChangesetsForArtifactWithComments(Tracker_Artifact $artifact)
    {
        $comment_dao    = new Tracker_Artifact_Changeset_CommentDao();
        $comments_cache = $comment_dao->searchLastVersionForArtifact($artifact->getId());

        $changesets = $this->getChangesetsForArtifact($artifact);
        foreach ($changesets as $changeset) {
            $this->setCommentsFromCache($changeset, $comments_cache);
        }
        return $changesets;
    }

    private function setCommentsFromCache(Tracker_Artifact_Changeset $changeset, array $comments_cache)
    {
        if (isset($comments_cache[$changeset->getId()])) {
            $row = $comments_cache[$changeset->getId()];
            $comment = new Tracker_Artifact_Changeset_Comment(
                $row['id'],
                $changeset,
                $row['comment_type_id'],
                $row['canned_response_id'],
                $row['submitted_by'],
                $row['submitted_on'],
                $row['body'],
                $row['body_format'],
                $row['parent_id']
            );
            $changeset->setLatestComment($comment);
        }
    }

    /**
     * Get all changesets in a format ready for json conversion
     *
     * @param Tracker_Artifact $artifact
     * @param Integer $changeset_id
     * @return array
     */
    public function getNewChangesetsFormattedForJson(Tracker_Artifact $artifact, $changeset_id) {
        $changesets = array();
        foreach ($this->dao->searchChangesetNewerThan($artifact->getId(), $changeset_id) as $row) {
            $changesets[] = $this->json_formatter->format($this->getChangesetFromRow($artifact, $row));
        }
        return $changesets;
    }

    private function getChangesetFromRow(Tracker_Artifact $artifact, $row) {
        return new Tracker_Artifact_Changeset(
            $row['id'],
            $artifact,
            $row['submitted_by'],
            $row['submitted_on'],
            $row['email']
        );
    }
}

?>

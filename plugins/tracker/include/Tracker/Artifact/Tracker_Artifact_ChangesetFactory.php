<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionRetriever;

class Tracker_Artifact_ChangesetFactory
{
    /** Tracker_Artifact_ChangesetDao */
    private $dao;
    /**
     * @var Tracker_Artifact_Changeset_ValueDao
     */
    private $changeset_value_dao;
    /**
     * @var Tracker_Artifact_Changeset_CommentDao
     */
    private $changeset_comment_dao;
    /** @var Tracker_Artifact_ChangesetJsonFormatter */
    private $json_formatter;
    /**
     * @var Tracker_FormElementFactory
     */
    private $tracker_form_element_factory;
    /**
     * @var TrackerPrivateCommentUGroupPermissionRetriever
     */
    private $private_comment_permission_retriever;

    public function __construct(
        Tracker_Artifact_ChangesetDao $dao,
        Tracker_Artifact_Changeset_ValueDao $changeset_value_dao,
        Tracker_Artifact_Changeset_CommentDao $changeset_comment_dao,
        Tracker_Artifact_ChangesetJsonFormatter $json_formatter,
        Tracker_FormElementFactory $tracker_form_element_factory,
        TrackerPrivateCommentUGroupPermissionRetriever $private_comment_permission_retriever,
    ) {
        $this->dao                                  = $dao;
        $this->changeset_value_dao                  = $changeset_value_dao;
        $this->changeset_comment_dao                = $changeset_comment_dao;
        $this->json_formatter                       = $json_formatter;
        $this->tracker_form_element_factory         = $tracker_form_element_factory;
        $this->private_comment_permission_retriever = $private_comment_permission_retriever;
    }

    /**
     * Return a changeset
     *
     * @param int $changeset_id
     * @return Tracker_Artifact_Changeset | null
     */
    public function getChangeset(Artifact $artifact, $changeset_id)
    {
        $row = $this->dao->searchByArtifactIdAndChangesetId($artifact->getId(), $changeset_id)->getRow();
        if ($row) {
            return $this->getChangesetFromRow($artifact, $row);
        }
        return null;
    }

    /**
     * @return \Tracker_Artifact_Changeset|null
     */
    public function getLastChangeset(Artifact $artifact)
    {
        $row = $this->dao->searchLastChangesetByArtifactId($artifact->getId())->getRow();
        if ($row) {
            return $this->getChangesetFromRow($artifact, $row);
        }
        return null;
    }

    /**
     * @return null|Tracker_Artifact_Changeset
     */
    public function getChangesetAtTimestamp(Artifact $artifact, $timestamp)
    {
        $row = $this->dao->searchChangesetByTimestamp($artifact->getId(), $timestamp)->getRow();
        if ($row) {
            return $this->getChangesetFromRow($artifact, $row);
        }

        return null;
    }

    public function getLastChangesetWithFieldValue(Artifact $artifact, Tracker_FormElement_Field $field): ?Tracker_Artifact_Changeset
    {
        $dar = $this->dao->searchLastChangesetAndValueForArtifactField($artifact->getId(), $field->getId());
        if ($dar === false) {
            return null;
        }
        $row = $dar->getRow();
        if ($row === false) {
            return null;
        }

        $changeset = $this->getChangesetFromRow($artifact, $row);
        $value     = $field->getChangesetValue($changeset, $row['value_id'], $row['has_changed']);
        $changeset->setFieldValue($field, $value);
        return $changeset;
    }

    /**
     * @return \Tracker_Artifact_Changeset|null
     */
    public function getPreviousChangesetWithFieldValue(
        Artifact $artifact,
        Tracker_FormElement_Field $field,
        $changeset_id,
    ) {
        $row = $this->dao->searchPreviousChangesetAndValueForArtifactField(
            $artifact->getId(),
            $field->getId(),
            $changeset_id
        );
        if ($row) {
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
     * @return Tracker_Artifact_Changeset[]
     */
    public function getChangesetsForArtifact(Artifact $artifact)
    {
        $changesets = [];
        foreach ($this->dao->searchByArtifactId($artifact->getId()) as $row) {
            $changesets[$row['id']] = $this->getChangesetFromRow($artifact, $row);
        }
        return $changesets;
    }

    /**
     * @return Tracker_Artifact_Changeset[]
     */
    public function getFullChangesetsForArtifact(Artifact $artifact, PFUser $user)
    {
        $changeset_values_cache = $this->changeset_value_dao->searchByArtifactId($artifact->getId());
        $comments_cache         = $this->changeset_comment_dao->searchLastVersionForArtifact($artifact->getId());

        $changesets         = $this->getChangesetsForArtifact($artifact);
        $previous_changeset = null;
        foreach ($changesets as $changeset) {
            $this->setCommentsFromCache($comments_cache, $changeset);
            $this->setFieldValuesFromCache($user, $changeset_values_cache, $changeset, $previous_changeset);

            $previous_changeset = $changeset;
        }
        return $changesets;
    }

    private function setCommentsFromCache(array $cache, Tracker_Artifact_Changeset $changeset)
    {
        if (isset($cache[$changeset->getId()])) {
            $row                     = $cache[$changeset->getId()];
            $comment_id              = $row['id'];
            $ugroups_private_comment = $this->private_comment_permission_retriever
                ->getUGroupsCanSeePrivateComment($changeset->getTracker(), (int) $comment_id);

            $comment = new Tracker_Artifact_Changeset_Comment(
                $comment_id,
                $changeset,
                $row['comment_type_id'],
                $row['canned_response_id'],
                $row['submitted_by'],
                $row['submitted_on'],
                $row['body'],
                $row['body_format'],
                $row['parent_id'],
                $ugroups_private_comment
            );
            $changeset->setLatestComment($comment);
        }
    }

    private function setFieldValuesFromCache(
        PFUser $user,
        array $cache,
        Tracker_Artifact_Changeset $changeset,
        ?Tracker_Artifact_Changeset $previous_changeset = null,
    ) {
        foreach ($cache[$changeset->getId()] as $changeset_value_row) {
            $field = $this->tracker_form_element_factory->getFieldById($changeset_value_row['field_id']);
            if ($field === null || ! $field->userCanRead($user)) {
                continue;
            }
            if ($changeset_value_row['has_changed']) {
                $changeset_value = $field->getChangesetValue($changeset, $changeset_value_row['id'], $changeset_value_row['has_changed']);
                $changeset->setFieldValue($field, $changeset_value);
            } elseif ($previous_changeset !== null) {
                $changeset->setFieldValue($field, $previous_changeset->getValue($field));
            } else {
                $changeset->setNoFieldValue($field);
            }
        }
    }

    /**
     * Get all changesets in a format ready for json conversion
     *
     * @param int $changeset_id
     * @return array
     */
    public function getNewChangesetsFormattedForJson(Artifact $artifact, $changeset_id, PFUser $current_user)
    {
        $changesets = [];
        foreach ($this->dao->searchChangesetNewerThan($artifact->getId(), $changeset_id) as $row) {
            $changesets[] = $this->json_formatter->format($this->getChangesetFromRow($artifact, $row), $current_user);
        }
        return $changesets;
    }

    private function getChangesetFromRow(Artifact $artifact, $row)
    {
        return new Tracker_Artifact_Changeset(
            $row['id'],
            $artifact,
            $row['submitted_by'],
            $row['submitted_on'],
            $row['email']
        );
    }
}

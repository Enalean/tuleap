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

class Tracker_REST_Artifact_ArtifactUpdater {

    /** @var Tracker_REST_Artifact_ArtifactValidator */
    private $artifact_validator;

    public function __construct(Tracker_REST_Artifact_ArtifactValidator $artifact_validator) {
        $this->artifact_validator = $artifact_validator;
    }

    public function update(PFUser $user, Tracker_Artifact $artifact, array $values, Tuleap\Tracker\REST\ChangesetCommentRepresentation $comment = null) {
        $this->checkArtifact($user, $artifact);
        $fields_data = $this->artifact_validator->getFieldData($values, $artifact);

        $comment_body   = '';
        $comment_format = Tracker_Artifact_Changeset_Comment::TEXT_COMMENT;
        if ($comment) {
            $comment_body   = $comment->body;
            $comment_format = $comment->format;
        }

        $artifact->createNewChangeset($fields_data, $comment_body, $user, true, $comment_format);
    }

    private function checkArtifact(PFUser $user, Tracker_Artifact $artifact) {
        if (! $artifact) {
            throw new RestException(404, 'Artifact not found');
        }
        if (! $artifact->userCanUpdate($user)) {
            throw new RestException(403, 'You have not the permission to update this card');
        }
    }
}

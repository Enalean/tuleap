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

namespace Tuleap\Tracker\REST\Artifact;

use Luracast\Restler\RestException;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\FormElement\Field\Files\CreatedFileURLMapping;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\NewChangesetCommentRepresentation;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataBuilder;

/**
 * I'm responsible for updating all fields of artifact (title, description, artifact_link ...)
 */
class ArtifactUpdater
{
    public function __construct(
        private FieldsDataBuilder $fields_data_builder,
        private NewChangesetCreator $changeset_creator,
        private CheckArtifactRestUpdateConditions $check_artifact_rest_update_conditions,
    ) {
    }

    /**
     * @throws \Tracker_Exception
     * @throws \Tracker_NoChangeException
     * @throws RestException
     */
    public function update(
        \PFUser $user,
        Artifact $artifact,
        array $values,
        ?NewChangesetCommentRepresentation $comment = null,
    ): void {
        $this->check_artifact_rest_update_conditions->checkIfArtifactUpdateCanBePerformedThroughREST($user, $artifact);
        $changeset_values = $this->fields_data_builder->getFieldsDataOnUpdate($values, $artifact, $user);

        $comment_body   = $comment?->body ?? '';
        $comment_format = $comment?->format ?? CommentFormatIdentifier::COMMONMARK->value;

        $submitted_on  = $_SERVER['REQUEST_TIME'];
        $new_changeset = NewChangeset::fromFieldsDataArray(
            $artifact,
            $changeset_values->getFieldsData(),
            $comment_body,
            CommentFormatIdentifier::fromStringWithDefault($comment_format),
            [],
            $user,
            $submitted_on,
            new CreatedFileURLMapping()
        );
        $this->changeset_creator->create(
            $new_changeset,
            PostCreationContext::withNoConfig(true)
        );
    }
}

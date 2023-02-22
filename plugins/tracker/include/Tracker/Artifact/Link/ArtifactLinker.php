<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\Link;

use Feedback;
use PFUser;
use RuntimeException;
use Tracker;
use Tracker_Exception;
use Tracker_FormElement_Field_ArtifactLink;
use Tracker_NoChangeException;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\Changeset\CreateNewChangeset;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\Artifact\RetrieveTracker;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\RetrieveUsedArtifactLinkFields;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;

final class ArtifactLinker
{
    public function __construct(
        private RetrieveUsedArtifactLinkFields $form_element_factory,
        private RetrieveTracker $tracker_factory,
        private CreateNewChangeset $changeset_creator,
        private FilterArtifactLink $artifact_link_filter,
    ) {
    }

    /**
     * User want to link an artifact to the current one
     */
    public function linkArtifact(
        Artifact $current_artifact,
        int|string $linked_artifact_id,
        PFUser $current_user,
        string $artifact_link_type = Tracker_FormElement_Field_ArtifactLink::NO_TYPE,
    ): bool {
        $tracker        = $this->getTrackerFromArtifact($current_artifact);
        $artlink_fields = $this->form_element_factory->getUsedArtifactLinkFields($tracker);

        if (count($artlink_fields) === 0) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext(
                    'tuleap-tracker',
                    'The artifact doesn\'t have an artifact link field or you have not the permission to modify it, please reconfigure your tracker'
                )
            );
            return false;
        }

        $comment       = '';
        $artlink_field = $artlink_fields[0];

        $linked_artifact_id = $this->artifact_link_filter->filterArtifactIdsIAmAlreadyLinkedTo($current_artifact, $artlink_field, (string) $linked_artifact_id);

        $fields_data                                        = [];
        $fields_data[$artlink_field->getId()]['new_values'] = $linked_artifact_id;

        if ($tracker->isProjectAllowedToUseType()) {
            $fields_data[$artlink_field->getId()]['types'] = $this->getTypeForLink(
                $linked_artifact_id,
                $artifact_link_type
            );
        }

        try {
            $comment_format_at_artifact_linking = CommentFormatIdentifier::buildCommonMark();
            $new_changeset                      = NewChangeset::fromFieldsDataArray(
                $current_artifact,
                $fields_data,
                $comment,
                $comment_format_at_artifact_linking,
                [],
                $current_user,
                (new \DateTimeImmutable())->getTimestamp(),
                new CreatedFileURLMapping()
            );
            $this->changeset_creator->create($new_changeset, PostCreationContext::withNoConfig(true));

            return true;
        } catch (Tracker_NoChangeException $e) {
            $GLOBALS['Response']->addFeedback(Feedback::INFO, $e->getMessage(), CODENDI_PURIFIER_LIGHT);
            return false;
        } catch (Tracker_Exception $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $e->getMessage());
            return false;
        }
    }

    private function getTrackerFromArtifact(Artifact $artifact): Tracker
    {
        $tracker = $this->tracker_factory->getTrackerById($artifact->getTrackerId());
        if ($tracker === null) {
            throw new RuntimeException('Tracker does not exist');
        }
        return $tracker;
    }

    private function getTypeForLink(string $linked_artifact_id, string $artifact_link_type): array
    {
        $types                     = [];
        $linked_artifact_ids_array = explode(',', $linked_artifact_id);
        foreach ($linked_artifact_ids_array as $linked_artifact_id) {
            $types[$linked_artifact_id] = $artifact_link_type;
        }

        return $types;
    }
}

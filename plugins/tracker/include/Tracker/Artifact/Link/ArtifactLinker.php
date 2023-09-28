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
use Tracker_Exception;
use Tracker_NoChangeException;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\Changeset\CreateNewChangeset;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\RetrieveForwardLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValuesContainer;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\RetrieveUsedArtifactLinkFields;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;

/**
 * I'm responsible for link an artifact to another using artifact link field
 */
class ArtifactLinker
{
    public function __construct(
        private readonly RetrieveUsedArtifactLinkFields $form_element_factory,
        private readonly CreateNewChangeset $changeset_creator,
        private readonly RetrieveForwardLinks $forward_links_retriever,
    ) {
    }

    /**
     * User want to link an artifact to the current one
     */
    public function linkArtifact(
        Artifact $current_artifact,
        CollectionOfForwardLinks $forward_links,
        PFUser $current_user,
    ): bool {
        $artlink_fields = $this->form_element_factory->getUsedArtifactLinkFields($current_artifact->getTracker());

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

        $comment             = '';
        $artifact_link_field = $artlink_fields[0];

        $existing_links      = $this->forward_links_retriever->retrieve($current_user, $artifact_link_field, $current_artifact);
        $new_changeset_value = Option::fromValue(
            NewArtifactLinkChangesetValue::fromAddedAndUpdatedTypeValues(
                $artifact_link_field->getId(),
                $existing_links->differenceByIdAndType($forward_links),
            )
        );
        $container           = new ChangesetValuesContainer([], $new_changeset_value);

        try {
            $new_changeset = NewChangeset::fromFieldsDataArray(
                $current_artifact,
                $container->getFieldsData(),
                $comment,
                CommentFormatIdentifier::buildCommonMark(),
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
}

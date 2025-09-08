<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\Changeset;

use Tuleap\Tracker\Artifact\Exception\FieldValidationException;
use Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\LinkToParentWithoutCurrentArtifactChangeException;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction;

final readonly class NewChangesetValidator implements ValidateNewChangeset
{
    public function __construct(
        private \Tracker_Artifact_Changeset_FieldsValidator $fields_validator,
        private \Tracker_Artifact_Changeset_ChangesetDataInitializator $field_initializator,
        private ParentLinkAction $parent_link_action,
    ) {
    }

    /**
     * @throws \Tracker_Workflow_Transition_InvalidConditionForTransitionException
     * @throws \Tracker_Workflow_GlobalRulesViolationException
     * @throws FieldValidationException
     * @throws \Tracker_NoChangeException
     * @throws \Tracker_Exception
     * @throws LinkToParentWithoutCurrentArtifactChangeException
     */
    #[\Override]
    public function validateNewChangeset(NewChangeset $new_changeset, ?string $email, \Workflow $workflow): void
    {
        $artifact    = $new_changeset->getArtifact();
        $submitter   = $new_changeset->getSubmitter();
        $fields_data = $new_changeset->getFieldsData();
        $comment     = $new_changeset->getComment()->getBody();

        if ($submitter->isAnonymous() && ($email === null || $email === '')) {
            $message = dgettext('tuleap-tracker', 'You are not logged in.');
            throw new \Tracker_Exception($message);
        }

        $are_fields_valid = $this->fields_validator->validate(
            $artifact,
            $submitter,
            $fields_data,
            new NullChangesetValidationContext()
        );
        if (! $are_fields_valid) {
            $errors_from_feedback = $GLOBALS['Response']->getFeedbackErrors();
            $GLOBALS['Response']->clearFeedbackErrors();

            throw new FieldValidationException($errors_from_feedback);
        }

        $last_changeset = $artifact->getLastChangeset();

        if ($last_changeset && ! $comment && ! $last_changeset->hasChanges($fields_data)) {
            if ($this->parent_link_action->linkParent($artifact, $submitter, $fields_data)) {
                throw new LinkToParentWithoutCurrentArtifactChangeException();
            }
            throw new \Tracker_NoChangeException($artifact->getId(), $artifact->getXRef());
        }

        $initialized_fields_data = $this->field_initializator->process($artifact, $fields_data);

        $workflow->validate($initialized_fields_data, $artifact, $comment, $submitter);
        /*
         * We need to run the post actions to validate the data
         */
        $workflow->before($initialized_fields_data, $submitter, $artifact);
        $workflow->checkGlobalRules($initialized_fields_data);
    }
}

<?php
/**
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic;

use PFUser;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;
use Tuleap\Tracker\Permission\VerifySubmissionPermissions;
use Tuleap\Tracker\Semantic\Title\RetrieveSemanticTitleField;
use Tuleap\Tracker\Semantic\Title\TrackerSemanticTitle;
use Tuleap\Tracker\Tracker;

final readonly class ArtifactCannotBeCreatedReasonsGetter
{
    public function __construct(
        private VerifySubmissionPermissions $can_submit_artifact_verifier,
        private RetrieveUsedFields $used_fields_retriever,
        private RetrieveSemanticTitleField $title_field_retriever,
    ) {
    }

    public function getCannotCreateArtifactReasons(CollectionOfCreationSemanticToCheck $semantics_to_check, Tracker $tracker, PFUser $user): CollectionOfCannotCreateArtifactReason
    {
        $cannot_create_reasons = CollectionOfCannotCreateArtifactReason::fromEmptyReason();

        if ($semantics_to_check->isEmpty()) {
            return $cannot_create_reasons;
        }

        $cannot_create_reasons = $cannot_create_reasons->addReasons($this->canUserCreateArtifact($tracker, $user));

        foreach ($semantics_to_check->semantics as $semantic) {
            if ($semantic->isSemanticTitle()) {
                $cannot_create_reasons = $cannot_create_reasons->addReasons($this->getReasonsFromSemanticTitle($tracker, $user));
            }
        }
        return $cannot_create_reasons;
    }

    private function canUserCreateArtifact(Tracker $tracker, PFUser $user): CollectionOfCannotCreateArtifactReason
    {
        $cannot_create_reasons = CollectionOfCannotCreateArtifactReason::fromEmptyReason();

        if (! $this->can_submit_artifact_verifier->canUserSubmitArtifact($user, $tracker)) {
            $cannot_create_reasons = $cannot_create_reasons->addReason(CannotCreateArtifactReason::fromString(dgettext('tuleap-tracker', 'You can\'t submit an artifact because you do not have the right to submit all required fields')));
        }
        return $cannot_create_reasons;
    }

    private function getReasonsFromSemanticTitle(Tracker $tracker, PFUser $user): CollectionOfCannotCreateArtifactReason
    {
        $title_field           = $this->title_field_retriever->fromTracker($tracker);
        $cannot_create_reasons = CollectionOfCannotCreateArtifactReason::fromEmptyReason();

        if ($title_field === null) {
            return $cannot_create_reasons->addReason(CannotCreateArtifactReason::fromString(dgettext('tuleap-tracker', 'Title semantic is not defined')));
        }
        if (! $title_field->userCanSubmit($user)) {
            $cannot_create_reasons = $cannot_create_reasons->addReason(CannotCreateArtifactReason::fromString(sprintf(dgettext('tuleap-tracker', "You do not have the right to submit '%s' field"), $title_field->getLabel())));
        }
        if (! $this->hasTrackerOnlyTitleRequired($tracker, new TrackerSemanticTitle($tracker, $title_field), $user)) {
            $cannot_create_reasons = $cannot_create_reasons->addReason(CannotCreateArtifactReason::fromString(sprintf(dgettext('tuleap-tracker', "Other field than '%s' is required"), $title_field->getLabel())));
        }
        return $cannot_create_reasons;
    }

    private function hasTrackerOnlyTitleRequired(Tracker $tracker, TrackerSemanticTitle $semantic_title, PFUser $user): bool
    {
        $used_fields = $this->used_fields_retriever->getUsedFields($tracker);
        foreach ($used_fields as $used_field) {
            if (($used_field->isRequired() && $used_field->userCanSubmit($user)) && $used_field->getId() !== $semantic_title->getFieldId()) {
                return false;
            }
        }
        return true;
    }
}

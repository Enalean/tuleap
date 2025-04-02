<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use Tracker_Artifact_ChangesetValue_ArtifactLink;
use Tuleap\Tracker\Artifact\Artifact;

class SubmittedValueEmptyChecker
{
    /**
     * Say if the submitted value is empty:
     *
     * if no last changeset values and empty submitted values and no reverse links: empty
     * if not empty last changeset values and empty submitted values : not empty
     * if has any reverse links: not empty
     * if only submits new parent : not empty
     * if empty new values and not empty last changeset values and not empty removed values have the same size: empty
     */
    public function isSubmittedValueEmpty(
        array $submitted_value,
        ArtifactLinkField $field_artifact_link,
        Artifact $artifact,
    ): bool {
        if ($this->isSubmittingAParentArtifact($submitted_value)) {
            return false;
        }

        if ($this->isSubmittingNewLinks($submitted_value)) {
            return false;
        }

        if ($this->hasReverseLinks($field_artifact_link, $artifact)) {
            return false;
        }

        $currently_linked_artifact = [];
        $last_changeset_value      = $field_artifact_link->getLastChangesetValue($artifact);
        if ($last_changeset_value) {
            assert($last_changeset_value instanceof Tracker_Artifact_ChangesetValue_ArtifactLink);
            $currently_linked_artifact = $last_changeset_value->getArtifactIds();
        }

        if (! empty($currently_linked_artifact) && ! $this->isRemovingAllLinks($currently_linked_artifact, $submitted_value)) {
            return false;
        }

        return true;
    }

    private function isSubmittingAParentArtifact(array $submitted_value): bool
    {
        return isset($submitted_value['parent']) &&
            count($submitted_value['parent']) !== 0 &&
            (isset($submitted_value['parent'][0]) && $submitted_value['parent'][0] !== '');
    }

    private function isSubmittingNewLinks(array $submitted_value): bool
    {
        return isset($submitted_value['new_values']) && ! empty($submitted_value['new_values']);
    }

    private function hasReverseLinks(
        ArtifactLinkField $field_artifact_link,
        Artifact $artifact,
    ): bool {
        $reverse_artifact_links = $field_artifact_link->getReverseLinks($artifact->getId());

        return ! empty($reverse_artifact_links);
    }

    private function isRemovingAllLinks(array $currently_linked_artifact, array $submitted_value): bool
    {
        return isset($submitted_value['removed_values']) &&
            ! empty($submitted_value['removed_values']) &&
            count($currently_linked_artifact) === count($submitted_value['removed_values']);
    }
}

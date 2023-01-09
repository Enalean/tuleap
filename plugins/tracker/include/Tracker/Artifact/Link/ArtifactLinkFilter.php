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

use Tracker_Artifact_ChangesetValue_ArtifactLink;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;

final class ArtifactLinkFilter implements FilterArtifactLink
{
    public function filterArtifactIdsIAmAlreadyLinkedTo(
        Artifact $artifact,
        Tracker_FormElement_Field_ArtifactLink $field,
        CollectionOfForwardLinks $collection_of_forward_links,
    ): CollectionOfForwardLinks {
        $last_changeset = $artifact->getLastChangeset();
        if (! $last_changeset) {
            return $collection_of_forward_links;
        }

        $changeset_value = $last_changeset->getValue($field);

        if (! $changeset_value) {
            return $collection_of_forward_links;
        }

        \assert($changeset_value instanceof Tracker_Artifact_ChangesetValue_ArtifactLink);

        $existing_links       = $changeset_value->getArtifactIds();
        $linked_artifact_list = $collection_of_forward_links->getArtifactLinks();

        $filtered_forward_links = [];
        foreach ($linked_artifact_list as $artifact_link) {
            if (! in_array($artifact_link->getTargetArtifactId(), $existing_links)) {
                $filtered_forward_links[] = $artifact_link;
            }
        }
        return new CollectionOfForwardLinks($filtered_forward_links);
    }
}

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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink;

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\RetrieveArtifact;

final class ArtifactForwardLinksRetriever implements RetrieveForwardLinks
{
    public function __construct(
        private ArtifactLinksByChangesetCache $cache,
        private ChangesetValueArtifactLinkDao $dao,
        private RetrieveArtifact $artifact_retriever,
    ) {
    }

    #[\Override]
    public function retrieve(
        \PFUser $submitter,
        \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField $link_field,
        Artifact $artifact,
    ): CollectionOfForwardLinks {
        $last_changeset = $artifact->getLastChangeset();
        if (! $last_changeset) {
            return new CollectionOfForwardLinks([]);
        }

        return $this->getLinksInfo($submitter, $link_field, $last_changeset);
    }

    protected function getLinksInfo(
        \PFUser $submitter,
        \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField $artifact_link_field,
        \Tracker_Artifact_Changeset $last_changeset,
    ): CollectionOfForwardLinks {
        $last_changeset_id = (int) $last_changeset->getId();

        if ($this->cache->hasCachedLinksInfoForChangeset($last_changeset)) {
            return $this->cache->getCachedLinksInfoForChangeset($last_changeset);
        }

        $changeset_values = $this->dao->searchChangesetValues($artifact_link_field->getId(), $last_changeset_id);

        $links          = array_map(
            fn(array $row) => StoredForwardLink::fromRow($this->artifact_retriever, $submitter, $row),
            $changeset_values
        );
        $non_null_links = array_filter($links, static fn(?StoredForwardLink $link) => $link !== null);

        $collection = new CollectionOfForwardLinks($non_null_links);
        $this->cache->cacheLinksInfoForChangeset($last_changeset, $collection);

        return $collection;
    }
}

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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\UpdateValue;

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ChangesetValueArtifactLinkDao;
use Tuleap\Tracker\Artifact\RetrieveArtifact;

final class ArtifactForwardLinksRetriever implements RetrieveForwardLinks
{
    public function __construct(
        private ArtifactLinksByChangesetCache $cache,
        private ChangesetValueArtifactLinkDao $dao,
        private RetrieveArtifact $artifact_retriever,
    ) {
    }

    public function retrieve(
        \PFUser $submitter,
        \Tracker_FormElement_Field_ArtifactLink $link_field,
        ?Artifact $artifact,
    ): CollectionOfArtifactLinks {
        if (! $artifact) {
            return new CollectionOfArtifactLinks([]);
        }

        $last_changeset = $artifact->getLastChangeset();
        if (! $last_changeset) {
            return new CollectionOfArtifactLinks([]);
        }

        return $this->getLinksInfo($submitter, $link_field, $last_changeset);
    }

    protected function getLinksInfo(
        \PFUser $submitter,
        \Tracker_FormElement_Field_ArtifactLink $artifact_link_field,
        \Tracker_Artifact_Changeset $last_changeset,
    ): CollectionOfArtifactLinks {
        $last_changeset_id = (int) $last_changeset->getId();

        if ($this->cache->hasCachedLinksInfoForChangeset($last_changeset)) {
            return $this->cache->getCachedLinksInfoForChangeset($last_changeset);
        }

        $changeset_values = $this->dao->searchChangesetValues($artifact_link_field->getId(), $last_changeset_id);

        $links          = array_map(
            fn(array $row) => StoredLink::fromRow($this->artifact_retriever, $submitter, $row),
            $changeset_values
        );
        $non_null_links = array_filter($links, static fn(?StoredLink $link) => $link !== null);

        $collection = new CollectionOfArtifactLinks($non_null_links);
        $this->cache->cacheLinksInfoForChangeset($last_changeset, $collection);

        return $collection;
    }
}

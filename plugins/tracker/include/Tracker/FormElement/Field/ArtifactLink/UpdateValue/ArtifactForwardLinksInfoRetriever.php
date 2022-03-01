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

use Tracker_Artifact_Changeset;
use Tracker_ArtifactFactory;
use Tracker_ArtifactLinkInfo;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ChangesetValueArtifactLinkDao;

final class ArtifactForwardLinksInfoRetriever implements RetrieveForwardLinksInfo
{
    public function __construct(
        private ArtifactLinksByChangesetCache $cache,
        private ChangesetValueArtifactLinkDao $dao,
        private Tracker_ArtifactFactory $tracker_artifact_factory,
    ) {
    }

    public function retrieve(
        \PFUser $submitter,
        Tracker_FormElement_Field_ArtifactLink $link_field,
        ?Artifact $artifact,
    ): CollectionOfArtifactLinksInfo {
        if (! $artifact) {
            return new CollectionOfArtifactLinksInfo([]);
        }

        $last_changeset = $artifact->getLastChangeset();
        if (! $last_changeset) {
            return new CollectionOfArtifactLinksInfo([]);
        }

        return $this->getLinksInfo($submitter, $link_field, $last_changeset);
    }

    protected function getLinksInfo(
        \PFUser $submitter,
        Tracker_FormElement_Field_ArtifactLink $artifact_link_field,
        Tracker_Artifact_Changeset $last_changeset,
    ): CollectionOfArtifactLinksInfo {
        $last_changeset_id = (int) $last_changeset->getId();

        if ($this->cache->hasCachedLinksInfoForChangeset($last_changeset)) {
            return $this->cache->getCachedLinksInfoForChangeset($last_changeset);
        }

        $links_info       = [];
        $changeset_values = $this->dao->searchChangesetValues($artifact_link_field->getId(), $last_changeset_id);

        foreach ($changeset_values as $row) {
            $artifact = $this->tracker_artifact_factory->getArtifactById($row['artifact_id']);
            if (! $artifact) {
                continue;
            }

            $artifact_link_info = Tracker_ArtifactLinkInfo::buildFromArtifact($artifact, $row['nature'] ?? Tracker_FormElement_Field_ArtifactLink::NO_TYPE);
            if (! $artifact_link_info->userCanView($submitter)) {
                continue;
            }

            $links_info[] = $artifact_link_info;
        }

        $collection_of_links_info = new CollectionOfArtifactLinksInfo($links_info);
        $this->cache->cacheLinksInfoForChangeset($last_changeset, $collection_of_links_info);

        return $collection_of_links_info;
    }
}

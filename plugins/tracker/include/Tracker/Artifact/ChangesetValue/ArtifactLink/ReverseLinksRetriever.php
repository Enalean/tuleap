<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

final class ReverseLinksRetriever implements RetrieveReverseLinks
{
    public function __construct(
        private SearchReverseLinks $searcher,
        private RetrieveArtifact $artifact_retriever,
    ) {
    }

    #[\Override]
    public function retrieveReverseLinks(
        Artifact $artifact,
        \PFUser $user,
    ): CollectionOfReverseLinks {
        $stored_links   = $this->searcher->searchReverseLinksById($artifact->getId());
        $links          = array_map(
            fn(StoredLinkRow $row) => StoredReverseLink::fromRow($this->artifact_retriever, $user, $row),
            $stored_links
        );
        $non_null_links = array_values(array_filter($links, static fn(?StoredReverseLink $link) => $link !== null));
        return new CollectionOfReverseLinks($non_null_links);
    }
}

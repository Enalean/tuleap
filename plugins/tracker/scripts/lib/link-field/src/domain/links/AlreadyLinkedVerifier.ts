/*
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

import type { VerifyIsAlreadyLinked } from "./VerifyIsAlreadyLinked";
import type { LinkableArtifact } from "./LinkableArtifact";
import type { RetrieveLinkedArtifactsSync } from "./RetrieveLinkedArtifactsSync";
import type { RetrieveNewLinks } from "./RetrieveNewLinks";

export const AlreadyLinkedVerifier = (
    links_retriever: RetrieveLinkedArtifactsSync,
    new_links_retriever: RetrieveNewLinks,
): VerifyIsAlreadyLinked => ({
    isAlreadyLinked(linkable_artifact: LinkableArtifact): boolean {
        const has_non_removed_existing_link = links_retriever
            .getLinkedArtifacts()
            .some((link) => link.identifier.id === linkable_artifact.id);
        const has_new_link = new_links_retriever
            .getNewLinks()
            .some((link) => link.identifier.id === linkable_artifact.id);

        return has_new_link || has_non_removed_existing_link;
    },
});

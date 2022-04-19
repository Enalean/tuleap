/*
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

import type {
    LinkSelectorSearchFieldCallback,
    GroupOfItems,
    GroupCollection,
    HTMLTemplateStringProcessor,
} from "@tuleap/link-selector";
import type { Result } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { getMatchingArtifactLabel, getNoResultFoundEmptyState } from "../../../../gettext-catalog";
import type { Artifact } from "../../../../domain/Artifact";
import type { RetrieveMatchingArtifact } from "../../../../domain/fields/link-field-v2/RetrieveMatchingArtifact";
import { LinkableArtifactIdentifierProxy } from "./LinkableArtifactIdentifierProxy";
import type { CurrentArtifactIdentifier } from "../../../../domain/CurrentArtifactIdentifier";

const buildGroupFromResult = (
    html: typeof HTMLTemplateStringProcessor,
    result: Result<Artifact, Fault>
): GroupOfItems => {
    if (!result.isOk()) {
        return {
            label: getMatchingArtifactLabel(),
            empty_message: getNoResultFoundEmptyState(),
            items: [],
        };
    }
    const artifact = result.value;
    return {
        label: getMatchingArtifactLabel(),
        empty_message: getNoResultFoundEmptyState(),
        items: [
            {
                value: String(artifact.id),
                template: html`
                    <span class="tlp-badge-secondary link-field-xref-badge">${artifact.xref}</span>
                    ${artifact.title}
                `,
            },
        ],
    };
};

export interface ArtifactLinkSelectorAutoCompleterType {
    autoComplete: () => LinkSelectorSearchFieldCallback;
}

export const ArtifactLinkSelectorAutoCompleter = (
    retrieve_matching_artifact: RetrieveMatchingArtifact,
    current_artifact_identifier: CurrentArtifactIdentifier | null
): ArtifactLinkSelectorAutoCompleterType => ({
    autoComplete: (): LinkSelectorSearchFieldCallback => {
        return async (query: string, html): Promise<GroupCollection> => {
            const artifact_identifier = LinkableArtifactIdentifierProxy.fromQueryString(
                query,
                current_artifact_identifier
            );
            if (artifact_identifier === null) {
                return [];
            }

            const result = await retrieve_matching_artifact.getMatchingArtifact(
                artifact_identifier
            );
            return [buildGroupFromResult(html, result)];
        };
    },
});

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

import type { LinkSelectorSearchFieldCallback } from "@tuleap/link-selector";

import { getMatchingArtifactLabel, getNoResultFoundEmptyState } from "../../../../gettext-catalog";
import type { Artifact } from "../../../../domain/Artifact";
import type { RetrieveMatchingArtifact } from "../../../../domain/fields/link-field-v2/RetrieveMatchingArtifact";
import { LinkableArtifactIdentifierProxy } from "./LinkableArtifactIdentifierProxy";

export interface ArtifactLinkSelectorAutoCompleterType {
    autoComplete: (select: HTMLSelectElement) => LinkSelectorSearchFieldCallback;
}

export const ArtifactLinkSelectorAutoCompleter = (
    retrieve_matching_artifact: RetrieveMatchingArtifact
): ArtifactLinkSelectorAutoCompleterType => ({
    autoComplete: (select: HTMLSelectElement): LinkSelectorSearchFieldCallback => {
        const matching_artifact_optgroup = getMatchingArtifactOptionGroup();
        const empty_state = getEmptyState();

        select.appendChild(matching_artifact_optgroup);

        return async (query: string): Promise<void> => {
            const artifact_identifier = LinkableArtifactIdentifierProxy.fromQueryString(query);
            if (artifact_identifier === null) {
                clearSelectOptions();
                return;
            }

            const result = await retrieve_matching_artifact.getMatchingArtifact(
                artifact_identifier
            );
            if (!result.isOk()) {
                clearSelectOptions();
                matching_artifact_optgroup.appendChild(empty_state);
                return;
            }

            clearSelectOptions();
            matching_artifact_optgroup.appendChild(getMatchingArtifactOption(result.value));
        };

        function clearSelectOptions(): void {
            select.options.length = 0;
        }
    },
});

function getMatchingArtifactOption(artifact: Artifact): HTMLOptionElement {
    const option = document.createElement("option");
    option.value = String(artifact.id);
    option.textContent = artifact.xref + " - " + artifact.title;

    return option;
}

function getEmptyState(): HTMLOptionElement {
    const option = document.createElement("option");
    option.value = "";
    option.disabled = true;
    option.setAttribute("data-link-selector-role", "empty-state");
    option.textContent = getNoResultFoundEmptyState();

    return option;
}

function getMatchingArtifactOptionGroup(): HTMLOptGroupElement {
    const optgroup = document.createElement("optgroup");
    optgroup.label = getMatchingArtifactLabel();

    return optgroup;
}

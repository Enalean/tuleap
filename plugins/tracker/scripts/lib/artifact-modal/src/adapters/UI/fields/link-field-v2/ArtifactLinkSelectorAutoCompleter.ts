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

import type { LinkSelectorSearchFieldCallback, LinkSelector } from "@tuleap/link-selector";
import type { Fault } from "@tuleap/fault";
import type { RetrieveMatchingArtifact } from "../../../../domain/fields/link-field-v2/RetrieveMatchingArtifact";
import { LinkableNumberProxy } from "./LinkableNumberProxy";
import type { CurrentArtifactIdentifier } from "../../../../domain/CurrentArtifactIdentifier";
import { MatchingArtifactsGroup } from "./MatchingArtifactsGroup";
import type { ClearFaultNotification } from "../../../../domain/ClearFaultNotification";
import type { NotifyFault } from "../../../../domain/NotifyFault";
import { MatchingArtifactRetrievalFault } from "../../../../domain/fields/link-field-v2/MatchingArtifactRetrievalFault";

export type ArtifactLinkSelectorAutoCompleterType = {
    autoComplete: LinkSelectorSearchFieldCallback;
};

const isExpectedFault = (fault: Fault): boolean =>
    ("isForbidden" in fault && fault.isForbidden() === true) ||
    ("isNotFound" in fault && fault.isNotFound() === true);

export const ArtifactLinkSelectorAutoCompleter = (
    retrieve_matching_artifact: RetrieveMatchingArtifact,
    fault_notifier: NotifyFault,
    notification_clearer: ClearFaultNotification,
    current_artifact_identifier: CurrentArtifactIdentifier | null
): ArtifactLinkSelectorAutoCompleterType => ({
    autoComplete: async (link_selector: LinkSelector, query: string): Promise<void> => {
        notification_clearer.clearFaultNotification();

        const linkable_number = LinkableNumberProxy.fromQueryString(
            query,
            current_artifact_identifier
        );
        if (linkable_number === null) {
            link_selector.setDropdownContent([]);
            return;
        }

        link_selector.setDropdownContent([MatchingArtifactsGroup.buildLoadingState()]);

        const result = await retrieve_matching_artifact.getMatchingArtifact(linkable_number);
        link_selector.setDropdownContent([
            result.match(MatchingArtifactsGroup.fromMatchingArtifact, (fault) => {
                if (!isExpectedFault(fault)) {
                    fault_notifier.onFault(MatchingArtifactRetrievalFault(fault));
                }
                return MatchingArtifactsGroup.buildEmpty();
            }),
        ]);
    },
});

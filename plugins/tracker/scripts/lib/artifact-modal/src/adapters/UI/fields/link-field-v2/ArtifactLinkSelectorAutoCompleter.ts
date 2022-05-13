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
    GroupOfItems,
    LinkSelector,
    LinkSelectorSearchFieldCallback,
} from "@tuleap/link-selector";
import type { Fault } from "@tuleap/fault";
import type { RetrieveMatchingArtifact } from "../../../../domain/fields/link-field-v2/RetrieveMatchingArtifact";
import { LinkableNumberProxy } from "./LinkableNumberProxy";
import type { CurrentArtifactIdentifier } from "../../../../domain/CurrentArtifactIdentifier";
import { MatchingArtifactsGroup } from "./MatchingArtifactsGroup";
import type { ClearFaultNotification } from "../../../../domain/ClearFaultNotification";
import type { NotifyFault } from "../../../../domain/NotifyFault";
import { MatchingArtifactRetrievalFault } from "../../../../domain/fields/link-field-v2/MatchingArtifactRetrievalFault";
import type { RetrieveSelectedLinkType } from "../../../../domain/fields/link-field-v2/RetrieveSelectedLinkType";
import { LinkType } from "../../../../domain/fields/link-field-v2/LinkType";
import { PossibleParentsGroup } from "./PossibleParentsGroup";
import type { LinkableNumber } from "../../../../domain/fields/link-field-v2/LinkableNumber";
import type { RetrievePossibleParents } from "../../../../domain/fields/link-field-v2/RetrievePossibleParents";
import type { CurrentTrackerIdentifier } from "../../../../domain/CurrentTrackerIdentifier";
import { LinkableArtifactFilter } from "../../../../domain/fields/link-field-v2/LinkableArtifactFilter";
import type { VerifyIsAlreadyLinked } from "../../../../domain/fields/link-field-v2/VerifyIsAlreadyLinked";

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
    type_retriever: RetrieveSelectedLinkType,
    parents_retriever: RetrievePossibleParents,
    link_verifier: VerifyIsAlreadyLinked,
    current_artifact_identifier: CurrentArtifactIdentifier | null,
    current_tracker_identifier: CurrentTrackerIdentifier
): ArtifactLinkSelectorAutoCompleterType => {
    const isParentSelected = (): boolean => {
        const selected_type = type_retriever.getSelectedLinkType();
        return LinkType.isReverseChild(selected_type);
    };

    const getMatchingArtifactsGroup = (linkable_number: LinkableNumber): Promise<GroupOfItems> =>
        retrieve_matching_artifact.getMatchingArtifact(linkable_number).match(
            (artifact) => MatchingArtifactsGroup.fromMatchingArtifact(link_verifier, artifact),
            (fault) => {
                if (!isExpectedFault(fault)) {
                    fault_notifier.onFault(MatchingArtifactRetrievalFault(fault));
                }
                return MatchingArtifactsGroup.buildEmpty();
            }
        );

    const getPossibleParentsGroup = (query: string): Promise<GroupOfItems> => {
        const filter = LinkableArtifactFilter(query);
        return parents_retriever
            .getPossibleParents(current_tracker_identifier)
            .map((artifacts) => artifacts.filter(filter.matchesQuery))
            .match(
                (artifacts) => PossibleParentsGroup.fromPossibleParents(link_verifier, artifacts),
                (fault) => {
                    fault_notifier.onFault(fault);
                    return PossibleParentsGroup.buildEmpty();
                }
            );
    };

    return {
        autoComplete: async (link_selector: LinkSelector, query: string): Promise<void> => {
            notification_clearer.clearFaultNotification();

            const linkable_number = LinkableNumberProxy.fromQueryString(
                query,
                current_artifact_identifier
            );
            const is_parent_selected = isParentSelected();
            if (!linkable_number && !is_parent_selected) {
                link_selector.setDropdownContent([]);
                return;
            }
            const loading_groups = [];
            if (linkable_number) {
                loading_groups.push(MatchingArtifactsGroup.buildLoadingState());
            }
            if (is_parent_selected) {
                loading_groups.push(PossibleParentsGroup.buildLoadingState());
            }
            link_selector.setDropdownContent(loading_groups);

            const groups = [];
            if (linkable_number) {
                groups.push(await getMatchingArtifactsGroup(linkable_number));
            }
            if (is_parent_selected) {
                groups.push(await getPossibleParentsGroup(query));
            }
            link_selector.setDropdownContent(groups);
        },
    };
};

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

import type { GroupCollection, GroupOfItems } from "@tuleap/link-selector";
import type { Fault } from "@tuleap/fault";
import type { RetrieveMatchingArtifact } from "../../../../domain/fields/link-field/RetrieveMatchingArtifact";
import { LinkableNumberProxy } from "./LinkableNumberProxy";
import type { CurrentArtifactIdentifier } from "../../../../domain/CurrentArtifactIdentifier";
import { MatchingArtifactsGroup } from "./MatchingArtifactsGroup";
import type { ClearFaultNotification } from "../../../../domain/ClearFaultNotification";
import type { NotifyFault } from "../../../../domain/NotifyFault";
import { MatchingArtifactRetrievalFault } from "../../../../domain/fields/link-field/MatchingArtifactRetrievalFault";
import { LinkType } from "../../../../domain/fields/link-field/LinkType";
import { PossibleParentsGroup } from "./PossibleParentsGroup";
import type { LinkableNumber } from "../../../../domain/fields/link-field/LinkableNumber";
import type { RetrievePossibleParents } from "../../../../domain/fields/link-field/RetrievePossibleParents";
import type { CurrentTrackerIdentifier } from "../../../../domain/CurrentTrackerIdentifier";
import { LinkableArtifactFilter } from "../../../../domain/fields/link-field/LinkableArtifactFilter";
import type { VerifyIsAlreadyLinked } from "../../../../domain/fields/link-field/VerifyIsAlreadyLinked";
import { LinkFieldPossibleParentsGroupsByProjectBuilder } from "./LinkFieldPossibleParentsGroupsByProjectBuilder";
import type { LinkField } from "./LinkField";
import { RecentlyViewedArtifactGroup } from "./RecentlyViewedArtifactGroup";

export type ArtifactLinkSelectorAutoCompleterType = {
    autoComplete(host: LinkField, query: string): void;
    getRecentlyViewedItems(): GroupOfItems;
};

const isExpectedFault = (fault: Fault): boolean =>
    ("isForbidden" in fault && fault.isForbidden() === true) ||
    ("isNotFound" in fault && fault.isNotFound() === true);

const isParentSelected = (host: LinkField): boolean =>
    LinkType.isReverseChild(host.current_link_type);

export const ArtifactLinkSelectorAutoCompleter = (
    retrieve_matching_artifact: RetrieveMatchingArtifact,
    fault_notifier: NotifyFault,
    notification_clearer: ClearFaultNotification,
    parents_retriever: RetrievePossibleParents,
    link_verifier: VerifyIsAlreadyLinked,
    current_artifact_identifier: CurrentArtifactIdentifier | null,
    current_tracker_identifier: CurrentTrackerIdentifier,
    is_search_feature_flag_enabled: boolean
): ArtifactLinkSelectorAutoCompleterType => {
    let loaded_possible_parents_cache: GroupCollection = [PossibleParentsGroup.buildLoadingState()];

    const getRecentlyViewedItems = (): GroupOfItems => {
        return RecentlyViewedArtifactGroup.buildEmpty();
    };

    const getMatchingArtifactsGroup = (
        linkable_number: LinkableNumber
    ): PromiseLike<GroupOfItems> =>
        retrieve_matching_artifact.getMatchingArtifact(linkable_number).match(
            (artifact) => MatchingArtifactsGroup.fromMatchingArtifact(link_verifier, artifact),
            (fault) => {
                if (!isExpectedFault(fault)) {
                    fault_notifier.onFault(MatchingArtifactRetrievalFault(fault));
                }
                return MatchingArtifactsGroup.buildEmpty();
            }
        );

    const getPossibleParentsGroup = (query: string): PromiseLike<GroupCollection> => {
        const filter = LinkableArtifactFilter(query);
        return parents_retriever
            .getPossibleParents(current_tracker_identifier)
            .map((artifacts) => artifacts.filter(filter.matchesQuery))
            .match(
                (artifacts) =>
                    LinkFieldPossibleParentsGroupsByProjectBuilder.buildGroupsSortedByProject(
                        link_verifier,
                        artifacts
                    ),
                (fault) => {
                    fault_notifier.onFault(fault);
                    return [PossibleParentsGroup.buildEmpty()];
                }
            );
    };

    const getFilteredPossibleParentsGroups = async (query: string): Promise<GroupCollection> => {
        const matching_parents = await getPossibleParentsGroup(query);
        if (matching_parents.length === 0) {
            const empty_possible_parents_group = [PossibleParentsGroup.buildEmpty()];
            loaded_possible_parents_cache = empty_possible_parents_group;
            return empty_possible_parents_group;
        }

        loaded_possible_parents_cache = matching_parents;

        return matching_parents;
    };

    return {
        autoComplete: async (host: LinkField, query: string): Promise<void> => {
            notification_clearer.clearFaultNotification();

            const linkable_number = LinkableNumberProxy.fromQueryString(
                query,
                current_artifact_identifier
            );
            const is_parent_selected = isParentSelected(host);

            if (!linkable_number && !is_parent_selected) {
                host.dropdown_content = [];
                if (is_search_feature_flag_enabled) {
                    host.dropdown_content = [getRecentlyViewedItems()];
                }
                return;
            }
            let loading_groups = [];

            if (linkable_number) {
                loading_groups.push(MatchingArtifactsGroup.buildLoadingState());
                if (is_search_feature_flag_enabled && !is_parent_selected) {
                    loading_groups.push(RecentlyViewedArtifactGroup.buildLoadingState());
                }
            }
            if (is_parent_selected) {
                loading_groups = loading_groups.concat(loaded_possible_parents_cache);
            }
            host.dropdown_content = loading_groups;

            let groups = [];
            if (linkable_number) {
                groups.push(await getMatchingArtifactsGroup(linkable_number));
                if (!is_parent_selected && is_search_feature_flag_enabled) {
                    groups.push(getRecentlyViewedItems());
                }
            }

            if (is_parent_selected) {
                groups = groups.concat(await getFilteredPossibleParentsGroups(query));
            }
            host.dropdown_content = groups;
        },
        getRecentlyViewedItems,
    };
};

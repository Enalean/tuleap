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
import type { RetrieveUserHistory } from "../../../../domain/fields/link-field/RetrieveUserHistory";
import type { UserIdentifier } from "../../../../domain/UserIdentifier";
import { SearchResultsGroup } from "./SearchResultsGroup";
import { UserHistoryRetrievalFault } from "../../../../domain/fields/link-field/UserHistoryRetrievalFault";
import type { SearchArtifacts } from "../../../../domain/fields/link-field/SearchArtifacts";
import { SearchArtifactsFault } from "../../../../domain/fields/link-field/SearchArtifactsFault";

export type ArtifactLinkSelectorAutoCompleterType = {
    autoComplete(host: LinkField, query: string): void;
};

const isExpectedFault = (fault: Fault): boolean =>
    ("isForbidden" in fault && fault.isForbidden() === true) ||
    ("isNotFound" in fault && fault.isNotFound() === true);

const isSearchBackendUnavailable = (fault: Fault): boolean =>
    "isNotFound" in fault && fault.isNotFound() === true;

const isParentSelected = (host: LinkField): boolean =>
    LinkType.isReverseChild(host.current_link_type);

const SEARCH_QUERY_MINIMUM_LENGTH = 3;

export const ArtifactLinkSelectorAutoCompleter = (
    retrieve_matching_artifact: RetrieveMatchingArtifact,
    fault_notifier: NotifyFault,
    parents_retriever: RetrievePossibleParents,
    link_verifier: VerifyIsAlreadyLinked,
    user_history_retriever: RetrieveUserHistory,
    artifacts_searcher: SearchArtifacts,
    current_artifact_identifier: CurrentArtifactIdentifier | null,
    current_tracker_identifier: CurrentTrackerIdentifier,
    user: UserIdentifier,
    is_search_feature_flag_enabled: boolean
): ArtifactLinkSelectorAutoCompleterType => {
    const getRecentlyViewedItems = (query: string): PromiseLike<GroupOfItems> => {
        const filter = LinkableArtifactFilter(query);
        return user_history_retriever
            .getUserArtifactHistory(user)
            .map((artifacts) => artifacts.filter(filter.matchesQuery))
            .match(
                (artifacts) =>
                    RecentlyViewedArtifactGroup.fromUserHistory(link_verifier, artifacts),
                (fault) => {
                    fault_notifier.onFault(UserHistoryRetrievalFault(fault));
                    return RecentlyViewedArtifactGroup.buildEmpty();
                }
            );
    };

    const getSearchResults = (query: string): PromiseLike<GroupCollection> =>
        artifacts_searcher.searchArtifacts(query).match(
            (artifacts) => {
                return [SearchResultsGroup.fromSearchResults(link_verifier, artifacts)];
            },
            (fault) => {
                if (isSearchBackendUnavailable(fault)) {
                    return [];
                }
                fault_notifier.onFault(SearchArtifactsFault(fault));
                return [SearchResultsGroup.buildEmpty()];
            }
        );

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
            return [PossibleParentsGroup.buildEmpty()];
        }

        return matching_parents;
    };

    return {
        autoComplete: (host: LinkField, query: string): void => {
            host.matching_artifact_section = [];
            host.recently_viewed_section = [];
            host.search_results_section = [];
            host.possible_parents_section = [];

            const is_parent_selected = isParentSelected(host);

            const linkable_number = LinkableNumberProxy.fromQueryString(
                query,
                current_artifact_identifier
            );
            if (linkable_number) {
                host.matching_artifact_section = [MatchingArtifactsGroup.buildLoadingState()];
                getMatchingArtifactsGroup(linkable_number).then((group) => {
                    host.matching_artifact_section = [group];
                });
            }
            if (is_search_feature_flag_enabled && !is_parent_selected) {
                host.recently_viewed_section = [RecentlyViewedArtifactGroup.buildLoadingState()];
                getRecentlyViewedItems(query).then((group) => {
                    if (!isParentSelected(host)) {
                        host.recently_viewed_section = [group];
                    }
                });
                if (query.length >= SEARCH_QUERY_MINIMUM_LENGTH) {
                    host.search_results_section = [SearchResultsGroup.buildLoadingState()];
                    getSearchResults(query).then((groups) => {
                        if (!isParentSelected(host)) {
                            host.search_results_section = groups;
                        }
                    });
                }
            }
            if (is_parent_selected) {
                host.possible_parents_section = [PossibleParentsGroup.buildLoadingState()];
                getFilteredPossibleParentsGroups(query).then((groups) => {
                    if (isParentSelected(host)) {
                        host.possible_parents_section = groups;
                    }
                });
            }
        },
    };
};

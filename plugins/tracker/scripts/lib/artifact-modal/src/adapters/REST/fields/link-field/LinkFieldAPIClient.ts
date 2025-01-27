/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import type { ResultAsync } from "neverthrow";
import { getAllJSON, getJSON, postJSON, uri } from "@tuleap/fetch-result";
import type { Fault } from "@tuleap/fault";
import type { Option } from "@tuleap/option";
import type { CurrentArtifactIdentifier } from "@tuleap/plugin-tracker-artifact-common";
import type { RetrieveMatchingArtifact } from "../../../../domain/fields/link-field/RetrieveMatchingArtifact";
import type { LinkableNumber } from "../../../../domain/fields/link-field/LinkableNumber";
import type { LinkableArtifact } from "../../../../domain/fields/link-field/LinkableArtifact";
import type { ArtifactWithStatus } from "../../ArtifactWithStatus";
import { LinkableArtifactProxy } from "./LinkableArtifactProxy";
import type { RetrieveLinkTypes } from "../../../../domain/fields/link-field/RetrieveLinkTypes";
import type { LinkType } from "../../../../domain/fields/link-field/LinkType";
import type { RetrieveLinkedArtifactsByType } from "../../../../domain/fields/link-field/RetrieveLinkedArtifactsByType";
import type { LinkedArtifact } from "../../../../domain/fields/link-field/LinkedArtifact";
import { LinkedArtifactProxy } from "./LinkedArtifactProxy";
import type { RetrievePossibleParents } from "../../../../domain/fields/link-field/RetrievePossibleParents";
import { PossibleParentsRetrievalFault } from "../../../../domain/fields/link-field/PossibleParentsRetrievalFault";
import type { RetrieveUserHistory } from "../../../../domain/fields/link-field/RetrieveUserHistory";
import type { UserIdentifier } from "../../../../domain/UserIdentifier";
import type { SearchResultEntry, UserHistoryResponse } from "@tuleap/core-rest-api-types";
import { LinkableArtifactRESTFilter } from "./LinkableArtifactRESTFilter";
import type { SearchArtifacts } from "../../../../domain/fields/link-field/SearchArtifacts";

type AllLinkTypesResponse = {
    readonly natures: ReadonlyArray<LinkType>;
};

export type LinkedArtifactCollection = {
    readonly collection: ReadonlyArray<ArtifactWithStatus>;
};

export type LinkFieldAPIClient = RetrieveLinkTypes &
    RetrieveLinkedArtifactsByType &
    RetrieveMatchingArtifact &
    RetrievePossibleParents &
    RetrieveUserHistory &
    SearchArtifacts;

export const LinkFieldAPIClient = (
    current_artifact_option: Option<CurrentArtifactIdentifier>,
): LinkFieldAPIClient => ({
    getAllLinkTypes: (
        artifact_id: CurrentArtifactIdentifier,
    ): ResultAsync<readonly LinkType[], Fault> =>
        getJSON<AllLinkTypesResponse>(uri`/api/v1/artifacts/${artifact_id.id}/links`).map(
            ({ natures }) => natures,
        ),

    getLinkedArtifactsByLinkType(
        artifact_id: CurrentArtifactIdentifier,
        link_type: LinkType,
    ): ResultAsync<readonly LinkedArtifact[], Fault> {
        const id = artifact_id.id;
        return getAllJSON<LinkedArtifact, LinkedArtifactCollection>(
            uri`/api/v1/artifacts/${id}/linked_artifacts`,
            {
                params: {
                    limit: 50,
                    nature: link_type.shortname,
                    direction: link_type.direction,
                },
                getCollectionCallback: (payload): readonly LinkedArtifact[] =>
                    payload.collection.map((artifact) =>
                        LinkedArtifactProxy.fromAPIArtifactAndType(artifact, link_type),
                    ),
            },
        );
    },

    getMatchingArtifact: (linkable_number: LinkableNumber): ResultAsync<LinkableArtifact, Fault> =>
        getJSON<ArtifactWithStatus>(uri`/api/v1/artifacts/${linkable_number.id}`).map(
            LinkableArtifactProxy.fromAPIArtifact,
        ),

    getPossibleParents(tracker_id): ResultAsync<readonly LinkableArtifact[], Fault> {
        const id = tracker_id.id;
        return getAllJSON<ArtifactWithStatus>(uri`/api/v1/trackers/${id}/parent_artifacts`, {
            params: { limit: 1000 },
        })
            .map((artifacts) => artifacts.map(LinkableArtifactProxy.fromAPIArtifact))
            .mapErr(PossibleParentsRetrievalFault);
    },

    getUserArtifactHistory(
        user_identifier: UserIdentifier,
    ): ResultAsync<readonly LinkableArtifact[], Fault> {
        return getJSON<UserHistoryResponse>(uri`/api/v1/users/${user_identifier.id}/history`).map(
            (history) => {
                return history.entries
                    .filter((entry) =>
                        LinkableArtifactRESTFilter.filterArtifact(entry, current_artifact_option),
                    )
                    .map((entry) => LinkableArtifactProxy.fromAPIUserHistory(entry));
            },
        );
    },

    searchArtifacts(query): ResultAsync<readonly LinkableArtifact[], Fault> {
        return postJSON<readonly SearchResultEntry[]>(uri`/api/search?limit=50`, {
            keywords: query,
        }).map((results) => {
            return results
                .filter((entry) =>
                    LinkableArtifactRESTFilter.filterArtifact(entry, current_artifact_option),
                )
                .map((entry) => LinkableArtifactProxy.fromAPIUserHistory(entry));
        });
    },
});

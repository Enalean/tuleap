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

import { getJSON, getAllJSON } from "@tuleap/fetch-result";
import type { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import type { RetrieveParent } from "../../domain/parent/RetrieveParent";
import type { RetrieveMatchingArtifact } from "../../domain/fields/link-field/RetrieveMatchingArtifact";
import type { RetrieveLinkTypes } from "../../domain/fields/link-field/RetrieveLinkTypes";
import type { RetrieveLinkedArtifactsByType } from "../../domain/fields/link-field/RetrieveLinkedArtifactsByType";
import type { LinkedArtifact } from "../../domain/fields/link-field/LinkedArtifact";
import type { ArtifactWithStatus } from "./ArtifactWithStatus";
import { LinkedArtifactProxy } from "./LinkedArtifactProxy";
import type { CurrentArtifactIdentifier } from "../../domain/CurrentArtifactIdentifier";
import type { ParentArtifact } from "../../domain/parent/ParentArtifact";
import type { ParentArtifactIdentifier } from "../../domain/parent/ParentArtifactIdentifier";
import type { LinkableNumber } from "../../domain/fields/link-field/LinkableNumber";
import { ParentRetrievalFault } from "../../domain/parent/ParentRetrievalFault";
import type { LinkableArtifact } from "../../domain/fields/link-field/LinkableArtifact";
import { LinkableArtifactProxy } from "./LinkableArtifactProxy";
import type { LinkType } from "../../domain/fields/link-field/LinkType";
import type { RetrievePossibleParents } from "../../domain/fields/link-field/RetrievePossibleParents";
import { PossibleParentsRetrievalFault } from "../../domain/fields/link-field/PossibleParentsRetrievalFault";

export type LinkedArtifactCollection = {
    readonly collection: ReadonlyArray<ArtifactWithStatus>;
};

type TuleapAPIClientType = RetrieveParent &
    RetrieveMatchingArtifact &
    RetrieveLinkTypes &
    RetrieveLinkedArtifactsByType &
    RetrievePossibleParents;

type AllLinkTypesResponse = {
    readonly natures: ReadonlyArray<LinkType>;
};

export const TuleapAPIClient = (): TuleapAPIClientType => ({
    getParent: (artifact_id: ParentArtifactIdentifier): ResultAsync<ParentArtifact, Fault> =>
        getJSON<ParentArtifact>(`/api/v1/artifacts/${artifact_id.id}`).mapErr(ParentRetrievalFault),

    getMatchingArtifact: (linkable_number: LinkableNumber): ResultAsync<LinkableArtifact, Fault> =>
        getJSON<ArtifactWithStatus>(`/api/v1/artifacts/${linkable_number.id}`).map(
            LinkableArtifactProxy.fromAPIArtifact
        ),

    getAllLinkTypes: (
        artifact_id: CurrentArtifactIdentifier
    ): ResultAsync<readonly LinkType[], Fault> =>
        getJSON<AllLinkTypesResponse>(`/api/v1/artifacts/${artifact_id.id}/links`).map(
            ({ natures }) => natures
        ),

    getLinkedArtifactsByLinkType(
        artifact_id: CurrentArtifactIdentifier,
        link_type: LinkType
    ): ResultAsync<readonly LinkedArtifact[], Fault> {
        const id = artifact_id.id;
        return getAllJSON<LinkedArtifactCollection, LinkedArtifact>(
            `/api/v1/artifacts/${id}/linked_artifacts`,
            {
                params: {
                    limit: 50,
                    offset: 0,
                    nature: link_type.shortname,
                    direction: link_type.direction,
                },
                getCollectionCallback: (
                    payload: LinkedArtifactCollection
                ): readonly LinkedArtifact[] =>
                    payload.collection.map((artifact) =>
                        LinkedArtifactProxy.fromAPIArtifactAndType(artifact, link_type)
                    ),
            }
        );
    },

    getPossibleParents(tracker_id): ResultAsync<readonly LinkableArtifact[], Fault> {
        const id = tracker_id.id;
        return getAllJSON<readonly ArtifactWithStatus[], ArtifactWithStatus>(
            `/api/v1/trackers/${id}/parent_artifacts`,
            { params: { limit: 1000 } }
        )
            .map((artifacts) => artifacts.map(LinkableArtifactProxy.fromAPIArtifact))
            .mapErr(PossibleParentsRetrievalFault);
    },
});

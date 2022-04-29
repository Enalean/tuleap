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

import { getJSON } from "@tuleap/fetch-result";
import type { FetchWrapperError } from "@tuleap/tlp-fetch";
import { get, recursiveGet } from "@tuleap/tlp-fetch";
import { Fault } from "@tuleap/fault";
import { ResultAsync } from "neverthrow";
import type { RetrieveParent } from "../../domain/parent/RetrieveParent";
import type { RetrieveMatchingArtifact } from "../../domain/fields/link-field-v2/RetrieveMatchingArtifact";
import { getArtifact } from "../../rest/rest-service";
import type { RetrieveLinkTypes } from "../../domain/fields/link-field-v2/RetrieveLinkTypes";
import type { RetrieveLinkedArtifactsByType } from "../../domain/fields/link-field-v2/RetrieveLinkedArtifactsByType";
import type { LinkedArtifact } from "../../domain/fields/link-field-v2/LinkedArtifact";
import type { ArtifactWithStatus } from "./ArtifactWithStatus";
import { LinkedArtifactProxy } from "./LinkedArtifactProxy";
import type { CurrentArtifactIdentifier } from "../../domain/CurrentArtifactIdentifier";
import type { ParentArtifact } from "../../domain/parent/ParentArtifact";
import type { ParentArtifactIdentifier } from "../../domain/parent/ParentArtifactIdentifier";
import type { LinkableNumber } from "../../domain/fields/link-field-v2/LinkableNumber";
import { ParentRetrievalFault } from "../../domain/parent/ParentRetrievalFault";
import type { LinkableArtifact } from "../../domain/fields/link-field-v2/LinkableArtifact";
import { LinkableArtifactProxy } from "./LinkableArtifactProxy";
import type { LinkType } from "../../domain/fields/link-field-v2/LinkType";

export interface LinkedArtifactCollection {
    readonly collection: ArtifactWithStatus[];
}

type TuleapAPIClientType = RetrieveParent &
    RetrieveMatchingArtifact &
    RetrieveLinkTypes &
    RetrieveLinkedArtifactsByType;

export const TuleapAPIClient = (): TuleapAPIClientType => ({
    getParent: (artifact_id: ParentArtifactIdentifier): ResultAsync<ParentArtifact, Fault> =>
        ResultAsync.fromPromise(getArtifact(artifact_id.id), (error) => {
            if (error instanceof Error) {
                return Fault.fromError(error);
            }
            return Fault.fromMessage("Unknown error");
        }).mapErr(ParentRetrievalFault),

    getMatchingArtifact: (linkable_number: LinkableNumber): ResultAsync<LinkableArtifact, Fault> =>
        getJSON<ArtifactWithStatus>(`/api/v1/artifacts/${linkable_number.id}`).map(
            LinkableArtifactProxy.fromAPIArtifact
        ),

    getAllLinkTypes(artifact_id: CurrentArtifactIdentifier): Promise<LinkType[]> {
        const id = artifact_id.id;
        return get(`/api/v1/artifacts/${id}/links`).then(
            async (response) => {
                const { natures } = await response.json();

                return natures;
            },
            async (error: FetchWrapperError) => {
                const message = await getExtractedErrorMessage(error);

                throw new Error(message);
            }
        );
    },

    getLinkedArtifactsByLinkType(
        artifact_id: CurrentArtifactIdentifier,
        link_type: LinkType
    ): Promise<LinkedArtifact[]> {
        const id = artifact_id.id;
        return recursiveGet<LinkedArtifactCollection, LinkedArtifact>(
            `/api/v1/artifacts/${id}/linked_artifacts`,
            {
                params: {
                    limit: 50,
                    offset: 0,
                    nature: link_type.shortname,
                    direction: link_type.direction,
                },
                getCollectionCallback: (payload: LinkedArtifactCollection): LinkedArtifact[] =>
                    payload.collection.map((artifact) =>
                        LinkedArtifactProxy.fromAPIArtifactAndType(artifact, link_type)
                    ),
            }
        ).catch(async (error) => {
            const message = await getExtractedErrorMessage(error);
            throw new Error(message);
        });
    },
});

async function getExtractedErrorMessage(exception: FetchWrapperError): Promise<string> {
    const { error } = await exception.response.json();

    return `${error.code} ${error.message}`;
}

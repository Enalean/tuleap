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

import type { RetrieveArtifact } from "../../domain/RetrieveArtifact";
import { getArtifact } from "../../rest/rest-service";
import type { FetchWrapperError } from "tlp";
import { get, recursiveGet } from "tlp";
import type { RetrieveLinkTypes } from "../../domain/fields/link-field-v2/RetrieveLinkTypes";
import type { RetrieveLinkedArtifactsByType } from "../../domain/fields/link-field-v2/RetrieveLinkedArtifactsByType";
import type { LinkedArtifact, LinkType } from "../../domain/fields/link-field-v2/LinkedArtifact";

interface APILinkedArtifact {
    readonly xref: string;
    readonly title: string;
    readonly html_url: string;
    readonly tracker: Tracker;
    readonly status: string;
    readonly is_open: boolean;
}

interface Tracker {
    readonly color_name: string;
}

export interface LinkedArtifactCollection {
    readonly collection: APILinkedArtifact[];
}

type TuleapAPIClientType = RetrieveArtifact & RetrieveLinkTypes & RetrieveLinkedArtifactsByType;

export const TuleapAPIClient = (): TuleapAPIClientType => ({
    getArtifact,

    getAllLinkTypes(artifact_id: number): Promise<LinkType[]> {
        return get(`/api/v1/artifacts/${artifact_id}/links`).then(
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
        artifact_id: number,
        link_type: LinkType
    ): Promise<LinkedArtifact[]> {
        return recursiveGet<LinkedArtifactCollection, LinkedArtifact>(
            `/api/v1/artifacts/${artifact_id}/linked_artifacts`,
            {
                params: {
                    limit: 50,
                    offset: 0,
                    nature: link_type.shortname,
                    direction: link_type.direction,
                },
                getCollectionCallback: (payload: LinkedArtifactCollection): LinkedArtifact[] => {
                    return payload.collection.map((linked_artifact: APILinkedArtifact) => {
                        return {
                            ...linked_artifact,
                            link_type,
                        };
                    });
                },
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

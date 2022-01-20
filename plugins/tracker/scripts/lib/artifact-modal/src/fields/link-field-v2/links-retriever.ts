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

import { get, recursiveGet } from "tlp";

export interface LinkType {
    readonly shortname: string;
    readonly direction: string;
    readonly label: string;
}

export interface LinkedArtifact extends APILinkedArtifact {
    readonly link_type: LinkType;
}

interface APILinkedArtifact {
    readonly xref: string;
    readonly title: string;
    readonly html_url: string;
    readonly tracker: Tracker;
    readonly status: string;
}

interface Tracker {
    readonly color_name: string;
}

export interface LinkedArtifactCollection {
    readonly collection: APILinkedArtifact[];
}

export async function getLinkedArtifacts(current_artifact_id: number): Promise<LinkedArtifact[]> {
    const link_types = await getAllLinkTypes(current_artifact_id);
    const promises = link_types.map((type: LinkType) => {
        return getLinkedArtifactsByLinkType(current_artifact_id, type);
    });

    return Promise.all(promises).then((collections) => collections.flat());
}

async function getAllLinkTypes(current_artifact_id: number): Promise<LinkType[]> {
    const response = await get(`/api/v1/artifacts/${current_artifact_id}/links`);

    const { natures } = await response.json();

    return natures;
}

function getLinkedArtifactsByLinkType(
    current_artifact_id: number,
    link_type: LinkType
): Promise<LinkedArtifact[]> {
    return recursiveGet<LinkedArtifactCollection, LinkedArtifact>(
        `/api/v1/artifacts/${current_artifact_id}/linked_artifacts`,
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
    );
}

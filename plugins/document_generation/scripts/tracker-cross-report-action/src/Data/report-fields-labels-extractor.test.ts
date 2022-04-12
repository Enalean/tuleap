/**
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

import type { ArtifactResponse } from "../../../lib/docx";
import { extractFieldsLabels } from "./report-fields-labels-extractor";

describe("report-fields-labels-extractor", () => {
    it("Extracts the fields labels", (): void => {
        const artifact_representations_map: Map<number, ArtifactResponse> =
            buildArtifactRepresentationsMap();

        const fields_labels = extractFieldsLabels(artifact_representations_map);
        expect(fields_labels).toStrictEqual(["Artifact ID", "Field02", "Assigned to"]);
    });
    it("returns an empty array if provided data does not have any ArtifactResponse", (): void => {
        const artifact_representations_map: Map<number, ArtifactResponse> = new Map();

        const fields_labels = extractFieldsLabels(artifact_representations_map);
        expect(fields_labels).toStrictEqual([]);
    });
});

function buildArtifactRepresentationsMap(): Map<number, ArtifactResponse> {
    const artifact_representations_map: Map<number, ArtifactResponse> = new Map();
    artifact_representations_map.set(74, {
        id: 74,
        title: null,
        xref: "bug #74",
        tracker: { id: 14 },
        html_url: "/plugins/tracker/?aid=74",
        values: [
            {
                field_id: 1,
                type: "aid",
                label: "Artifact ID",
                value: 74,
            },
            {
                field_id: 2,
                type: "string",
                label: "Field02",
                value: "value02",
            },
            {
                field_id: 3,
                type: "sb",
                label: "Assigned to",
                values: [],
            },
            {
                field_id: 4,
                type: "art_link",
                label: "Artifact links",
                values: [],
            },
        ],
    } as ArtifactResponse);
    artifact_representations_map.set(4, {
        id: 4,
        title: null,
        xref: "bug #4",
        tracker: { id: 14 },
        html_url: "/plugins/tracker/?aid=4",
        values: [
            {
                field_id: 1,
                type: "aid",
                label: "Artifact ID",
                value: 4,
            },
            {
                field_id: 2,
                type: "string",
                label: "Field02",
                value: "value04",
            },
            {
                field_id: 3,
                type: "sb",
                label: "Assigned to",
                values: [],
            },
            {
                field_id: 4,
                type: "art_link",
                label: "Artifact links",
                values: [],
            },
        ],
    } as ArtifactResponse);

    return artifact_representations_map;
}

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

        const fields_labels = extractFieldsLabels(artifact_representations_map, [74, 4]);
        expect(fields_labels).toStrictEqual(["Artifact ID", "Field02", "Assigned to"]);
    });
    it("throws an Error if provided data does not have any ArtifactResponse", (): void => {
        const artifact_representations_map: Map<number, ArtifactResponse> = new Map();

        expect(() => extractFieldsLabels(artifact_representations_map, [74])).toThrowError(
            "This must not happen. Check must be done before."
        );
    });
    it("throws an Error if provided data does not have any artifact ids", (): void => {
        const artifact_representations_map: Map<number, ArtifactResponse> =
            buildArtifactRepresentationsMap();

        expect(() => extractFieldsLabels(artifact_representations_map, [])).toThrowError(
            "This must not happen. Check must be done before."
        );
    });
    it("throws an Error if provided data does not have the matching ArtifactResponse in provided artifacts id", (): void => {
        const artifact_representations_map: Map<number, ArtifactResponse> =
            buildArtifactRepresentationsMap();

        expect(() => extractFieldsLabels(artifact_representations_map, [8])).toThrowError(
            "This must not happen. Collection must be consistent."
        );
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

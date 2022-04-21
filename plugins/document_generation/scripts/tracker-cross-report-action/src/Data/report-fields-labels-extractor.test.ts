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

import { extractFieldsLabels } from "./report-fields-labels-extractor";
import type { ArtifactForCrossReportDocGen } from "../type";

describe("report-fields-labels-extractor", () => {
    it("Extracts the fields labels", (): void => {
        const artifact_representations_map: Map<number, ArtifactForCrossReportDocGen> =
            buildArtifactRepresentationsMap();

        const fields_labels = extractFieldsLabels(artifact_representations_map);
        expect(fields_labels).toStrictEqual(["Artifact ID", "Field02", "Assigned to"]);
    });
    it("returns an empty array if provided data does not have any ArtifactResponse", (): void => {
        const artifact_representations_map: Map<number, ArtifactForCrossReportDocGen> = new Map();

        const fields_labels = extractFieldsLabels(artifact_representations_map);
        expect(fields_labels).toStrictEqual([]);
    });
});

function buildArtifactRepresentationsMap(): Map<number, ArtifactForCrossReportDocGen> {
    const artifact_representations_map: Map<number, ArtifactForCrossReportDocGen> = new Map();
    artifact_representations_map.set(74, {
        id: 74,
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
    } as ArtifactForCrossReportDocGen);
    artifact_representations_map.set(4, {
        id: 4,
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
    } as ArtifactForCrossReportDocGen);

    return artifact_representations_map;
}

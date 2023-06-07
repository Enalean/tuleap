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

import { describe, it, expect } from "vitest";
import type { OrganizedReportsData, ArtifactForCrossReportDocGen } from "../type";
import { TextCell } from "@tuleap/plugin-docgen-xlsx";
import { formatReportsFieldsLabels } from "./reports-fields-labels-formator";

describe("reports-fields-labels-formator", () => {
    it("formats field labels from all selected reports", (): void => {
        const first_level_artifact_representations_map: Map<number, ArtifactForCrossReportDocGen> =
            new Map();
        first_level_artifact_representations_map.set(74, {
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
        first_level_artifact_representations_map.set(4, {
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

        const second_level_artifact_representations_map: Map<number, ArtifactForCrossReportDocGen> =
            new Map();
        second_level_artifact_representations_map.set(75, {
            id: 75,
            values: [
                {
                    field_id: 10,
                    type: "aid",
                    label: "Artifact ID",
                    value: 75,
                },
            ],
        } as ArtifactForCrossReportDocGen);

        const third_level_artifact_representations_map: Map<number, ArtifactForCrossReportDocGen> =
            new Map();
        third_level_artifact_representations_map.set(4, {
            id: 80,
            values: [
                {
                    field_id: 50,
                    type: "aid",
                    label: "Artifact Id",
                    value: 80,
                },
                {
                    field_id: 51,
                    type: "string",
                    label: "TestName",
                    value: "Test 01",
                },
            ],
        } as ArtifactForCrossReportDocGen);

        const organized_reports_data: OrganizedReportsData = {
            first_level: {
                tracker_name: "Tracker01",
                artifact_representations: first_level_artifact_representations_map,
                linked_artifacts: new Map(),
            },
            second_level: {
                tracker_name: "Tracker02",
                artifact_representations: second_level_artifact_representations_map,
                linked_artifacts: new Map(),
            },
            third_level: {
                tracker_name: "Tracker03",
                artifact_representations: third_level_artifact_representations_map,
            },
        };

        const formatted_headers = formatReportsFieldsLabels(organized_reports_data);
        expect(formatted_headers).toStrictEqual([
            new TextCell("Artifact ID"),
            new TextCell("Field02"),
            new TextCell("Assigned to"),
            new TextCell("Artifact ID"),
            new TextCell("Artifact Id"),
            new TextCell("TestName"),
        ]);
    });
    it("throws an Error if organized data does not have any ArtifactResponse in first level", (): void => {
        const artifact_representations_map: Map<number, ArtifactForCrossReportDocGen> = new Map();

        const organized_reports_data: OrganizedReportsData = {
            first_level: {
                tracker_name: "Tracker01",
                artifact_representations: artifact_representations_map,
                linked_artifacts: new Map(),
            },
        };

        expect(() => formatReportsFieldsLabels(organized_reports_data)).toThrowError(
            "This must not happen. Check must be done before."
        );
    });
});

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
import type { OrganizedReportsData } from "../type";
import { TextCell } from "@tuleap/plugin-docgen-xlsx";
import { formatReportsFieldsLabels } from "./reports-fields-labels-formator";

describe("reports-fields-labels-formator", () => {
    it("formats field labels from all selected reports", (): void => {
        const first_level_artifact_representations_map: Map<number, ArtifactResponse> = new Map();
        first_level_artifact_representations_map.set(74, {
            id: 74,
            title: null,
            xref: "story #74",
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
        first_level_artifact_representations_map.set(4, {
            id: 4,
            title: null,
            xref: "story #4",
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

        const second_level_artifact_representations_map: Map<number, ArtifactResponse> = new Map();
        second_level_artifact_representations_map.set(75, {
            id: 75,
            title: null,
            xref: "Task #75",
            tracker: { id: 15 },
            html_url: "/plugins/tracker/?aid=75",
            values: [
                {
                    field_id: 10,
                    type: "aid",
                    label: "Artifact ID",
                    value: 75,
                },
            ],
        } as ArtifactResponse);

        const third_level_artifact_representations_map: Map<number, ArtifactResponse> = new Map();
        third_level_artifact_representations_map.set(4, {
            id: 80,
            title: null,
            xref: "test #80",
            tracker: { id: 30 },
            html_url: "/plugins/tracker/?aid=80",
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
        } as ArtifactResponse);

        const organized_reports_data: OrganizedReportsData = {
            first_level: {
                tracker_name: "Tracker01",
                artifact_representations: first_level_artifact_representations_map,
            },
            second_level: {
                tracker_name: "Tracker02",
                artifact_representations: second_level_artifact_representations_map,
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
        const artifact_representations_map: Map<number, ArtifactResponse> = new Map();

        const organized_reports_data: OrganizedReportsData = {
            first_level: {
                tracker_name: "Tracker01",
                artifact_representations: artifact_representations_map,
            },
        };

        expect(() => formatReportsFieldsLabels(organized_reports_data)).toThrowError(
            "This must not happen. Check must be done before."
        );
    });
});

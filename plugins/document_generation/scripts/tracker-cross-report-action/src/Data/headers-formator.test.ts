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
import { formatHeaders } from "./headers-formator";
import { TextCell } from "@tuleap/plugin-docgen-xlsx";
import { TextCellWithMerges } from "../type";

describe("headers-formator", () => {
    it("builds the headers TextCell", (): void => {
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
        };

        const formatted_headers = formatHeaders(organized_reports_data);
        expect(formatted_headers).toStrictEqual({
            tracker_names: [
                new TextCellWithMerges("Tracker01", 3),
                new TextCellWithMerges("Tracker02", 1),
            ],
            reports_fields_labels: [
                new TextCell("Artifact ID"),
                new TextCell("Field02"),
                new TextCell("Assigned to"),
                new TextCell("Artifact ID"),
            ],
        });
    });
    it("throws an Error if organized data does not have any ArtifactResponse in first level", (): void => {
        const artifact_representations_map: Map<number, ArtifactResponse> = new Map();

        const organized_reports_data: OrganizedReportsData = {
            first_level: {
                tracker_name: "Tracker01",
                artifact_representations: artifact_representations_map,
                linked_artifacts: new Map(),
            },
        };

        expect(() => formatHeaders(organized_reports_data)).toThrowError(
            "This must not happen. Check must be done before."
        );
    });
});

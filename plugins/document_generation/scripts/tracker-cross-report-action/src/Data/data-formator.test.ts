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

import { formatData } from "./data-formator";
import { TextCell, NumberCell, EmptyCell } from "@tuleap/plugin-docgen-xlsx";
import * as organized_data from "./organize-reports-data";
import type { ArtifactResponse } from "@tuleap/plugin-docgen-docx";
import type { OrganizedReportsData } from "../type";
import { TextCellWithMerges } from "../type";
import type { ExportSettings } from "../export-document";

describe("data-formator", () => {
    it("generates the formatted data that will be used to create the XLSX document", async (): Promise<void> => {
        jest.spyOn(organized_data, "organizeReportsData").mockResolvedValue({
            first_level: {
                tracker_name: "tracker01",
                artifact_representations: buildFirstLevelRepresentationsMap(),
                linked_artifacts: new Map([
                    [74, [75]],
                    [4, []],
                ]),
            },
            second_level: {
                tracker_name: "tracker02",
                artifact_representations: buildSecondLevelRepresentationsMap(),
                linked_artifacts: new Map(),
            },
        });

        const formatted_data = await formatData({
            first_level: {
                tracker_name: "tracker01",
                report_id: 1,
                report_name: "report01",
                artifact_link_types: ["_is_child"],
            },
            second_level: {
                tracker_name: "tracker02",
                report_id: 2,
                report_name: "report02",
                artifact_link_types: [],
            },
        });

        expect(formatted_data).toStrictEqual({
            headers: {
                tracker_names: [
                    new TextCellWithMerges("tracker01", 3),
                    new TextCellWithMerges("tracker02", 1),
                ],
                reports_fields_labels: [
                    new TextCell("Artifact ID"),
                    new TextCell("Field02"),
                    new TextCell("Assigned to"),
                    new TextCell("Artifact ID"),
                ],
            },
            artifacts_rows: [
                [new NumberCell(74), new TextCell("value02"), new EmptyCell(), new NumberCell(75)],
                [new NumberCell(4), new TextCell("value04"), new EmptyCell()],
            ],
        });
    });
    it("generates empty formatted data if no artifact found", async (): Promise<void> => {
        jest.spyOn(organized_data, "organizeReportsData").mockResolvedValue({
            first_level: {
                artifact_representations: new Map(),
            },
        } as OrganizedReportsData);

        const formatted_data = await formatData({
            first_level: {
                tracker_name: "tracker01",
                report_id: 1,
                report_name: "report01",
                artifact_link_types: [],
            },
        });

        expect(formatted_data).toStrictEqual({});
    });
    it("throws an error if first level artifact not found", async (): Promise<void> => {
        jest.spyOn(organized_data, "organizeReportsData").mockResolvedValue({
            first_level: {
                tracker_name: "tracker01",
                artifact_representations: buildFirstLevelRepresentationsMap(),
                linked_artifacts: new Map([
                    [74, [75]],
                    [76, []],
                    [4, []],
                ]),
            },
            second_level: {
                tracker_name: "tracker02",
                artifact_representations: buildSecondLevelRepresentationsMap(),
                linked_artifacts: new Map(),
            },
        });

        const export_settings: ExportSettings = {
            first_level: {
                tracker_name: "tracker01",
                report_id: 1,
                report_name: "report01",
                artifact_link_types: ["_is_child"],
            },
            second_level: {
                tracker_name: "tracker02",
                report_id: 2,
                report_name: "report02",
                artifact_link_types: [],
            },
        };

        await expect(() => formatData(export_settings)).rejects.toThrowError(
            "Artifact 76 representation not found in collection."
        );
    });
    it("throws an error if second level artifact not found", async (): Promise<void> => {
        jest.spyOn(organized_data, "organizeReportsData").mockResolvedValue({
            first_level: {
                tracker_name: "tracker01",
                artifact_representations: buildFirstLevelRepresentationsMap(),
                linked_artifacts: new Map([
                    [74, [75]],
                    [4, [76]],
                ]),
            },
            second_level: {
                tracker_name: "tracker02",
                artifact_representations: buildSecondLevelRepresentationsMap(),
                linked_artifacts: new Map(),
            },
        });

        const export_settings: ExportSettings = {
            first_level: {
                tracker_name: "tracker01",
                report_id: 1,
                report_name: "report01",
                artifact_link_types: ["_is_child"],
            },
            second_level: {
                tracker_name: "tracker02",
                report_id: 2,
                report_name: "report02",
                artifact_link_types: [],
            },
        };

        await expect(() => formatData(export_settings)).rejects.toThrowError(
            "Artifact 76 representation not found in collection."
        );
    });
});

function buildFirstLevelRepresentationsMap(): Map<number, ArtifactResponse> {
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
            {
                field_id: 5,
                type: "file",
                label: "Attachment",
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
            {
                field_id: 5,
                type: "file",
                label: "Attachment",
                values: [],
            },
        ],
    } as ArtifactResponse);

    return first_level_artifact_representations_map;
}

function buildSecondLevelRepresentationsMap(): Map<number, ArtifactResponse> {
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

    return second_level_artifact_representations_map;
}

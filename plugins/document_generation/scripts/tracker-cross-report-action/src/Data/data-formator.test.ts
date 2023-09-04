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

import { describe, it, expect, vi } from "vitest";
import { formatData } from "./data-formator";
import { TextCell, NumberCell, EmptyCell } from "@tuleap/plugin-docgen-xlsx";
import * as organized_data from "./organize-reports-data";
import type { OrganizedReportsData, ArtifactForCrossReportDocGen } from "../type";
import { TextCellWithMerges } from "../type";
import type { ExportSettings } from "../export-document";

describe("data-formator", () => {
    it("generates the formatted data with all 3 levels that will be used to create the XLSX document with rows without all links", async (): Promise<void> => {
        const second_level_representation_map = buildSecondLevelRepresentationsMap();
        second_level_representation_map.set(76, {
            id: 76,
            values: [
                {
                    field_id: 10,
                    type: "aid",
                    label: "Artifact ID",
                    value: 76,
                },
            ],
        } as ArtifactForCrossReportDocGen);

        vi.spyOn(organized_data, "organizeReportsData").mockResolvedValue({
            first_level: {
                tracker_name: "tracker01",
                artifact_representations: buildFirstLevelRepresentationsMap(),
                linked_artifacts: new Map([[74, [75, 76]]]),
            },
            second_level: {
                tracker_name: "tracker02",
                artifact_representations: second_level_representation_map,
                linked_artifacts: new Map([[75, [80, 81]]]),
            },
            third_level: {
                tracker_name: "tracker03",
                artifact_representations: buildThirdLevelRepresentationsMap(),
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
            third_level: {
                tracker_name: "tracker03",
                report_id: 3,
                report_name: "report03",
            },
        });

        expect(formatted_data.artifacts_rows).toBeDefined();
        expect(formatted_data.artifacts_rows?.length).toBe(4);
        expect(formatted_data).toStrictEqual({
            headers: {
                tracker_names: [
                    new TextCellWithMerges("tracker01", 3),
                    new TextCellWithMerges("tracker02", 1),
                    new TextCellWithMerges("tracker03", 2),
                ],
                reports_fields_labels: [
                    new TextCell("Artifact ID"),
                    new TextCell("Field02"),
                    new TextCell("Assigned to"),
                    new TextCell("Artifact ID"),
                    new TextCell("Artifact Id"),
                    new TextCell("Title"),
                ],
            },
            artifacts_rows: [
                [
                    new NumberCell(74),
                    new TextCell("value02"),
                    new EmptyCell(),
                    new NumberCell(75),
                    new NumberCell(80),
                    new TextCell("Subtask title 01"),
                ],
                [
                    new NumberCell(74),
                    new TextCell("value02"),
                    new EmptyCell(),
                    new NumberCell(75),
                    new NumberCell(81),
                    new TextCell("Subtask title 02"),
                ],
                [new NumberCell(74), new TextCell("value02"), new EmptyCell(), new NumberCell(76)],
                [new NumberCell(4), new TextCell("value04"), new EmptyCell()],
            ],
        });
    });
    it("generates the formatted data with all 3 levels that will be used to create the XLSX document", async (): Promise<void> => {
        vi.spyOn(organized_data, "organizeReportsData").mockResolvedValue({
            first_level: {
                tracker_name: "tracker01",
                artifact_representations: buildFirstLevelRepresentationsMap(),
                linked_artifacts: new Map([[74, [75]]]),
            },
            second_level: {
                tracker_name: "tracker02",
                artifact_representations: buildSecondLevelRepresentationsMap(),
                linked_artifacts: new Map([[75, [80, 81]]]),
            },
            third_level: {
                tracker_name: "tracker03",
                artifact_representations: buildThirdLevelRepresentationsMap(),
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
            third_level: {
                tracker_name: "tracker03",
                report_id: 3,
                report_name: "report03",
            },
        });

        expect(formatted_data).toStrictEqual({
            headers: {
                tracker_names: [
                    new TextCellWithMerges("tracker01", 3),
                    new TextCellWithMerges("tracker02", 1),
                    new TextCellWithMerges("tracker03", 2),
                ],
                reports_fields_labels: [
                    new TextCell("Artifact ID"),
                    new TextCell("Field02"),
                    new TextCell("Assigned to"),
                    new TextCell("Artifact ID"),
                    new TextCell("Artifact Id"),
                    new TextCell("Title"),
                ],
            },
            artifacts_rows: [
                [
                    new NumberCell(74),
                    new TextCell("value02"),
                    new EmptyCell(),
                    new NumberCell(75),
                    new NumberCell(80),
                    new TextCell("Subtask title 01"),
                ],
                [
                    new NumberCell(74),
                    new TextCell("value02"),
                    new EmptyCell(),
                    new NumberCell(75),
                    new NumberCell(81),
                    new TextCell("Subtask title 02"),
                ],
                [new NumberCell(4), new TextCell("value04"), new EmptyCell()],
            ],
        });
    });
    it("generates the formatted data with the first 2 levels that will be used to create the XLSX document", async (): Promise<void> => {
        vi.spyOn(organized_data, "organizeReportsData").mockResolvedValue({
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

    it("generates the formatted data with the first level that will be used to create the XLSX document", async (): Promise<void> => {
        vi.spyOn(organized_data, "organizeReportsData").mockResolvedValue({
            first_level: {
                tracker_name: "tracker01",
                artifact_representations: buildFirstLevelRepresentationsMap(),
                linked_artifacts: new Map([
                    [74, [75]],
                    [4, []],
                ]),
            },
        });

        const formatted_data = await formatData({
            first_level: {
                tracker_name: "tracker01",
                report_id: 1,
                report_name: "report01",
                artifact_link_types: ["_is_child"],
            },
        });

        expect(formatted_data).toStrictEqual({
            headers: {
                tracker_names: [new TextCellWithMerges("tracker01", 3)],
                reports_fields_labels: [
                    new TextCell("Artifact ID"),
                    new TextCell("Field02"),
                    new TextCell("Assigned to"),
                ],
            },
            artifacts_rows: [
                [new NumberCell(74), new TextCell("value02"), new EmptyCell()],
                [new NumberCell(4), new TextCell("value04"), new EmptyCell()],
            ],
        });
    });
    it("generates empty formatted data if no artifact found", async (): Promise<void> => {
        vi.spyOn(organized_data, "organizeReportsData").mockResolvedValue({
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
    it("throws an error if second level artifact not found", async (): Promise<void> => {
        vi.spyOn(organized_data, "organizeReportsData").mockResolvedValue({
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
            "Artifact 76 representation not found in collection.",
        );
    });
    it("throws an error if third level artifact not found", async (): Promise<void> => {
        vi.spyOn(organized_data, "organizeReportsData").mockResolvedValue({
            first_level: {
                tracker_name: "tracker01",
                artifact_representations: buildFirstLevelRepresentationsMap(),
                linked_artifacts: new Map([[74, [75]]]),
            },
            second_level: {
                tracker_name: "tracker02",
                artifact_representations: buildSecondLevelRepresentationsMap(),
                linked_artifacts: new Map([[75, [80, 82]]]),
            },
            third_level: {
                tracker_name: "tracker03",
                artifact_representations: buildThirdLevelRepresentationsMap(),
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
            third_level: {
                tracker_name: "tracker03",
                report_id: 3,
                report_name: "report03",
            },
        };

        await expect(() => formatData(export_settings)).rejects.toThrowError(
            "Artifact 82 representation not found in collection.",
        );
    });
});

function buildFirstLevelRepresentationsMap(): Map<number, ArtifactForCrossReportDocGen> {
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
            {
                field_id: 5,
                type: "file",
                label: "Attachment",
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
            {
                field_id: 5,
                type: "file",
                label: "Attachment",
                values: [],
            },
        ],
    } as ArtifactForCrossReportDocGen);

    return first_level_artifact_representations_map;
}

function buildSecondLevelRepresentationsMap(): Map<number, ArtifactForCrossReportDocGen> {
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

    return second_level_artifact_representations_map;
}

function buildThirdLevelRepresentationsMap(): Map<number, ArtifactForCrossReportDocGen> {
    const third_level_artifact_representations_map: Map<number, ArtifactForCrossReportDocGen> =
        new Map();
    third_level_artifact_representations_map.set(80, {
        id: 80,
        values: [
            {
                field_id: 20,
                type: "aid",
                label: "Artifact Id",
                value: 80,
            },
            {
                field_id: 21,
                type: "string",
                label: "Title",
                value: "Subtask title 01",
            },
        ],
    } as ArtifactForCrossReportDocGen);
    third_level_artifact_representations_map.set(81, {
        id: 81,
        values: [
            {
                field_id: 20,
                type: "aid",
                label: "Artifact Id",
                value: 81,
            },
            {
                field_id: 21,
                type: "string",
                label: "Title",
                value: "Subtask title 02",
            },
        ],
    } as ArtifactForCrossReportDocGen);

    return third_level_artifact_representations_map;
}

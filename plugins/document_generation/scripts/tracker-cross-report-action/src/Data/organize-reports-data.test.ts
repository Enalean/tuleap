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

import { organizeReportsData } from "./organize-reports-data";
import * as rest_querier from "../rest-querier";
import type { ArtifactResponse } from "@tuleap/plugin-docgen-docx";
import type { OrganizedReportsData } from "../type";
import type { LinkedArtifactsResponse } from "../type";

describe("organize-reports-data", () => {
    it("organizes the reports data that will be used to create the XLSX document", async (): Promise<void> => {
        const artifacts_first_report_response: ArtifactResponse[] = [
            {
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
            } as ArtifactResponse,
            {
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
            } as ArtifactResponse,
        ];

        const linked_artifacts_collection: LinkedArtifactsResponse[] = [
            {
                collection: [
                    {
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
                            {
                                field_id: 11,
                                type: "string",
                                label: "Title",
                                value: "Task 01",
                            },
                        ],
                    } as ArtifactResponse,
                    {
                        id: 750,
                        title: null,
                        xref: "whatever #75",
                        tracker: { id: 150 },
                        html_url: "/plugins/tracker/?aid=750",
                        values: [
                            {
                                field_id: 100,
                                type: "aid",
                                label: "Artifact ID",
                                value: 750,
                            },
                            {
                                field_id: 110,
                                type: "string",
                                label: "Title",
                                value: "Whatever 01",
                            },
                        ],
                    } as ArtifactResponse,
                ],
            },
        ];

        const artifacts_second_report_response: ArtifactResponse[] = [
            {
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
                    {
                        field_id: 11,
                        type: "string",
                        label: "Title",
                        value: "Task 01",
                    },
                    {
                        field_id: 12,
                        type: "sb",
                        label: "Assigned to",
                        values: [],
                    },
                    {
                        field_id: 13,
                        type: "art_link",
                        label: "Artifact links",
                        values: [],
                    },
                ],
            } as ArtifactResponse,
        ];

        jest.spyOn(rest_querier, "getReportArtifacts").mockResolvedValueOnce(
            artifacts_first_report_response
        );
        jest.spyOn(rest_querier, "getLinkedArtifacts").mockResolvedValue(
            linked_artifacts_collection
        );
        jest.spyOn(rest_querier, "getReportArtifacts").mockResolvedValueOnce(
            artifacts_second_report_response
        );

        const organized_reports_data: OrganizedReportsData = await organizeReportsData({
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

        const expected_artifact_representations_map: Map<number, ArtifactResponse> = new Map();
        expected_artifact_representations_map.set(74, {
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
        expected_artifact_representations_map.set(4, {
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
        expected_artifact_representations_map.set(75, {
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
                {
                    field_id: 11,
                    type: "string",
                    label: "Title",
                    value: "Task 01",
                },
                {
                    field_id: 12,
                    type: "sb",
                    label: "Assigned to",
                    values: [],
                },
                {
                    field_id: 13,
                    type: "art_link",
                    label: "Artifact links",
                    values: [],
                },
            ],
        } as ArtifactResponse);

        expect(organized_reports_data.artifact_representations.size).toBe(3);
        expect(organized_reports_data).toStrictEqual({
            artifact_representations: expected_artifact_representations_map,
            first_level: {
                artifact_ids: [74, 4],
                tracker_name: "tracker01",
                report_fields_labels: ["Artifact ID", "Field02", "Assigned to"],
            },
            second_level: {
                artifact_ids: [75],
                tracker_name: "tracker02",
                report_fields_labels: ["Artifact ID", "Title", "Assigned to"],
            },
        });
    });
    it("generates empty organized data if no artifact found", async (): Promise<void> => {
        const artifacts_report_response: ArtifactResponse[] = [];
        jest.spyOn(rest_querier, "getReportArtifacts").mockResolvedValue(artifacts_report_response);

        const organized_reports_data: OrganizedReportsData = await organizeReportsData({
            first_level: {
                tracker_name: "tracker01",
                report_id: 1,
                report_name: "report01",
                artifact_link_types: [],
            },
        });

        expect(organized_reports_data).toStrictEqual({
            artifact_representations: new Map(),
            first_level: {
                artifact_ids: [],
                tracker_name: "tracker01",
                report_fields_labels: [],
            },
        });
    });
});

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

describe("organize-reports-data", () => {
    it("organizes the reports data that will be used to create the XLSX document", async (): Promise<void> => {
        const artifacts_report_response: ArtifactResponse[] = [
            {
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
                    {
                        field_id: 5,
                        type: "file",
                        label: "Attachment",
                        values: [],
                    },
                ],
            } as ArtifactResponse,
        ];

        jest.spyOn(rest_querier, "getReportArtifacts").mockResolvedValue(artifacts_report_response);

        const organized_reports_data = await organizeReportsData({
            first_level: {
                tracker_name: "tracker01",
                report_id: 1,
                report_name: "report01",
                artifact_link_types: ["_is_child"],
            },
        });

        const expected_artifact_representations_map: Map<number, ArtifactResponse> = new Map();
        expected_artifact_representations_map.set(74, {
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
                {
                    field_id: 5,
                    type: "file",
                    label: "Attachment",
                    values: [],
                },
            ],
        } as ArtifactResponse);

        expect(organized_reports_data).toStrictEqual({
            artifact_representations: expected_artifact_representations_map,
            first_level_artifacts_ids: [74, 4],
        });
    });
    it("generates empty organized data if no artifact found", async (): Promise<void> => {
        const artifacts_report_response: ArtifactResponse[] = [];
        jest.spyOn(rest_querier, "getReportArtifacts").mockResolvedValue(artifacts_report_response);

        const organized_reports_data = await organizeReportsData({
            first_level: {
                tracker_name: "tracker01",
                report_id: 1,
                report_name: "report01",
                artifact_link_types: [],
            },
        });

        expect(organized_reports_data).toStrictEqual({
            artifact_representations: new Map(),
            first_level_artifacts_ids: [],
        });
    });
});

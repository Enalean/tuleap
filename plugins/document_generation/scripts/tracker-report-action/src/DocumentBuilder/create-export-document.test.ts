/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import * as artifact_retriever from "./artifacts-retriever";
import { createExportDocument } from "./create-export-document";
import type {
    ArtifactFromReport,
    ArtifactReportResponseUnknownFieldValue,
} from "./artifacts-retriever";

describe("Create ArtifactValues Collection", () => {
    it("Transforms json content into a collection", async () => {
        const report_artifacts: ArtifactFromReport[] = [
            {
                id: 1001,
                title: "title01",
                values: [
                    {
                        field_id: 1,
                        type: "aid",
                        label: "Artifact Number",
                        value: 1001,
                    },
                    {
                        field_id: 2,
                        type: "whatever",
                        label: "What Ever",
                        value: 9999,
                    } as ArtifactReportResponseUnknownFieldValue,
                    {
                        field_id: 3,
                        type: "string",
                        label: "Title",
                        value: "title01",
                    },
                    {
                        field_id: 4,
                        type: "int",
                        label: "Capacity",
                        value: 5,
                    },
                    {
                        field_id: 5,
                        type: "float",
                        label: "Effort",
                        value: 1.5,
                    },
                    {
                        field_id: 6,
                        type: "atid",
                        label: "Per tracker ID",
                        value: 1,
                    },
                    {
                        field_id: 7,
                        type: "priority",
                        label: "Rank",
                        value: 50,
                    },
                    {
                        field_id: 8,
                        type: "computed",
                        label: "Computed",
                        value: null,
                        manual_value: 10,
                        is_autocomputed: false,
                    },
                    {
                        field_id: 9,
                        type: "subon",
                        label: "Submitted On",
                        value: "2020-12-28T09:55:55+00:00",
                        is_time_displayed: true,
                    },
                    {
                        field_id: 10,
                        type: "lud",
                        label: "Last Update Date",
                        value: "2021-07-30T15:56:09+00:00",
                        is_time_displayed: false,
                    },
                    {
                        field_id: 11,
                        type: "date",
                        label: "Closed Date",
                        value: null,
                        is_time_displayed: false,
                    },
                    {
                        field_id: 13,
                        type: "text",
                        label: "Description",
                        value: "Some long description in art #1001",
                        format: "text",
                    },
                ],
                containers: [
                    {
                        name: "Details",
                        values: [],
                        containers: [
                            {
                                name: "Sub details",
                                values: [
                                    {
                                        field_id: 12,
                                        type: "string",
                                        label: "A detail",
                                        value: "Value in art #1001",
                                    },
                                ],
                                containers: [],
                            },
                        ],
                    },
                ],
            },
            {
                id: 1002,
                title: "title02",
                values: [
                    {
                        field_id: 1,
                        type: "aid",
                        label: "Artifact Number",
                        value: 1002,
                    },
                    {
                        field_id: 3,
                        type: "string",
                        label: "Title",
                        value: "title02",
                    },
                    {
                        field_id: 4,
                        type: "int",
                        label: "Capacity",
                        value: 2,
                    },
                    {
                        field_id: 5,
                        type: "float",
                        label: "Effort",
                        value: 2.5,
                    },
                    {
                        field_id: 6,
                        type: "atid",
                        label: "Per tracker ID",
                        value: 2,
                    },
                    {
                        field_id: 7,
                        type: "priority",
                        label: "Rank",
                        value: 51,
                    },
                    {
                        field_id: 8,
                        type: "computed",
                        label: "Computed",
                        value: 10,
                        manual_value: null,
                        is_autocomputed: true,
                    },
                    {
                        field_id: 9,
                        type: "subon",
                        label: "Submitted On",
                        value: "2020-12-29T09:55:55+00:00",
                        is_time_displayed: true,
                    },
                    {
                        field_id: 10,
                        type: "lud",
                        label: "Last Update Date",
                        value: "2021-07-29T15:56:09+00:00",
                        is_time_displayed: false,
                    },
                    {
                        field_id: 11,
                        type: "date",
                        label: "Closed Date",
                        value: null,
                        is_time_displayed: false,
                    },
                    {
                        field_id: 13,
                        type: "text",
                        label: "Description",
                        value: "<p>Some long description in art #1002</p>",
                        format: "html",
                    },
                ],
                containers: [
                    {
                        name: "Details",
                        values: [],
                        containers: [
                            {
                                name: "Sub details",
                                values: [
                                    {
                                        field_id: 12,
                                        type: "string",
                                        label: "A detail",
                                        value: "Value in art #1002",
                                    },
                                ],
                                containers: [],
                            },
                        ],
                    },
                ],
            },
        ];
        jest.spyOn(artifact_retriever, "retrieveReportArtifacts").mockResolvedValueOnce(
            report_artifacts
        );

        const report = await createExportDocument(
            1,
            false,
            "report_name",
            123,
            "tracker_shortname",
            { locale: "en-US", timezone: "UTC" }
        );

        expect(report.name).toEqual("tracker_shortname - report_name");

        const collection = report.artifacts;

        expect(collection).toStrictEqual([
            {
                id: 1001,
                title: "tracker_shortname #1001 - title01",
                fields: [
                    {
                        content_length: "short",
                        field_name: "Artifact Number",
                        field_value: "1001",
                    },
                    {
                        content_length: "short",
                        field_name: "Title",
                        field_value: "title01",
                    },
                    {
                        content_length: "short",
                        field_name: "Capacity",
                        field_value: "5",
                    },
                    {
                        content_length: "short",
                        field_name: "Effort",
                        field_value: "1.5",
                    },
                    {
                        content_length: "short",
                        field_name: "Per tracker ID",
                        field_value: "1",
                    },
                    {
                        content_length: "short",
                        field_name: "Rank",
                        field_value: "50",
                    },
                    {
                        content_length: "short",
                        field_name: "Computed",
                        field_value: "10",
                    },
                    {
                        content_length: "short",
                        field_name: "Submitted On",
                        field_value: "12/28/2020 9:55:55 AM",
                    },
                    {
                        content_length: "short",
                        field_name: "Last Update Date",
                        field_value: "7/30/2021",
                    },
                    {
                        content_length: "short",
                        field_name: "Closed Date",
                        field_value: "",
                    },
                    {
                        content_length: "long",
                        content_format: "plaintext",
                        field_name: "Description",
                        field_value: "Some long description in art #1001",
                    },
                ],
                containers: [
                    {
                        name: "Details",
                        fields: [],
                        containers: [
                            {
                                name: "Sub details",
                                fields: [
                                    {
                                        content_length: "short",
                                        field_name: "A detail",
                                        field_value: "Value in art #1001",
                                    },
                                ],
                                containers: [],
                            },
                        ],
                    },
                ],
            },
            {
                id: 1002,
                title: "tracker_shortname #1002 - title02",
                fields: [
                    {
                        content_length: "short",
                        field_name: "Artifact Number",
                        field_value: "1002",
                    },
                    {
                        content_length: "short",
                        field_name: "Title",
                        field_value: "title02",
                    },
                    {
                        content_length: "short",
                        field_name: "Capacity",
                        field_value: "2",
                    },
                    {
                        content_length: "short",
                        field_name: "Effort",
                        field_value: "2.5",
                    },
                    {
                        content_length: "short",
                        field_name: "Per tracker ID",
                        field_value: "2",
                    },
                    {
                        content_length: "short",
                        field_name: "Rank",
                        field_value: "51",
                    },
                    {
                        content_length: "short",
                        field_name: "Computed",
                        field_value: "10",
                    },
                    {
                        content_length: "short",
                        field_name: "Submitted On",
                        field_value: "12/29/2020 9:55:55 AM",
                    },
                    {
                        content_length: "short",
                        field_name: "Last Update Date",
                        field_value: "7/29/2021",
                    },
                    {
                        content_length: "short",
                        field_name: "Closed Date",
                        field_value: "",
                    },
                    {
                        content_length: "long",
                        content_format: "html",
                        field_name: "Description",
                        field_value: "<p>Some long description in art #1002</p>",
                    },
                ],
                containers: [
                    {
                        name: "Details",
                        fields: [],
                        containers: [
                            {
                                name: "Sub details",
                                fields: [
                                    {
                                        content_length: "short",
                                        field_name: "A detail",
                                        field_value: "Value in art #1002",
                                    },
                                ],
                                containers: [],
                            },
                        ],
                    },
                ],
            },
        ]);
    });
});

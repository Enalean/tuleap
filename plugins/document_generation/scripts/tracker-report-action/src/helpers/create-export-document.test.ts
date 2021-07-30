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

import { createExportDocument } from "./create-export-document";
import type { ArtifactReportResponse, ArtifactReportResponseUnknownFieldValue } from "../type";

describe("Create ArtifactValues Collection", () => {
    it("Transforms json content into a collection", () => {
        const report_artifacts: ArtifactReportResponse[] = [
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
                ],
            },
        ];

        const report = createExportDocument(report_artifacts, "report_name", "tracker_shortname");

        expect(report.name).toEqual("tracker_shortname - report_name");

        const collection = report.artifacts;

        expect(collection.length).toEqual(2);
        expect(collection[0].title).toEqual("tracker_shortname #1001 - title01");
        expect(collection[0].fields.length).toEqual(7);
        expect(collection[0].fields[0].field_name).toEqual("Artifact Number");
        expect(collection[0].fields[0].field_value).toEqual("1001");
        expect(collection[0].fields[1].field_name).toEqual("Title");
        expect(collection[0].fields[1].field_value).toEqual("title01");
        expect(collection[0].fields[2].field_name).toEqual("Capacity");
        expect(collection[0].fields[2].field_value).toEqual("5");
        expect(collection[0].fields[3].field_name).toEqual("Effort");
        expect(collection[0].fields[3].field_value).toEqual("1.5");
        expect(collection[0].fields[4].field_name).toEqual("Per tracker ID");
        expect(collection[0].fields[4].field_value).toEqual("1");
        expect(collection[0].fields[5].field_name).toEqual("Rank");
        expect(collection[0].fields[5].field_value).toEqual("50");
        expect(collection[0].fields[6].field_name).toEqual("Computed");
        expect(collection[0].fields[6].field_value).toEqual("10");
        expect(collection[1].title).toEqual("tracker_shortname #1002 - title02");
        expect(collection[1].fields.length).toEqual(7);
        expect(collection[1].fields[0].field_name).toEqual("Artifact Number");
        expect(collection[1].fields[0].field_value).toEqual("1002");
        expect(collection[1].fields[1].field_name).toEqual("Title");
        expect(collection[1].fields[1].field_value).toEqual("title02");
        expect(collection[1].fields[2].field_name).toEqual("Capacity");
        expect(collection[1].fields[2].field_value).toEqual("2");
        expect(collection[1].fields[3].field_name).toEqual("Effort");
        expect(collection[1].fields[3].field_value).toEqual("2.5");
        expect(collection[1].fields[4].field_name).toEqual("Per tracker ID");
        expect(collection[1].fields[4].field_value).toEqual("2");
        expect(collection[1].fields[5].field_name).toEqual("Rank");
        expect(collection[1].fields[5].field_value).toEqual("51");
        expect(collection[1].fields[6].field_name).toEqual("Computed");
        expect(collection[1].fields[6].field_value).toEqual("10");
    });
});

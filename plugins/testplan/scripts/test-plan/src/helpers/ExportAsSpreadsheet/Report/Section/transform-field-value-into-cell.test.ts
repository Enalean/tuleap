/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { transformFieldValueIntoACell } from "./transform-field-value-into-cell";
import type { ReportCell } from "@tuleap/plugin-docgen-xlsx";
import { DateCell, EmptyCell, HTMLCell, NumberCell, TextCell } from "@tuleap/plugin-docgen-xlsx";
import type { ArtifactReportResponseFieldValue } from "@tuleap/plugin-docgen-docx";

describe("transform-field-value-into-cell", () => {
    it.each([
        [
            "string field",
            {
                label: "String field",
                type: "string",
                value: "A string",
            } as ArtifactReportResponseFieldValue,
            new TextCell("A string"),
        ],
        [
            "text field value plaintext formatted",
            {
                label: "Plaintext Text",
                type: "text",
                format: "text",
                value: "Plaintext Content",
            } as ArtifactReportResponseFieldValue,
            new TextCell("Plaintext Content"),
        ],
        [
            "text field value HTML formatted",
            {
                label: "HTML Text",
                type: "text",
                format: "html",
                value: "HTML Content",
            } as ArtifactReportResponseFieldValue,
            new HTMLCell("HTML Content"),
        ],
        [
            "int field value",
            {
                label: "Int",
                type: "int",
                value: 10,
            } as ArtifactReportResponseFieldValue,
            new NumberCell(10),
        ],
        [
            "float field value with no value",
            {
                label: "Float no value",
                type: "float",
                value: null,
            } as ArtifactReportResponseFieldValue,
            new EmptyCell(),
        ],
        [
            "computed field with autocomputed value",
            {
                label: "Computed autocomputed",
                type: "computed",
                is_autocomputed: true,
                value: 12.12,
            } as ArtifactReportResponseFieldValue,
            new NumberCell(12.12),
        ],
        [
            "computed field with manual value",
            {
                label: "Computed autocomputed",
                type: "computed",
                is_autocomputed: false,
                manual_value: 14.14,
            } as ArtifactReportResponseFieldValue,
            new NumberCell(14.14),
        ],
        [
            "date field with null value",
            {
                label: "Date null value",
                type: "date",
                value: null,
            } as ArtifactReportResponseFieldValue,
            null,
        ],
        [
            "submitted on value",
            {
                label: "Submitted on",
                type: "subon",
                value: "2020-08-18T10:40:03+01:00",
            } as ArtifactReportResponseFieldValue,
            new DateCell(new Date("2020-08-18T10:40:03+01:00")),
        ],
        [
            "last updated on value",
            {
                label: "Last updated on",
                type: "lud",
                value: "2020-08-18T10:40:03+01:00",
            } as ArtifactReportResponseFieldValue,
            new DateCell(new Date("2020-08-18T10:40:03+01:00")),
        ],
        [
            "unknown field value",
            {
                label: "Field that cannot be processed",
                type: "something",
            } as ArtifactReportResponseFieldValue,
            null,
        ],
    ])(
        "transforms %s into a cell",
        (
            _: string,
            field_value: Readonly<ArtifactReportResponseFieldValue>,
            expected_cell: ReportCell | null,
        ) => {
            expect(transformFieldValueIntoACell(field_value)).toStrictEqual(expected_cell);
        },
    );
});

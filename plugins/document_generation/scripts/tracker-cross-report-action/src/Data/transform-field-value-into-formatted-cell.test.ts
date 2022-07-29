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
import { EmptyCell, NumberCell, TextCell, HTMLCell, DateCell } from "@tuleap/plugin-docgen-xlsx";
import type {
    ArtifactReportResponseFieldValue,
    ArtifactReportResponseUserRepresentation,
    ArtifactReportResponseUserGroupRepresentation,
} from "@tuleap/plugin-docgen-docx";
import { transformFieldValueIntoAFormattedCell } from "./transform-field-value-into-formatted-cell";

describe("transform-field-value-into-formatted-cell", () => {
    it("transforms string value into TextCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 2,
            type: "string",
            label: "Field02",
            value: "string_value",
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new TextCell("string_value"));
    });
    it("transforms HTML text value into HTMLCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 2,
            type: "text",
            label: "HTML text",
            value: "<p>string_value</p>",
            format: "html",
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new HTMLCell("<p>string_value</p>"));
    });
    it("transforms plain text value into TextCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 2,
            type: "text",
            label: "Plan text",
            value: "plain_text_value",
            format: "text",
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new TextCell("plain_text_value"));
    });
    it("transforms int value into NumberCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "int",
            label: "integer field",
            value: 74,
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new NumberCell(74));
    });
    it("transforms float value into NumberCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "float",
            label: "Float field",
            value: 89.26,
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new NumberCell(89.26));
    });
    it("transforms aid value into NumberCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "aid",
            label: "Artifact ID",
            value: 123,
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new NumberCell(123));
    });
    it("transforms atid value into NumberCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "atid",
            label: "Artifact ID",
            value: 123,
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new NumberCell(123));
    });
    it("transforms priority value into NumberCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "priority",
            label: "Priority",
            value: 1,
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new NumberCell(1));
    });
    it("transforms computed field manual value into NumberCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "computed",
            label: "Computed field",
            value: 456,
            manual_value: 123,
            is_autocomputed: false,
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new NumberCell(123));
    });
    it("transforms computed field autocomputed value into NumberCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "computed",
            label: "Computed field",
            value: 456,
            manual_value: 123,
            is_autocomputed: true,
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new NumberCell(456));
    });
    it("transforms date field value into DateCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "date",
            label: "Date field",
            value: "03/11/2020 09:36:10",
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new DateCell(new Date("03/11/2020 09:36:10")));
    });
    it("transforms null date field value into EmptyCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "date",
            label: "Date field",
            value: null,
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new EmptyCell());
    });
    it("transforms submitted on field value into DateCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "subon",
            label: "Submitted On",
            value: "03/11/2020 09:36:10",
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new DateCell(new Date("03/11/2020 09:36:10")));
    });
    it("transforms null submitted on field value into EmptyCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "subon",
            label: "Submitted On",
            value: null,
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new EmptyCell());
    });
    it("transforms last update date field value into DateCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "lud",
            label: "Last update date",
            value: "03/11/2020 09:36:10",
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new DateCell(new Date("03/11/2020 09:36:10")));
    });
    it("transforms null last update date field value into EmptyCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "lud",
            label: "Last update date",
            value: null,
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new EmptyCell());
    });
    it("transforms selectbox field bound to static values into TextCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "sb",
            label: "Selectbox Static",
            values: [
                {
                    id: 300,
                    label: "On Going",
                    color: null,
                    tlp_color: "lake-placid-blue",
                },
            ],
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new TextCell("On Going"));
    });
    it("transforms selectbox field bound to users into TextCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "sb",
            label: "Selectbox Users",
            values: [
                {
                    id: 101,
                    display_name: "User01",
                } as ArtifactReportResponseUserRepresentation,
            ],
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new TextCell("User01"));
    });
    it("transforms selectbox field bound to user groups into TextCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "sb",
            label: "Selectbox Ugroups",
            values: [
                {
                    id: "101",
                    label: "Ugroup01",
                } as ArtifactReportResponseUserGroupRepresentation,
            ],
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new TextCell("Ugroup01"));
    });
    it("transforms selectbox empty value into EmptyCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "sb",
            label: "Selectbox Empty",
            values: [],
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new EmptyCell());
    });
    it("transforms radiobutton field bound to static values into TextCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "rb",
            label: "Radiobutton Static",
            values: [
                {
                    id: 300,
                    label: "On Going",
                    color: null,
                    tlp_color: "lake-placid-blue",
                },
            ],
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new TextCell("On Going"));
    });
    it("transforms radiobutton field bound to users into TextCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "rb",
            label: "Radiobutton Users",
            values: [
                {
                    id: 101,
                    display_name: "User01",
                } as ArtifactReportResponseUserRepresentation,
            ],
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new TextCell("User01"));
    });
    it("transforms radiobutton field bound to user groups into TextCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "rb",
            label: "Radiobutton Ugroups",
            values: [
                {
                    id: "101",
                    label: "Ugroup01",
                } as ArtifactReportResponseUserGroupRepresentation,
            ],
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new TextCell("Ugroup01"));
    });
    it("transforms radiobutton empty value into EmptyCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "rb",
            label: "Radiobutton Empty",
            values: [],
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new EmptyCell());
    });
    it("transforms multiselectbox field bound to static values into TextCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "msb",
            label: "Multiselectbox Static",
            values: [
                {
                    id: 1,
                    label: "value01",
                    color: null,
                    tlp_color: "lake-placid-blue",
                },
                {
                    id: 2,
                    label: "value02",
                    color: null,
                    tlp_color: "lake-placid-blue",
                },
            ],
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new TextCell("value01, value02"));
    });
    it("transforms multiselectbox field bound to users into TextCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "msb",
            label: "Multiselectbox Users",
            values: [
                {
                    id: 101,
                    display_name: "User01",
                } as ArtifactReportResponseUserRepresentation,
                {
                    id: 102,
                    display_name: "User02",
                } as ArtifactReportResponseUserRepresentation,
            ],
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new TextCell("User01, User02"));
    });
    it("transforms multiselectbox field bound to user groups into TextCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "msb",
            label: "Multiselectbox Ugroups",
            values: [
                {
                    id: "101",
                    label: "Ugroup01",
                } as ArtifactReportResponseUserGroupRepresentation,
                {
                    id: "102",
                    label: "Ugroup02",
                } as ArtifactReportResponseUserGroupRepresentation,
            ],
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new TextCell("Ugroup01, Ugroup02"));
    });
    it("transforms multiselectbox empty value into EmptyCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "msb",
            label: "Multiselectbox Empty",
            values: [],
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new EmptyCell());
    });
    it("transforms checkbox field bound to static values into TextCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "cb",
            label: "Checkbox Static",
            values: [
                {
                    id: 1,
                    label: "value01",
                    color: null,
                    tlp_color: "lake-placid-blue",
                },
                {
                    id: 2,
                    label: "value02",
                    color: null,
                    tlp_color: "lake-placid-blue",
                },
            ],
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new TextCell("value01, value02"));
    });
    it("transforms checkbox field bound to users into TextCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "cb",
            label: "Checkbox Users",
            values: [
                {
                    id: 101,
                    display_name: "User01",
                } as ArtifactReportResponseUserRepresentation,
                {
                    id: 102,
                    display_name: "User02",
                } as ArtifactReportResponseUserRepresentation,
            ],
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new TextCell("User01, User02"));
    });
    it("transforms checkbox field bound to user groups into TextCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "cb",
            label: "Checkbox Ugroups",
            values: [
                {
                    id: "101",
                    label: "Ugroup01",
                } as ArtifactReportResponseUserGroupRepresentation,
                {
                    id: "102",
                    label: "Ugroup02",
                } as ArtifactReportResponseUserGroupRepresentation,
            ],
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new TextCell("Ugroup01, Ugroup02"));
    });
    it("transforms checkbox empty value into EmptyCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "cb",
            label: "Checkbox Empty",
            values: [],
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new EmptyCell());
    });
    it("transforms openlist field bound to static values into TextCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "tbl",
            label: "OpenList Static",
            bind_value_objects: [
                {
                    id: 1,
                    label: "value01",
                },
                {
                    id: 1452,
                    label: "value02",
                    color: null,
                    tlp_color: "lake-placid-blue",
                },
            ],
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new TextCell("value01, value02"));
    });
    it("transforms openlist field bound to users into TextCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "tbl",
            label: "OpenList Users",
            bind_value_objects: [
                {
                    id: 101,
                    display_name: "User01",
                } as ArtifactReportResponseUserRepresentation,
                {
                    id: 102,
                    display_name: "User02",
                } as ArtifactReportResponseUserRepresentation,
            ],
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new TextCell("User01, User02"));
    });
    it("transforms openlist field bound to user groups into TextCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "tbl",
            label: "OpenList Ugroups",
            bind_value_objects: [
                {
                    id: "101",
                    label: "Ugroup01",
                } as ArtifactReportResponseUserGroupRepresentation,
                {
                    id: "102",
                    label: "Ugroup02",
                } as ArtifactReportResponseUserGroupRepresentation,
            ],
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new TextCell("Ugroup01, Ugroup02"));
    });
    it("transforms openlist empty value into EmptyCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "tbl",
            label: "OpenList Empty",
            bind_value_objects: [],
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new EmptyCell());
    });
    it("transforms all other fields into EmptyCell", (): void => {
        const field_value: ArtifactReportResponseFieldValue = {
            field_id: 1,
            type: "art_link",
            label: "Artifact Link",
            links: [],
            reverse_links: [],
        };
        const formatted_cell = transformFieldValueIntoAFormattedCell(field_value);

        expect(formatted_cell).toStrictEqual(new EmptyCell());
    });
});

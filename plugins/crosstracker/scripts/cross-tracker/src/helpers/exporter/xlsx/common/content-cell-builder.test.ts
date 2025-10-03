/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import { buildXLSXContentCell } from "./content-cell-builder";

import type { Cell } from "../../../../domain/ArtifactsTable";
import {
    STATIC_LIST_CELL,
    TEXT_CELL,
    PROJECT_CELL,
    NUMERIC_CELL,
    USER_CELL,
    USER_LIST_CELL,
    USER_GROUP_LIST_CELL,
    TRACKER_CELL,
    DATE_CELL,
    PRETTY_TITLE_CELL,
} from "../../../../domain/ArtifactsTable";
import type { NumberCell, TextCell, DateCell } from "@tuleap/plugin-docgen-xlsx/src";
import { Option } from "@tuleap/option";

describe("buildXLSXContentCell", () => {
    it("should return an EmptyCell if the input is undefined", () => {
        const result = buildXLSXContentCell(undefined);
        expect(result.type).toBe("empty");
    });

    it("should return a NumberCell for NUMERIC_CELL type with valid value", () => {
        const cell: Cell = {
            type: NUMERIC_CELL,
            value: Option.fromNullable(42),
        };
        const result = buildXLSXContentCell(cell) as NumberCell;
        expect(result.type).toBe("number");
        expect(result.value).toBe(42);
    });

    it("should return a TextCell for PROJECT_CELL type with icon", () => {
        const cell: Cell = {
            type: PROJECT_CELL,
            icon: "📦",
            name: "ProjectName",
        };
        const result = buildXLSXContentCell(cell) as TextCell;
        expect(result.type).toBe("text");
        expect(result.value).toBe("📦 ProjectName");
    });

    it("should return a TextCell for PROJECT_CELL type without icon", () => {
        const cell: Cell = {
            type: PROJECT_CELL,
            icon: "",
            name: "ProjectName",
        };
        const result = buildXLSXContentCell(cell) as TextCell;
        expect(result.type).toBe("text");
        expect(result.value).toBe("ProjectName");
    });

    it("should return a HTMLCell for TEXT_CELL type", () => {
        const cell: Cell = {
            type: TEXT_CELL,
            value: "<b>Example</b>",
        };
        const result = buildXLSXContentCell(cell) as TextCell;
        expect(result.type).toBe("html");
        expect(result.value).toBe("<b>Example</b>");
    });

    it("should return a TextCell for USER_CELL type", () => {
        const cell: Cell = {
            type: USER_CELL,
            display_name: "Alice Doe",
            avatar_uri: "/alice/avatar",
            user_uri: Option.fromNullable("/alice/uri"),
        };
        const result = buildXLSXContentCell(cell) as TextCell;
        expect(result.type).toBe("text");
        expect(result.value).toBe("Alice Doe");
    });

    it("should return a TextCell for TRACKER_CELL type", () => {
        const cell: Cell = {
            type: TRACKER_CELL,
            name: "TrackerName",
            color: "acid-green",
        };
        const result = buildXLSXContentCell(cell) as TextCell;
        expect(result.type).toBe("text");
        expect(result.value).toBe("TrackerName");
    });

    it("should return a TextCell for USER_LIST_CELL type", () => {
        const cell: Cell = {
            type: USER_LIST_CELL,
            value: [
                {
                    display_name: "Alice",
                    avatar_uri: "/alice/avatar",
                    user_uri: Option.fromNullable("/alice/uri"),
                },
                {
                    display_name: "Bob",
                    avatar_uri: "/bob/avatar",
                    user_uri: Option.fromNullable("/bob/uri"),
                },
            ],
        };
        const result = buildXLSXContentCell(cell) as TextCell;
        expect(result.type).toBe("text");
        expect(result.value).toBe("Alice, Bob");
    });

    it("should return a TextCell for USER_GROUP_LIST_CELL type", () => {
        const cell: Cell = {
            type: USER_GROUP_LIST_CELL,
            value: [{ label: "Group A" }, { label: "Group B" }],
        };
        const result = buildXLSXContentCell(cell) as TextCell;
        expect(result.type).toBe("text");
        expect(result.value).toBe("Group A, Group B");
    });

    it("should return a DateCell for DATE_CELL type with valid date", () => {
        const cell: Cell = {
            type: DATE_CELL,
            value: Option.fromNullable("2025-10-01"),
            with_time: false,
        };
        const result = buildXLSXContentCell(cell) as DateCell;
        expect(result.type).toBe("date");
        expect(result.value).toBe("2025-10-01");
    });

    it("should return a TextCell for STATIC_LIST_CELL type", () => {
        const cell: Cell = {
            type: STATIC_LIST_CELL,
            value: [
                { label: "Value 1", color: Option.fromNullable("army-green") },
                { label: "Value 2", color: Option.fromNullable("acid-green") },
            ],
        };
        const result = buildXLSXContentCell(cell) as TextCell;
        expect(result.type).toBe("text");
        expect(result.value).toBe("Value 1, Value 2");
    });

    it("should return a TextCell for PRETTY_TITLE_CELL type", () => {
        const cell: Cell = {
            type: PRETTY_TITLE_CELL,
            tracker_name: "TrackerName",
            artifact_id: 123,
            title: "Artifact Title",
            color: "acid-green",
        };
        const result = buildXLSXContentCell(cell) as TextCell;
        expect(result.type).toBe("text");
        expect(result.value).toBe("TrackerName#123 Artifact Title");
    });
});

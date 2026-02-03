/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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
import { v4 as uuidv4 } from "uuid";
import {
    CUSTOM_LAYOUT,
    getFieldsetLayout,
    ONE_COLUMN,
    THREE_COLUMNS,
    TWO_COLUMNS,
} from "./get-fieldset-layout";
import type { Column, ColumnWrapper, Fieldset } from "../type";
import {
    CONTAINER_COLUMN,
    CONTAINER_FIELDSET,
    SELECTBOX_FIELD,
} from "@tuleap/plugin-tracker-constants";
import { StaticBoundListFieldTestBuilder } from "../tests/builders/StaticBoundListFieldTestBuilder";
import type { StructureFields } from "@tuleap/plugin-tracker-rest-api-types";

describe("getFieldsetLayout", () => {
    let id = 123;

    function aFieldset(children: Fieldset["children"]): Fieldset {
        return {
            field: {
                field_id: id++,
                name: "fieldset",
                required: false,
                has_notifications: false,
                label: "Le fieldset",
                type: CONTAINER_FIELDSET,
                label_decorators: [],
            },
            children,
        };
    }

    function aColumn(children: Column["children"] = []): Column {
        return {
            field: {
                field_id: id++,
                name: "column",
                required: false,
                has_notifications: false,
                label: "La column",
                type: CONTAINER_COLUMN,
                label_decorators: [],
            },
            children,
        };
    }

    function aSelectbox(): { field: StructureFields } {
        return {
            field: StaticBoundListFieldTestBuilder.aStaticBoundListField(SELECTBOX_FIELD).build(),
        };
    }

    function aColumnWrapper(columns: ColumnWrapper["columns"]): ColumnWrapper {
        return {
            identifier: uuidv4(),
            columns,
        };
    }

    it("returns custom layout when the fieldset does not have children", () => {
        const children: Fieldset["children"] = [];

        expect(getFieldsetLayout(aFieldset(children))).toBe(CUSTOM_LAYOUT);
    });

    it("returns one-column when the fieldset has only one column", () => {
        const children = [aColumn()];

        expect(getFieldsetLayout(aFieldset(children))).toBe(ONE_COLUMN);
    });

    it("returns two-column when the fieldset has only two columns", () => {
        const children = [aColumn(), aColumn()];

        expect(getFieldsetLayout(aFieldset(children))).toBe(TWO_COLUMNS);
    });

    it("returns three-column when the fieldset has only three columns", () => {
        const children = [aColumn(), aColumn(), aColumn()];

        expect(getFieldsetLayout(aFieldset(children))).toBe(THREE_COLUMNS);
    });

    it("returns one-column when the fieldset has only one column in a column wrapper", () => {
        const children = [aColumnWrapper([aColumn()])];

        expect(getFieldsetLayout(aFieldset(children))).toBe(ONE_COLUMN);
    });

    it("returns two-column when the fieldset has only two columns in a column wrapper", () => {
        const children = [aColumnWrapper([aColumn(), aColumn()])];

        expect(getFieldsetLayout(aFieldset(children))).toBe(TWO_COLUMNS);
    });

    it("returns three-column when the fieldset has only three columns in a column wrapper", () => {
        const children = [aColumnWrapper([aColumn(), aColumn(), aColumn()])];

        expect(getFieldsetLayout(aFieldset(children))).toBe(THREE_COLUMNS);
    });

    it("returns custom layout when the fieldset has more than three columns", () => {
        const children = [aColumn(), aColumn(), aColumn(), aColumn()];

        expect(getFieldsetLayout(aFieldset(children))).toBe(CUSTOM_LAYOUT);
    });

    it("returns custom layout when the fieldset has more than three columns in a column wrapper", () => {
        const children = [aColumnWrapper([aColumn(), aColumn(), aColumn(), aColumn()])];

        expect(getFieldsetLayout(aFieldset(children))).toBe(CUSTOM_LAYOUT);
    });

    it("returns custom layout when the fieldset has more than one column wrapper", () => {
        const children = [aColumnWrapper([aColumn()]), aColumnWrapper([aColumn()])];

        expect(getFieldsetLayout(aFieldset(children))).toBe(CUSTOM_LAYOUT);
    });

    it("returns custom layout when the fieldset contains a fieldset", () => {
        const children = [aFieldset([])];

        expect(getFieldsetLayout(aFieldset(children))).toBe(CUSTOM_LAYOUT);
    });

    it("returns custom layout when the fieldset contains a column in a column", () => {
        const children = [aColumn([aColumn()])];

        expect(getFieldsetLayout(aFieldset(children))).toBe(CUSTOM_LAYOUT);
    });

    it("returns custom layout when the fieldset contains a column wrapper in a column", () => {
        const children = [aColumn([aColumnWrapper([aColumn()])])];

        expect(getFieldsetLayout(aFieldset(children))).toBe(CUSTOM_LAYOUT);
    });

    it("returns custom layout when the fieldset contains a fieldset in a column", () => {
        const children = [aColumn([aFieldset([])])];

        expect(getFieldsetLayout(aFieldset(children))).toBe(CUSTOM_LAYOUT);
    });

    it("returns custom layout when the fieldset contains a column in a column in a column wrapper", () => {
        const children = [aColumnWrapper([aColumn([aColumn()])])];

        expect(getFieldsetLayout(aFieldset(children))).toBe(CUSTOM_LAYOUT);
    });

    it("returns custom layout when the fieldset contains a column wrapper in a column in a column wrapper", () => {
        const children = [aColumnWrapper([aColumn([aColumnWrapper([aColumn()])])])];

        expect(getFieldsetLayout(aFieldset(children))).toBe(CUSTOM_LAYOUT);
    });

    it("returns custom layout when the fieldset contains a fieldset in a column in a column wrapper", () => {
        const children = [aColumnWrapper([aColumn([aFieldset([])])])];

        expect(getFieldsetLayout(aFieldset(children))).toBe(CUSTOM_LAYOUT);
    });

    it("returns one-column layout when the fieldset does not contains any columns nor column wrappers nor fieldsets", () => {
        const children = [aSelectbox(), aSelectbox()];

        expect(getFieldsetLayout(aFieldset(children))).toBe(ONE_COLUMN);
    });
});

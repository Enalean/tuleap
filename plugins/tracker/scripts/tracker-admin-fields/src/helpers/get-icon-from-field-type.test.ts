/*
 * Copyright (c) Enalean, 2026-present. All Rights Reserved.
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
import { getIconFromFieldType } from "./get-icon-from-field-type";
import {
    CONTAINER_FIELDSET,
    DATE_FIELD,
    FLOAT_FIELD,
    INT_FIELD,
    LINE_BREAK,
    MULTI_SELECTBOX_FIELD,
    SELECTBOX_FIELD,
    SEPARATOR,
    STRING_FIELD,
    TEXT_FIELD,
    CHECKBOX_FIELD,
    RADIO_BUTTON_FIELD,
    ARTIFACT_ID_FIELD,
    ARTIFACT_ID_IN_TRACKER_FIELD,
    STATIC_RICH_TEXT,
    ARTIFACT_LINK_FIELD,
    SUBMITTED_BY_FIELD,
    LAST_UPDATED_BY_FIELD,
    SUBMISSION_DATE_FIELD,
    LAST_UPDATE_DATE_FIELD,
    CROSS_REFERENCE_FIELD,
    FILE_FIELD,
    OPEN_LIST_FIELD,
    PERMISSION_FIELD,
    COMPUTED_FIELD,
    CONTAINER_COLUMN,
    PRIORITY_FIELD,
    SHARED_FIELD,
} from "@tuleap/plugin-tracker-constants";

describe("getIconFromFieldType", () => {
    it.each([[STRING_FIELD], [TEXT_FIELD]])(
        `displays text icon for '%s' field`,
        (field_type: string) => {
            expect(getIconFromFieldType(field_type)).toBe("fa-solid fa-t");
        },
    );

    it.each([[INT_FIELD], [FLOAT_FIELD]])(
        `displays number icon for '%s' field`,
        (field_type: string) => {
            expect(getIconFromFieldType(field_type)).toBe("fa-solid fa-3");
        },
    );

    it.each([[DATE_FIELD], [LAST_UPDATE_DATE_FIELD], [SUBMISSION_DATE_FIELD]])(
        `displays calendar icon for '%s' field`,
        (field_type: string) => {
            expect(getIconFromFieldType(field_type)).toBe("fa-solid fa-calendar-days");
        },
    );

    it.each([[SELECTBOX_FIELD], [MULTI_SELECTBOX_FIELD], [OPEN_LIST_FIELD]])(
        `displays calendar icon for '%s' field`,
        (field_type: string) => {
            expect(getIconFromFieldType(field_type)).toBe("fa-solid fa-list");
        },
    );

    it("displays radio button icon for radio button field", () => {
        expect(getIconFromFieldType(RADIO_BUTTON_FIELD)).toBe("fa-regular fa-circle-dot");
    });

    it("displays checkbox icon for checkbox field", () => {
        expect(getIconFromFieldType(CHECKBOX_FIELD)).toBe("fa-regular fa-square-check");
    });

    it("displays upload icon for file field", () => {
        expect(getIconFromFieldType(FILE_FIELD)).toBe("fa-solid fa-upload");
    });

    it("displays link icon for artifact link field", () => {
        expect(getIconFromFieldType(ARTIFACT_LINK_FIELD)).toBe("fa-solid fa-link");
    });

    it("displays lock icon for permission field", () => {
        expect(getIconFromFieldType(PERMISSION_FIELD)).toBe("fa-solid fa-lock");
    });

    it.each([[LAST_UPDATED_BY_FIELD], [SUBMITTED_BY_FIELD]])(
        `displays user icon for '%s' field`,
        (field_type: string) => {
            expect(getIconFromFieldType(field_type)).toBe("fa-solid fa-user");
        },
    );

    it.each([[LAST_UPDATED_BY_FIELD], [SUBMITTED_BY_FIELD]])(
        `displays user icon for '%s' field`,
        (field_type: string) => {
            expect(getIconFromFieldType(field_type)).toBe("fa-solid fa-user");
        },
    );

    it.each([[ARTIFACT_ID_FIELD], [ARTIFACT_ID_IN_TRACKER_FIELD]])(
        `displays hashtag icon for '%s' field`,
        (field_type: string) => {
            expect(getIconFromFieldType(field_type)).toBe("fa-solid fa-hashtag");
        },
    );

    it("displays cross reference icon for cross reference field", () => {
        expect(getIconFromFieldType(CROSS_REFERENCE_FIELD)).toBe("fa-solid fa-arrows-turn-to-dots");
    });

    it("displays calculator icon for computed field", () => {
        expect(getIconFromFieldType(COMPUTED_FIELD)).toBe("fa-solid fa-calculator");
    });

    it("displays priority icon for priority field", () => {
        expect(getIconFromFieldType(PRIORITY_FIELD)).toBe("fa-solid fa-arrow-up-short-wide");
    });

    it("displays square icon for container fieldset", () => {
        expect(getIconFromFieldType(CONTAINER_FIELDSET)).toBe("fa-regular fa-square");
    });

    it("displays columns icon for container column", () => {
        expect(getIconFromFieldType(CONTAINER_COLUMN)).toBe("fa-solid fa-table-columns");
    });

    it("displays line break icon for line break", () => {
        expect(getIconFromFieldType(LINE_BREAK)).toBe("fa-solid fa-arrow-turn-down fa-rotate-90");
    });

    it("displays separator icon for separator", () => {
        expect(getIconFromFieldType(SEPARATOR)).toBe("fa-solid fa-minus");
    });

    it("displays align left icon for static rich text", () => {
        expect(getIconFromFieldType(STATIC_RICH_TEXT)).toBe("fa-solid fa-align-left");
    });

    it("displays shapes icon for shared field", () => {
        expect(getIconFromFieldType(SHARED_FIELD)).toBe("fa-solid fa-shapes");
    });

    it("displays question icon for unknown field type", () => {
        expect(getIconFromFieldType("Unknown")).toBe("fa-solid fa-question");
    });
});

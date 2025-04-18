/**
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

import type { Ref } from "vue";
import { ref } from "vue";
import { describe, it, expect, beforeEach } from "vitest";
import type { FieldsReorderer } from "@/sections/readonly-fields/FieldsReorderer";
import { buildFieldsReorderer } from "@/sections/readonly-fields/FieldsReorderer";
import type { ConfigurationField } from "@/sections/readonly-fields/AvailableReadonlyFields";
import { ConfigurationFieldStub } from "@/sections/stubs/ConfigurationFieldStub";

describe("FieldsReorderer", () => {
    let fields_reorderer: FieldsReorderer, selected_fields: Ref<ConfigurationField[]>;

    const field_1 = ConfigurationFieldStub.withFieldId(1);
    const field_2 = ConfigurationFieldStub.withFieldId(2);
    const field_3 = ConfigurationFieldStub.withFieldId(3);

    beforeEach(() => {
        selected_fields = ref([field_1, field_2, field_3]);
        fields_reorderer = buildFieldsReorderer(selected_fields);
    });

    describe("isFirstField", () => {
        it("should return true if the field is at the top", () => {
            expect(fields_reorderer.isFirstField(field_1)).toBe(true);
        });

        it("should return false if the field is not at the top", () => {
            expect(fields_reorderer.isFirstField(field_2)).toBe(false);
        });
    });

    describe("isLastField", () => {
        it("should return true if the field is at the bottom", () => {
            expect(fields_reorderer.isLastField(field_3)).toBe(true);
        });

        it("should return false if the field is not at the top", () => {
            expect(fields_reorderer.isLastField(field_2)).toBe(false);
        });
    });

    describe("moveFieldUp", () => {
        it("should do nothing if the field is already at the top", () => {
            fields_reorderer.moveFieldUp(field_1);

            expect(selected_fields.value).toStrictEqual([field_1, field_2, field_3]);
        });

        it("should move the field up if it is not at the top", () => {
            fields_reorderer.moveFieldUp(field_2);

            expect(selected_fields.value).toStrictEqual([field_2, field_1, field_3]);
        });
    });

    describe("moveFieldDown", () => {
        it("should do nothing if the field is already at the end", () => {
            fields_reorderer.moveFieldDown(field_3);

            expect(selected_fields.value).toStrictEqual([field_1, field_2, field_3]);
        });

        it("should move the field down if it is not at the end", () => {
            fields_reorderer.moveFieldDown(field_2);

            expect(selected_fields.value).toStrictEqual([field_1, field_3, field_2]);
        });
    });

    describe("moveFieldBeforeSibling", () => {
        it("Given a field and a next sibling field, When the field is after the expected sibling field, then it should move the field before the next sibling", () => {
            fields_reorderer.moveFieldBeforeSibling(field_3, field_1);

            expect(selected_fields.value).toStrictEqual([field_3, field_1, field_2]);
        });

        it("Given a field and a next sibling field, When the field is before the expected sibling field, then it should move the field before the next sibling", () => {
            fields_reorderer.moveFieldBeforeSibling(field_1, field_3);

            expect(selected_fields.value).toStrictEqual([field_2, field_1, field_3]);
        });
    });

    describe("moveFieldAtTheEnd", () => {
        it("Given a field, then it should move it at the end of the collection", () => {
            fields_reorderer.moveFieldAtTheEnd(field_1);

            expect(selected_fields.value).toStrictEqual([field_2, field_3, field_1]);
        });
    });
});

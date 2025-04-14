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

import { describe, it, expect, beforeEach } from "vitest";
import type { ConfigurationField } from "@/sections/readonly-fields/AvailableReadonlyFields";
import {
    ignoreAlreadySelectedFields,
    getStringFields,
    ignoreSemanticsTitle,
} from "@/sections/readonly-fields/AvailableReadonlyFields";
import type { StructureFields } from "@tuleap/plugin-tracker-rest-api-types";
import { ConfigurationFieldStub } from "@/sections/stubs/ConfigurationFieldStub";

describe("getAvailableFields", () => {
    const title_field_id = 599;

    const field_summary = ConfigurationFieldStub.withFieldId(title_field_id);
    const field_string = ConfigurationFieldStub.withFieldId(602);

    const field_other = {
        field_id: 591,
        label: "Access information left column",
        type: "column",
    };

    const semantics = { title: { field_id: title_field_id } };
    const all_fields = [field_summary, field_string, field_other] as StructureFields[];
    const string_fields = [field_summary, field_string];

    const tracker_information = { fields: all_fields, semantics };

    describe("getStringFields", () => {
        it("should return only the string fields of a field list", () => {
            const string_fields = getStringFields(all_fields);

            expect(string_fields).toStrictEqual([field_summary, field_string]);
        });
    });

    describe("ignoreSemanticsTitle", () => {
        it("should remove the semantics title of a field list", () => {
            const available_fields = ignoreSemanticsTitle(tracker_information, string_fields);

            expect(available_fields).toStrictEqual([field_string]);
        });
    });

    describe("ignoreAlreadySelectedFields", () => {
        const field_string_2 = ConfigurationFieldStub.withFieldId(603);

        let string_fields: ConfigurationField[];
        let selected_fields: ConfigurationField[];

        beforeEach(() => {
            string_fields = [field_string, field_string_2];
            selected_fields = [];
        });

        it("should return all available fields if no field is selected", () => {
            const available_fields = ignoreAlreadySelectedFields(string_fields, selected_fields);

            expect(available_fields).toStrictEqual(string_fields);
        });

        it("should return available fields without the selected fields if there is selected fields", () => {
            selected_fields = [field_string];
            const available_fields = ignoreAlreadySelectedFields(string_fields, selected_fields);

            expect(available_fields).toStrictEqual([field_string_2]);
        });

        it("should return no available fields if all fields are selected", () => {
            selected_fields = [field_string_2, field_string];
            const available_fields = ignoreAlreadySelectedFields(string_fields, selected_fields);

            expect(available_fields).toStrictEqual([]);
        });
    });
});

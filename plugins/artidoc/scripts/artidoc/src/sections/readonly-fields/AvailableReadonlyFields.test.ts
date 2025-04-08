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

import { describe, it, expect } from "vitest";
import type { ConfigurationField } from "@/sections/readonly-fields/AvailableReadonlyFields";
import {
    getStringFields,
    ignoreSemanticsTitle,
} from "@/sections/readonly-fields/AvailableReadonlyFields";
import type { StructureFields } from "@tuleap/plugin-tracker-rest-api-types";

describe("getAvailableFields", () => {
    const title_field_id = 599;

    const field_summary: ConfigurationField = {
        field_id: title_field_id,
        label: "Summary",
        type: "string",
        display_type: "column",
    };

    const field_string: ConfigurationField = {
        field_id: 602,
        label: "String Field",
        type: "string",
        display_type: "column",
    };

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
});

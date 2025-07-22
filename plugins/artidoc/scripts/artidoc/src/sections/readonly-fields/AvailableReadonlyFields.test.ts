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
    filterAlreadySelectedFields,
    getSupportedFields,
    filterSemanticTitleBoundField,
} from "@/sections/readonly-fields/AvailableReadonlyFields";
import type { StructureFields } from "@tuleap/plugin-tracker-rest-api-types";
import {
    STRING_FIELD as TRACKER_STRING_FIELD,
    SELECTBOX_FIELD,
    MULTI_SELECTBOX_FIELD,
    OPEN_LIST_FIELD,
    CONTAINER_COLUMN,
    LIST_BIND_UGROUPS,
    LIST_BIND_STATIC,
    LIST_BIND_USERS,
    CHECKBOX_FIELD,
    ARTIFACT_ID_FIELD,
    ARTIFACT_ID_IN_TRACKER_FIELD,
    FLOAT_FIELD,
    INT_FIELD,
    PRIORITY_FIELD,
} from "@tuleap/plugin-tracker-constants";
import { ConfigurationFieldBuilder } from "@/sections/readonly-fields/ConfigurationFieldBuilder";

describe("getAvailableFields", () => {
    const title_field_id = 599;

    const string_field = {
        field_id: 123,
        type: TRACKER_STRING_FIELD,
        label: "String field",
    } as StructureFields;

    const summary_field = {
        field_id: title_field_id,
        type: TRACKER_STRING_FIELD,
        label: "Summary",
    } as StructureFields;

    const user_group_list_field = {
        field_id: 125,
        type: SELECTBOX_FIELD,
        label: "User group",
        bindings: { type: LIST_BIND_UGROUPS },
    } as StructureFields;

    const static_value_list_field = {
        field_id: 126,
        type: SELECTBOX_FIELD,
        label: "Static value",
        bindings: { type: LIST_BIND_STATIC },
    } as StructureFields;

    const user_value_list_field = {
        field_id: 127,
        type: SELECTBOX_FIELD,
        label: "Assignee",
        bindings: { type: LIST_BIND_USERS },
    } as StructureFields;

    const multi_user_groups_list_field = {
        field_id: 128,
        type: MULTI_SELECTBOX_FIELD,
        label: "User groups",
        bindings: { type: LIST_BIND_UGROUPS },
    } as StructureFields;

    const user_groups_open_list_field = {
        field_id: 129,
        type: OPEN_LIST_FIELD,
        label: "Open user groups",
        bindings: { type: LIST_BIND_UGROUPS },
    } as StructureFields;

    const user_groups_checkbox_field = {
        field_id: 130,
        type: CHECKBOX_FIELD,
        label: "Checkbox user groups",
        bindings: { type: LIST_BIND_UGROUPS },
    } as unknown as StructureFields;

    const user_groups_radio_button_field = {
        field_id: 130,
        type: CHECKBOX_FIELD,
        label: "Radio user groups",
        bindings: { type: LIST_BIND_UGROUPS },
    } as unknown as StructureFields;

    const multi_static_list_field = {
        field_id: 128,
        type: MULTI_SELECTBOX_FIELD,
        label: "Statics",
        bindings: { type: LIST_BIND_STATIC },
    } as StructureFields;

    const static_open_list_field = {
        field_id: 129,
        type: OPEN_LIST_FIELD,
        label: "Open static",
        bindings: { type: LIST_BIND_STATIC },
    } as StructureFields;

    const static_checkbox_field = {
        field_id: 130,
        type: CHECKBOX_FIELD,
        label: "Checkbox static",
        bindings: { type: LIST_BIND_STATIC },
    } as unknown as StructureFields;

    const static_radio_button_field = {
        field_id: 130,
        type: CHECKBOX_FIELD,
        label: "Radio static",
        bindings: { type: LIST_BIND_STATIC },
    } as unknown as StructureFields;

    const multi_user_list_field = {
        field_id: 128,
        type: MULTI_SELECTBOX_FIELD,
        label: "Users",
        bindings: { type: LIST_BIND_USERS },
    } as StructureFields;

    const user_open_list_field = {
        field_id: 129,
        type: OPEN_LIST_FIELD,
        label: "Open user",
        bindings: { type: LIST_BIND_USERS },
    } as StructureFields;

    const user_checkbox_field = {
        field_id: 130,
        type: CHECKBOX_FIELD,
        label: "Checkbox user",
        bindings: { type: LIST_BIND_USERS },
    } as unknown as StructureFields;

    const user_radio_button_field = {
        field_id: 130,
        type: CHECKBOX_FIELD,
        label: "Radio user",
        bindings: { type: LIST_BIND_USERS },
    } as unknown as StructureFields;

    const artifact_id_field = {
        field_id: 140,
        type: ARTIFACT_ID_FIELD,
        label: "Artifact id",
    } as StructureFields;

    const per_tracker_id_field = {
        field_id: 141,
        type: ARTIFACT_ID_IN_TRACKER_FIELD,
        label: "Per tracker id",
    } as StructureFields;

    const float_field = {
        field_id: 142,
        type: FLOAT_FIELD,
        label: "Float",
    } as StructureFields;

    const int_field = {
        field_id: 143,
        type: INT_FIELD,
        label: "Integer",
    } as StructureFields;

    const priority_field = {
        field_id: 144,
        type: PRIORITY_FIELD,
        label: "Rank",
    } as StructureFields;

    const all_fields: Readonly<StructureFields[]> = [
        string_field,
        summary_field,
        user_group_list_field,
        static_value_list_field,
        user_value_list_field,
        multi_user_groups_list_field,
        user_groups_open_list_field,
        user_groups_checkbox_field,
        user_groups_radio_button_field,
        multi_static_list_field,
        static_open_list_field,
        static_checkbox_field,
        static_radio_button_field,
        multi_user_list_field,
        user_open_list_field,
        user_checkbox_field,
        user_radio_button_field,
        {
            field_id: 591,
            label: "Access information left column",
            type: CONTAINER_COLUMN,
        } as StructureFields,
        artifact_id_field,
        per_tracker_id_field,
        float_field,
        int_field,
        priority_field,
    ];

    const tracker_information = {
        fields: all_fields,
        semantics: { title: { field_id: title_field_id } },
    };

    describe("getSupportedFields", () => {
        it("Given a tracker, then it should return a collection of supported ConfigurationFields", () => {
            const supported_fields = getSupportedFields(all_fields);

            expect(supported_fields).toStrictEqual([
                ConfigurationFieldBuilder.fromSupportedTrackerField(string_field),
                ConfigurationFieldBuilder.fromSupportedTrackerField(summary_field),
                ConfigurationFieldBuilder.fromSupportedTrackerField(user_group_list_field),
                ConfigurationFieldBuilder.fromSupportedTrackerField(static_value_list_field),
                ConfigurationFieldBuilder.fromSupportedTrackerField(user_value_list_field),
                ConfigurationFieldBuilder.fromSupportedTrackerField(multi_user_groups_list_field),
                ConfigurationFieldBuilder.fromSupportedTrackerField(user_groups_open_list_field),
                ConfigurationFieldBuilder.fromSupportedTrackerField(user_groups_checkbox_field),
                ConfigurationFieldBuilder.fromSupportedTrackerField(user_groups_radio_button_field),
                ConfigurationFieldBuilder.fromSupportedTrackerField(multi_static_list_field),
                ConfigurationFieldBuilder.fromSupportedTrackerField(static_open_list_field),
                ConfigurationFieldBuilder.fromSupportedTrackerField(static_checkbox_field),
                ConfigurationFieldBuilder.fromSupportedTrackerField(static_radio_button_field),
                ConfigurationFieldBuilder.fromSupportedTrackerField(multi_user_list_field),
                ConfigurationFieldBuilder.fromSupportedTrackerField(user_open_list_field),
                ConfigurationFieldBuilder.fromSupportedTrackerField(user_checkbox_field),
                ConfigurationFieldBuilder.fromSupportedTrackerField(user_radio_button_field),
                ConfigurationFieldBuilder.fromSupportedTrackerField(artifact_id_field),
                ConfigurationFieldBuilder.fromSupportedTrackerField(per_tracker_id_field),
                ConfigurationFieldBuilder.fromSupportedTrackerField(float_field),
                ConfigurationFieldBuilder.fromSupportedTrackerField(int_field),
                ConfigurationFieldBuilder.fromSupportedTrackerField(priority_field),
            ]);
        });
    });

    describe("filterSemanticTitleBoundField", () => {
        it("should remove the semantics title of a ConfigurationFields collection", () => {
            const configuration_string_field =
                ConfigurationFieldBuilder.fromSupportedTrackerField(string_field);
            const available_fields = filterSemanticTitleBoundField(tracker_information, [
                configuration_string_field,
                ConfigurationFieldBuilder.fromSupportedTrackerField(summary_field),
            ]);

            expect(available_fields).toStrictEqual([configuration_string_field]);
        });
    });

    describe("filterAlreadySelectedFields", () => {
        const selected_field_1 = ConfigurationFieldBuilder.fromSupportedTrackerField(string_field);
        const selected_field_2 =
            ConfigurationFieldBuilder.fromSupportedTrackerField(user_group_list_field);

        const available_fields = [selected_field_1, selected_field_2];
        let selected_fields: ConfigurationField[];

        it("should return all available fields if no field is selected", () => {
            const fields = filterAlreadySelectedFields(available_fields, []);

            expect(fields).toStrictEqual([selected_field_1, selected_field_2]);
        });

        it("should return available fields without the selected fields if there are selected fields", () => {
            selected_fields = [selected_field_1];
            const fields = filterAlreadySelectedFields(available_fields, selected_fields);

            expect(fields).toStrictEqual([selected_field_2]);
        });

        it("should return no available fields if all fields are selected", () => {
            selected_fields = [selected_field_1, selected_field_2];
            const fields = filterAlreadySelectedFields(available_fields, selected_fields);

            expect(fields).toStrictEqual([]);
        });
    });
});

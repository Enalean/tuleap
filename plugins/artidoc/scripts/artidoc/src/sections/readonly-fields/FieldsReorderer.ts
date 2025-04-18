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
import type { ConfigurationField } from "@/sections/readonly-fields/AvailableReadonlyFields";

export type FieldsReorderer = {
    isFirstField(field: ConfigurationField): boolean;
    isLastField(field: ConfigurationField): boolean;
    moveFieldUp(field: ConfigurationField): void;
    moveFieldDown(field: ConfigurationField): void;
    moveFieldBeforeSibling(field: ConfigurationField, sibling: ConfigurationField): void;
    moveFieldAtTheEnd(field: ConfigurationField): void;
};

export const buildFieldsReorderer = (
    selected_fields: Ref<ConfigurationField[]>,
): FieldsReorderer => {
    function isFirst(field: ConfigurationField): boolean {
        const field_index = selected_fields.value.indexOf(field);
        return field_index === 0;
    }

    function isLast(field: ConfigurationField): boolean {
        const field_index = selected_fields.value.indexOf(field);
        return field_index === selected_fields.value.length - 1;
    }

    function moveFieldUp(field: ConfigurationField): void {
        if (isFirst(field)) {
            return;
        }
        const field_index = selected_fields.value.indexOf(field);
        const previous_field_index = field_index - 1;
        selected_fields.value[field_index] = selected_fields.value[previous_field_index];
        selected_fields.value[previous_field_index] = field;
    }

    function moveFieldDown(field: ConfigurationField): void {
        if (isLast(field)) {
            return;
        }
        const field_index = selected_fields.value.indexOf(field);
        const next_field_index = field_index + 1;
        selected_fields.value[field_index] = selected_fields.value[next_field_index];
        selected_fields.value[next_field_index] = field;
    }

    function moveFieldAtTheEnd(field: ConfigurationField): void {
        const field_index = selected_fields.value.indexOf(field);
        if (field_index === -1) {
            return;
        }

        selected_fields.value.splice(field_index, 1);
        selected_fields.value.push(field);
    }

    function moveFieldBeforeSibling(field: ConfigurationField, sibling: ConfigurationField): void {
        const field_index = selected_fields.value.indexOf(field);
        const sibling_index = selected_fields.value.indexOf(sibling);
        if (sibling_index === -1 || field_index === -1) {
            return;
        }

        const new_field_index = field_index > sibling_index ? sibling_index : sibling_index - 1;

        selected_fields.value.splice(field_index, 1);
        selected_fields.value.splice(new_field_index, 0, field);
    }

    return {
        isFirstField: isFirst,
        isLastField: isLast,
        moveFieldUp,
        moveFieldDown,
        moveFieldBeforeSibling,
        moveFieldAtTheEnd,
    };
};

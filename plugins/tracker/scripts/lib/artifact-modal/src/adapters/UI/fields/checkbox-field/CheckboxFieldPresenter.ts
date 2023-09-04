/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { CheckboxFieldType, CheckboxFieldValue } from "./CheckboxFieldType";

export interface CheckboxFieldPresenter {
    readonly field_id: number;
    readonly field_label: string;
    readonly is_field_required: boolean;
    readonly checkbox_values: ReadonlyArray<CheckboxFieldValuePresenter>;
    readonly is_field_disabled: boolean;
}

export interface CheckboxFieldValuePresenter {
    readonly id: number;
    readonly label: string;
    readonly is_checked: boolean;
}

function buildValues(
    values: readonly CheckboxFieldValue[],
    bind_value_ids: ReadonlyArray<number | null>,
): readonly CheckboxFieldValuePresenter[] {
    return values.map((value, index) => ({
        ...value,
        is_checked: bind_value_ids[index] !== null,
    }));
}

export const CheckboxFieldPresenter = {
    fromField: (
        field: CheckboxFieldType,
        bind_value_ids: ReadonlyArray<number | null>,
        is_field_disabled: boolean,
    ): CheckboxFieldPresenter => ({
        field_id: field.field_id,
        field_label: field.label,
        is_field_required: field.required,
        checkbox_values: buildValues(field.values, bind_value_ids),
        is_field_disabled,
    }),
};

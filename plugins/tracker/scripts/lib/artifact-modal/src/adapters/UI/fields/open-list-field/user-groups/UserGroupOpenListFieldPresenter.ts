/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import type { UserGroupValueModelItem } from "../../../../../domain/fields/user-group-open-list-field/UserGroupOpenListValueModel";
import type { UserGroupOpenListFieldType } from "../../../../../domain/fields/user-group-open-list-field/UserGroupOpenListFieldType";

type UserGroupsOpenListFieldValuesCollection = ReadonlyArray<
    UserGroupValueModelItem & { readonly selected: boolean }
>;

export type UserGroupOpenListFieldPresenter = {
    readonly field_id: string;
    readonly label: string;
    readonly name: string;
    readonly hint: string;
    readonly required: boolean;
    readonly is_required_and_empty: boolean;
    readonly values: UserGroupsOpenListFieldValuesCollection;
};

const buildValues = (
    field_values: UserGroupValueModelItem[],
    selection: UserGroupValueModelItem[],
): UserGroupsOpenListFieldValuesCollection =>
    field_values.map((value) => ({
        ...value,
        selected: selection.some(
            (value_object) => value_object.id.toString() === value.id.toString(),
        ),
    }));

export const UserGroupOpenListFieldPresenter = {
    withSelectableValues: (
        field: UserGroupOpenListFieldType,
        selection: UserGroupValueModelItem[],
        field_values: UserGroupValueModelItem[],
    ): UserGroupOpenListFieldPresenter => ({
        field_id: `tracker_field_${field.field_id}`,
        label: field.label,
        name: field.name,
        hint: field.hint,
        required: field.required,
        is_required_and_empty: field.required && selection.length === 0,
        values: buildValues(field_values, selection),
    }),
};

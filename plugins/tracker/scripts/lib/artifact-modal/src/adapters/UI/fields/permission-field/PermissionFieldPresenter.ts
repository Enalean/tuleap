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

import type { PermissionFieldType } from "./PermissionFieldType";
import type { UserGroupRepresentation } from "@tuleap/plugin-tracker-rest-api-types";

export interface PermissionFieldPresenter {
    readonly field_id: number;
    readonly label: string;
    readonly user_groups: ReadonlyArray<UserGroupRepresentation>;
    readonly granted_groups: ReadonlyArray<string>;
    readonly is_field_disabled: boolean;
    readonly is_field_required: boolean;
    readonly is_used: boolean;
    readonly is_select_box_required: boolean;
    readonly is_select_box_disabled: boolean;
}

export const PermissionFieldPresenter = {
    fromField: (
        field: PermissionFieldType,
        granted_groups: ReadonlyArray<string>,
        is_field_disabled: boolean,
        is_used: boolean,
        is_select_box_required: boolean,
        is_select_box_disabled: boolean,
    ): PermissionFieldPresenter => ({
        field_id: field.field_id,
        label: field.label,
        is_field_required: field.required,
        user_groups: field.values.ugroup_representations,
        granted_groups,
        is_field_disabled,
        is_used,
        is_select_box_required,
        is_select_box_disabled,
    }),
};

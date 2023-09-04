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

import type { PermissionFieldValueModel } from "./PermissionFieldValueModel";
import type { PermissionFieldType } from "./PermissionFieldType";
import { PermissionFieldPresenter } from "./PermissionFieldPresenter";

export interface PermissionFieldControllerType {
    buildPresenter(): PermissionFieldPresenter;
    setIsFieldUsedByDefault(is_used_by_default: boolean): PermissionFieldPresenter;
    setGrantedGroups(granted_groups: string[]): PermissionFieldPresenter;
}

const isSelectBoxDisabled = (
    field: PermissionFieldType,
    value_model: PermissionFieldValueModel,
    is_field_disabled: boolean,
): boolean => {
    if (field.required) {
        return is_field_disabled;
    }

    return !value_model.value.is_used_by_default || is_field_disabled;
};

const isSelectBoxRequired = (
    field: PermissionFieldType,
    value_model: PermissionFieldValueModel,
): boolean => {
    if (value_model.value.is_used_by_default) {
        return true;
    }

    return field.required;
};

export const PermissionFieldController = (
    field: PermissionFieldType,
    value_model: PermissionFieldValueModel,
    is_field_disabled: boolean,
): PermissionFieldControllerType => ({
    buildPresenter(): PermissionFieldPresenter {
        return PermissionFieldPresenter.fromField(
            field,
            value_model.value.granted_groups,
            is_field_disabled,
            value_model.value.is_used_by_default,
            isSelectBoxRequired(field, value_model),
            isSelectBoxDisabled(field, value_model, is_field_disabled),
        );
    },
    setIsFieldUsedByDefault(is_used_by_default: boolean): PermissionFieldPresenter {
        value_model.value.is_used_by_default = is_used_by_default;
        if (!value_model.value.is_used_by_default) {
            value_model.value.granted_groups = [];
        }

        return this.buildPresenter();
    },
    setGrantedGroups(granted_groups: string[]): PermissionFieldPresenter {
        value_model.value.granted_groups = granted_groups;
        return this.buildPresenter();
    },
});

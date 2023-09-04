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

import type { ListFieldItem, ListFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import type { ColorName } from "@tuleap/plugin-tracker-constants";
import type { BindValueId } from "../../../../domain/fields/select-box-field/BindValueId";

export interface SelectBoxFieldPresenter {
    readonly field_id: number;
    readonly field_label: string;
    readonly is_multiple_select_box: boolean;
    readonly is_field_required: boolean;
    readonly is_field_disabled: boolean;
    readonly select_box_options: ReadonlyArray<SelectBoxOptionPresenter>;
}

type SelectBoxBaseOptionPresenter = {
    readonly id: string;
    readonly label: string;
};

type SelectBoxUserOptionPresenter = SelectBoxBaseOptionPresenter & {
    readonly avatar_url: string;
};

type SelectBoxColoredOptionPresenter = SelectBoxBaseOptionPresenter & {
    readonly value_color: ColorName;
};

export type SelectBoxOptionPresenter =
    | SelectBoxBaseOptionPresenter
    | SelectBoxUserOptionPresenter
    | SelectBoxColoredOptionPresenter;

const buildOptionPresenter = (value: ListFieldItem): SelectBoxOptionPresenter => {
    if ("user_reference" in value) {
        return {
            id: String(value.id),
            label: value.label,
            avatar_url: value.user_reference.avatar_url,
        };
    }

    if ("value_color" in value && value.value_color !== "") {
        return {
            id: String(value.id),
            label: value.label,
            value_color: value.value_color,
        };
    }

    return { id: String(value.id), label: value.label };
};

function buildSelectBoxOptions(
    field: ListFieldStructure,
    allowed_bind_value_ids: ReadonlyArray<BindValueId>,
): ReadonlyArray<SelectBoxOptionPresenter> {
    const presenters = [];
    for (const value of field.values) {
        if (!allowed_bind_value_ids.includes(value.id)) {
            continue;
        }
        presenters.push(buildOptionPresenter(value));
    }
    return presenters;
}

export const SelectBoxFieldPresenter = {
    fromField: (
        field: ListFieldStructure,
        allowed_bind_value_ids: ReadonlyArray<BindValueId>,
        is_field_disabled: boolean,
    ): SelectBoxFieldPresenter => ({
        field_id: field.field_id,
        field_label: field.label,
        is_multiple_select_box: field.type === "msb",
        is_field_required: field.required,
        is_field_disabled,
        select_box_options: buildSelectBoxOptions(field, allowed_bind_value_ids),
    }),
};

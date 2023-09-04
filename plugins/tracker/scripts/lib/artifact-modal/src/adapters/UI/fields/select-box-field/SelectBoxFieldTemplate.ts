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

import { html } from "hybrids";
import type { UpdateFunction } from "hybrids";
import type { SelectBoxField } from "./SelectBoxField";
import type { SelectBoxOptionPresenter } from "./SelectBoxFieldPresenter";
import type { BindValueId } from "../../../../domain/fields/select-box-field/BindValueId";

const getOptionsTemplates = (
    bind_value_ids: ReadonlyArray<BindValueId>,
    select_box_options: ReadonlyArray<SelectBoxOptionPresenter>,
): UpdateFunction<SelectBoxField>[] => {
    return select_box_options.map((option) => {
        const is_selected = bind_value_ids.some(
            (bind_value_id) => String(bind_value_id) === option.id,
        );
        if ("value_color" in option) {
            return html`<option
                value="${option.id}"
                selected="${is_selected}"
                data-color-value="${option.value_color}"
            >
                ${option.label}
            </option>`;
        }

        if ("avatar_url" in option) {
            return html`<option
                value="${option.id}"
                selected="${is_selected}"
                data-avatar-url="${option.avatar_url}"
            >
                ${option.label}
            </option>`;
        }

        return html`<option value="${option.id}" selected="${is_selected}">
            ${option.label}
        </option>`;
    });
};

export const onSelectChange = (host: SelectBoxField, event: Event): void => {
    const select = event.target;
    if (!(select instanceof HTMLSelectElement)) {
        return;
    }

    const selected_value_ids = Array.from(select.selectedOptions).map((option) => {
        if (option.value && !option.value.includes("_")) {
            return Number.parseInt(option.value, 10);
        }

        return option.value;
    });

    host.bind_value_ids = selected_value_ids;
    host.controller.setSelectedValue(selected_value_ids);
};

export const buildSelectBox = (host: SelectBoxField): UpdateFunction<SelectBoxField> => {
    if (host.field_presenter.is_multiple_select_box) {
        return html`
            <select
                multiple
                id="${"tracker_field_" + host.field_presenter.field_id}"
                required="${host.field_presenter.is_field_required}"
                disabled="${host.field_presenter.is_field_disabled}"
                onchange="${onSelectChange}"
                data-select="list-picker"
                data-test="multi-selectbox-field-select"
            >
                ${getOptionsTemplates(host.bind_value_ids, host.field_presenter.select_box_options)}
            </select>
        `;
    }

    return html`
        <select
            id="${"tracker_field_" + host.field_presenter.field_id}"
            required="${host.field_presenter.is_field_required}"
            disabled="${host.field_presenter.is_field_disabled}"
            onchange="${onSelectChange}"
            data-select="list-picker"
            data-test="selectbox-field-select"
        >
            ${getOptionsTemplates(host.bind_value_ids, host.field_presenter.select_box_options)}
        </select>
    `;
};

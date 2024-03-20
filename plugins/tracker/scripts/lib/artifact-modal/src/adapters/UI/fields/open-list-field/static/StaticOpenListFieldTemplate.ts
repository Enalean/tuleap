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

import { html } from "hybrids";
import type { UpdateFunction } from "hybrids";
import { getPleaseSelectAListItem } from "../../../../../gettext-catalog";
import type { InternalStaticOpenListField } from "./StaticOpenListField";

export const renderStaticOpenListField = (
    host: InternalStaticOpenListField,
): UpdateFunction<InternalStaticOpenListField> => {
    const form_element_classes = {
        "tlp-form-element": true,
        "tlp-form-element-disabled": host.disabled,
        "tlp-form-element-error": host.presenter.is_required_and_empty,
    };

    return html`
        <div class="${form_element_classes}" data-test="openlist-field">
            <label
                class="tlp-label"
                data-test="static-open-list-field-label"
                for="${host.presenter.field_id}"
            >
                ${host.presenter.label}
                ${host.presenter.required &&
                html`<i
                    class="fa-solid fa-asterisk"
                    aria-hidden="true"
                    data-test="static-open-list-field-required-flag"
                ></i>`}
            </label>
            <select
                multiple
                id="${host.presenter.field_id}"
                disabled="${host.disabled}"
                required="${host.presenter.required}"
                title="${host.presenter.hint}"
                style="width: 100%"
                class="tlp-select tuleap-artifact-modal-open-list-static"
                data-test="static-open-list-field-select"
                data-role="select-element"
            >
                ${host.presenter.values.map(
                    (field_value) => html`
                        <option value="${field_value.id}" selected="${field_value.selected}">
                            ${field_value.label}
                        </option>
                    `,
                )}
            </select>
            ${host.presenter.is_required_and_empty &&
            html`<p class="tlp-text-danger" data-test="static-open-list-field-error">
                ${getPleaseSelectAListItem()}
            </p>`}
        </div>
    `;
};

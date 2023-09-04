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

import type { UpdateFunction } from "hybrids";
import { define, html } from "hybrids";
import type { CheckboxFieldControllerType } from "./CheckboxFieldController";
import type { CheckboxFieldPresenter, CheckboxFieldValuePresenter } from "./CheckboxFieldPresenter";

export interface CheckboxField {
    readonly controller: CheckboxFieldControllerType;
    field_presenter: CheckboxFieldPresenter;
}

export type HostElement = CheckboxField & HTMLElement;

export const buildCheckbox = (
    host: CheckboxField,
    checkbox_field_value: CheckboxFieldValuePresenter,
    index: number,
): UpdateFunction<CheckboxField> => {
    const onCheckboxValueChange = (host: CheckboxField, event: Event): void => {
        const target = event.target;
        if (!(target instanceof HTMLInputElement)) {
            return;
        }

        host.field_presenter = host.controller.setCheckboxValue(
            checkbox_field_value.id,
            index,
            Boolean(target.checked),
        );
    };

    return html`
        <label
            for="${"cb_" + host.field_presenter.field_id + "_" + checkbox_field_value.id}"
            class="tlp-label tlp-checkbox"
            data-test="checkbox-field-value"
        >
            <input
                type="checkbox"
                id="${"cb_" + host.field_presenter.field_id + "_" + checkbox_field_value.id}"
                checked="${checkbox_field_value.is_checked}"
                onchange="${onCheckboxValueChange}"
                disabled="${host.field_presenter.is_field_disabled}"
                data-test="checkbox-field-input"
            />
            ${checkbox_field_value.label}
        </label>
    `;
};

export const CheckboxField = define<CheckboxField>({
    tag: "tuleap-artifact-modal-checkbox-field",
    controller: {
        set: (host, controller: CheckboxFieldControllerType) => {
            host.field_presenter = controller.buildPresenter();
            return controller;
        },
    },
    field_presenter: undefined,
    content: (host) => html`
        <div class="tlp-form-element">
            <label class="tlp-label">
                ${host.field_presenter.field_label}
                ${host.field_presenter.is_field_required &&
                html`<i class="fas fa-asterisk" aria-hidden="true"></i>`}
            </label>

            ${host.field_presenter.checkbox_values.map((value, index) =>
                buildCheckbox(host, value, index),
            )}
        </div>
    `,
});

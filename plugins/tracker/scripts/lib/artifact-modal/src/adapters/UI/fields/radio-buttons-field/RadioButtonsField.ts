/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import { define, dispatch, html } from "hybrids";
import { getNone } from "../../../../gettext-catalog";

export const RADIO_BUTTONS_NONE_VALUE = "100";

export interface RadioButtonsField {
    fieldId: number;
    label: string;
    name: string;
    required: boolean;
    disabled: boolean;
    values: Array<RadioButtonValue>;
    value: string;
}
type InternalRadioButtonsField = RadioButtonsField & {
    content(): HTMLElement;
};
export type HostElement = InternalRadioButtonsField & HTMLElement;

export interface RadioButtonValue {
    id: number;
    label: string;
}

export const onInput = (host: HostElement, event: Event): void => {
    if (!(event.target instanceof HTMLInputElement)) {
        return;
    }
    host.value = event.target.value;
    // Event type is value-changed to avoid interference with native "input" event
    // which will bubble since the element does not use shadow DOM
    dispatch(host, "value-changed", {
        detail: {
            field_id: host.fieldId,
            value: host.value,
        },
    });
};

export const RadioButtonsField = define<InternalRadioButtonsField>({
    tag: "tuleap-artifact-modal-radio-buttons-field",
    fieldId: 0,
    label: "",
    name: "",
    required: false,
    disabled: false,
    values: {
        get: (host, value = []) => value,
        set: (host, value) => [...value],
    },
    value: RADIO_BUTTONS_NONE_VALUE,
    content: (host) => html`
        <div class="tlp-form-element">
            <label class="tlp-label">
                ${host.label}${host.required && html`<i class="fas fa-asterisk"></i>`}
            </label>

            ${!host.required &&
            html`
                <label class="tlp-label tlp-radio" data-test="radiobutton-field-value">
                    <input
                        type="radio"
                        name="${host.name}"
                        oninput="${onInput}"
                        value="${RADIO_BUTTONS_NONE_VALUE}"
                        data-test="radiobutton-field-input"
                        required="${host.required}"
                        disabled="${host.disabled}"
                        checked="${host.value === RADIO_BUTTONS_NONE_VALUE}"
                    />
                    ${getNone()}
                </label>
            `}
            ${host.values.map(
                (value) => html`
                    <label class="tlp-label tlp-radio" data-test="radiobutton-field-value">
                        <input
                            type="radio"
                            name="${host.name}"
                            oninput="${onInput}"
                            value="${value.id}"
                            data-test="radiobutton-field-input"
                            required="${host.required}"
                            disabled="${host.disabled}"
                            checked="${host.value === String(value.id)}"
                        />
                        ${value.label}
                    </label>
                `
            )}
        </div>
    `,
});

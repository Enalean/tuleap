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
import type { UpdateFunction } from "hybrids";

export interface StringField {
    fieldId: number;
    label: string;
    required: boolean;
    disabled: boolean;
    value: string;
}
type InternalStringField = StringField & {
    render(): HTMLElement;
};
export type HostElement = InternalStringField & HTMLElement;

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

export const renderStringField = (
    host: InternalStringField,
): UpdateFunction<InternalStringField> => html`
    <div class="tlp-form-element">
        <label for="${"tracker_field_" + host.fieldId}" class="tlp-label">
            ${host.label}${host.required && html`<i class="fas fa-asterisk"></i>`}
        </label>
        <input
            type="text"
            class="tlp-input"
            data-test="string-field-input"
            oninput="${onInput}"
            value="${host.value}"
            required="${host.required}"
            disabled="${host.disabled}"
            id="${"tracker_field_" + host.fieldId}"
        />
    </div>
`;

export const StringField = define<InternalStringField>({
    tag: "tuleap-artifact-modal-string-field",
    fieldId: 0,
    label: "",
    required: false,
    disabled: false,
    value: "",
    render: renderStringField,
});

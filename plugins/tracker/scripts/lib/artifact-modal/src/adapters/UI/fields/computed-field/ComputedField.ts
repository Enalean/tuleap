/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
import { define, dispatch, html } from "hybrids";
import {
    getAutocomputeLabel,
    getAutoComputedValueLabel,
    getComputedValueLabel,
    getEmptyLabel,
    getEditButtonLabel,
} from "../../../../gettext-catalog";

export type AllowedValue = number | "";

export interface ComputedField {
    fieldId: number;
    label: string;
    required: boolean;
    disabled: boolean;
    autocomputed: boolean;
    manualValue: AllowedValue | null;
    value: number | null;
    content: () => HTMLElement;
}
export type HostElement = ComputedField & HTMLElement;

const cleanValue = (value: string): AllowedValue => {
    if (value === "") {
        return "";
    }
    const float_value = Number.parseFloat(value);
    return Number.isNaN(float_value) ? "" : float_value;
};

export const validateInput = (host: ComputedField, value: string | null): number | null => {
    if (value === null) {
        return null;
    }
    const float_value = Number.parseFloat(value);
    return Number.isNaN(float_value) ? null : float_value;
};

export const onInput = (host: HostElement, event: Event): void => {
    if (!(event.target instanceof HTMLInputElement)) {
        return;
    }
    host.manualValue = cleanValue(event.target.value);
    dispatch(host, "change", { bubbles: true });
    dispatch(host, "value-changed", {
        detail: {
            field_id: host.fieldId,
            autocomputed: false,
            manual_value: host.manualValue,
        },
    });
};

function switchToManualValue(host: HostElement): void {
    host.autocomputed = false;
    dispatch(host, "change", { bubbles: true });
    dispatch(host, "value-changed", {
        detail: {
            field_id: host.fieldId,
            autocomputed: host.autocomputed,
            manual_value: host.manualValue,
        },
    });
    const target = host.content();
    const manual_value_input = target.querySelector("[data-manual-value-input]");
    if (manual_value_input instanceof HTMLElement) {
        manual_value_input.focus();
    }
}

function switchToAutoComputed(host: HostElement): void {
    host.manualValue = "";
    host.autocomputed = true;
    dispatch(host, "change", { bubbles: true });
    dispatch(host, "value-changed", {
        detail: {
            field_id: host.fieldId,
            autocomputed: host.autocomputed,
            manual_value: host.manualValue,
        },
    });
}

export const getDisplayedValue = (host: ComputedField): string => {
    if (host.value !== null) {
        return String(host.value);
    }
    if (host.manualValue !== null) {
        return String(host.manualValue);
    }
    return getEmptyLabel();
};

const getAutoComputedTemplate = (host: ComputedField): UpdateFunction<ComputedField> => html`
    ${getDisplayedValue(host)}
    <span class="tlp-text-muted">${getAutoComputedValueLabel()}</span>
    <button
        type="button"
        class="tuleap-artifact-modal-field-computed-edit-button tlp-button-primary tlp-button-outline tlp-button-small"
        onclick="${switchToManualValue}"
        data-test="switch-to-manual"
    >
        <i class="fas fa-pencil-alt tlp-button-icon"></i>
        ${getEditButtonLabel()}
    </button>
`;

const getManualValueTemplate = (host: ComputedField): UpdateFunction<ComputedField> => html`
    <div class="tlp-form-element tlp-form-element-append">
        <input
            class="tlp-input"
            type="number"
            size="5"
            step="any"
            oninput="${onInput}"
            value="${host.manualValue}"
            required="${!host.autocomputed}"
            id="${"tracker_field_" + host.fieldId}"
            data-test="computed-field-input"
            data-manual-value-input
        />
        <button
            type="button"
            class="tlp-append tlp-button-primary tlp-button-outline"
            onclick="${switchToAutoComputed}"
            data-test="switch-to-auto"
        >
            <i class="fas fa-undo tlp-button-icon"></i>
            ${getAutocomputeLabel()}
        </button>
    </div>
    <p class="tlp-text-info">
        ${getComputedValueLabel()} ${host.value !== null ? host.value : getEmptyLabel()}
    </p>
`;

const getFieldTemplate = (host: ComputedField): UpdateFunction<ComputedField> => {
    if (host.disabled) {
        return html`${getDisplayedValue(host)}`;
    }
    if (host.autocomputed) {
        return getAutoComputedTemplate(host);
    }
    return getManualValueTemplate(host);
};

export const ComputedField = define<ComputedField>({
    tag: "tuleap-artifact-modal-computed-field",
    fieldId: 0,
    label: "",
    required: false,
    disabled: false,
    autocomputed: false,
    manualValue: { set: validateInput },
    value: { set: validateInput },
    content: (host) => html`
        <div class="tlp-form-element">
            <label class="tlp-label">
                ${host.label}${host.required && html`<i class="fas fa-asterisk"></i>`}
            </label>
            ${getFieldTemplate(host)}
        </div>
    `,
});

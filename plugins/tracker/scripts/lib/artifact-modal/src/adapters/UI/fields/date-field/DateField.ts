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

import { define, dispatch, html } from "hybrids";
import { getFieldDateRequiredAndEmptyMessage } from "../../../../gettext-catalog";

import type { DatePickerInitializerType } from "./DatePickerInitializer";
import type { EditableDateFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";

const DATE_PICKER_SIZE = 11;
const DATETIME_PICKER_SIZE = 19;

export interface DateField {
    readonly date_input_element: HTMLInputElement | null;
    readonly field: EditableDateFieldStructure;
    readonly isDisabled: boolean;
    readonly datePickerInitializer: DatePickerInitializerType;
    value: string;
}
type InternalDateField = DateField & {
    content(): HTMLElement;
};

export type HostElement = InternalDateField & HTMLElement;

type MapOfClasses = Record<string, boolean>;

const isRequiredAndEmpty = (host: DateField): boolean => host.field.required && host.value === "";

const getFieldSize = (field: EditableDateFieldStructure): number =>
    field.is_time_displayed ? DATETIME_PICKER_SIZE : DATE_PICKER_SIZE;

const getDateFormElementClasses = (host: DateField): MapOfClasses => ({
    "tlp-form-element": true,
    "tlp-form-element-disabled": host.isDisabled,
    "tlp-form-element-error": isRequiredAndEmpty(host),
});

export const onInput = (host: HostElement, event: Event): void => {
    if (!(event.target instanceof HTMLInputElement)) {
        return;
    }
    host.value = event.target.value;
    // Event type is value-changed to avoid interference with native "input" event
    // which will bubble since the element does not use shadow DOM
    dispatch(host, "value-changed", {
        detail: {
            field_id: host.field.field_id,
            value: host.value,
        },
    });
};

export const DateField = define<InternalDateField>({
    tag: "tuleap-artifact-modal-date-field",
    field: undefined,
    isDisabled: false,
    value: "",
    datePickerInitializer: {
        set: (host: DateField, initializer: DatePickerInitializerType): void => {
            if (host.date_input_element === null) {
                return;
            }

            initializer.initDatePicker(host.date_input_element);
        },
    },
    date_input_element: ({ content }) => {
        const input = content().querySelector(`[data-input=date-field-input]`);
        if (!(input instanceof HTMLInputElement)) {
            return null;
        }
        return input;
    },
    content: (host) => html`
        <div class="${getDateFormElementClasses(host)}" data-test="date-field">
            <label for="${"tracker_field_" + host.field.field_id}" class="tlp-label">
                ${host.field.label}
                ${host.field.required &&
                html`<i class="fas fa-asterisk" data-test="date-field-required-flag"></i>`}
            </label>
            <div class="tlp-form-element tlp-form-element-prepend">
                <span class="tlp-prepend"><i class="fas fa-calendar-alt"></i></span>
                <input
                    id="${"tracker_field_" + host.field.field_id}"
                    type="text"
                    class="tlp-input tlp-input-date"
                    data-test="date-field-input"
                    data-input="date-field-input"
                    size="${getFieldSize(host.field)}"
                    value="${host.value}"
                    disabled="${host.isDisabled}"
                    data-enabletime="${Boolean(host.field.is_time_displayed)}"
                    oninput="${onInput}"
                />
            </div>
            ${isRequiredAndEmpty(host) &&
            html`
                <p class="tlp-text-danger" data-test="date-field-required-and-empty-error">
                    ${getFieldDateRequiredAndEmptyMessage()}
                </p>
            `}
        </div>
    `,
});

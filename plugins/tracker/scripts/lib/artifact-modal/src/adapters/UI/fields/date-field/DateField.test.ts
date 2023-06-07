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

import { setCatalog } from "../../../../gettext-catalog";
import { DateField, onInput } from "./DateField";

import type { EditableDateFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import type { HostElement } from "./DateField";

function getField(data?: Partial<EditableDateFieldStructure>): EditableDateFieldStructure {
    return {
        field_id: 60,
        label: "Start date",
        ...data,
    } as unknown as EditableDateFieldStructure;
}

describe("DateField", () => {
    let target: ShadowRoot;

    beforeEach(() => {
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;

        setCatalog({ getString: (msgid) => msgid });
    });

    it("should display the field", () => {
        const field_data = { is_time_displayed: true, required: true };
        const host = {
            field: getField(field_data),
            isDisabled: false,
            value: "27-01-2022 12:30",
        } as unknown as HostElement;

        const update = DateField.content(host);

        update(host, target);

        const input = target.querySelector("[data-test=date-field-input]");
        if (!(input instanceof HTMLInputElement)) {
            throw new Error("Input not found in DateField");
        }

        expect(input.id).toBe("tracker_field_60");
        expect(input.size).toBe(19);
        expect(input.value).toBe("27-01-2022 12:30");
        expect(input.disabled).toBe(false);
        expect(input.hasAttribute("data-enabletime")).toBe(true);

        expect(target.querySelector("[data-test=date-field-required-flag]")).not.toBeNull();
    });

    it("When the field is required and no value has been provided, Then the field is in error", () => {
        const field_data = { is_time_displayed: true, required: true };
        const host = {
            field: getField(field_data),
            isDisabled: false,
            value: "",
        } as unknown as HostElement;

        const update = DateField.content(host);

        update(host, target);

        const form_element = target.querySelector("[data-test=date-field]");
        if (!(form_element instanceof HTMLElement)) {
            throw new Error("form element not found in DateField");
        }

        expect(form_element.classList.contains("tlp-form-element-error")).toBe(true);
        expect(
            target.querySelector("[data-test=date-field-required-and-empty-error]")
        ).not.toBeNull();
    });

    it("When the field is disabled, then the form-element and its input should be disabled", () => {
        const host = {
            field: getField(),
            isDisabled: true,
        } as unknown as HostElement;

        const update = DateField.content(host);

        update(host, target);

        const form_element = target.querySelector("[data-test=date-field]");
        const input = target.querySelector("[data-test=date-field-input]");
        if (!(form_element instanceof HTMLElement) || !(input instanceof HTMLInputElement)) {
            throw new Error("form element or input not found in DateField");
        }

        expect(form_element.classList.contains("tlp-form-element-disabled")).toBe(true);
        expect(input.disabled).toBe(true);
    });

    it.each([
        ["when the input is emptied", "empty string", ""],
        ["when the input has a value", "the date string", "28-01-2022 09:45"],
    ])(
        `%s, it dispatches a "value-changed" event with value as %s`,
        (when_statement, expected_statement, input_value) => {
            const host = {
                field: getField(),
                dispatchEvent: (): void => {
                    // Do nothing
                },
            } as unknown as HostElement;

            const dispatchEvent = jest.spyOn(host, "dispatchEvent");
            const inner_input = document.implementation.createHTMLDocument().createElement("input");
            inner_input.addEventListener("input", (event) => onInput(host, event));

            inner_input.value = input_value;
            inner_input.dispatchEvent(new InputEvent("input"));

            const event = dispatchEvent.mock.calls[0][0];
            if (!(event instanceof CustomEvent)) {
                throw new Error("Expected a CustomEvent");
            }
            expect(event.type).toBe("value-changed");
            expect(event.detail.field_id).toBe(60);
            expect(event.detail.value).toBe(input_value);
        }
    );
});

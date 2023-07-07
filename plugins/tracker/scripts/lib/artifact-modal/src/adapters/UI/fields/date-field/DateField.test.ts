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

import { selectOrThrow } from "@tuleap/dom";
import type { EditableDateFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import { setCatalog } from "../../../../gettext-catalog";
import type { HostElement } from "./DateField";
import { DateField, onInput } from "./DateField";

function getField(data?: Partial<EditableDateFieldStructure>): EditableDateFieldStructure {
    return {
        field_id: 60,
        label: "Start date",
        ...data,
    } as EditableDateFieldStructure;
}

describe("DateField", () => {
    let is_disabled: boolean, is_required: boolean, doc: Document;
    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
        doc = document.implementation.createHTMLDocument();
        is_disabled = false;
        is_required = false;
    });

    const getHost = (): HostElement => {
        const element = doc.createElement("div");
        const field_data = { is_time_displayed: true, required: is_required };
        return Object.assign(element, {
            field: getField(field_data),
            isDisabled: is_disabled,
            value: "27-01-2022 12:30",
        } as HostElement);
    };

    const render = (host: HostElement): ShadowRoot => {
        const update = DateField.content(host);
        update(host, host);
        return host as unknown as ShadowRoot;
    };

    it("should display the field", () => {
        is_required = true;
        const target = render(getHost());

        const input = selectOrThrow(target, "[data-test=date-field-input]", HTMLInputElement);
        expect(input.id).toBe("tracker_field_60");
        expect(input.size).toBe(19);
        expect(input.value).toBe("27-01-2022 12:30");
        expect(input.disabled).toBe(false);
        expect(input.hasAttribute("data-enabletime")).toBe(true);

        expect(target.querySelector("[data-test=date-field-required-flag]")).not.toBeNull();
    });

    it("When the field is required and no value has been provided, Then the field is in error", () => {
        is_required = true;
        const host = getHost();
        host.value = "";
        const target = render(host);

        const form_element = selectOrThrow(target, "[data-test=date-field]");

        expect(form_element.classList.contains("tlp-form-element-error")).toBe(true);
        expect(
            target.querySelector("[data-test=date-field-required-and-empty-error]")
        ).not.toBeNull();
    });

    it("When the field is disabled, then the form-element and its input should be disabled", () => {
        is_disabled = true;
        const target = render(getHost());

        const form_element = selectOrThrow(target, "[data-test=date-field]");
        const input = selectOrThrow(target, "[data-test=date-field-input]", HTMLInputElement);

        expect(form_element.classList.contains("tlp-form-element-disabled")).toBe(true);
        expect(input.disabled).toBe(true);
    });

    it.each([
        ["when the input is emptied", "empty string", ""],
        ["when the input has a value", "the date string", "28-01-2022 09:45"],
    ])(
        `%s, it dispatches a "value-changed" event with value as %s`,
        (when_statement, expected_statement, input_value) => {
            const host = getHost();
            const dispatchEvent = jest.spyOn(host, "dispatchEvent");
            const inner_input = doc.createElement("input");
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

    it(`dispatches a bubbling "change" event when its inner field is changed
        so that the modal shows a warning when closed`, () => {
        const host = getHost();
        const target = render(host);
        let is_bubbling = false;
        host.addEventListener("change", (event) => {
            is_bubbling = event.bubbles;
        });
        const input = selectOrThrow(target, "[data-test=date-field-input]", HTMLInputElement);
        input.value = "12-07-2029 01:43";
        input.dispatchEvent(new Event("change", { bubbles: true }));

        expect(is_bubbling).toBe(true);
    });
});

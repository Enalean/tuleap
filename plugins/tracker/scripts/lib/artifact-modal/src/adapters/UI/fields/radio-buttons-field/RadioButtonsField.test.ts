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

import { selectOrThrow } from "@tuleap/dom";
import type { HostElement, RadioButtonValue } from "./RadioButtonsField";
import { RadioButtonsField, onInput } from "./RadioButtonsField";
import { setCatalog } from "../../../../gettext-catalog";

describe(`RadioButtonsField`, () => {
    let doc: Document, inner_input: HTMLInputElement;
    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });

        doc = document.implementation.createHTMLDocument();
    });

    const getHost = (): HostElement => {
        const element = doc.createElement("div");
        const values: RadioButtonValue[] = [];
        return Object.assign(element, {
            fieldId: 1,
            label: "Radio Buttons Field",
            name: "",
            required: false,
            disabled: false,
            value: "100",
            values,
        } as HostElement);
    };

    it(`dispatches a "value-changed" event with the input's value when value changes`, () => {
        const host = getHost();
        const dispatchEvent = jest.spyOn(host, "dispatchEvent");
        inner_input = doc.createElement("input");
        inner_input.addEventListener("input", (event) => onInput(host, event));
        inner_input.value = "ichi";
        inner_input.dispatchEvent(new InputEvent("input"));

        const event = dispatchEvent.mock.calls[0][0];
        expect(event.type).toBe("value-changed");
        if (!(event instanceof CustomEvent)) {
            throw Error("Expected a custom event");
        }
        expect(event.detail.field_id).toBe(1);
        expect(event.detail.value).toBe("ichi");
    });

    const render = (host: HostElement): ShadowRoot => {
        const update = RadioButtonsField.content(host);
        update(host, host);
        return host as unknown as ShadowRoot;
    };

    it(`adds a "none" value when the field is not required`, () => {
        const host = getHost();
        host.required = false;
        const target = render(host);

        const none_input = target.querySelector(`[data-test=radiobutton-field-input][value="100"]`);
        expect(none_input).not.toBeNull();
    });

    it(`checks matching radio button when given a bind_value_id`, () => {
        const host = getHost();
        host.values = [
            { id: 505, label: "rondache" },
            { id: 704, label: "pearlitic" },
        ];
        host.value = "505";
        const target = render(host);

        const inputs = target.querySelectorAll<HTMLInputElement>(
            `[data-test=radiobutton-field-input]`,
        );
        const has_checked_input = Array.from(inputs).some(
            (input) => input.value === "505" && input.checked,
        );
        expect(has_checked_input).toBe(true);
    });

    it(`dispatches a bubbling "change" event when its inner radio buttons are changed
        so that the modal shows a warning when closed`, () => {
        const host = getHost();
        const target = render(host);
        let is_bubbling = false;
        host.addEventListener("change", (event) => {
            is_bubbling = event.bubbles;
        });
        const input = selectOrThrow(
            target,
            "[data-test=radiobutton-field-input]",
            HTMLInputElement,
        );
        input.select();
        input.dispatchEvent(new Event("change", { bubbles: true }));

        expect(is_bubbling).toBe(true);
    });
});

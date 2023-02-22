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

import type { HostElement } from "./RadioButtonsField";
import { RadioButtonsField, onInput } from "./RadioButtonsField";
import { setCatalog } from "../../gettext-catalog";

const getDocument = (): Document => document.implementation.createHTMLDocument();

describe(`RadioButtonsField`, () => {
    let dispatchEvent: jest.SpyInstance, host: HostElement, inner_input: HTMLInputElement;

    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });

        dispatchEvent = jest.fn();
        host = {
            fieldId: 1,
            label: "Radio Buttons Field",
            name: "",
            required: false,
            disabled: false,
            value: 100,
            values: [],
            dispatchEvent,
        } as unknown as HostElement;
        inner_input = getDocument().createElement("input");
        inner_input.addEventListener("input", (event) => onInput(host, event));
    });

    it(`dispatches a "value-changed" event with the input's value when value changes`, () => {
        inner_input.value = "ichi";
        inner_input.dispatchEvent(new InputEvent("input"));

        const event = dispatchEvent.mock.calls[0][0];
        expect(event.type).toBe("value-changed");
        expect(event.detail.field_id).toBe(1);
        expect(event.detail.value).toBe("ichi");
    });

    it(`adds a "none" value when the field is not required`, () => {
        const doc = document.implementation.createHTMLDocument();
        const target = doc.createElement("div") as unknown as ShadowRoot;
        host.required = false;
        const update = RadioButtonsField.content(host);
        update(host, target);

        const none_input = target.querySelector(`[data-test=radiobutton-field-input][value="100"]`);
        expect(none_input).not.toBeNull();
    });

    it(`checks matching radio button when given a bind_value_id`, () => {
        const doc = document.implementation.createHTMLDocument();
        const target = doc.createElement("text") as unknown as ShadowRoot;
        host.values = [
            { id: 505, label: "rondache" },
            { id: 704, label: "pearlitic" },
        ];
        host.value = "505";
        const update = RadioButtonsField.content(host);
        update(host, target);

        const inputs = target.querySelectorAll(
            `[data-test=radiobutton-field-input]`
        ) as NodeListOf<HTMLInputElement>;
        const has_checked_input = [...inputs].some(
            (input) => input.value === "505" && input.checked
        );
        expect(has_checked_input).toBe(true);
    });
});

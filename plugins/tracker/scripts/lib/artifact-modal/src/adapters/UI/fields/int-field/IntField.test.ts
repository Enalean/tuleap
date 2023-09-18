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
import type { HostElement } from "./IntField";
import { IntField, onInput } from "./IntField";

const FIELD_ID = 43;

describe(`IntField`, () => {
    let doc: Document;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    const getHost = (): HostElement => {
        const element = doc.createElement("div");
        return Object.assign(element, {
            fieldId: FIELD_ID,
            label: "Int Field",
            required: false,
            disabled: false,
            value: 0,
        } as HostElement);
    };

    it.each([
        ["when the input is emptied", "empty string", "", ""],
        ["when the input's value is a number", "the number", "13", 13],
        ["when the input's value is not a number", "empty string", "not a number", ""],
    ])(
        `%s, it dispatches a "value-changed" event with value as %s`,
        (when_statement, expected_statement, input_value, expected_manual_value) => {
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
            expect(event.detail.field_id).toBe(FIELD_ID);
            expect(event.detail.value).toBe(expected_manual_value);
        },
    );

    it(`does not use hybrids default setter (otherwise, zero is converted to empty string)`, () => {
        if (typeof IntField.value !== "object") {
            throw Error("value should have a setter");
        }
        expect(typeof IntField.value.set).toBe("function");
    });

    it(`dispatches a bubbling "change" event when its inner input is changed
        so that the modal shows a warning when closed`, () => {
        const host = getHost();
        const update = IntField.content(host);
        update(host, host);
        let is_bubbling = false;
        host.addEventListener("change", (event) => {
            is_bubbling = event.bubbles;
        });
        const input = selectOrThrow(host, "[data-test=int-field-input]", HTMLInputElement);
        input.value = "53";
        input.dispatchEvent(new Event("change", { bubbles: true }));

        expect(is_bubbling).toBe(true);
    });
});

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

import type { HostElement } from "./IntField";
import { onInput } from "./IntField";

const getDocument = (): Document => document.implementation.createHTMLDocument();

describe(`IntField`, () => {
    let dispatchEvent: jest.SpyInstance, host: HostElement, inner_input: HTMLInputElement;
    beforeEach(() => {
        dispatchEvent = jest.fn();
        host = {
            fieldId: 87,
            label: "Int Field",
            required: false,
            disabled: false,
            value: 0,
            dispatchEvent,
        } as unknown as HostElement;
        inner_input = getDocument().createElement("input");
        inner_input.addEventListener("input", (event) => onInput(host, event));
    });

    it(`when the input is emptied, it dispatches a "value-changed" event with empty string value`, () => {
        inner_input.value = "";
        inner_input.dispatchEvent(new InputEvent("input"));

        const event = dispatchEvent.mock.calls[0][0];
        expect(event.type).toEqual("value-changed");
        expect(event.detail.field_id).toEqual(87);
        expect(event.detail.value).toEqual("");
    });

    it(`when the input's value is a number, it dispatches a "value-changed" event with the number`, () => {
        inner_input.value = "13";
        inner_input.dispatchEvent(new InputEvent("input"));

        const event = dispatchEvent.mock.calls[0][0];
        expect(event.type).toEqual("value-changed");
        expect(event.detail.field_id).toEqual(87);
        expect(event.detail.value).toEqual(13);
    });

    it(`when the input's value is not a number, it dispatches a "value-changed" event with empty string value`, () => {
        inner_input.value = "not a number";
        inner_input.dispatchEvent(new InputEvent("input"));

        const event = dispatchEvent.mock.calls[0][0];
        expect(event.type).toEqual("value-changed");
        expect(event.detail.field_id).toEqual(87);
        expect(event.detail.value).toEqual("");
    });
});

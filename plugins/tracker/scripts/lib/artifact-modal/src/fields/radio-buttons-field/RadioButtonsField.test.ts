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

import type { RadioButtonsField } from "./RadioButtonsField";
import { onInput } from "./RadioButtonsField";

const getDocument = (): Document => document.implementation.createHTMLDocument();

describe(`RadioButtonsField`, () => {
    let dispatchEvent: jest.SpyInstance,
        host: Element & RadioButtonsField,
        inner_input: HTMLInputElement;

    beforeEach(() => {
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
        } as unknown as Element & RadioButtonsField;
        inner_input = getDocument().createElement("input");
        inner_input.addEventListener("input", (event) => onInput(host, event));
    });

    it(`dispatches a "value-changed" event with the input's value when value changes`, () => {
        inner_input.value = "ichi";
        inner_input.dispatchEvent(new InputEvent("input"));

        const event = dispatchEvent.mock.calls[0][0];
        expect(event.type).toEqual("value-changed");
        expect(event.detail.field_id).toEqual(1);
        expect(event.detail.value).toEqual("ichi");
    });
});

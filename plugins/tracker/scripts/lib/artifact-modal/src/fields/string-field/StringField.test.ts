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

import type { HostElement } from "./StringField";
import { onInput } from "./StringField";

const getDocument = (): Document => document.implementation.createHTMLDocument();

describe(`StringField`, () => {
    let dispatchEvent: jest.SpyInstance, host: HostElement, inner_input: HTMLInputElement;
    beforeEach(() => {
        dispatchEvent = jest.fn();
        host = {
            fieldId: 4,
            label: "String Field",
            required: false,
            disabled: false,
            value: "",
            dispatchEvent,
        } as unknown as HostElement;
        inner_input = getDocument().createElement("input");
        inner_input.addEventListener("input", (event) => onInput(host, event));
    });

    it(`on input, it dispatches a "value-changed" event with the input's value`, () => {
        inner_input.value = "lateen";
        inner_input.dispatchEvent(new InputEvent("input"));

        const event = dispatchEvent.mock.calls[0][0];
        expect(event.type).toBe("value-changed");
        expect(event.detail.field_id).toBe(4);
        expect(event.detail.value).toBe("lateen");
    });
});

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
import { onInput, StringField } from "./StringField";
import { selectOrThrow } from "@tuleap/dom";

describe(`StringField`, () => {
    let doc: Document, inner_input: HTMLInputElement;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    const getHost = (): HostElement => {
        const element = doc.createElement("div");
        return Object.assign(element, {
            fieldId: 4,
            label: "String Field",
            required: false,
            disabled: false,
            value: "",
        } as HostElement);
    };

    it(`on input, it dispatches a "value-changed" event with the input's value`, () => {
        const host = getHost();
        const dispatchEvent = jest.spyOn(host, "dispatchEvent");
        inner_input = doc.createElement("input");
        inner_input.addEventListener("input", (event) => onInput(host, event));
        inner_input.value = "lateen";
        inner_input.dispatchEvent(new InputEvent("input"));

        const event = dispatchEvent.mock.calls[0][0];
        expect(event.type).toBe("value-changed");
        if (!(event instanceof CustomEvent)) {
            throw Error("Expected a custom event");
        }
        expect(event.detail.field_id).toBe(4);
        expect(event.detail.value).toBe("lateen");
    });

    it(`dispatches a bubbling "change" event when its inner input is changed
        so that the modal shows a warning when closed`, () => {
        const host = getHost();
        const update = StringField.content(host);
        update(host, host);
        let is_bubbling = false;
        host.addEventListener("change", (event) => {
            is_bubbling = event.bubbles;
        });
        const input = selectOrThrow(host, "[data-test=string-field-input]", HTMLInputElement);
        input.value = "native";
        input.dispatchEvent(new Event("change", { bubbles: true }));

        expect(is_bubbling).toBe(true);
    });
});

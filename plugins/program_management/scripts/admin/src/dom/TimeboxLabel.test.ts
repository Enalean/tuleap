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

import { TimeboxLabel } from "./TimeboxLabel";
import { RetrieveElementStub } from "./RetrieveElementStub";

describe(`TimeboxLabel`, () => {
    let doc: Document;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    it(`gets the input's value`, () => {
        const input = doc.createElement("input");
        input.value = "lactonize";

        const label = TimeboxLabel.fromId(RetrieveElementStub.withElements(input), "some_id");
        expect(label.value).toBe("lactonize");
    });

    it(`disables the input and sets a class on its parent div`, () => {
        const input = doc.createElement("input");
        const form_element = doc.createElement("div");
        form_element.classList.add("tlp-form-element");
        form_element.append(input);

        const label = TimeboxLabel.fromId(RetrieveElementStub.withElements(input), "some_id");
        label.disable();

        expect(form_element.classList.contains("tlp-form-element-disabled")).toBe(true);
        expect(input.disabled).toBe(true);
    });

    it(`enables the input and removes a class on its parent div`, () => {
        const input = doc.createElement("input");
        input.disabled = true;
        const form_element = doc.createElement("div");
        form_element.classList.add("tlp-form-element", "tlp-form-element-disabled");
        form_element.append(input);

        const label = TimeboxLabel.fromId(RetrieveElementStub.withElements(input), "some_id");
        label.enable();

        expect(form_element.classList.contains("tlp-form-element-disabled")).toBe(false);
        expect(input.disabled).toBe(false);
    });

    it(`adds an event listener on "input" and calls the callback with the input's value`, () => {
        const input = doc.createElement("input");
        input.value = "Sicyonic";

        const label = TimeboxLabel.fromId(RetrieveElementStub.withElements(input), "some_id");
        const callback = jest.fn();
        label.addInputListener(callback);

        input.dispatchEvent(new InputEvent("input"));
        expect(callback).toHaveBeenCalledWith("Sicyonic");
    });

    it(`removes all input listeners`, () => {
        const input = doc.createElement("input");
        input.value = "sophiologic";

        const label = TimeboxLabel.fromId(RetrieveElementStub.withElements(input), "some_id");
        const callback = jest.fn();
        expect(callback).not.toHaveBeenCalled();
        label.addInputListener(callback);
        label.addInputListener(callback);
        label.removeInputListeners();

        input.dispatchEvent(new InputEvent("input"));
    });
});

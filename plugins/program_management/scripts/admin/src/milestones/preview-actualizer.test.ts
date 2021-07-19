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

import { RetrieveElementStub } from "../dom/RetrieveElementStub";
import { initPreview } from "./preview-actualizer";
import type { GettextProvider } from "../GettextProvider";
import { TimeboxLabel } from "../dom/TimeboxLabel";

describe(`preview-actualizer`, () => {
    let doc: Document,
        gettext_stub: GettextProvider,
        label_input_element: HTMLInputElement,
        sub_label_input_element: HTMLInputElement,
        label_input: TimeboxLabel,
        sub_label_input: TimeboxLabel;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        gettext_stub = {
            gettext: (source): string => source,
        };
        label_input_element = doc.createElement("input");
        label_input = TimeboxLabel.fromId(
            RetrieveElementStub.withElements(label_input_element),
            "some_id"
        );
        sub_label_input_element = doc.createElement("input");
        sub_label_input = TimeboxLabel.fromId(
            RetrieveElementStub.withElements(sub_label_input_element),
            "some_id"
        );
    });

    it(`changes the illustration's label when the label input is changed`, () => {
        const label_element = doc.createElement("span");
        const retriever = RetrieveElementStub.withElements(
            label_element,
            doc.createElement("span"),
            doc.createElement("span"),
            doc.createElement("span")
        );

        initPreview(retriever, gettext_stub, label_input, sub_label_input);
        label_input_element.value = "Release";
        label_input_element.dispatchEvent(new InputEvent("input"));
        expect(label_element.textContent).toEqual("Release");
    });

    it(`changes the illustration's new label and example labels when the sub label input is changed`, () => {
        const new_label_element = doc.createElement("span");
        const first_example_element = doc.createElement("span");
        const second_example_element = doc.createElement("span");
        const retriever = RetrieveElementStub.withElements(
            doc.createElement("span"),
            new_label_element,
            first_example_element,
            second_example_element
        );

        initPreview(retriever, gettext_stub, label_input, sub_label_input);
        sub_label_input_element.value = "release";
        sub_label_input_element.dispatchEvent(new InputEvent("input"));
        expect(new_label_element.textContent).toContain("release");
        expect(first_example_element.textContent).toEqual("release 2");
        expect(second_example_element.textContent).toEqual("release 1");
    });

    it(`defaults the labels when they are empty`, () => {
        const label_element = doc.createElement("span");
        const new_label_element = doc.createElement("span");
        const first_example_element = doc.createElement("span");
        const second_example_element = doc.createElement("span");
        const retriever = RetrieveElementStub.withElements(
            label_element,
            new_label_element,
            first_example_element,
            second_example_element
        );

        initPreview(retriever, gettext_stub, label_input, sub_label_input);
        expect(label_element.textContent).toEqual("Program Increments");
        expect(new_label_element.textContent).toContain("program increment");
        expect(first_example_element.textContent).toContain("program increment");
        expect(second_example_element.textContent).toContain("program increment");
    });
});

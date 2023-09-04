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
import { TimeboxLabel } from "../dom/TimeboxLabel";
import { PreviewActualizer } from "./PreviewActualizer";
import { RetrieveContainedNodeStub } from "../dom/RetrieveContainedNodeStub";

describe(`PreviewActualizer`, () => {
    let doc: Document,
        retriever: RetrieveContainedNodeStub,
        label_input_element: HTMLInputElement,
        sub_label_input_element: HTMLInputElement;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        label_input_element = doc.createElement("input");
        sub_label_input_element = doc.createElement("input");
    });

    it(`changes the illustration's label when the label input is changed`, () => {
        const timebox_label_element = doc.createElement("span");
        retriever = RetrieveContainedNodeStub.withNodes(
            timebox_label_element,
            doc.createElement("span"),
            doc.createElement("span"),
            doc.createElement("span"),
        );
        getActualizer().initTimeboxPreview();

        label_input_element.value = "Release";
        label_input_element.dispatchEvent(new InputEvent("input"));
        expect(timebox_label_element.textContent).toBe("Release");
    });

    it(`changes the illustration's new label and example labels when the sub label input is changed`, () => {
        const new_label_element = doc.createElement("span");
        const first_example_element = doc.createElement("span");
        const second_example_element = doc.createElement("span");
        retriever = RetrieveContainedNodeStub.withNodes(
            doc.createElement("span"),
            new_label_element,
            first_example_element,
            second_example_element,
        );
        getActualizer().initTimeboxPreview();

        sub_label_input_element.value = "release";
        sub_label_input_element.dispatchEvent(new InputEvent("input"));
        expect(new_label_element.textContent).toContain("release");
        expect(first_example_element.textContent).toBe("release 2");
        expect(second_example_element.textContent).toBe("release 1");
    });

    it(`defaults the labels when they are empty`, () => {
        const timebox_label_element = doc.createElement("span");
        const new_label_element = doc.createElement("span");
        const first_example_element = doc.createElement("span");
        const second_example_element = doc.createElement("span");
        retriever = RetrieveContainedNodeStub.withNodes(
            timebox_label_element,
            new_label_element,
            first_example_element,
            second_example_element,
        );
        label_input_element.value = "";
        sub_label_input_element.value = "";
        getActualizer().initTimeboxPreview();

        expect(timebox_label_element.textContent).toBe("Default Label");
        expect(new_label_element.textContent).toContain("default label lowercase");
        expect(first_example_element.textContent).toContain("default label lowercase");
        expect(second_example_element.textContent).toContain("default label lowercase");
    });

    it(`stops changing labels when told to`, () => {
        const timebox_label_element = doc.createElement("span");
        const new_label_element = doc.createElement("span");
        const first_example_element = doc.createElement("span");
        const second_example_element = doc.createElement("span");
        retriever = RetrieveContainedNodeStub.withNodes(
            timebox_label_element,
            new_label_element,
            first_example_element,
            second_example_element,
        );
        label_input_element.value = "Iterations";
        sub_label_input_element.value = "iteration";

        const actualizer = getActualizer();
        actualizer.initTimeboxPreview();
        actualizer.stopTimeboxPreview();
        label_input_element.value = "Sprint";
        label_input_element.dispatchEvent(new InputEvent("input"));
        sub_label_input_element.value = "sprint";
        sub_label_input_element.dispatchEvent(new InputEvent("input"));

        expect(timebox_label_element.textContent).toBe("Iterations");
        expect(new_label_element.textContent).toBe("New iteration");
        expect(first_example_element.textContent).toBe("iteration 2");
        expect(second_example_element.textContent).toBe("iteration 1");
    });

    function getActualizer(): PreviewActualizer {
        const gettext_stub = {
            gettext: (source: string): string => source,
        };
        const label_input = TimeboxLabel.fromId(
            RetrieveElementStub.withElements(label_input_element),
            "some_id",
        );
        const sub_label_input = TimeboxLabel.fromId(
            RetrieveElementStub.withElements(sub_label_input_element),
            "some_id",
        );
        return PreviewActualizer.fromContainerAndTimeboxLabels(
            gettext_stub,
            retriever,
            label_input,
            sub_label_input,
            "Default Label",
            "default label lowercase",
        );
    }
});

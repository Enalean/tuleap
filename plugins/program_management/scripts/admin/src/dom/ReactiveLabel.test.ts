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
import { ReactiveLabel } from "./ReactiveLabel";
import { RetrieveContainedNodeStub } from "./RetrieveContainedNodeStub";

const svg_namespace = "http://www.w3.org/2000/svg";

describe(`ReactiveLabel`, () => {
    let doc: Document, input_element: HTMLInputElement, label_input: TimeboxLabel;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        input_element = doc.createElement("input");
        label_input = TimeboxLabel.fromId(
            RetrieveElementStub.withElements(input_element),
            "some_id",
        );
    });

    it(`applies the given callback to the input value and changes its node's text content`, () => {
        const node = doc.createElementNS(svg_namespace, "tspan");
        const reactive_label = ReactiveLabel.fromSelectorAndTimeboxLabel(
            RetrieveContainedNodeStub.withNodes(node),
            "some_selector",
            label_input,
        );
        input_element.value = "iteration";
        reactive_label.reactOnLabelChange((text) => "New " + text);

        expect(node.textContent).toBe("New iteration");
    });

    describe(`when its TimeboxLabel has an input`, () => {
        it(`applies the given callback to the input value and changes its node's text content`, () => {
            const node = doc.createElementNS(svg_namespace, "tspan");
            const reactive_label = ReactiveLabel.fromSelectorAndTimeboxLabel(
                RetrieveContainedNodeStub.withNodes(node),
                "some_selector",
                label_input,
            );
            reactive_label.reactOnLabelChange((text) => "New " + text);
            input_element.value = "release";
            input_element.dispatchEvent(new InputEvent("input"));

            expect(node.textContent).toBe("New release");
        });

        it(`does not react when told to stop reacting`, () => {
            const node = doc.createElementNS(svg_namespace, "tspan");
            const reactive_label = ReactiveLabel.fromSelectorAndTimeboxLabel(
                RetrieveContainedNodeStub.withNodes(node),
                "some_selector",
                label_input,
            );
            input_element.value = "iteration";
            reactive_label.reactOnLabelChange((text) => "New " + text);
            reactive_label.stopReacting();
            input_element.value = "release";
            input_element.dispatchEvent(new InputEvent("input"));

            expect(node.textContent).toBe("New iteration");
        });
    });
});

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
import { ReactiveLabelCollection } from "./ReactiveLabelCollection";
import { RetrieveContainedNodeStub } from "./RetrieveContainedNodeStub";

const svg_namespace = "http://www.w3.org/2000/svg";

describe(`ReactiveLabelCollection`, () => {
    let doc: Document, input_element: HTMLInputElement, label_input: TimeboxLabel;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        input_element = doc.createElement("input");
        label_input = TimeboxLabel.fromId(
            RetrieveElementStub.withElements(input_element),
            "some_id",
        );
    });

    it(`applies the given callback to the input value and changes all the nodes' text content`, () => {
        const first_node = doc.createElementNS(svg_namespace, "tspan");
        const second_node = doc.createElementNS(svg_namespace, "tspan");
        const collection = ReactiveLabelCollection.fromSelectorAndTimeboxLabel(
            RetrieveContainedNodeStub.withNodes(first_node, second_node),
            "some_selector",
            label_input,
        );
        input_element.value = "iteration";
        collection.reactOnLabelChange((text, index, length) => text + " " + (length - index));

        expect(first_node.textContent).toBe("iteration 2");
        expect(second_node.textContent).toBe("iteration 1");
    });

    describe(`when its TimeboxLabel has an input`, () => {
        it(`applies the given callback to the input value and changes its node's text content`, () => {
            const first_node = doc.createElementNS(svg_namespace, "tspan");
            const second_node = doc.createElementNS(svg_namespace, "tspan");
            const collection = ReactiveLabelCollection.fromSelectorAndTimeboxLabel(
                RetrieveContainedNodeStub.withNodes(first_node, second_node),
                "some_selector",
                label_input,
            );
            collection.reactOnLabelChange((text, index, length) => text + " " + (length - index));
            input_element.value = "release";
            input_element.dispatchEvent(new InputEvent("input"));

            expect(first_node.textContent).toBe("release 2");
            expect(second_node.textContent).toBe("release 1");
        });

        it(`does not react when told to stop reacting`, () => {
            const first_node = doc.createElementNS(svg_namespace, "tspan");
            const second_node = doc.createElementNS(svg_namespace, "tspan");
            const collection = ReactiveLabelCollection.fromSelectorAndTimeboxLabel(
                RetrieveContainedNodeStub.withNodes(first_node, second_node),
                "some_selector",
                label_input,
            );
            input_element.value = "iteration";
            collection.reactOnLabelChange((text, index, length) => text + " " + (length - index));
            collection.stopReacting();
            input_element.value = "release";
            input_element.dispatchEvent(new InputEvent("input"));

            expect(first_node.textContent).toBe("iteration 2");
            expect(second_node.textContent).toBe("iteration 1");
        });
    });
});

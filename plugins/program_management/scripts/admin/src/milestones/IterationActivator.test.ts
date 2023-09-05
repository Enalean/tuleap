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

import { IterationActivator } from "./IterationActivator";
import { TimeboxLabel } from "../dom/TimeboxLabel";
import { RetrieveElementStub } from "../dom/RetrieveElementStub";
import { TrackerSelector } from "../dom/TrackerSelector";
import { PreviewActualizer } from "./PreviewActualizer";
import { RetrieveContainedNodeStub } from "../dom/RetrieveContainedNodeStub";

const svg_namespace = "http://www.w3.org/2000/svg";

describe(`IterationActivator`, () => {
    let doc: Document,
        iteration_label_element: HTMLInputElement,
        iteration_sub_label_element: HTMLInputElement,
        iteration_select_element: HTMLSelectElement,
        iteration_illustration_label: SVGTSpanElement;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        iteration_label_element = doc.createElement("input");
        iteration_sub_label_element = doc.createElement("input");
        iteration_select_element = doc.createElement("select");
        iteration_illustration_label = doc.createElementNS(svg_namespace, "tspan");
    });

    it(`when iteration selector has no selection, it disables iteration labels`, () => {
        const option = doc.createElement("option");
        option.value = "";
        iteration_select_element.add(option);
        iteration_select_element.selectedIndex = 0;

        getActivator().watchIterationSelection();

        expect(iteration_label_element.disabled).toBe(true);
        expect(iteration_sub_label_element.disabled).toBe(true);
    });

    describe(`when iteration selection changes`, () => {
        beforeEach(() => {
            iteration_select_element.insertAdjacentHTML(
                "afterbegin",
                `<option value=""></option><option value="101"></option><option value="125"></option>`,
            );
        });

        it(`when iteration tracker is selected, it enables iteration labels and illustration preview`, () => {
            iteration_select_element.selectedIndex = 0;

            getActivator().watchIterationSelection();
            iteration_select_element.selectedIndex = 2;
            iteration_select_element.dispatchEvent(new Event("change"));
            iteration_label_element.value = "iteration";
            iteration_label_element.dispatchEvent(new InputEvent("input"));

            expect(iteration_label_element.disabled).toBe(false);
            expect(iteration_sub_label_element.disabled).toBe(false);
            expect(iteration_illustration_label.textContent).toBe("iteration");
        });

        it(`when no selection, it disables iteration labels and illustration preview`, () => {
            iteration_select_element.selectedIndex = 2;

            getActivator().watchIterationSelection();
            iteration_select_element.selectedIndex = -1;
            iteration_select_element.dispatchEvent(new Event("change"));

            expect(iteration_label_element.disabled).toBe(true);
            expect(iteration_sub_label_element.disabled).toBe(true);
            iteration_label_element.value = "sprint";
            iteration_label_element.dispatchEvent(new InputEvent("input"));

            expect(iteration_illustration_label.textContent).not.toBe("sprint");
        });
    });

    function getActivator(): IterationActivator {
        const iteration_label = TimeboxLabel.fromId(
            RetrieveElementStub.withElements(iteration_label_element),
            "some_id",
        );
        const iteration_sub_label = TimeboxLabel.fromId(
            RetrieveElementStub.withElements(iteration_sub_label_element),
            "some_id",
        );
        const iteration_selector = TrackerSelector.fromId(
            RetrieveElementStub.withElements(iteration_select_element),
            "some_id",
        );
        const gettext_stub = {
            gettext: (source: string): string => source,
        };
        const actualizer = PreviewActualizer.fromContainerAndTimeboxLabels(
            gettext_stub,
            RetrieveContainedNodeStub.withNodes(
                iteration_illustration_label,
                doc.createElementNS(svg_namespace, "tspan"),
                doc.createElementNS(svg_namespace, "tspan"),
                doc.createElementNS(svg_namespace, "tspan"),
            ),
            iteration_label,
            iteration_sub_label,
            "Default Iterations",
            "default iteration lowercase",
        );
        return new IterationActivator(
            iteration_label,
            iteration_sub_label,
            iteration_selector,
            actualizer,
        );
    }
});

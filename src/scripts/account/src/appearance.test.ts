/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { initSelectBoxPreview } from "./appearance";

describe("appearance", () => {
    let doc: Document, select_box: HTMLSelectElement, svg: SVGElement;

    const SELECT_BOX_ID = "source-select-box";
    const OPTION_DATA_ATTRIBUTE = "data-bound-value";
    const OPTION_CLASSNAME = "select_box_option";
    const PREVIEW_ID = "preview";

    function getSourceSelectBox(): HTMLSelectElement {
        const select_box = document.createElement("select");
        select_box.setAttribute("id", SELECT_BOX_ID);

        const option_1 = document.createElement("option");
        option_1.value = "option-1";
        option_1.text = "option 1";
        option_1.selected = true;

        const option_2 = document.createElement("option");
        option_2.value = "option-2";
        option_2.text = "option 2";

        select_box.appendChild(option_1);
        select_box.appendChild(option_2);

        return select_box;
    }

    function getSvgElement(): SVGElement {
        const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
        const preview = document.createElementNS("http://www.w3.org/2000/svg", "g");
        preview.setAttribute("id", PREVIEW_ID);

        const preview_element_option_1 = document.createElementNS(
            "http://www.w3.org/2000/svg",
            "rect",
        );
        preview_element_option_1.setAttribute(OPTION_DATA_ATTRIBUTE, "option-1");
        preview_element_option_1.classList.add(OPTION_CLASSNAME);

        const preview_element_option_2 = document.createElementNS(
            "http://www.w3.org/2000/svg",
            "rect",
        );
        preview_element_option_2.setAttribute(OPTION_DATA_ATTRIBUTE, "option-2");
        preview_element_option_2.classList.add(OPTION_CLASSNAME);

        preview.appendChild(preview_element_option_1);
        preview.appendChild(preview_element_option_2);
        svg.appendChild(preview);

        return svg;
    }

    function getCurrentlyPreviewedElement(): SVGElement | null {
        const currently_previewed_elem_query = `.${OPTION_CLASSNAME}.shown`;
        const elements = svg.querySelectorAll(currently_previewed_elem_query);

        if (elements.length > 1) {
            throw new Error(
                "More than 1 preview elements are displayed, should be 1 or 0. Found: " +
                    elements.length,
            );
        }

        if (elements.length === 0) {
            return null;
        }

        const elem = elements[0];

        if (!(elem instanceof SVGElement)) {
            throw new Error("The previewed element is not a SVGElement, something must be wrong.");
        }

        return elem;
    }

    describe("Live previews", () => {
        beforeEach(() => {
            doc = document.implementation.createHTMLDocument();
            select_box = getSourceSelectBox();
            svg = getSvgElement();

            doc.body.appendChild(select_box);
            doc.body.appendChild(svg);
        });

        it("Inits the preview according to the selected value in the source <select>", () => {
            expect(getCurrentlyPreviewedElement()).toBeNull();

            initSelectBoxPreview(
                doc,
                `#${SELECT_BOX_ID}`,
                `#${PREVIEW_ID}`,
                `.${OPTION_CLASSNAME}`,
                OPTION_DATA_ATTRIBUTE,
            );

            const previewed_elem = getCurrentlyPreviewedElement();

            if (previewed_elem === null) {
                throw new Error("The preview has not been initialized.");
            }

            expect(previewed_elem.dataset.boundValue).toBe("option-1");
        });

        it("Listens to the changes of the source <select> and displays the right preview element", () => {
            initSelectBoxPreview(
                doc,
                `#${SELECT_BOX_ID}`,
                `#${PREVIEW_ID}`,
                `.${OPTION_CLASSNAME}`,
                OPTION_DATA_ATTRIBUTE,
            );

            const previewed_elem_after_init = getCurrentlyPreviewedElement();
            if (previewed_elem_after_init === null) {
                throw new Error("The preview has not been initialized.");
            }

            expect(previewed_elem_after_init.dataset.boundValue).toBe("option-1");

            select_box.selectedIndex = 1;
            select_box.dispatchEvent(new Event("change"));

            const currently_previewed_elem = getCurrentlyPreviewedElement();
            if (currently_previewed_elem === null) {
                throw new Error("No preview element found, something must be wrong.");
            }

            expect(currently_previewed_elem.dataset.boundValue).toBe("option-2");
        });
    });
});

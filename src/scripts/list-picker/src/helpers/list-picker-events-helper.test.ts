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

import { attachEvents } from "./list-picker-events-helper";
import * as dropdown_toggler from "./dropdown-helper";

describe("list-picker-events-helper", () => {
    let doc: HTMLDocument,
        source_select_box: HTMLSelectElement,
        component_root: Element,
        component_dropdown: Element;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        source_select_box = document.createElement("select");
        component_root = document.createElement("span");
        component_dropdown = document.createElement("span");
    });

    describe("Dropdown opening", () => {
        it("Opens the dropdown when I click on the component root, closes it when it is open", () => {
            const openListPicker = jest.spyOn(dropdown_toggler, "openListPicker");
            const closeListPicker = jest.spyOn(dropdown_toggler, "closeListPicker");

            attachEvents(doc, source_select_box, component_root, component_dropdown);

            component_root.dispatchEvent(new MouseEvent("click"));
            expect(openListPicker).toHaveBeenCalled();

            component_root.dispatchEvent(new MouseEvent("click"));
            expect(closeListPicker).toHaveBeenCalled();
        });

        it("Does not open the dropdown when I click on the component root while the source <select> is disabled", () => {
            const openListPicker = jest.spyOn(dropdown_toggler, "openListPicker");
            source_select_box.setAttribute("disabled", "disabled");

            attachEvents(doc, source_select_box, component_root, component_dropdown);
            component_root.dispatchEvent(new MouseEvent("click"));

            expect(openListPicker).not.toHaveBeenCalled();
        });
    });

    describe("Dropdown closure", () => {
        it("should close the dropdown when the escape key has been pressed", () => {
            const closeListPicker = jest.spyOn(dropdown_toggler, "closeListPicker");

            attachEvents(doc, source_select_box, component_root, component_dropdown);

            [{ key: "Escape" }, { key: "Esc" }, { keyCode: 27 }].forEach(
                (event_init: KeyboardEventInit) => {
                    doc.dispatchEvent(new KeyboardEvent("keyup", event_init));

                    expect(closeListPicker).toHaveBeenCalled();
                }
            );
        });

        it("should close the dropdown when the user clicks outside the list-picker", () => {
            const closeListPicker = jest.spyOn(dropdown_toggler, "closeListPicker");

            attachEvents(doc, source_select_box, component_root, component_dropdown);

            doc.dispatchEvent(new MouseEvent("click"));

            expect(closeListPicker).toHaveBeenCalled();
        });
    });
});

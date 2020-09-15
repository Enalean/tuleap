/*
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

import { createListPicker } from "./list-picker";

describe("ListPicker", () => {
    let doc: HTMLDocument;
    let select: HTMLSelectElement;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        select = document.createElement("select");
        select.id = "select-box";

        doc.body.appendChild(select);
    });

    it("should append a list-picker right after the source <select> in the same parent", () => {
        const parent_element = select.parentElement;

        if (!parent_element) {
            throw new Error("No parent element found");
        }

        createListPicker(select);

        const list_picker = doc.body.querySelector(".list-picker");
        if (list_picker === null) {
            throw new Error("List picker not found in DOM");
        }

        const list_picker_index = [...parent_element.children].indexOf(list_picker);

        const select_index = [...parent_element.children].indexOf(select);
        expect(select.classList).toContain("list-picker-hidden-accessible");
        expect(list_picker_index).toEqual(select_index + 1);
    });

    it("When the source <select> is disabled, then the list-picker should appear disabled", () => {
        select.disabled = true;
        createListPicker(select);

        const list_picker = doc.body.querySelector(".list-picker.list-picker-disabled");

        expect(list_picker).not.toBeNull();
    });
});

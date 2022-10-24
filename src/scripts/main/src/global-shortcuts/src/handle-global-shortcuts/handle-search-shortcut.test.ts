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

import { callSearchShortcut } from "./handle-search-shortcut";

describe("callSearchShortcut", () => {
    let button: HTMLElement;
    let doc: Document;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        button = doc.createElement("button");
        button.id = "switch-to-button";
        doc.body.appendChild(button);
    });

    it("Click on the button to open the switch to modal", () => {
        const event = new KeyboardEvent("keypress");
        const click = jest.spyOn(button, "click");

        callSearchShortcut(doc, event);

        expect(click).toHaveBeenCalled();
    });

    it("Prevents the default to not insert unwanted characters in the already focused filter input", () => {
        const event = new KeyboardEvent("keypress");
        const preventDefault = jest.spyOn(event, "preventDefault");

        callSearchShortcut(doc, event);

        expect(preventDefault).toHaveBeenCalled();
    });
});

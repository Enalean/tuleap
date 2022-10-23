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

import { callHelpShortcut } from "./handle-help-shortcut";

describe("callHelpShortcut", () => {
    let button: HTMLElement;
    let doc: Document;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        button = doc.createElement("button");
        button.id = "help-dropdomn-shortcuts";
        doc.body.appendChild(button);
    });

    it("Clicks on the button to open the help modal", () => {
        const click = jest.spyOn(button, "click");

        callHelpShortcut(doc);

        expect(click).toHaveBeenCalled();
    });
});

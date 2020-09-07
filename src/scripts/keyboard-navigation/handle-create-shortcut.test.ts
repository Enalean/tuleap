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

import { callCreateShortcut } from "./handle-create-shortcut";

describe("callCreateShortcut", () => {
    let button: HTMLElement;
    let doc: Document;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        button = doc.createElement("button");
        button.dataset.shortcutCreate = "true";
        doc.body.appendChild(button);
    });

    it("Clicks on the button to open the create menu", () => {
        const click = jest.spyOn(button, "click");

        callCreateShortcut(doc);

        expect(click).toHaveBeenCalled();
    });

    it("Preselects a create option", () => {
        const link = doc.createElement("a");
        link.dataset.shortcutCreateOption = "true";
        doc.body.appendChild(link);

        const focus = jest.spyOn(link, "focus");

        callCreateShortcut(doc);

        expect(focus).toHaveBeenCalled();
    });
});

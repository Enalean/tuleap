/**
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

import { clickOnElement, focusElement } from "./trigger-datashortcut-element";

describe("trigger-datashortcut-element", () => {
    let doc: Document;
    let button: HTMLButtonElement;
    const datashortcut = "datashortcut";

    let click: jest.SpyInstance;
    let focus: jest.SpyInstance;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        button = doc.createElement("button");
        button.setAttribute(datashortcut, "");
        doc.body.append(button);
    });

    describe("clickOnElement", () => {
        it("clicks on the first element that has the datashortcut attribute", () => {
            click = jest.spyOn(button, "click");
            clickOnElement(doc, `[${datashortcut}]`);

            expect(click).toHaveBeenCalled();
        });
    });

    describe("focusElement", () => {
        it("focuses the first element that has the datashortcut attribute", () => {
            focus = jest.spyOn(button, "focus");
            focusElement(doc, `[${datashortcut}]`);

            expect(focus).toHaveBeenCalled();
        });
    });
});

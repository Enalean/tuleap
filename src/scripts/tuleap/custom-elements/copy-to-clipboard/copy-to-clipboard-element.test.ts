/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { CopyToClipboardElement } from "./copy-to-clipboard-element";
import * as clipboard from "./clipboard";

describe("copy-to-clipboard element", () => {
    let writeTextToClipboardSpy: jest.SpyInstance;

    beforeAll(() => {
        window.customElements.define("copy-to-clipboard", CopyToClipboardElement);
    });

    beforeEach(() => {
        writeTextToClipboardSpy = jest.spyOn(clipboard, "writeTextToClipboard").mockResolvedValue();
    });

    it("copies value on click", () => {
        const container = document.createElement("div");
        container.innerHTML = '<copy-to-clipboard value="test"></copy-to-clipboard>';

        const clipboard_copy_button = container.querySelector("copy-to-clipboard");
        expect(clipboard_copy_button).not.toBeNull();
        if (clipboard_copy_button instanceof HTMLElement) {
            clipboard_copy_button.click();
        }

        expect(writeTextToClipboardSpy).toHaveBeenCalledWith("test");
    });

    it("does nothing if no attribute value has been defined", () => {
        document.createElement("copy-to-clipboard").click();

        expect(writeTextToClipboardSpy).not.toBeCalled();
    });

    it("copies value of a focused element when Enter is pressed", () => {
        const element = document.createElement("copy-to-clipboard");
        element.setAttribute("value", "test");

        element.dispatchEvent(new FocusEvent("focus"));
        const enter = new KeyboardEvent("keydown", { key: "Enter" });
        element.dispatchEvent(enter);
        element.dispatchEvent(new FocusEvent("blur"));
        element.dispatchEvent(enter);

        expect(writeTextToClipboardSpy).toHaveBeenNthCalledWith(1, "test");
    });
});

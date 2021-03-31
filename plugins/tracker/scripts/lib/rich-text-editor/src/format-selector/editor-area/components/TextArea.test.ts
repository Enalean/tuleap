/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

import { wrapTextArea } from "./TextArea";

const createDocument = (): Document => document.implementation.createHTMLDocument();

describe("TextArea", () => {
    let textarea: HTMLTextAreaElement;
    beforeEach(() => {
        const doc = createDocument();
        textarea = doc.createElement("textarea");
    });

    it.each([
        ["hide", true],
        ["show", false],
    ])(`will %s the textarea`, async (textarea_display, is_hidden) => {
        const presenter = {
            promise_of_preview: Promise.resolve("some content"),
            is_hidden,
            textarea: textarea,
        };

        wrapTextArea(presenter);
        await presenter.promise_of_preview;
        expect(presenter.textarea.classList.contains("rte-hide-textarea")).toBe(is_hidden);
    });
    it(`disables the textarea until the CommonMark is not interpreted`, async () => {
        const presenter = {
            promise_of_preview: Promise.resolve("some content"),
            is_hidden: true,
            textarea: textarea,
        };

        wrapTextArea(presenter);
        expect(presenter.textarea.disabled).toBe(true);
        await presenter.promise_of_preview;
        expect(presenter.textarea.disabled).toBe(false);
    });
});

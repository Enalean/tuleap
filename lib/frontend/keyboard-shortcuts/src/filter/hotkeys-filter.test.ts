/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach } from "vitest";
import { ignoreInputsEvenThoseInCustomElementsShadowDOM } from "./hotkeys-filter";

const createKeyboardEvent = (element: HTMLElement): KeyboardEvent => {
    return {
        target: element as EventTarget,
        composedPath: (): EventTarget[] => [element],
    } as KeyboardEvent;
};

describe("hotkeys-filter", () => {
    let doc: Document;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    it("Given a Keyboard event whose target is not an input, then it should return true", () => {
        const div = doc.createElement("div");
        const will_call_shortcuts = ignoreInputsEvenThoseInCustomElementsShadowDOM(
            createKeyboardEvent(div),
        );

        expect(will_call_shortcuts).toBe(true);
    });

    it("Given a Keyboard event whose target is an element with editable content, then it should return false", () => {
        const div = Object.assign(doc.createElement("div"), { isContentEditable: true });
        const will_call_shortcuts = ignoreInputsEvenThoseInCustomElementsShadowDOM(
            createKeyboardEvent(div),
        );

        expect(will_call_shortcuts).toBe(false);
    });

    it("Given a Keyboard event whose target is a writable input, then it should return false", () => {
        const input = doc.createElement("input");
        const will_call_shortcuts = ignoreInputsEvenThoseInCustomElementsShadowDOM(
            createKeyboardEvent(input),
        );

        expect(will_call_shortcuts).toBe(false);
    });

    it("Given a Keyboard event whose target is a readonly input, then it should return true", () => {
        const input = Object.assign(doc.createElement("input"), { readOnly: true });
        const will_call_shortcuts = ignoreInputsEvenThoseInCustomElementsShadowDOM(
            createKeyboardEvent(input),
        );

        expect(will_call_shortcuts).toBe(true);
    });

    it("Given a Keyboard event whose target is a writable textarea, then it should return false", () => {
        const textarea = doc.createElement("textarea");
        const will_call_shortcuts = ignoreInputsEvenThoseInCustomElementsShadowDOM(
            createKeyboardEvent(textarea),
        );

        expect(will_call_shortcuts).toBe(false);
    });

    it("Given a Keyboard event whose target is a readonly textarea, then it should return true", () => {
        const textarea = Object.assign(doc.createElement("textarea"), { readOnly: true });
        const will_call_shortcuts = ignoreInputsEvenThoseInCustomElementsShadowDOM(
            createKeyboardEvent(textarea),
        );

        expect(will_call_shortcuts).toBe(true);
    });

    it("Given a Keyboard event whose target is a select, then it should return false", () => {
        const select = doc.createElement("select");
        const will_call_shortcuts = ignoreInputsEvenThoseInCustomElementsShadowDOM(
            createKeyboardEvent(select),
        );

        expect(will_call_shortcuts).toBe(false);
    });
});

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
 */

import type { MockInstance } from "vitest";
import { beforeAll, beforeEach, describe, expect, it, vi } from "vitest";
import { SyntaxHighlightElement } from "./syntax-highlight-element";
import * as prism from "./prism";

vi.useFakeTimers();

type ObserverCallback = (entries: unknown[]) => Promise<void>;

describe("SyntaxHighlightElement", () => {
    const observe = vi.fn();
    const unobserve = vi.fn();
    let observerCallback: ObserverCallback;
    let syntaxHighlightElement: MockInstance;

    function createSyntaxHighlightElement(): SyntaxHighlightElement {
        const doc = document.implementation.createHTMLDocument();
        const container = document.createElement("div");

        container.innerHTML = `<tlp-syntax-highlighting>
            <pre><code class="language-php">class Foo {}</code></pre>
        </tlp-syntax-highlighting>`;

        const code_block = container.querySelector("tlp-syntax-highlighting");
        if (!(code_block instanceof SyntaxHighlightElement)) {
            throw Error("Unable to find just created element");
        }

        doc.body.appendChild(container);

        return code_block;
    }

    beforeAll(() => {
        window.customElements.define("tlp-syntax-highlighting", SyntaxHighlightElement);
    });

    beforeEach(() => {
        vi.resetAllMocks();
        syntaxHighlightElement = vi.spyOn(prism, "syntaxHighlightElement");
        const mockIntersectionObserver = vi.fn(
            class {
                constructor(callback: ObserverCallback) {
                    observerCallback = callback;
                }

                observe = observe;
                unobserve = unobserve;
            },
        );
        vi.stubGlobal("IntersectionObserver", mockIntersectionObserver);
    });

    it("observes if code block is in the viewport", () => {
        createSyntaxHighlightElement();

        expect(observe).toHaveBeenCalled();
        expect(syntaxHighlightElement).not.toHaveBeenCalled();
    });

    it("Syntax highlight the code block (and stops observing) whenever the block enters the viewport", async (): Promise<void> => {
        const mermaid_diagram = createSyntaxHighlightElement();

        await observerCallback([{ isIntersecting: true, target: mermaid_diagram }]);

        expect(syntaxHighlightElement).toHaveBeenCalled();
        expect(unobserve).toHaveBeenCalled();
    });

    it("Run again the syntax highlight when the code block change", async (): Promise<void> => {
        const mermaid_diagram = createSyntaxHighlightElement();

        await observerCallback([{ isIntersecting: true, target: mermaid_diagram }]);

        expect(syntaxHighlightElement).toHaveBeenCalledTimes(1);

        const code = mermaid_diagram.querySelector("code");
        if (!code) {
            throw new Error("Unable to find code");
        }
        code.textContent = "class Bar {}";

        await vi.runOnlyPendingTimersAsync();
        expect(syntaxHighlightElement).toHaveBeenCalledTimes(2);
    });
});

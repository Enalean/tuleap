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

import { SyntaxHighlightElement } from "./syntax-highlight-element";
const syntaxHighlightElement = jest.fn();
jest.mock("./prism", () => {
    return {
        syntaxHighlightElement,
    };
});

jest.useFakeTimers();

describe("SyntaxHighlightElement", () => {
    const windowIntersectionObserver = window.IntersectionObserver;

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
        syntaxHighlightElement.mockReset();
    });

    afterEach(() => {
        window.IntersectionObserver = windowIntersectionObserver;
    });

    it("observes if code block is in the viewport", () => {
        const observe = jest.fn();
        const mockIntersectionObserver = jest.fn();
        mockIntersectionObserver.mockReturnValue({
            observe,
        });
        window.IntersectionObserver = mockIntersectionObserver;

        createSyntaxHighlightElement();

        expect(observe).toHaveBeenCalled();
        expect(syntaxHighlightElement).not.toHaveBeenCalled();
    });

    it("Syntax highlight the code block (and stops observing) whenever the block enters the viewport", async (): Promise<void> => {
        const observe = (): void => {
            // mocking observe
        };
        const unobserve = jest.fn();
        const mockIntersectionObserver = jest.fn();
        mockIntersectionObserver.mockReturnValue({
            observe,
            unobserve,
        });
        window.IntersectionObserver = mockIntersectionObserver;

        const mermaid_diagram = createSyntaxHighlightElement();

        const observerCallback = mockIntersectionObserver.mock.calls[0][0];
        await observerCallback([{ isIntersecting: true, target: mermaid_diagram }]);

        expect(syntaxHighlightElement).toHaveBeenCalled();
        expect(unobserve).toHaveBeenCalled();
    });

    it("Run again the syntax highlight when the code block change", async (): Promise<void> => {
        const observe = (): void => {
            // mocking observe
        };
        const unobserve = jest.fn();
        const mockIntersectionObserver = jest.fn();
        mockIntersectionObserver.mockReturnValue({
            observe,
            unobserve,
        });
        window.IntersectionObserver = mockIntersectionObserver;

        const mermaid_diagram = createSyntaxHighlightElement();

        const observerCallback = mockIntersectionObserver.mock.calls[0][0];
        await observerCallback([{ isIntersecting: true, target: mermaid_diagram }]);

        expect(syntaxHighlightElement).toHaveBeenCalledTimes(1);

        const code = mermaid_diagram.querySelector("code");
        if (!code) {
            throw new Error("Unable to find code");
        }
        code.textContent = "class Bar {}";

        await jest.runOnlyPendingTimersAsync();
        expect(syntaxHighlightElement).toHaveBeenCalledTimes(2);
    });
});

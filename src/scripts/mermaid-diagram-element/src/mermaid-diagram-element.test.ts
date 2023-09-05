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

import type { RenderResult } from "mermaid";
import { describe, it, expect, vi, beforeEach, beforeAll, afterEach } from "vitest";
import { MermaidDiagramElement } from "./mermaid-diagram-element";

vi.mock("./id-generator", () => {
    return {
        generateMermaidElementId: vi.fn(),
    };
});

const render = vi.fn();
vi.mock("./mermaid-render", () => {
    return { render };
});

describe("MermaidDiagramElement", () => {
    const windowIntersectionObserver = window.IntersectionObserver;

    function createMermaidDiagramElement(): MermaidDiagramElement {
        const doc = document.implementation.createHTMLDocument();
        const container = document.createElement("div");

        container.innerHTML = `<tlp-mermaid-diagram><pre><code class="language-mermaid">
            classDiagram
                class Animal
                Vehicle <|-- Car
        </code></pre></tlp-mermaid-diagram>`;

        const mermaid_diagram = container.querySelector("tlp-mermaid-diagram");
        if (!(mermaid_diagram instanceof MermaidDiagramElement)) {
            throw Error("Unable to find just created element");
        }

        doc.body.appendChild(container);

        return mermaid_diagram;
    }

    beforeAll(() => {
        window.customElements.define("tlp-mermaid-diagram", MermaidDiagramElement);
    });

    beforeEach(() => {
        render.mockReset();
        render.mockImplementation(
            (id: string, txt: string): Promise<RenderResult> =>
                Promise.resolve({ svg: `<svg>${txt}</svg>` }),
        );
    });

    afterEach(() => {
        window.IntersectionObserver = windowIntersectionObserver;
    });

    it("displays a spinner while observing if mermaid block is in the viewport", () => {
        const observe = vi.fn();
        const mockIntersectionObserver = vi.fn();
        mockIntersectionObserver.mockReturnValue({
            observe,
        });
        window.IntersectionObserver = mockIntersectionObserver;

        const mermaid_diagram = createMermaidDiagramElement();

        expect(mermaid_diagram).toMatchSnapshot();
        expect(observe).toHaveBeenCalled();
        expect(render).not.toHaveBeenCalled();
    });

    it("Renders the diagram (and stops observing) whenever the mermaid block enters in the viewport", async () => {
        const observe = (): void => {
            // mocking observe
        };
        const unobserve = vi.fn();
        const mockIntersectionObserver = vi.fn();
        mockIntersectionObserver.mockReturnValue({
            observe,
            unobserve,
        });
        window.IntersectionObserver = mockIntersectionObserver;

        const mermaid_diagram = createMermaidDiagramElement();

        const observerCallback = mockIntersectionObserver.mock.calls[0][0];
        await observerCallback([{ isIntersecting: true, target: mermaid_diagram }]);

        expect(mermaid_diagram).toMatchSnapshot();
        expect(render).toHaveBeenCalled();
        expect(unobserve).toHaveBeenCalled();
    });

    it("On click, the diagram is magnified", async () => {
        const observe = (): void => {
            // mocking observe
        };
        const unobserve = vi.fn();
        const mockIntersectionObserver = vi.fn();
        mockIntersectionObserver.mockReturnValue({
            observe,
            unobserve,
        });
        window.IntersectionObserver = mockIntersectionObserver;

        const mermaid_diagram = createMermaidDiagramElement();

        const observerCallback = mockIntersectionObserver.mock.calls[0][0];
        await observerCallback([{ isIntersecting: true, target: mermaid_diagram }]);

        const backdrop = mermaid_diagram.querySelector("div");
        if (!backdrop) {
            throw Error("Unable to find the backdrop element");
        }
        backdrop.click();

        expect(backdrop.classList.contains("diagram-mermaid-backdrop-magnified")).toBe(true);
    });

    it("Once magnified, on click on backdrop, the diagram is back to normal", async () => {
        const observe = (): void => {
            // mocking observe
        };
        const unobserve = vi.fn();
        const mockIntersectionObserver = vi.fn();
        mockIntersectionObserver.mockReturnValue({
            observe,
            unobserve,
        });
        window.IntersectionObserver = mockIntersectionObserver;

        const mermaid_diagram = createMermaidDiagramElement();

        const observerCallback = mockIntersectionObserver.mock.calls[0][0];
        await observerCallback([{ isIntersecting: true, target: mermaid_diagram }]);

        const backdrop = mermaid_diagram.querySelector("div");
        if (!backdrop) {
            throw Error("Unable to find the backdrop element");
        }
        backdrop.click();
        backdrop.click();

        expect(backdrop.classList.contains("diagram-mermaid-backdrop-magnified")).toBe(false);
    });

    it("Once magnified, on click on close button, the diagram is back to normal", async () => {
        const observe = (): void => {
            // mocking observe
        };
        const unobserve = vi.fn();
        const mockIntersectionObserver = vi.fn();
        mockIntersectionObserver.mockReturnValue({
            observe,
            unobserve,
        });
        window.IntersectionObserver = mockIntersectionObserver;

        const mermaid_diagram = createMermaidDiagramElement();

        const observerCallback = mockIntersectionObserver.mock.calls[0][0];
        await observerCallback([{ isIntersecting: true, target: mermaid_diagram }]);

        const backdrop = mermaid_diagram.querySelector("div");
        if (!backdrop) {
            throw Error("Unable to find the backdrop element");
        }
        backdrop.click();

        const button = backdrop.querySelector(".diagram-mermaid-close-button");
        if (!(button instanceof HTMLButtonElement)) {
            throw Error("Unable to find the close button element");
        }
        button.click();

        expect(backdrop.classList.contains("diagram-mermaid-backdrop-magnified")).toBe(false);
    });
});

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

import { MermaidDiagramElement } from "./mermaid-diagram-element";
import mermaid from "mermaid";

jest.mock("./id-generator", () => {
    return {
        generateMermaidElementId: jest.fn(),
    };
});

jest.mock("./initialize-mermaid", () => {
    return {
        initializeMermaid: jest.fn(),
    };
});

jest.mock("mermaid", () => {
    return {
        render: jest.fn(),
    };
});

describe("MermaidDiagramElement", () => {
    const windowIntersectionObserver = window.IntersectionObserver;
    let render: jest.SpyInstance;

    function createMermaidDiagramElement(): MermaidDiagramElement {
        const doc = document.implementation.createHTMLDocument();
        const container = document.createElement("div");

        container.innerHTML = `<tlp-mermaid-diagram>
            classDiagram
                class Animal
                Vehicle <|-- Car
        </tlp-mermaid-diagram>`;

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
        render = jest
            .spyOn(mermaid, "render")
            .mockImplementation((id: string, txt: string) => `<svg>${txt}</svg>`);
    });

    afterEach(() => {
        window.IntersectionObserver = windowIntersectionObserver;
    });

    it("displays a spinner while observing if mermaid block is in the viewport", () => {
        const observe = jest.fn();
        const mockIntersectionObserver = jest.fn();
        mockIntersectionObserver.mockReturnValue({
            observe,
        });
        window.IntersectionObserver = mockIntersectionObserver;

        const mermaid_diagram = createMermaidDiagramElement();

        expect(mermaid_diagram).toMatchSnapshot();
        expect(observe).toHaveBeenCalled();
        expect(render).not.toHaveBeenCalled();
    });

    it("Renders the diagram (and stops observing) whenever the mermaid block enters in the viewport", () => {
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

        const mermaid_diagram = createMermaidDiagramElement();

        const observerCallback = mockIntersectionObserver.mock.calls[0][0];
        observerCallback([{ isIntersecting: true, target: mermaid_diagram }]);

        expect(mermaid_diagram).toMatchSnapshot();
        expect(render).toHaveBeenCalled();
        expect(unobserve).toHaveBeenCalled();
    });
});

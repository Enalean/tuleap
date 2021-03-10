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

import { replaceAllCodeBlocksByMermaidDiagramsInElement } from "./replace-code-blocks-by-mermaid-diagrams";
import mermaid from "mermaid";

jest.mock("./id-generator", () => {
    return {
        generateMermaidElementId: jest.fn(),
    };
});

jest.mock("mermaid", () => {
    return {
        initialize: jest.fn(),
        render: jest.fn(),
    };
});

describe("replaceAllCodeBlocksByMermaidDiagramsInElement", () => {
    const windowIntersectionObserver = window.IntersectionObserver;
    let initialize: jest.SpyInstance;
    let render: jest.SpyInstance;

    beforeEach(() => {
        initialize = jest.spyOn(mermaid, "initialize");
        render = jest
            .spyOn(mermaid, "render")
            .mockImplementation((id: string, txt: string) => `<svg>${txt}</svg>`);
    });

    afterEach(() => {
        window.IntersectionObserver = windowIntersectionObserver;
    });

    it("Does nothing if it does not find mermaid block", () => {
        const observe = jest.fn();
        const mockIntersectionObserver = jest.fn();
        mockIntersectionObserver.mockReturnValue({
            observe,
        });
        window.IntersectionObserver = mockIntersectionObserver;

        const doc = document.implementation.createHTMLDocument();
        doc.body.innerHTML = `<div>
            <pre>
                <code class="language-php">
                class Foo {}
                </code>
            </pre>
        </div>`;

        replaceAllCodeBlocksByMermaidDiagramsInElement(doc, doc.body);

        expect(observe).not.toHaveBeenCalled();
        expect(initialize).not.toHaveBeenCalled();
        expect(render).not.toHaveBeenCalled();
    });

    it("Initializes mermaid and observes if mermaid blocks are in the viewport", () => {
        const observe = jest.fn();
        const mockIntersectionObserver = jest.fn();
        mockIntersectionObserver.mockReturnValue({
            observe,
        });
        window.IntersectionObserver = mockIntersectionObserver;

        const doc = document.implementation.createHTMLDocument();
        doc.body.innerHTML = `<div>
            <pre>
                <code class="language-mermaid">
                class Foo {}
                </code>
            </pre>
            <p>Some text</p>
            <pre>
                <code class="language-mermaid">
                classDiagram
                    class Animal
                    Vehicle <|-- Car
                </code>
            </pre>
        </div>`;

        const codes = doc.querySelectorAll("code");

        replaceAllCodeBlocksByMermaidDiagramsInElement(doc, doc.body);

        expect(observe).toHaveBeenCalledWith(codes[0]);
        expect(observe).toHaveBeenCalledWith(codes[1]);
        expect(initialize).toHaveBeenCalledWith({
            startOnLoad: false,
            securityLevel: "strict",
            theme: "default",
            flowchart: {
                htmlLabels: false,
            },
            secure: [
                "secure",
                "securityLevel",
                "startOnLoad",
                "maxTextSize",
                "theme",
                "fontFamily",
            ],
        });
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

        const doc = document.implementation.createHTMLDocument();
        doc.body.innerHTML = `<div>
            <pre>
                <code class="language-mermaid">
                graph TD
                    Start --> Stop
                </code>
            </pre>
        </div>`;

        const code = doc.querySelector("code");

        replaceAllCodeBlocksByMermaidDiagramsInElement(doc, doc.body);

        const observerCallback = mockIntersectionObserver.mock.calls[0][0];
        observerCallback([{ isIntersecting: true, target: code }]);

        expect(doc.body).toMatchSnapshot();

        expect(render).toHaveBeenCalled();
        expect(unobserve).toHaveBeenCalledWith(code);
    });
});

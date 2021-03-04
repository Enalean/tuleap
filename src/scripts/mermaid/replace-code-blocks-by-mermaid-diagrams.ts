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

import mermaid from "mermaid";
import { generateMermaidElementId } from "./id-generator";

export function replaceAllCodeBlocksByMermaidDiagramsInElement(
    doc: Document,
    element: Element
): void {
    const mermaid_code_collection = element.querySelectorAll(".language-mermaid");
    if (mermaid_code_collection.length === 0) {
        return;
    }

    mermaid.initialize({
        startOnLoad: false,
        securityLevel: "strict",
        theme: "default",
        flowchart: {
            htmlLabels: false,
        },
        // Prevent users to screw up to much the page with nasty %%init%% directive
        secure: ["secure", "securityLevel", "startOnLoad", "maxTextSize", "theme", "fontFamily"],
    });

    const mermaid_observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                const code = entry.target;
                mermaid_observer.unobserve(code);
                replaceCodeBlockByMermaidDiagram(doc, code);
            }
        });
    });

    mermaid_code_collection.forEach((code) => {
        mermaid_observer.observe(code);
    });
}

function replaceCodeBlockByMermaidDiagram(doc: Document, code: Element): void {
    if (!code.textContent) {
        return;
    }

    const pre = code.parentElement;
    if (!pre || pre.tagName.toLowerCase() !== "pre") {
        return;
    }

    const container = doc.createElement("div");
    container.classList.add("diagram-mermaid", "diagram-mermaid-computing");

    pre.insertAdjacentElement("afterend", container);

    const source_wrapper = doc.createElement("div");
    source_wrapper.classList.add("diagram-mermaid-source-computing");
    pre.insertAdjacentElement("afterend", source_wrapper);
    source_wrapper.appendChild(pre);

    const spinner = doc.createElement("i");
    spinner.classList.add("fas", "fa-circle-notch", "fa-spin");
    source_wrapper.appendChild(spinner);

    const svg_code = mermaid.render(
        generateMermaidElementId(),
        code.textContent,
        undefined,
        container
    );

    // We have to trust mermaid code to not produce broken svg
    // If we start using DOMPurify, then it will remove elements that
    // can be used by mermaid / d3 to produce the graph.
    // eslint-disable-next-line no-unsanitized/property
    container.innerHTML = svg_code;

    // Replace the pre by the generated svg_element because we do not have
    // anymore need for code block, the diagram should live on its own.
    source_wrapper.remove();

    container.classList.remove("diagram-mermaid-computing");
}

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
import { initializeMermaid } from "./initialize-mermaid";

export class MermaidDiagramElement extends HTMLElement {
    private source_code = "";
    private source_wrapper: HTMLElement | null = null;
    private container: HTMLElement | null = null;

    public connectedCallback(): void {
        if (!this.textContent) {
            return;
        }

        this.source_code = this.textContent;
        this.textContent = "";

        this.source_wrapper = document.createElement("div");
        this.source_wrapper.classList.add("diagram-mermaid-source-computing");

        const pre = document.createElement("pre");
        pre.textContent = this.source_code;
        this.source_wrapper.appendChild(pre);

        const spinner = document.createElement("i");
        spinner.classList.add("fas", "fa-circle-notch", "fa-spin");
        spinner.setAttribute("aria-hidden", "true");
        this.source_wrapper.appendChild(spinner);

        this.textContent = "";
        this.appendChild(this.source_wrapper);

        this.container = document.createElement("div");
        this.container.classList.add("diagram-mermaid", "diagram-mermaid-computing");
        this.appendChild(this.container);

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    this.replaceCodeBlockByMermaidDiagram();
                    observer.unobserve(this);
                }
            });
        });

        observer.observe(this);
    }

    private replaceCodeBlockByMermaidDiagram(): void {
        if (!this.container || !this.source_wrapper) {
            return;
        }

        initializeMermaid();

        const svg_code = mermaid.render(
            generateMermaidElementId(),
            this.source_code,
            undefined,
            this.container
        );

        // We have to trust mermaid code to not produce broken svg
        // If we start using DOMPurify, then it will remove elements that
        // can be used by mermaid / d3 to produce the graph.
        // eslint-disable-next-line no-unsanitized/property
        this.container.innerHTML = svg_code;

        // Replace the pre by the generated svg_element because we do not have
        // anymore need for code block, the diagram should live on its own.
        this.removeChild(this.source_wrapper);

        this.container.classList.remove("diagram-mermaid-computing");
    }
}

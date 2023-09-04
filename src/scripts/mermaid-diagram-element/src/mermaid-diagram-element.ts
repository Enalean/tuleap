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

import type { PanZoom } from "panzoom";
import { generateMermaidElementId } from "./id-generator";

export class MermaidDiagramElement extends HTMLElement {
    private source_code = "";
    private source_wrapper: HTMLElement | null = null;
    private spinner: HTMLElement | null = null;
    private backdrop: HTMLElement | null = null;
    private container: HTMLElement | null = null;
    private svg: SVGElement | null = null;
    private panzoom_instance: PanZoom | null = null;
    private magnified_classname = "diagram-mermaid-backdrop-magnified";

    private toggle_magnified_listener: ((event: MouseEvent) => void) | null = null;
    private handle_keyup_listener: ((event: KeyboardEvent) => void) | null = null;

    public connectedCallback(): void {
        const pre = this.querySelector("pre");
        if (!pre) {
            return;
        }

        if (!pre.textContent) {
            return;
        }

        this.source_code = pre.textContent;

        this.source_wrapper = document.createElement("div");
        this.source_wrapper.classList.add("diagram-mermaid-source-computing");
        this.source_wrapper.appendChild(pre);

        this.spinner = document.createElement("i");
        this.spinner.classList.add("fas", "fa-circle-notch", "fa-spin");
        this.spinner.setAttribute("aria-hidden", "true");
        this.source_wrapper.appendChild(this.spinner);

        this.appendChild(this.source_wrapper);

        this.backdrop = document.createElement("div");
        this.appendChild(this.backdrop);

        this.container = document.createElement("div");
        this.container.classList.add("diagram-mermaid", "diagram-mermaid-computing");
        this.backdrop.appendChild(this.container);

        const observer = new IntersectionObserver(async (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    observer.unobserve(this);
                    await this.replaceCodeBlockByMermaidDiagram();
                }
            }
        });

        observer.observe(this);

        this.handle_keyup_listener = this.handleKeyUp.bind(this);
        document.addEventListener("keyup", this.handle_keyup_listener);
    }

    private async replaceCodeBlockByMermaidDiagram(): Promise<void> {
        if (!this.container || !this.source_wrapper || !this.backdrop) {
            return;
        }
        const { render } = await import("./mermaid-render");

        try {
            const rendered_result = await render(
                generateMermaidElementId(),
                this.source_code,
                this.container,
            );

            // We have to trust mermaid code to not produce broken svg
            // If we start using DOMPurify, then it will remove elements that
            // can be used by mermaid / d3 to produce the graph.
            // eslint-disable-next-line no-unsanitized/property
            this.container.innerHTML = rendered_result.svg;
            this.svg = this.container.querySelector("svg");

            // Replace the pre by the generated svg_element because we do not have
            // anymore need for code block, the diagram should live on its own.
            this.removeChild(this.source_wrapper);

            this.container.classList.remove("diagram-mermaid-computing");

            this.toggle_magnified_listener = this.toggleMagnified.bind(this);
            this.backdrop.addEventListener("click", this.toggle_magnified_listener);
        } catch (error) {
            if (this.spinner) {
                this.spinner.remove();
            }

            this.source_wrapper.classList.add("diagram-mermaid-source-computing-error");

            const is_mermaid_probably_failed_to_parse = Boolean(
                this.container.querySelector(".error-icon, .error-text"),
            );
            this.container.remove();

            if (is_mermaid_probably_failed_to_parse) {
                const alert = document.createElement("div");
                alert.classList.add(
                    "tlp-alert-danger",
                    "alert",
                    "alert-error",
                    "diagram-mermaid-source-computing-explanation",
                );
                alert.innerText = String(error);

                this.source_wrapper.appendChild(alert);
                this.source_wrapper.classList.add(
                    "diagram-mermaid-source-computing-error-with-details",
                );
            } else {
                throw error;
            }
        }
    }

    public disconnectedCallback(): void {
        if (this.backdrop && this.toggle_magnified_listener) {
            this.backdrop.removeEventListener("click", this.toggle_magnified_listener);
        }

        if (this.handle_keyup_listener) {
            document.removeEventListener("keyup", this.handle_keyup_listener);
        }
    }

    private handleKeyUp(event: KeyboardEvent): void {
        if (event.altKey) {
            return;
        }

        if (event.key !== "Escape") {
            return;
        }

        this.removeMagnified();
    }

    private toggleMagnified(event: MouseEvent): void {
        if (!this.backdrop || !this.container) {
            return;
        }

        if (this.backdrop.classList.contains(this.magnified_classname)) {
            this.removeMagnifiedOnMouseEvent(event);
        } else {
            this.addMagnified();
        }
    }

    private async addMagnified(): Promise<void> {
        if (!this.backdrop || !this.svg) {
            return;
        }

        if (!this.backdrop.querySelector(".diagram-mermaid-close-button")) {
            const close = document.createElement("button");
            close.classList.add("diagram-mermaid-close-button");
            close.type = "button";

            const close_icon = document.createElement("i");
            close_icon.classList.add("fas", "fa-times");
            close_icon.setAttribute("aria-hidden", "true");
            close.appendChild(close_icon);

            this.backdrop.appendChild(close);
        }

        this.backdrop.classList.add(this.magnified_classname, "diagram-mermaid-panzoom-loading");

        const { panzoom } = await import("./panzoom");

        this.panzoom_instance = panzoom(this.svg, {
            transformOrigin: { x: 0, y: 0 },
        });

        this.backdrop.classList.remove("diagram-mermaid-panzoom-loading");
    }

    private removeMagnifiedOnMouseEvent(event: MouseEvent): void {
        if (!event.target) {
            return;
        }

        if (!(event.target instanceof HTMLElement)) {
            return;
        }

        if (event.target instanceof HTMLElement && event.target.closest(".diagram-mermaid")) {
            return;
        }

        this.removeMagnified();
    }

    private removeMagnified(): void {
        if (this.panzoom_instance) {
            this.resetPanAndZoom();
            this.panzoom_instance.dispose();
        }

        if (this.backdrop) {
            this.backdrop.classList.remove(this.magnified_classname);
        }
    }

    private resetPanAndZoom(): void {
        if (this.panzoom_instance) {
            this.panzoom_instance.moveTo(0, 0);
            this.panzoom_instance.zoomAbs(0, 0, 1);
        }
    }
}

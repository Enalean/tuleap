/**
 * Copyright (c) 2021-Present Enalean
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

import { defineComponent, VueElement } from "vue";
import ProjectSidebar from "./ProjectSidebar.vue";
import fonts_style from "./font-faces.scss?inline";

function defineSidebarCustomElement(): CustomElementConstructor {
    return class extends VueElement {
        constructor() {
            // See https://github.com/vuejs/vue-next/blob/eb721d49c004abf5eff0a4e7da71bad904aa6bac/packages/runtime-dom/src/apiCustomElement.ts#L127
            // eslint-disable-next-line @typescript-eslint/consistent-type-assertions,@typescript-eslint/no-explicit-any
            super(defineComponent(ProjectSidebar as any));
        }

        override connectedCallback(): void {
            super.connectedCallback();
            this.addEventListener("sidebar-collapsed", this.sidebarCollapsedEvent);
        }

        override disconnectedCallback(): void {
            super.disconnectedCallback();
            this.removeEventListener("sidebar-collapsed", this.sidebarCollapsedEvent);
        }

        private sidebarCollapsedEvent(event: Event): void {
            event.stopImmediatePropagation();
            const is_collapsed = Boolean(this.hasAttribute("collapsed"));
            if (is_collapsed) {
                this.removeAttribute("collapsed");
            } else {
                this.setAttribute("collapsed", "");
            }
        }
    };
}

if (!window.customElements.get("tuleap-project-sidebar")) {
    const font_face_styles = document.createElement("style");
    font_face_styles.textContent = fonts_style.toString();
    document.head.appendChild(font_face_styles);

    window.customElements.define("tuleap-project-sidebar", defineSidebarCustomElement());
}

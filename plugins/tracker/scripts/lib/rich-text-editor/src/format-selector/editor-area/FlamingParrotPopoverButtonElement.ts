/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import jQuery from "jquery";

type KeyUpHandler = (event: KeyboardEvent) => void;

export class FlamingParrotPopoverButtonElement extends HTMLElement {
    private button?: HTMLButtonElement;
    private readonly escapeHandler: KeyUpHandler;

    public constructor() {
        super();
        this.escapeHandler = this.handleKeyUp.bind(this);
    }

    public connectedCallback(): void {
        const potential_button = this.querySelector("[data-button]");
        if (!(potential_button instanceof HTMLButtonElement)) {
            return;
        }
        this.button = potential_button;

        const popover_content = this.getPopoverRootElement();
        if (!popover_content) {
            return;
        }
        jQuery(this.button).popover({
            content: popover_content.outerHTML,
            trigger: "click",
            html: true,
            placement: "right",
        });
        this.ownerDocument.addEventListener("keyup", this.escapeHandler);
    }

    public disconnectedCallback(): void {
        this.ownerDocument.removeEventListener("keyup", this.escapeHandler);
        if (this.button) {
            jQuery(this.button).popover("destroy");
        }
    }

    private getPopoverRootElement(): Element | null {
        const popover_template = this.querySelector("[data-popover-content]");
        if (!(popover_template instanceof HTMLTemplateElement)) {
            return null;
        }
        return popover_template.content.querySelector("[data-popover-root]");
    }

    private handleKeyUp(event: KeyboardEvent): void {
        if (event.key !== "Escape") {
            return;
        }
        this.hidePopover();
    }

    override hidePopover(): void {
        if (this.button) {
            jQuery(this.button).popover("hide");
        }
    }
}

if (!window.customElements.get("fp-popover-button")) {
    window.customElements.define("fp-popover-button", FlamingParrotPopoverButtonElement);
}

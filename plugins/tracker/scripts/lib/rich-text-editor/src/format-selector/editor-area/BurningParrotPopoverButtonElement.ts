/*
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import type { Popover } from "@tuleap/tlp-popovers";
import { createPopover } from "@tuleap/tlp-popovers";

type KeyUpHandler = (event: KeyboardEvent) => void;

export class BurningParrotPopoverButtonElement extends HTMLElement {
    private popover_instance?: Popover;
    private readonly escapeHandler: KeyUpHandler;

    public constructor() {
        super();
        this.escapeHandler = this.handleKeyUp.bind(this);
    }

    public connectedCallback(): void {
        const button = this.querySelector("[data-button]");
        if (!(button instanceof HTMLButtonElement)) {
            return;
        }

        const popover_content = this.getPopoverRootElement();
        if (!popover_content) {
            return;
        }

        this.popover_instance = createPopover(button, popover_content, {
            trigger: "click",
            placement: "right",
        });
        this.ownerDocument.addEventListener("keyup", this.escapeHandler);
    }

    public disconnectedCallback(): void {
        this.ownerDocument.removeEventListener("keyup", this.escapeHandler);
        this.popover_instance?.destroy();
    }

    private getPopoverRootElement(): HTMLElement | null {
        const popover_content = this.querySelector("[data-popover-content]");
        if (!(popover_content instanceof HTMLElement)) {
            return null;
        }
        return popover_content;
    }

    private handleKeyUp(event: KeyboardEvent): void {
        if (event.key !== "Escape") {
            return;
        }
        this.hidePopover();
    }

    override hidePopover(): void {
        this.popover_instance?.hide();
    }
}

if (!window.customElements.get("bp-popover-button")) {
    window.customElements.define("bp-popover-button", BurningParrotPopoverButtonElement);
}

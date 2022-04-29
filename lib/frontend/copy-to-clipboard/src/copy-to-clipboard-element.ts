/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { writeTextToClipboard } from "./clipboard";

export class CopyToClipboardElement extends HTMLElement {
    constructor() {
        super();
        this.addEventListener("click", this.clickEvent);
        this.addEventListener("focus", this.focusEvent);
        this.addEventListener("blur", this.blurEvent);
    }

    public connectedCallback(): void {
        if (!this.hasAttribute("tabindex")) {
            this.setAttribute("tabindex", "0");
        }

        if (!this.hasAttribute("role")) {
            this.setAttribute("role", "button");
        }
    }

    public disconnectedCallback(): void {
        this.removeEventListener("click", this.clickEvent);
        this.removeEventListener("focus", this.focusEvent);
        this.removeEventListener("blur", this.blurEvent);
        this.removeEventListener("keydown", this.keydownEvent);
    }

    private async clickEvent(): Promise<void> {
        await this.copyValueToClipboard();
    }

    private focusEvent(): void {
        this.addEventListener("keydown", this.keydownEvent);
    }

    private blurEvent(): void {
        this.removeEventListener("keydown", this.keydownEvent);
    }

    private async keydownEvent(event: KeyboardEvent): Promise<void> {
        if (event.key === " " || event.key === "Enter") {
            event.preventDefault();
            await this.copyValueToClipboard();
        }
    }

    private async copyValueToClipboard(): Promise<void> {
        const value = this.getAttribute("value");
        if (value === null) {
            return;
        }

        await writeTextToClipboard(value);
        this.dispatchEvent(new CustomEvent("copied-to-clipboard", { bubbles: true }));
    }
}

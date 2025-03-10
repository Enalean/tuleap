/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import type { TemplateResult } from "lit";
import { html, LitElement, nothing } from "lit";

class CustomButton extends LitElement {
    isDisabled: boolean = false;

    override createRenderRoot(): this {
        return this;
    }

    override render(): TemplateResult {
        return html`
            <button
                class="prose-mirror-button tlp-button-secondary tlp-button-outline"
                title="My custom button"
                disabled="${this.isDisabled || nothing}"
            >
                <i class="fa-solid fa-fw fa-tlp-tuleap" aria-hidden="true"></i>
            </button>
        `;
    }
}

const CustomButtonTagName = "tuleap-prose-mirror-toolbar-custom-button";
const isCustomButton = (button: HTMLElement): button is HTMLButtonElement & CustomButton =>
    button.tagName === CustomButtonTagName.toUpperCase();

export const buildCustomButton = (is_disabled: boolean): HTMLElement => {
    const custom_button = document.createElement(CustomButtonTagName);
    if (!isCustomButton(custom_button)) {
        throw new Error("Unable to create a tuleap-prose-mirror-toolbar-custom-button");
    }

    custom_button.isDisabled = is_disabled;

    return custom_button;
};

if (!window.customElements.get(CustomButtonTagName)) {
    window.customElements.define(CustomButtonTagName, CustomButton);
}

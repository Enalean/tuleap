/**
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

import { createColorPicker } from "@tuleap/tlp-color-picker";

class ColorPickerWrapper extends HTMLElement {
    #current_color: string = "clockwork-orange";
    #is_unsupported_color: boolean = false;

    set current_color(current_color: string) {
        this.#current_color = current_color;
        this.update();
    }
    set is_unsupported_color(is_unsupported_color: boolean) {
        this.#is_unsupported_color = is_unsupported_color;
        this.update();
    }

    update(): void {
        this.innerHTML = "";
        const mount_point = document.createElement("div");
        this.appendChild(mount_point);

        createColorPicker(mount_point, {
            input_name: "color_name",
            input_id: "color_name",
            current_color: this.#current_color,
            is_unsupported_color: this.#is_unsupported_color,
        });
    }

    connectedCallback(): void {
        this.update();
    }
}

if (!window.customElements.get("tuleap-color-picker-wrapper")) {
    window.customElements.define("tuleap-color-picker-wrapper", ColorPickerWrapper);
}

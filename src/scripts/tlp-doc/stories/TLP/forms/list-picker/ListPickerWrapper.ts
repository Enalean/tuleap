/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { ListPicker, ListPickerOptions } from "@tuleap/list-picker";
import { createListPicker } from "@tuleap/list-picker";

class ListPickerWrapper extends HTMLElement {
    select_element: HTMLSelectElement | null = null;
    list_picker_instance: ListPicker | undefined = undefined;
    items_template_formatter: ListPickerOptions["items_template_formatter"] | undefined = undefined;

    static get observedAttributes(): string[] {
        return ["locale", "placeholder", "filterable"];
    }

    update(): void {
        this.select_element = this.querySelector("select");
        if (!this.select_element) {
            return;
        }
        this.list_picker_instance?.destroy();
        const options: ListPickerOptions = {
            locale: this.getAttribute("locale") ?? "en_US",
            is_filterable: this.hasAttribute("filterable"),
            placeholder: this.getAttribute("placeholder") ?? "Choose a value",
        };
        if (this.items_template_formatter !== undefined) {
            options.items_template_formatter = this.items_template_formatter;
        }
        this.list_picker_instance = createListPicker(this.select_element, options);
    }

    connectedCallback(): void {
        this.update();
    }

    attributeChangedCallback(): void {
        this.update();
    }
}

if (!window.customElements.get("tuleap-list-picker-wrapper")) {
    window.customElements.define("tuleap-list-picker-wrapper", ListPickerWrapper);
}

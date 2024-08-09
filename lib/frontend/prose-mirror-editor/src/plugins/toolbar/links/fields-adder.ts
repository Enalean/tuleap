/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
 *
 */

import type { TextField } from "./popover-link";

export function createAndInsertField(
    fields: Array<TextField>,
    container: HTMLElement,
    doc: Document,
): void {
    fields.forEach((text_field: TextField) => {
        const div_form_element = doc.createElement("div");
        div_form_element.className = "tlp-form-element";

        const div_label = doc.createElement("label");
        div_label.setAttribute("for", text_field.id);
        div_label.className = "tlp-label";
        div_label.textContent = text_field.label;

        if (text_field.required) {
            const icon = doc.createElement("i");
            icon.className = "fa-solid fa-asterisk";
            icon.setAttribute("aria-hidden", "true");
            div_label.appendChild(icon);
        }

        const input = doc.createElement("input");
        input.id = text_field.id;
        input.name = text_field.name;
        input.type = text_field.type;
        input.className = "tlp-input";
        input.placeholder = text_field.placeholder;
        input.value = text_field.value || "";
        if (text_field.required) {
            input.required = text_field.required;
        }
        if (text_field.pattern) {
            input.setAttribute("pattern", text_field.pattern);
        }
        if (text_field.focus) {
            input.focus();
        }

        div_form_element.appendChild(div_label);
        div_form_element.appendChild(input);

        container.appendChild(div_form_element);
    });
}

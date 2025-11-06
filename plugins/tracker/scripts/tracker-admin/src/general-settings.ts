/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

document.addEventListener("DOMContentLoaded", () => {
    document
        .querySelectorAll<HTMLElement>(".tracker-admin-general-settings-color-picker")
        .forEach((element) => {
            createColorPicker(element, {
                input_name: "tracker_color",
                input_id: "tracker_color",
                current_color: String(element.dataset.color),
                is_unsupported_color: false,
                is_no_color_allowed: false,
                on_color_change_callback(color: string) {
                    const parent = element.closest(".tlp-form-element");
                    if (!parent) {
                        return;
                    }
                    [...parent.classList.values()]
                        .filter((classname) => classname.startsWith("tlp-swatch-"))
                        .forEach((current) => {
                            parent.classList.remove(current);
                        });
                    parent.classList.add("tlp-swatch-" + color);
                },
            });
        });
});

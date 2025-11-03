/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import { getAttributeOrThrow } from "@tuleap/dom";
import { createColorPicker } from "@tuleap/tlp-color-picker";
import { createListPicker } from "@tuleap/list-picker";
import "../themes/colorpicker.scss";

document.addEventListener("DOMContentLoaded", () => {
    const mount_points = document.querySelectorAll(".vue-colorpicker-mount-point");

    for (const element of mount_points) {
        if (!(element instanceof HTMLElement)) {
            continue;
        }
        createColorPicker(element, {
            input_name: getAttributeOrThrow(element, "data-input-name"),
            input_id: getAttributeOrThrow(element, "data-input-id"),
            current_color: getAttributeOrThrow(element, "data-current-color"),
            is_unsupported_color: Boolean(
                getAttributeOrThrow(element, "data-is-unsupported-color"),
            ),
        });
    }

    const tracker_picker = document.querySelector<HTMLSelectElement>("#backlog_tracker_ids");
    if (tracker_picker !== null) {
        createListPicker(tracker_picker, {
            is_filterable: true,
        });
    }

    const user_group_picker = document.getElementById(
        "planning[PLUGIN_AGILEDASHBOARD_PLANNING_PRIORITY_CHANGE]",
    );
    if (user_group_picker instanceof HTMLSelectElement) {
        createListPicker(user_group_picker, {
            is_filterable: true,
        });
    }

    for (const element of document.querySelectorAll(".mapping-value-selector")) {
        if (!(element instanceof HTMLSelectElement)) {
            continue;
        }

        createListPicker(element, {});
    }
});

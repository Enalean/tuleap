/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { init } from "./options-manager";

document.addEventListener("DOMContentLoaded", () => {
    const computation_method_selector = document.getElementById("computation-method");
    const total_effort_selector = document.getElementById("total-effort");
    const remaining_effort_selector = document.getElementById("remaining-effort");
    const effort_based_config_section = document.getElementById("effort-based-config");

    if (
        !(computation_method_selector instanceof HTMLSelectElement) ||
        !(total_effort_selector instanceof HTMLSelectElement) ||
        !(remaining_effort_selector instanceof HTMLSelectElement) ||
        !(effort_based_config_section instanceof HTMLElement)
    ) {
        return;
    }
    init(
        computation_method_selector,
        effort_based_config_section,
        total_effort_selector,
        remaining_effort_selector
    );
});

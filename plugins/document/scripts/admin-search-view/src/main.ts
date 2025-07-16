/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { createListPicker } from "@tuleap/list-picker";

document.addEventListener("DOMContentLoaded", () => {
    const criteria = document.getElementById("list-picker-criteria");
    if (criteria instanceof HTMLSelectElement) {
        createListPicker(criteria, {});
    }
    const columns = document.getElementById("list-picker-columns");
    if (columns instanceof HTMLSelectElement) {
        createListPicker(columns, {});
    }
});

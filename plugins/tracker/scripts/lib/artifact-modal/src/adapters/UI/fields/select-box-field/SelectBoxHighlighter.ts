/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import type { HostElement } from "./SelectBoxField";

export const highlightSelectBoxField = (host: HostElement): void => {
    const list_picker = host.content().querySelector("[data-list-picker=wrapper]");
    if (!list_picker) {
        return;
    }

    list_picker.classList.add("select-box-allowed-values-updated-highlight");

    setTimeout(() => {
        list_picker.classList.remove("select-box-allowed-values-updated-highlight");
    }, 2000);
};

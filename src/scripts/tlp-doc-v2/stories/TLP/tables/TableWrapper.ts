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

import { filterInlineTable } from "@tuleap/filter-inline-table";

class TableWrapper extends HTMLElement {
    select_element: HTMLInputElement | null = null;

    connectedCallback(): void {
        this.select_element = this.querySelector("#filter-table");
        if (!this.select_element) {
            return;
        }
        filterInlineTable(this.select_element);
    }
}

if (!window.customElements.get("tuleap-table-wrapper")) {
    window.customElements.define("tuleap-table-wrapper", TableWrapper);
}

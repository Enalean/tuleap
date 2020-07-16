/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

interface TableFilter {
    filterTable(): void;
}
export function filterInlineTable(filter: Element): TableFilter;

export const EMPTY_STATE_CLASS_NAME = "tlp-table-empty-filter";
export const EMPTY_STATE_SHOWN_CLASS_NAME = "tlp-table-empty-filter-shown";
export const FILTERABLE_CELL_CLASS_NAME = "tlp-table-cell-filterable";
export const TABLE_SECTION_CLASS_NAME = "tlp-table-cell-section";
export const HIDDEN_ROW_CLASS_NAME = "tlp-table-row-hidden";
export const LAST_SHOWN_ROW_CLASS_NAME = "tlp-table-last-row";
export const HIDDEN_SECTION_CLASS_NAME = "tlp-table-tbody-hidden";

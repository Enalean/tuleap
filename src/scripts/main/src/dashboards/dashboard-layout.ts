/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

import { post } from "jquery";

const one_column_layout = "one-column";
const default_two_columns_layout = "two-columns";
const default_three_columns_layout = "three-columns";
const too_many_columns_layout = "too-many-columns";

const NumberOfColumnsForLayout = {
    1: ["one-column"],
    2: ["two-columns", "two-columns-small-big", "two-columns-big-small"],
    3: [
        "three-columns",
        "three-columns-small-big-small",
        "three-columns-big-small-small",
        "three-columns-small-small-big",
    ],
};

type NumberOfColumnsForLayoutTypes = keyof typeof NumberOfColumnsForLayout;

function isAvailableColumn(column_number: number): column_number is NumberOfColumnsForLayoutTypes {
    return Object.values(Object.keys(NumberOfColumnsForLayout)).includes(column_number.toString());
}

export function applyAutomaticLayout(row: HTMLElement): void {
    const current_layout = row.dataset.currentLayout;
    if (!current_layout) {
        throw new Error("");
    }
    const nb_columns = row.querySelectorAll(".dashboard-widgets-column").length;
    if (!isAvailableColumn(nb_columns)) {
        return;
    }
    if (isLayoutFitForNbColumns(current_layout, nb_columns)) {
        return;
    }

    const layout_name = getDefaultLayoutForNbColumns(nb_columns);
    extractAndSaveLayoutChoice(row, layout_name);
    applyLayoutToRow(row, layout_name);
}

export function applyLayout(row: HTMLElement, layout_name: string): void {
    applyLayoutToRow(row, layout_name);
    extractAndSaveLayoutChoice(row, layout_name);
}

function extractAndSaveLayoutChoice(row: HTMLElement, layout_name: string): void {
    const top_container = row.closest(".dashboard-widgets-container");
    if (!top_container) {
        throw new Error("No element with class dashboard-widgets-container");
    }
    const csrf_token_element = top_container.querySelector("input[name=challenge]");
    if (!(csrf_token_element instanceof HTMLInputElement)) {
        throw new Error("No CSRF token element");
    }
    const csrf_token = csrf_token_element.value;
    const line_id = row.dataset.lineId;
    if (!line_id) {
        throw new Error("No lineId data on row");
    }
    saveLayoutChoiceInBackend(csrf_token, line_id, layout_name);
}

function isLayoutFitForNbColumns(current_layout: string, nb_columns: number): boolean {
    if (nb_columns > 3 || !isAvailableColumn(nb_columns)) {
        return current_layout === too_many_columns_layout;
    }

    const fit_layouts = NumberOfColumnsForLayout[nb_columns];
    let found = false;
    fit_layouts.forEach(function (fit_layout) {
        if (current_layout === fit_layout) {
            found = true;
        }
    });

    return found;
}

function getDefaultLayoutForNbColumns(nb_columns: number): string {
    let layout_name = one_column_layout;
    if (nb_columns === 2) {
        layout_name = default_two_columns_layout;
    } else if (nb_columns === 3) {
        layout_name = default_three_columns_layout;
    } else if (nb_columns > 3) {
        layout_name = too_many_columns_layout;
    }

    return layout_name;
}

function applyLayoutToRow(row: HTMLElement, layout_classname: string): void {
    const current_layout = row.dataset.currentLayout;
    if (!current_layout) {
        throw new Error("No currentLayout in data of row");
    }
    row.classList.remove(current_layout);
    row.classList.add(layout_classname);
    row.dataset.currentLayout = layout_classname;
}

function saveLayoutChoiceInBackend(csrf_token: string, line_id: string, layout_name: string): void {
    const params = {
        action: "edit-widget-line",
        challenge: csrf_token,
        "line-id": line_id,
        layout: layout_name,
    };

    post(window.location.href, params);
}

/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

export { applyAutomaticLayout, applyLayout };

var one_column_layout = "one-column";
var default_two_columns_layout = "two-columns";
var default_three_columns_layout = "three-columns";
var too_many_columns_layout = "too-many-columns";

var number_of_columns_for_layout = {
    1: ["one-column"],
    2: ["two-columns", "two-columns-small-big", "two-columns-big-small"],
    3: [
        "three-columns",
        "three-columns-small-big-small",
        "three-columns-big-small-small",
        "three-columns-small-small-big",
    ],
};

function applyAutomaticLayout(row) {
    var current_layout = row.dataset.currentLayout;
    var nb_columns = row.querySelectorAll(".dashboard-widgets-column").length;

    if (isLayoutFitForNbColumns(current_layout, nb_columns)) {
        return;
    }

    var top_container = row.closest(".dashboard-widgets-container");
    var csrf_token = top_container.querySelector("input[name=challenge]").value;
    var line_id = row.dataset.lineId;
    var layout_name = getDefaultLayoutForNbColumns(nb_columns);

    applyLayoutToRow(row, layout_name);
    saveLayoutChoiceInBackend(csrf_token, line_id, layout_name);
}

function applyLayout(row, layout_name) {
    var top_container = row.closest(".dashboard-widgets-container");
    var csrf_token = top_container.querySelector("input[name=challenge]").value;
    var line_id = row.dataset.lineId;

    applyLayoutToRow(row, layout_name);
    saveLayoutChoiceInBackend(csrf_token, line_id, layout_name);
}

function isLayoutFitForNbColumns(current_layout, nb_columns) {
    if (nb_columns > 3) {
        return current_layout === too_many_columns_layout;
    }

    var fit_layouts = number_of_columns_for_layout[nb_columns];
    var found = false;
    fit_layouts.forEach(function (fit_layout) {
        if (current_layout === fit_layout) {
            found = true;
            return;
        }
    });

    return found;
}

function getDefaultLayoutForNbColumns(nb_columns) {
    var layout_name = one_column_layout;
    if (nb_columns === 2) {
        layout_name = default_two_columns_layout;
    } else if (nb_columns === 3) {
        layout_name = default_three_columns_layout;
    } else if (nb_columns > 3) {
        layout_name = too_many_columns_layout;
    }

    return layout_name;
}

function applyLayoutToRow(row, layout_classname) {
    var current_layout = row.dataset.currentLayout;
    row.classList.remove(current_layout);
    row.classList.add(layout_classname);
    row.dataset.currentLayout = layout_classname;
}

function saveLayoutChoiceInBackend(csrf_token, line_id, layout_name) {
    var params = {
        action: "edit-widget-line",
        challenge: csrf_token,
        "line-id": line_id,
        layout: layout_name,
    };

    post(window.location.href, params);
}

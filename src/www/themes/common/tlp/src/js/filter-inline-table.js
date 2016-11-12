/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

"use strict";

var tlp = tlp || {};

tlp.filterInlineTable = function filterInlineTable(filter) {
    var target_table = getTargetTable(filter);

    filter.addEventListener("keyup", handleEscape);
    filter.addEventListener("input", filterTable);

    function getTargetTable(filter) {
        var target_table_id, target_table;

        target_table_id = filter.dataset.targetTableId;
        if (! target_table_id) {
            throw "Filter input does not have data-target-table-id attribute";
        }

        target_table = document.getElementById(target_table_id);
        if (! target_table) {
            throw "Filter input attribute references an unknown table \"" + target_table_id + '"';
        }

        return target_table;
    }

    function handleEscape(event) {
        var ESC_KEYCODE = 27;

        if (event.keyCode === ESC_KEYCODE) {
            filter.value = "";
            filterTable();
        }
    }

    function filterTable() {
        var nb_displayed = toggleLines();

        toggleEmptyState(nb_displayed);
    }

    function toggleLines() {
        var body_margin         = + document.body.style.marginBottom.replace("px", ""),
            search              = filter.value.toUpperCase(),
            lines               = target_table.querySelectorAll("tbody > tr:not(.tlp-table-empty-filter)"),
            nb_displayed        = lines.length,
            last_line_displayed = null;

        [].forEach.call(lines, function (line) {
            var should_be_displayed = shouldTheLineBeDisplayed(line, search),
                was_hidden          = line.classList.contains('tlp-table-row-hidden');

            line.classList.remove('tlp-table-last-row');

            if (should_be_displayed) {
                line.classList.remove('tlp-table-row-hidden');

                if (was_hidden) {
                    body_margin -= line.offsetHeight;
                }

                last_line_displayed = line;
            } else {
                body_margin += line.offsetHeight;
                line.classList.add('tlp-table-row-hidden');
                nb_displayed --;
            }
            document.body.style.marginBottom = body_margin + "px";
        });

        if (last_line_displayed) {
            last_line_displayed.classList.add('tlp-table-last-row');
        }

        return nb_displayed;
    }

    function shouldTheLineBeDisplayed(line, search) {
        var should_be_displayed = false,
            filterable_cells    = line.querySelectorAll(".tlp-table-cell-filterable");

        for (var i = 0, n = filterable_cells.length; i < n; i ++) {
            var cell_content = filterable_cells[i].textContent.toUpperCase();

            if (cell_content.indexOf(search) !== - 1) {
                should_be_displayed = true;
                break;
            }
        }

        return should_be_displayed;
    }

    function toggleEmptyState(nb_displayed) {
        var empty_state = target_table.querySelector("tbody > tr.tlp-table-empty-filter");

        if (empty_state) {
            if (nb_displayed < 1) {
                empty_state.classList.add('tlp-table-empty-filter-shown');
            } else {
                empty_state.classList.remove('tlp-table-empty-filter-shown');
            }
        }
    }
}

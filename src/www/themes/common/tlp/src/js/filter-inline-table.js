/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

export default filterInlineTable;

const reset_search_term = '';

function filterInlineTable(filter) {
    const target_table = getTargetTable(filter);

    filter.addEventListener("keyup", handleEscape);
    filter.addEventListener("input", filterTable);

    function handleEscape(event) {
        const ESC_KEYCODE = 27;

        if (event.keyCode === ESC_KEYCODE) {
            filter.value = reset_search_term;
            filterTable();
        }
    }

    function filterTable() {
        let nb_displayed;

        const body_margin = parseInt(document.body.style.marginBottom, 10),
            search        = filter.value.toUpperCase(),
            has_section   = target_table.querySelector(".tlp-table-cell-section");

        if (has_section) {
            nb_displayed = toggleLinesWithSections(body_margin, search);
        } else {
            nb_displayed = toggleLinesWithoutSections(body_margin, search);
        }

        toggleEmptyState(nb_displayed);
    }

    function toggleLinesWithSections(body_margin, search) {
        const tbodies = target_table.querySelectorAll('tbody');

        let nb_total_displayed = 0,
            current_section,
            should_force_current_section_to_be_displayed;

        [].forEach.call(tbodies, function (tbody) {
            const is_section = tbody.querySelector('.tlp-table-cell-section');

            if (is_section) {
                current_section = tbody;

                should_force_current_section_to_be_displayed = toggleSection(current_section, search);
            } else {
                nb_total_displayed += toggleLineInSection(
                    tbody,
                    should_force_current_section_to_be_displayed,
                    search,
                    body_margin,
                    current_section
                );
            }
        });

        return nb_total_displayed;
    }

    function toggleLinesWithoutSections(body_margin, search) {
        const lines = target_table.querySelectorAll("tbody > tr:not(.tlp-table-empty-filter)");

        return toggleLines(lines, body_margin, search);
    }

    function toggleEmptyState(nb_displayed) {
        const empty_state = target_table.querySelector("tbody > tr.tlp-table-empty-filter");

        if (empty_state) {
            if (nb_displayed < 1) {
                empty_state.classList.add('tlp-table-empty-filter-shown');
            } else {
                empty_state.classList.remove('tlp-table-empty-filter-shown');
            }
        }
    }
}

function toggleLineInSection(tbody, should_force_current_section_to_be_displayed, search, body_margin, current_section) {
    const lines              = tbody.querySelectorAll("tr:not(.tlp-table-empty-filter)"),
          search_term        = should_force_current_section_to_be_displayed ? reset_search_term : search,
          nb_lines_displayed = toggleLines(lines, body_margin, search_term);

    if (current_section) {
        if (nb_lines_displayed > 0) {
            current_section.classList.remove('tlp-table-tbody-hidden');
        } else {
            current_section.classList.add('tlp-table-tbody-hidden');
        }
    }

    return nb_lines_displayed;
}

function toggleSection(current_section, search) {
    const is_filterable = current_section.querySelector('.tlp-table-cell-filterable');

    let should_force_current_section_to_be_displayed;

    if (is_filterable) {
        should_force_current_section_to_be_displayed = shouldTheLineBeDisplayed(current_section.children[0], search);
        if (should_force_current_section_to_be_displayed) {
            current_section.classList.remove('tlp-table-tbody-hidden');
        }
    } else {
        should_force_current_section_to_be_displayed = false;
    }

    return should_force_current_section_to_be_displayed;
}

/** @return int Number of lines that are displayed */
function toggleLines(lines, body_margin, search) {
    let last_line_displayed = null,
        nb_displayed = lines.length;

    [].forEach.call(lines, function (line) {
        let should_be_displayed = shouldTheLineBeDisplayed(line, search),
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
    let should_be_displayed = false;

    const filterable_cells = line.querySelectorAll(".tlp-table-cell-filterable");

    for (let i = 0, n = filterable_cells.length; i < n; i ++) {
        const cell_content = filterable_cells[i].textContent.toUpperCase();

        if (cell_content.indexOf(search) !== - 1) {
            should_be_displayed = true;
            break;
        }
    }

    return should_be_displayed;
}

function getTargetTable(filter) {
    let target_table_id, target_table;

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

/*
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

const reset_search_term = "";

const isEscapeKeyForInternetExplorer11 = (key) => key === "Esc";

export const EMPTY_STATE_CLASS_NAME = "tlp-table-empty-filter";
export const EMPTY_STATE_SHOWN_CLASS_NAME = "tlp-table-empty-filter-shown";
export const FILTERABLE_CELL_CLASS_NAME = "tlp-table-cell-filterable";
export const TABLE_SECTION_CLASS_NAME = "tlp-table-cell-section";
export const HIDDEN_ROW_CLASS_NAME = "tlp-table-row-hidden";
export const LAST_SHOWN_ROW_CLASS_NAME = "tlp-table-last-row";
export const HIDDEN_SECTION_CLASS_NAME = "tlp-table-tbody-hidden";

export function filterInlineTable(filter) {
    const target_table = getTargetTable(filter);

    filter.addEventListener("keyup", handleEscape);
    filter.addEventListener("input", filterTable);

    return {
        filterTable: filterTable,
    };

    function handleEscape(event) {
        if (event.key !== "Escape" && !isEscapeKeyForInternetExplorer11(event.key)) {
            return;
        }
        filter.value = reset_search_term;
        filterTable();
    }

    function filterTable() {
        let nb_displayed;

        const search = filter.value.toUpperCase(),
            has_section = target_table.querySelector("." + TABLE_SECTION_CLASS_NAME);

        if (has_section) {
            nb_displayed = toggleLinesWithSections(search);
        } else {
            nb_displayed = toggleLinesWithoutSections(search);
        }

        toggleEmptyState(nb_displayed);
    }

    function toggleLinesWithSections(search) {
        const tbodies = target_table.querySelectorAll("tbody");

        let nb_total_displayed = 0,
            current_section,
            should_force_current_section_to_be_displayed;

        for (const tbody of tbodies) {
            const is_section = tbody.querySelector("." + TABLE_SECTION_CLASS_NAME);

            if (is_section) {
                current_section = tbody;

                should_force_current_section_to_be_displayed = toggleSection(
                    current_section,
                    search
                );
            } else {
                nb_total_displayed += toggleLineInSection(
                    tbody,
                    should_force_current_section_to_be_displayed,
                    search,
                    current_section
                );
            }
        }

        return nb_total_displayed;
    }

    function toggleLinesWithoutSections(search) {
        const lines = target_table.querySelectorAll(
            "tbody > tr:not(." + EMPTY_STATE_CLASS_NAME + ")"
        );

        return toggleLines(lines, search);
    }

    function toggleEmptyState(nb_displayed) {
        const empty_state = target_table.querySelector("tbody > tr." + EMPTY_STATE_CLASS_NAME);

        if (empty_state) {
            if (nb_displayed < 1) {
                empty_state.classList.add(EMPTY_STATE_SHOWN_CLASS_NAME);
            } else {
                empty_state.classList.remove(EMPTY_STATE_SHOWN_CLASS_NAME);
            }
        }
    }
}

function toggleLineInSection(
    tbody,
    should_force_current_section_to_be_displayed,
    search,
    current_section
) {
    const lines = tbody.querySelectorAll("tr:not(." + EMPTY_STATE_CLASS_NAME + ")"),
        search_term = should_force_current_section_to_be_displayed ? reset_search_term : search,
        nb_lines_displayed = toggleLines(lines, search_term);

    if (current_section) {
        if (nb_lines_displayed > 0) {
            current_section.classList.remove(HIDDEN_SECTION_CLASS_NAME);
        } else {
            current_section.classList.add(HIDDEN_SECTION_CLASS_NAME);
        }
    }

    return nb_lines_displayed;
}

function toggleSection(current_section, search) {
    const is_filterable = current_section.querySelector("." + FILTERABLE_CELL_CLASS_NAME);

    let should_force_current_section_to_be_displayed;

    if (is_filterable) {
        should_force_current_section_to_be_displayed = shouldTheLineBeDisplayed(
            current_section.children[0],
            search
        );
        if (should_force_current_section_to_be_displayed) {
            current_section.classList.remove(HIDDEN_SECTION_CLASS_NAME);
        }
    } else {
        should_force_current_section_to_be_displayed = false;
    }

    return should_force_current_section_to_be_displayed;
}

/** @return int Number of lines that are displayed */
function toggleLines(lines, search) {
    let last_line_displayed = null,
        nb_displayed = lines.length;

    for (const line of lines) {
        const should_be_displayed = shouldTheLineBeDisplayed(line, search);

        line.classList.remove(LAST_SHOWN_ROW_CLASS_NAME);

        if (should_be_displayed) {
            line.classList.remove(HIDDEN_ROW_CLASS_NAME);

            last_line_displayed = line;
        } else {
            line.classList.add(HIDDEN_ROW_CLASS_NAME);
            nb_displayed--;
        }
    }

    if (last_line_displayed) {
        last_line_displayed.classList.add(LAST_SHOWN_ROW_CLASS_NAME);
    }

    return nb_displayed;
}

function shouldTheLineBeDisplayed(line, search) {
    let should_be_displayed = false;

    const filterable_cells = line.querySelectorAll("." + FILTERABLE_CELL_CLASS_NAME);

    for (const cell of filterable_cells) {
        const cell_content = cell.textContent.toUpperCase();
        if (cell_content.indexOf(search) !== -1) {
            should_be_displayed = true;
            break;
        }
    }

    return should_be_displayed;
}

function getTargetTable(filter) {
    let target_table_id, target_table;

    target_table_id = filter.dataset.targetTableId;
    if (!target_table_id) {
        throw new Error("Filter input does not have data-target-table-id attribute");
    }

    target_table = document.getElementById(target_table_id);
    if (!target_table) {
        throw new Error(
            'Filter input attribute references an unknown table "' + target_table_id + '"'
        );
    }

    return target_table;
}

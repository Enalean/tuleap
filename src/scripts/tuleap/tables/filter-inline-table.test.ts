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

import {
    filterInlineTable,
    EMPTY_STATE_CLASS_NAME,
    EMPTY_STATE_SHOWN_CLASS_NAME,
    FILTERABLE_CELL_CLASS_NAME,
    HIDDEN_ROW_CLASS_NAME,
    LAST_SHOWN_ROW_CLASS_NAME,
    HIDDEN_SECTION_CLASS_NAME,
    TABLE_SECTION_CLASS_NAME,
} from "./filter-inline-table";

describe(`Filter inline Table`, () => {
    let filter_input: HTMLInputElement;

    beforeEach(() => {
        filter_input = document.createElement("input");
        document.body.append(filter_input);
    });

    afterEach(() => {
        filter_input.remove();
    });

    it(`throws when the filter input does not have a data-target-table-id attribute`, () => {
        expect(() => filterInlineTable(filter_input)).toThrow();
    });

    it(`throws when the element referenced by data-target-table-id can't be found`, () => {
        const table = TableFactory.aTable(document).build();
        filter_input.dataset.targetTableId = "unknown_table_id";

        expect(() => filterInlineTable(filter_input)).toThrow();
        table.remove();
    });

    describe(`when I input characters in the filter input`, () => {
        beforeEach(() => {
            filter_input.dataset.targetTableId = "my-table";
        });

        describe(`and there is an empty state`, () => {
            it(`and there is no row displayed, it will show the empty state`, () => {
                const table = TableFactory.aTable(document).withEmptyState().build();
                filterInlineTable(filter_input);

                simulateInput(filter_input, "matches nothing");

                const empty_state = table.querySelector("." + EMPTY_STATE_CLASS_NAME);
                if (!empty_state) {
                    throw new Error("Expected empty state to exist in the test table.");
                }
                expect(empty_state.classList.contains(EMPTY_STATE_SHOWN_CLASS_NAME)).toBe(true);
                table.remove();
            });

            it(`and there are rows displayed, it will hide the empty state`, () => {
                const table = TableFactory.aTable(document)
                    .withEmptyState()
                    .withMatchingCell("matches something")
                    .build();
                filterInlineTable(filter_input);

                simulateInput(filter_input, "matches something");

                const empty_state = table.querySelector("." + EMPTY_STATE_CLASS_NAME);
                if (!empty_state) {
                    throw new Error("Expected empty state to exist in the test table.");
                }
                expect(empty_state.classList.contains(EMPTY_STATE_SHOWN_CLASS_NAME)).toBe(false);
                table.remove();
            });
        });

        it(`and there is a row matching the input,
            it will show that row`, () => {
            const table = TableFactory.aTable(document)
                .withMatchingCell("matches something")
                .build();
            filterInlineTable(filter_input);

            simulateInput(filter_input, "matches something");

            const hidden_cells = table.querySelectorAll("." + HIDDEN_ROW_CLASS_NAME);
            expect(hidden_cells.length).toBe(0);
            table.remove();
        });

        it(`and there are rows matching the input,
            it will set a custom CSS class on the last shown row`, () => {
            const table = TableFactory.aTable(document)
                .withMatchingCell("matches something")
                .withMatchingCell("also matches something")
                .build();
            filterInlineTable(filter_input);

            simulateInput(filter_input, "matches something");

            const cells = [...table.querySelectorAll("tr")];
            const last_cell = cells[cells.length - 1];
            expect(last_cell.classList.contains(LAST_SHOWN_ROW_CLASS_NAME)).toBe(true);
            table.remove();
        });

        it(`and there is a row that does not match the input,
            it will hide that row`, () => {
            const table = TableFactory.aTable(document).withNotMatchingCell().build();
            filterInlineTable(filter_input);

            simulateInput(filter_input, "matches nothing");

            const hidden_cell = table.querySelector("." + HIDDEN_ROW_CLASS_NAME);
            expect(hidden_cell).not.toBeNull();
            table.remove();
        });

        describe(`and the table has sections (special "header" tbody)`, () => {
            it(`and the section is not filterable,
                then it will be ignored`, () => {
                const table = TableFactory.aTable(document)
                    .withNotFilterableSection()
                    .withMatchingCell("matches something")
                    .build();
                filterInlineTable(filter_input);

                simulateInput(filter_input, "matches something");

                const hidden_sections = table.querySelectorAll("." + HIDDEN_SECTION_CLASS_NAME);
                expect(hidden_sections.length).toBe(0);
                table.remove();
            });

            it(`and the section is filterable
                and its text matches the input,
                then it will show that section`, () => {
                const table = TableFactory.aTable(document)
                    .withMatchingSection("matches something")
                    .withNotMatchingCell()
                    .build();
                filterInlineTable(filter_input);

                simulateInput(filter_input, "matches something");

                const hidden_sections = table.querySelectorAll("." + HIDDEN_SECTION_CLASS_NAME);
                expect(hidden_sections.length).toBe(0);
                table.remove();
            });

            it(`and the section is filterable
                and its text does not match the input
                and it is before a row matching the input,
                then it will show that section anyway`, () => {
                const table = TableFactory.aTable(document)
                    .withNotMatchingSection()
                    .withMatchingCell("matches something")
                    .build();
                filterInlineTable(filter_input);

                simulateInput(filter_input, "matches something");

                const hidden_sections = table.querySelectorAll("." + HIDDEN_SECTION_CLASS_NAME);
                expect(hidden_sections.length).toBe(0);
                table.remove();
            });

            it(`and the section is filterable
                and its text does not match the input
                and it is before a row that does not match the input,
                then it will hide that section`, () => {
                const table = TableFactory.aTable(document)
                    .withNotMatchingSection()
                    .withNotMatchingCell()
                    .build();
                filterInlineTable(filter_input);

                simulateInput(filter_input, "matches nothing");

                const hidden_section = table.querySelector("." + HIDDEN_SECTION_CLASS_NAME);
                expect(hidden_section).not.toBeNull();
                table.remove();
            });
        });
    });

    it(`when I hit a key that isn't Escape while the filter input is focused,
        then nothing happens`, () => {
        const table = TableFactory.aTable(document).withNotMatchingCell().build();
        filter_input.dataset.targetTableId = "my-table";
        filterInlineTable(filter_input);
        simulateInput(filter_input, "matches something");

        simulateKeyUp(filter_input, "A");

        const hidden_cells = table.querySelectorAll("." + HIDDEN_ROW_CLASS_NAME);
        expect(hidden_cells.length).not.toBe(0);
        table.remove();
    });

    it(`when I hit the Escape key while the filter input is focused,
        then the filter input will be cleared
        and all the hidden rows will be shown again`, () => {
        const table = TableFactory.aTable(document).withNotMatchingCell().build();
        filter_input.dataset.targetTableId = "my-table";
        filterInlineTable(filter_input);
        simulateInput(filter_input, "matches something");

        simulateKeyUp(filter_input, "Escape");

        const hidden_cells = table.querySelectorAll("." + HIDDEN_ROW_CLASS_NAME);
        expect(hidden_cells.length).toBe(0);
        expect(filter_input.value).toEqual("");
        table.remove();
    });
});

class TableFactory {
    private readonly doc: Document;
    private readonly table: HTMLElement;
    private readonly tbody: HTMLElement;

    private constructor(doc: Document, table: HTMLElement, tbody: HTMLElement) {
        this.doc = doc;
        this.table = table;
        this.tbody = tbody;
    }

    public static aTable(doc: Document): TableFactory {
        const table = doc.createElement("table");
        table.id = "my-table";
        const tbody = doc.createElement("tbody");
        return new TableFactory(doc, table, tbody);
    }

    public build(): HTMLElement {
        this.table.append(this.tbody);
        this.doc.body.append(this.table);
        return this.table;
    }

    public withEmptyState(): TableFactory {
        const empty_state_row = this.doc.createElement("tr");
        empty_state_row.classList.add(EMPTY_STATE_CLASS_NAME);
        this.tbody.append(empty_state_row);
        return this;
    }

    public withMatchingCell(search: string): TableFactory {
        const row = this.createRowWithText(search);
        this.tbody.append(row);
        return this;
    }

    public withNotMatchingCell(): TableFactory {
        const row = this.createRowWithText("generic text");
        this.tbody.append(row);
        return this;
    }

    public withMatchingSection(search: string): TableFactory {
        return this.createSection(true, search);
    }

    public withNotMatchingSection(): TableFactory {
        return this.createSection(true, "generic text");
    }

    public withNotFilterableSection(): TableFactory {
        return this.createSection(false, "");
    }

    private createSection(filterable: boolean, text_content: string): TableFactory {
        const cell = this.doc.createElement("td");
        cell.classList.add(TABLE_SECTION_CLASS_NAME);
        cell.textContent = text_content;
        if (filterable) {
            cell.classList.add(FILTERABLE_CELL_CLASS_NAME);
        }
        const row = this.doc.createElement("tr");
        const tbody = this.doc.createElement("tbody");
        row.append(cell);
        tbody.append(row);
        this.table.append(tbody);
        return this;
    }

    private createRowWithText(search: string): HTMLElement {
        const row = this.doc.createElement("tr");
        const matching_cell = this.doc.createElement("td");
        matching_cell.classList.add(FILTERABLE_CELL_CLASS_NAME);
        matching_cell.textContent = search;
        row.append(matching_cell);
        return row;
    }
}

function simulateInput(filter_input: HTMLInputElement, value: string): void {
    filter_input.value = value;
    filter_input.dispatchEvent(new Event("input"));
}

function simulateKeyUp(filter_input: HTMLInputElement, key: string): void {
    filter_input.dispatchEvent(new KeyboardEvent("keyup", { key }));
}

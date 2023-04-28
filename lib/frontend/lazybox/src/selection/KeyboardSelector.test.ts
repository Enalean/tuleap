/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import { beforeEach, describe, it, vi, expect } from "vitest";
import { KeyboardSelector } from "./KeyboardSelector";
import { ManageDropdownStub } from "../../tests/stubs/ManageDropdownStub";
import type { ListItemHighlighter } from "../navigation/ListItemHighlighter";
import type { SearchInput } from "../SearchInput";
import { ManageSelectionStub } from "../../tests/stubs/ManageSelectionStub";

const noop = (): void => {
    //Do nothing
};
describe(`KeyboardSelector`, () => {
    let doc: Document,
        dropdown_manager: ManageDropdownStub,
        highlighter: ListItemHighlighter,
        selection_manager: ManageSelectionStub,
        search_input: SearchInput;

    describe(`handleEnter`, () => {
        beforeEach(() => {
            doc = document.implementation.createHTMLDocument();

            highlighter = {
                highlightItem(item_to_highlight: Element) {
                    if (item_to_highlight) {
                        //Do nothing
                    }
                },
                getHighlightedItem() {
                    return null;
                },
            } as ListItemHighlighter;

            dropdown_manager = ManageDropdownStub.withOpenDropdown();
            selection_manager = ManageSelectionStub.withNoSelection();

            search_input = { clear: noop } as SearchInput;
        });

        const handleEnter = (): void => {
            const selector = KeyboardSelector(
                dropdown_manager,
                highlighter,
                selection_manager,
                search_input
            );
            selector.handleEnter();
        };

        it(`will select the highlighted element, close the dropdown and clear the search input`, () => {
            dropdown_manager = ManageDropdownStub.withOpenDropdown();
            const highlighted_item = doc.createElement("li");
            vi.spyOn(highlighter, "getHighlightedItem").mockReturnValue(highlighted_item);
            const clear = vi.spyOn(search_input, "clear");

            handleEnter();

            expect(selection_manager.getCurrentSelection()).toBe(highlighted_item);
            expect(dropdown_manager.getCloseLazyboxCallCount()).toBe(1);
            expect(clear).toHaveBeenCalled();
        });

        it(`does nothing when the dropdown is closed`, () => {
            dropdown_manager = ManageDropdownStub.withClosedDropdown();

            handleEnter();

            expect(selection_manager.hasSelection()).toBe(false);
            expect(dropdown_manager.getCloseLazyboxCallCount()).toBe(0);
        });

        it(`does nothing if no item is highlighted`, () => {
            vi.spyOn(highlighter, "getHighlightedItem").mockReturnValue(null);

            handleEnter();

            expect(selection_manager.hasSelection()).toBe(false);
            expect(dropdown_manager.getCloseLazyboxCallCount()).toBe(0);
        });
    });
});

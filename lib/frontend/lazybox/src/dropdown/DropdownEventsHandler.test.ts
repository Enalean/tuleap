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

import { describe, it, expect, beforeEach, vi } from "vitest";
import type { ScrollingManager } from "../events/ScrollingManager";
import type { SearchInput } from "../SearchInput";
import type { ListItemHighlighter } from "../navigation/ListItemHighlighter";
import { DropdownEventsHandler } from "./DropdownEventsHandler";
import type { SelectionElement } from "../selection/SelectionElement";

const noop = (): void => {
    // Do nothing
};

describe("DropdownEventsHandler", () => {
    let scrolling_manager: ScrollingManager,
        search_field: SearchInput,
        single_selection_element: SelectionElement,
        highlighter: ListItemHighlighter;

    beforeEach(() => {
        scrolling_manager = {
            lockScrolling: noop,
            unlockScrolling: noop,
        } as ScrollingManager;
        search_field = { setFocus: noop } as SearchInput;
        single_selection_element = { setFocus: noop } as SelectionElement;
        highlighter = { resetHighlight: noop } as ListItemHighlighter;
    });

    const getHandler = (): DropdownEventsHandler =>
        DropdownEventsHandler(
            scrolling_manager,
            search_field,
            single_selection_element,
            highlighter
        );

    it(`onDropdownOpen() should:
        - lock the page scrolling
        - give the focus to the search field`, () => {
        const lock = vi.spyOn(scrolling_manager, "lockScrolling");
        const setFocus = vi.spyOn(search_field, "setFocus");

        getHandler().onDropdownOpen();

        expect(lock).toHaveBeenCalledOnce();
        expect(setFocus).toHaveBeenCalledOnce();
    });

    it(`onDropdownClosed() should:
        - unlock the page scrolling
        - give the focus to the lazybox wrapper
        - and reset highlight`, () => {
        const unlock = vi.spyOn(scrolling_manager, "unlockScrolling");
        const setFocus = vi.spyOn(single_selection_element, "setFocus");
        const reset = vi.spyOn(highlighter, "resetHighlight");

        getHandler().onDropdownClosed();

        expect(unlock).toHaveBeenCalledOnce();
        expect(setFocus).toHaveBeenCalledOnce();
        expect(reset).toHaveBeenCalledOnce();
    });
});

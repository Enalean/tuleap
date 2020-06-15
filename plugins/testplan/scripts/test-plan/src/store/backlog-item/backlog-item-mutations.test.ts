/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { BacklogItemState } from "./type";
import { BacklogItem } from "../../type";
import {
    addBacklogItems,
    beginLoadingBacklogItems,
    endLoadingBacklogItems,
    loadingErrorHasBeenCatched,
} from "./backlog-item-mutations";

jest.useFakeTimers();

describe("BacklogItem state mutations", () => {
    it("beginLoadingBacklogItems", () => {
        const state: BacklogItemState = {
            is_loading: false,
            has_loading_error: false,
            backlog_items: [],
        };

        beginLoadingBacklogItems(state);

        expect(state.is_loading).toBe(true);
    });

    it("endLoadingBacklogItems", () => {
        const state: BacklogItemState = {
            is_loading: true,
            has_loading_error: false,
            backlog_items: [],
        };

        endLoadingBacklogItems(state);

        expect(state.is_loading).toBe(false);
    });

    it("addBacklogItems", () => {
        const state: BacklogItemState = {
            is_loading: true,
            has_loading_error: false,
            backlog_items: [{ id: 1 } as BacklogItem],
        };

        addBacklogItems(state, [{ id: 2 }, { id: 3 }] as BacklogItem[]);

        expect(state.backlog_items.length).toBe(3);
    });

    it("loadingErrorHasBeenCatched", () => {
        const state: BacklogItemState = {
            is_loading: true,
            has_loading_error: false,
            backlog_items: [],
        };

        loadingErrorHasBeenCatched(state);

        expect(state.has_loading_error).toBe(true);
    });
});

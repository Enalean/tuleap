/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    cutItem,
    copyItem,
    emptyClipboardAfterItemDeletion,
    emptyClipboard,
    startPasting,
    pastingHasFailed,
} from "./clipboard-mutations";
import { TYPE_EMPTY, CLIPBOARD_OPERATION_CUT, CLIPBOARD_OPERATION_COPY } from "../../constants";
import defaultState from "./clipboard-default-state";
import type { Empty } from "../../type";
import type { ClipboardState } from "./module";

describe("Clipboard mutations", () => {
    it("Cut item when no item is being pasted", () => {
        const item = {
            id: 3,
            title: "My doc",
            type: TYPE_EMPTY,
        } as Empty;

        const state = { pasting_in_progress: false, operation_type: null } as ClipboardState;
        cutItem(state, item);
        expect(state.item_id).toBe(item.id);
        expect(state.item_title).toBe(item.title);
        expect(state.item_type).toBe(item.type);
        expect(state.operation_type).toBe(CLIPBOARD_OPERATION_CUT);
    });

    it("Do not cut item when an item is being pasted", () => {
        const item = {
            id: 3,
            title: "My doc",
            type: TYPE_EMPTY,
        } as Empty;

        const state = { pasting_in_progress: true, item_id: null } as ClipboardState;
        cutItem(state, item);
        expect(state.item_id).toBeNull();
    });

    it("Copy item when no item is being pasted", () => {
        const item = {
            id: 3,
            title: "My doc",
            type: TYPE_EMPTY,
        } as Empty;

        const state = { pasting_in_progress: false, operation_type: null } as ClipboardState;
        copyItem(state, item);
        expect(state.item_id).toBe(item.id);
        expect(state.item_title).toBe(item.title);
        expect(state.item_type).toBe(item.type);
        expect(state.operation_type).toBe(CLIPBOARD_OPERATION_COPY);
    });

    it("Do not copy item when an item is being pasted", () => {
        const item = {
            id: 3,
            title: "My doc",
            type: TYPE_EMPTY,
        } as Empty;

        const state = { pasting_in_progress: true, item_id: null } as ClipboardState;
        copyItem(state, item);
        expect(state.item_id).toBeNull();
    });

    it("Clear clipboard", () => {
        const state = {
            item_id: 147,
            item_title: "My title",
            item_type: TYPE_EMPTY,
        } as ClipboardState;
        emptyClipboard(state);
        expect(state).toEqual(defaultState());
    });

    it("Mark paste in progress", () => {
        const state = {
            pasting_in_progress: false,
        } as ClipboardState;

        startPasting(state);
        expect(state.pasting_in_progress).toBe(true);
    });

    it("Unmark paste in progress when pasting has failed", () => {
        const state = {
            pasting_in_progress: true,
        } as ClipboardState;

        pastingHasFailed(state);
        expect(state.pasting_in_progress).toBe(false);
    });

    it("Clears the clipboard when the item in it is deleted", () => {
        const state = {
            item_id: 741,
        } as ClipboardState;

        emptyClipboardAfterItemDeletion(state, { id: 741 } as Empty);
        expect(state.item_id).toBeNull();
    });

    it("Keeps the clipboard intact when another item is deleted", () => {
        const state = {
            item_id: 741,
        } as ClipboardState;

        emptyClipboardAfterItemDeletion(state, { id: 999 } as Empty);
        expect(state.item_id).toBe(741);
    });
});

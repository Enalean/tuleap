/*
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { Embedded, Empty, Folder, Item, ItemFile, Link, State, Wiki } from "../type";
import {
    CLIPBOARD_OPERATION_COPY,
    CLIPBOARD_OPERATION_CUT,
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
} from "../constants";
import * as rest_querier from "../api/move-rest-querier";
import { createPinia, setActivePinia } from "pinia";
import type { PastePayload } from "./clipboard";
import { useClipboardStore } from "./clipboard";
import emitter from "../helpers/emitter";
import type { Store } from "vuex";
import type { Ref } from "vue";
import { ref } from "vue";

const mocked_store = { dispatch: vi.fn() };
vi.mock("../store", () => ({ store: { dispatch: vi.fn() } as unknown as Store<State> }));
vi.mock("@vueuse/core", () => ({
    useLocalStorage: (_: string, value: unknown): Ref<unknown> => ref(value),
}));

describe("Clipboard Store", () => {
    let emit: vi.SpyInstance;
    const copied_item_id = 852;
    const moved_item_id = 852;

    beforeEach(() => {
        setActivePinia(createPinia());
        emit = vi.spyOn(emitter, "emit");
    });

    describe("PasteItem", () => {
        it(`When an item is already being pasted
        Then it does nothing`, async () => {
            const store = useClipboardStore(mocked_store, "1", "1");
            store.$patch({
                pasting_in_progress: true,
            });
            const empty_clipboard_mock = vi.spyOn(store, "emptyClipboard");

            await store.pasteItem({} as PastePayload);

            expect(empty_clipboard_mock).not.toHaveBeenCalled();
        });

        it("Reject unknown paste operation", async () => {
            const store = useClipboardStore(mocked_store, "1", "1");
            store.$patch({
                operation_type: "unknown_operation",
            });
            const empty_clipboard_mock = vi.spyOn(store, "emptyClipboard");

            await store.pasteItem({} as PastePayload);
            expect(empty_clipboard_mock).toHaveBeenCalled();
        });

        it(`When item to paste is of an unknown type
            Then the paste is rejected and the clipboard state restored`, async () => {
            const store = useClipboardStore(mocked_store, "1", "1");
            store.$patch({
                item_type: "unknown_type",
            });
            const empty_clipboard_mock = vi.spyOn(store, "emptyClipboard").mockImplementation();

            await store.pasteItem({} as PastePayload);
            expect(empty_clipboard_mock).toHaveBeenCalled();
            expect(emit).not.toHaveBeenCalledWith(
                "new-item-has-just-been-created",
                expect.anything(),
            );
        });

        it(`When an error is raised when pasting an item
            Then the paste is rejected and the clipboard state is kept so the user can retry`, async () => {
            const store = useClipboardStore(mocked_store, "1", "1");
            store.$patch({
                item_type: TYPE_EMPTY,
            });

            const mocked_move = vi.spyOn(rest_querier, "moveDocument");
            mocked_move.mockRejectedValue("Forbidden");

            const pasting_has_failed_mock = vi.spyOn(store, "pastingHasFailed");

            await store.pasteItem({} as PastePayload);

            expect(pasting_has_failed_mock).toHaveBeenCalled();
            expect(emit).not.toHaveBeenCalledWith(
                "new-item-has-just-been-created",
                expect.anything(),
            );
        });
    });

    describe("Copy/PasteItem", () => {
        const testPasteSuccess = async (type: string): Promise<void> => {
            const store = useClipboardStore(mocked_store, "1", "1");
            store.$patch({
                item_type: type,
                item_id: copied_item_id,
                operation_type: CLIPBOARD_OPERATION_COPY,
            });

            const empty_clipboard_mock = vi.spyOn(store, "emptyClipboard").mockImplementation();

            const current_folder = { id: 147 } as Folder;
            const destination_folder = { id: 147 } as Folder;
            await store.pasteItem({ destination_folder, current_folder });

            expect(empty_clipboard_mock).toHaveBeenCalled();
        };

        it("Paste a file", async () => {
            const copyFile = vi.spyOn(rest_querier, "copyFile");
            copyFile.mockReturnValue(Promise.resolve({ id: 123 } as ItemFile));

            await testPasteSuccess(TYPE_FILE);

            expect(copyFile).toHaveBeenCalledWith(copied_item_id, expect.any(Number));
            expect(emit).toHaveBeenCalledWith("new-item-has-just-been-created", { id: 123 });
        });

        it("Paste a folder", async () => {
            const copyFolder = vi.spyOn(rest_querier, "copyFolder");
            copyFolder.mockReturnValue(Promise.resolve({ id: 123 } as Folder));

            await testPasteSuccess(TYPE_FOLDER);

            expect(copyFolder).toHaveBeenCalledWith(copied_item_id, expect.any(Number));
            expect(emit).toHaveBeenCalledWith("new-item-has-just-been-created", { id: 123 });
        });

        it("Paste an empty document", async () => {
            const copyEmpty = vi.spyOn(rest_querier, "copyEmpty");
            copyEmpty.mockReturnValue(Promise.resolve({ id: 123 } as Empty));

            await testPasteSuccess(TYPE_EMPTY);

            expect(copyEmpty).toHaveBeenCalledWith(copied_item_id, expect.any(Number));
            expect(emit).toHaveBeenCalledWith("new-item-has-just-been-created", { id: 123 });
        });

        it("Paste a wiki document", async () => {
            const copyWiki = vi.spyOn(rest_querier, "copyWiki");
            copyWiki.mockReturnValue(Promise.resolve({ id: 123 } as Wiki));

            await testPasteSuccess(TYPE_WIKI);

            expect(copyWiki).toHaveBeenCalledWith(copied_item_id, expect.any(Number));
            expect(emit).toHaveBeenCalledWith("new-item-has-just-been-created", { id: 123 });
        });

        it("Paste an embedded file", async () => {
            const copyEmbedded = vi.spyOn(rest_querier, "copyEmbedded");
            copyEmbedded.mockReturnValue(Promise.resolve({ id: 123 } as Embedded));

            await testPasteSuccess(TYPE_EMBEDDED);

            expect(copyEmbedded).toHaveBeenCalledWith(copied_item_id, expect.any(Number));
            expect(emit).toHaveBeenCalledWith("new-item-has-just-been-created", { id: 123 });
        });

        it("Paste a link", async () => {
            const copyLink = vi.spyOn(rest_querier, "copyLink");
            copyLink.mockReturnValue(Promise.resolve({ id: 123 } as Link));

            await testPasteSuccess(TYPE_LINK);

            expect(copyLink).toHaveBeenCalledWith(copied_item_id, expect.any(Number));
            expect(emit).toHaveBeenCalledWith("new-item-has-just-been-created", { id: 123 });
        });

        it("Paste another type of document", async () => {
            const copyOtherType = vi.spyOn(rest_querier, "copyOtherType");
            copyOtherType.mockReturnValue(Promise.resolve({ id: 123 } as Item));

            await testPasteSuccess("whatever");

            expect(copyOtherType).toHaveBeenCalledWith(copied_item_id, expect.any(Number));
            expect(emit).toHaveBeenCalledWith("new-item-has-just-been-created", { id: 123 });
        });
    });

    describe("Cut/PasteItem", () => {
        it("Paste a document", async () => {
            const moveDocument = vi.spyOn(rest_querier, "moveDocument");
            moveDocument.mockReturnValue(Promise.resolve());

            const store = useClipboardStore(mocked_store, "1", "1");
            store.$patch({
                item_type: TYPE_EMPTY,
                item_id: moved_item_id,
                move_uri: "/api/move/uri",
                operation_type: CLIPBOARD_OPERATION_CUT,
            });

            const empty_clipboard_mock = vi.spyOn(store, "emptyClipboard").mockImplementation();

            const current_folder = { id: 147 } as Folder;
            const destination_folder = { id: 147 } as Folder;
            await store.pasteItem({ destination_folder, current_folder });
            await expect(empty_clipboard_mock).toHaveBeenCalled();

            expect(moveDocument).toHaveBeenCalledWith("/api/move/uri", expect.any(Number));
            expect(emit).toHaveBeenCalledWith("new-item-has-just-been-created", {
                id: moved_item_id,
            });
        });
    });

    describe("cutItem", () => {
        it("Cut item when no item is being pasted", () => {
            const item = {
                id: 3,
                title: "My doc",
                type: TYPE_EMPTY,
                move_uri: "/api/move",
            } as Empty;

            const store = useClipboardStore(mocked_store, "1", "1");
            store.$patch({
                pasting_in_progress: false,
            });

            store.cutItem(item);

            expect(store.item_id).toBe(item.id);
            expect(store.move_uri).toBe(item.move_uri);
            expect(store.item_title).toBe(item.title);
            expect(store.item_type).toBe(item.type);
            expect(store.operation_type).toBe(CLIPBOARD_OPERATION_CUT);
        });

        it("Do not cut item when an item is being pasted", () => {
            const item = {
                id: 3,
                title: "My doc",
                type: TYPE_EMPTY,
            } as Empty;

            const store = useClipboardStore(mocked_store, "1", "1");
            store.$patch({
                pasting_in_progress: true,
                item_id: null,
            });

            store.cutItem(item);
            expect(store.item_id).toBeNull();
        });
    });

    describe("copyItem", () => {
        it("Copy item when no item is being pasted", () => {
            const item = {
                id: 3,
                title: "My doc",
                type: TYPE_EMPTY,
                move_uri: "/api/move",
            } as Empty;

            const store = useClipboardStore(mocked_store, "1", "1");
            store.$patch({
                pasting_in_progress: false,
            });

            store.copyItem(item);
            expect(store.item_id).toBe(item.id);
            expect(store.move_uri).toBe(item.move_uri);
            expect(store.item_title).toBe(item.title);
            expect(store.item_type).toBe(item.type);
            expect(store.operation_type).toBe(CLIPBOARD_OPERATION_COPY);
        });

        it("Do not copy item when an item is being pasted", () => {
            const item = {
                id: 3,
                title: "My doc",
                type: TYPE_EMPTY,
            } as Empty;

            const store = useClipboardStore(mocked_store, "1", "1");
            store.$patch({
                pasting_in_progress: true,
            });

            store.copyItem(item);
            expect(store.item_id).toBeNull();
        });
    });

    describe("emptyClipboard", () => {
        it("Clear clipboard", () => {
            const store = useClipboardStore(mocked_store, "1", "1");
            store.$patch({
                item_id: 147,
                item_title: "My title",
                item_type: TYPE_EMPTY,
                move_uri: "/api/move",
            });

            store.emptyClipboard();
            expect(store.$state).toStrictEqual({
                item_id: null,
                move_uri: null,
                item_title: null,
                item_type: null,
                operation_type: null,
                pasting_in_progress: false,
            });
        });
    });

    describe("startPasting", () => {
        it("Mark paste in progress", () => {
            const store = useClipboardStore(mocked_store, "1", "1");

            store.startPasting();
            expect(store.pasting_in_progress).toBe(true);
        });
    });

    describe("pastingHasFailed", () => {
        it("Unmark paste in progress when pasting has failed", () => {
            const store = useClipboardStore(mocked_store, "1", "1");
            store.$patch({
                pasting_in_progress: true,
            });

            store.pastingHasFailed();
            expect(store.pasting_in_progress).toBe(false);
        });
    });

    describe("emptyClipboardAfterItemDeletion", () => {
        it("Clears the clipboard when the item in it is deleted", () => {
            const store = useClipboardStore(mocked_store, "1", "1");
            store.$patch({
                item_id: 741,
            });

            store.emptyClipboardAfterItemDeletion({ id: 741 } as Empty);
            expect(store.item_id).toBeNull();
        });

        it("Keeps the clipboard intact when another item is deleted", () => {
            const store = useClipboardStore(mocked_store, "1", "1");
            store.$patch({
                item_id: 741,
            });

            store.emptyClipboardAfterItemDeletion({ id: 999 } as Empty);
            expect(store.item_id).toBe(741);
        });
    });
});

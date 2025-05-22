/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
} from "../constants";
import * as rest_querier from "../api/rest-querier";
import { deleteItem } from "./actions-delete";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import type { Item, RootState } from "../type";
import type { ActionContext } from "vuex";
import emitter from "../helpers/emitter";
import type { TestingPinia } from "@pinia/testing";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

let pinia: TestingPinia;

vi.mock("../helpers/emitter");

const empty_clipboard = vi.fn();
describe("actions-delete", () => {
    const useStore = defineStore("root", {
        state: () => ({
            item_id: null,
            item_type: null,
            item_title: null,
            operation_type: null,
            pasting_in_progress: false,
        }),
        actions: {
            emptyClipboardAfterItemDeletion: empty_clipboard,
        },
    });

    describe("deleteItem()", () => {
        let context: ActionContext<RootState, RootState>;

        beforeEach(() => {
            const project_id = 101;
            const user_id = 101;
            context = {
                commit: vi.fn(),
                state: {
                    configuration: { project_id, user_id },
                },
            } as unknown as ActionContext<RootState, RootState>;
            vi.clearAllMocks();

            pinia = createTestingPinia({ createSpy: vi.fn });
            useStore(pinia);
        });

        it("when item is a file, then the delete file route is called", async () => {
            const file_item = {
                id: 111,
                title: "My File",
                type: TYPE_FILE,
            } as Item;

            const deleteFile = vi.spyOn(rest_querier, "deleteFile");
            mockFetchSuccess(deleteFile);

            await deleteItem(context, {
                item: file_item,
                clipboard: { emptyClipboardAfterItemDeletion: empty_clipboard },
            });
            expect(deleteFile).toHaveBeenCalledWith(file_item);
            expect(emitter.emit).toHaveBeenCalledWith("item-has-just-been-deleted");
            expect(empty_clipboard).toHaveBeenCalled();
        });

        it("when item is a link, then the delete link route is called", async () => {
            const link_item = {
                id: 222,
                title: "My Link",
                type: TYPE_LINK,
            } as Item;

            mockFetchSuccess(vi.spyOn(rest_querier, "deleteLink"));

            await deleteItem(context, {
                item: link_item,
                clipboard: { emptyClipboardAfterItemDeletion: empty_clipboard },
            });
            expect(emitter.emit).toHaveBeenCalledWith("item-has-just-been-deleted");
            expect(empty_clipboard).toHaveBeenCalled();
        });

        it("when item is an embedded file, then the delete embedded file route is called", async () => {
            const embedded_file_item = {
                id: 222,
                title: "My embedded file",
                type: TYPE_EMBEDDED,
            } as Item;

            const deleteEmbeddedFile = vi.spyOn(rest_querier, "deleteEmbeddedFile");
            mockFetchSuccess(deleteEmbeddedFile);

            await deleteItem(context, {
                item: embedded_file_item,
                clipboard: { emptyClipboardAfterItemDeletion: empty_clipboard },
            });
            expect(deleteEmbeddedFile).toHaveBeenCalledWith(embedded_file_item);
            expect(emitter.emit).toHaveBeenCalledWith("item-has-just-been-deleted");
            expect(empty_clipboard).toHaveBeenCalled();
        });

        it("when item is a wiki, then the delete wiki route is called", async () => {
            const wiki_item = {
                id: 222,
                title: "My Wiki",
                type: TYPE_WIKI,
            } as Item;

            const deleteWiki = vi.spyOn(rest_querier, "deleteWiki");
            mockFetchSuccess(deleteWiki);

            const additional_options = { delete_associated_wiki_page: true };

            await deleteItem(context, {
                item: wiki_item,
                clipboard: { emptyClipboardAfterItemDeletion: empty_clipboard },
                additional_wiki_options: additional_options,
            });
            expect(deleteWiki).toHaveBeenCalledWith(wiki_item, additional_options);
            expect(emitter.emit).toHaveBeenCalledWith("item-has-just-been-deleted");
            expect(empty_clipboard).toHaveBeenCalled();
        });

        it("when item is an empty document, then the delete empty document route is called", async () => {
            const empty_doc_item = {
                id: 222,
                title: "My empty document",
                type: TYPE_EMPTY,
            } as Item;

            const deleteEmptyDocument = vi.spyOn(rest_querier, "deleteEmptyDocument");
            mockFetchSuccess(deleteEmptyDocument);

            await deleteItem(context, {
                item: empty_doc_item,
                clipboard: { emptyClipboardAfterItemDeletion: empty_clipboard },
            });
            expect(deleteEmptyDocument).toHaveBeenCalledWith(empty_doc_item);
            expect(emitter.emit).toHaveBeenCalledWith("item-has-just-been-deleted");
            expect(empty_clipboard).toHaveBeenCalled();
        });

        it("when item is a folder, then the delete folder route is called", async () => {
            const folder_item = {
                id: 222,
                title: "My folder",
                type: TYPE_FOLDER,
            } as Item;

            const deleteFolder = vi.spyOn(rest_querier, "deleteFolder");
            mockFetchSuccess(deleteFolder);

            await deleteItem(context, {
                item: folder_item,
                clipboard: { emptyClipboardAfterItemDeletion: empty_clipboard },
            });
            expect(deleteFolder).toHaveBeenCalledWith(folder_item);
            expect(emitter.emit).toHaveBeenCalledWith("item-has-just-been-deleted");
            expect(empty_clipboard).toHaveBeenCalled();
        });

        it("when item is another type, then the delete other type route is called", async () => {
            const other_item = {
                id: 222,
                title: "My other item",
                type: "whatever",
            } as Item;

            const deleteOtherType = vi.spyOn(rest_querier, "deleteOtherType");
            mockFetchSuccess(deleteOtherType);

            await deleteItem(context, {
                item: other_item,
                clipboard: { emptyClipboardAfterItemDeletion: empty_clipboard },
            });
            expect(deleteOtherType).toHaveBeenCalledWith(other_item);
            expect(emitter.emit).toHaveBeenCalledWith("item-has-just-been-deleted");
            expect(empty_clipboard).toHaveBeenCalled();
        });

        it("deletes the given item and removes it from the tree view", async () => {
            const item_to_delete = {
                id: 123,
                title: "My file",
                type: TYPE_FILE,
            } as Item;

            mockFetchSuccess(vi.spyOn(rest_querier, "deleteFile"));

            await deleteItem(context, {
                item: item_to_delete,
                clipboard: { emptyClipboardAfterItemDeletion: empty_clipboard },
            });

            expect(emitter.emit).toHaveBeenCalledWith("item-has-just-been-deleted");
            expect(context.commit).toHaveBeenCalledWith(
                "removeItemFromFolderContent",
                item_to_delete,
            );
            expect(empty_clipboard).toHaveBeenCalled();
        });

        it("display error if something wrong happens", async () => {
            const folder_item = {
                id: 222,
                title: "My folder",
                type: TYPE_FOLDER,
            } as Item;

            vi.spyOn(rest_querier, "deleteFolder").mockRejectedValue(
                new FetchWrapperError("", {
                    status: 400,
                } as Response),
            );

            await deleteItem(context, {
                item: folder_item,
                clipboard: { emptyClipboardAfterItemDeletion: empty_clipboard },
            });

            expect(context.commit).toHaveBeenCalledWith(
                "error/setModalError",
                "Internal server error",
            );
        });

        it("mark item as unknown when rest route fails with 404", async () => {
            const folder_item = {
                id: 222,
                title: "My folder",
                type: TYPE_FOLDER,
            } as Item;

            vi.spyOn(rest_querier, "deleteFolder").mockRejectedValue(
                new FetchWrapperError("", {
                    status: 404,
                    json: (): Promise<{ error: { code: number; i18n_error_message: string } }> =>
                        Promise.resolve({ error: { code: 404, i18n_error_message: "not found" } }),
                } as Response),
            );

            await deleteItem(context, {
                item: folder_item,
                clipboard: { emptyClipboardAfterItemDeletion: empty_clipboard },
            });

            expect(context.commit).toHaveBeenCalledWith("error/setModalError", "not found");
            expect(context.commit).toHaveBeenCalledWith("removeItemFromFolderContent", folder_item);
            expect(context.commit).toHaveBeenCalledWith("updateCurrentlyPreviewedItem", null);
        });
    });
});

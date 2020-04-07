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

import { pasteItem } from "./clipboard-actions.js";
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
    CLIPBOARD_OPERATION_CUT,
    CLIPBOARD_OPERATION_COPY,
} from "../../constants.js";
import { mockFetchError } from "../../../../../../src/themes/tlp/mocks/tlp-fetch-mock-helper.js";
import * as rest_querier from "../../api/rest-querier.js";
import * as adjust_item_to_content_after_item_creation_in_folder from "../actions-helpers/adjust-item-to-content-after-item-creation-in-folder.js";

describe("Clipboard actions", () => {
    it(`When an item is already being pasted
        Then it does nothing`, async () => {
        const context = {
            commit: jest.fn(),
            state: {
                pasting_in_progress: true,
            },
        };

        await pasteItem(context, [{}, {}, {}]);

        expect(context.commit).not.toHaveBeenCalled();
    });

    it("Reject unknown paste operation", async () => {
        const context = {
            commit: jest.fn(),
            state: {
                operation_type: "unknown_operation",
            },
        };
        const global_context = {
            commit: jest.fn(),
        };
        const adjustItemToContentAfterItemCreationInAFolder = jest.spyOn(
            adjust_item_to_content_after_item_creation_in_folder,
            "adjustItemToContentAfterItemCreationInAFolder"
        );

        await expect(pasteItem(context, [{}, {}, global_context])).rejects.toBeDefined();
        expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        expect(adjustItemToContentAfterItemCreationInAFolder).not.toHaveBeenCalled();
    });

    describe("Cut item", () => {
        let context, state, adjustItemToContentAfterItemCreationInAFolder;
        const moved_item_id = 852;

        beforeEach(() => {
            state = {
                item_id: moved_item_id,
                item_type: null,
                pasting_in_progress: false,
                operation_type: CLIPBOARD_OPERATION_CUT,
            };
            context = {
                commit: jest.fn(),
                state,
            };

            adjustItemToContentAfterItemCreationInAFolder = jest
                .spyOn(
                    adjust_item_to_content_after_item_creation_in_folder,
                    "adjustItemToContentAfterItemCreationInAFolder"
                )
                .mockReturnValue(Promise.resolve());
        });

        const testPasteSuccess = async (type) => {
            context.state.item_type = type;

            const current_folder = { id: 147 };
            const destination_folder = { id: 147 };
            await pasteItem(context, [destination_folder, current_folder, {}]);
        };

        it("Paste a file", async () => {
            const moveFile = jest.spyOn(rest_querier, "moveFile");
            moveFile.mockReturnValue(Promise.resolve({ id: 123 }));

            await testPasteSuccess(TYPE_FILE);

            expect(moveFile).toHaveBeenCalledWith(moved_item_id, expect.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        });

        it("Paste a folder", async () => {
            const moveFolder = jest.spyOn(rest_querier, "moveFolder");
            moveFolder.mockReturnValue(Promise.resolve({ id: 123 }));

            await testPasteSuccess(TYPE_FOLDER);

            expect(moveFolder).toHaveBeenCalledWith(moved_item_id, expect.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        });

        it("Paste an empty document", async () => {
            const moveEmpty = jest.spyOn(rest_querier, "moveEmpty");
            moveEmpty.mockReturnValue(Promise.resolve({ id: 123 }));

            await testPasteSuccess(TYPE_EMPTY);

            expect(moveEmpty).toHaveBeenCalledWith(moved_item_id, expect.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        });

        it("Paste a wiki document", async () => {
            const moveWiki = jest.spyOn(rest_querier, "moveWiki");
            moveWiki.mockReturnValue(Promise.resolve({ id: 123 }));

            await testPasteSuccess(TYPE_WIKI);

            expect(moveWiki).toHaveBeenCalledWith(moved_item_id, expect.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        });

        it("Paste an embedded file", async () => {
            const moveEmbedded = jest.spyOn(rest_querier, "moveEmbedded");
            moveEmbedded.mockReturnValue(Promise.resolve({ id: 123 }));

            await testPasteSuccess(TYPE_EMBEDDED);

            expect(moveEmbedded).toHaveBeenCalledWith(moved_item_id, expect.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        });

        it("Paste a link", async () => {
            const moveLink = jest.spyOn(rest_querier, "moveLink");
            moveLink.mockReturnValue(Promise.resolve({ id: 123 }));

            await testPasteSuccess(TYPE_LINK);

            expect(moveLink).toHaveBeenCalledWith(moved_item_id, expect.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        });

        it(`When item to paste is of an unknown type
            Then the paste is rejected and the clipboard state restored`, async () => {
            context.state.item_type = "unknown_type";
            const global_context = {
                commit: jest.fn(),
            };

            await expect(pasteItem(context, [{}, {}, global_context])).rejects.toBeDefined();
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
            expect(adjustItemToContentAfterItemCreationInAFolder).not.toHaveBeenCalled();
        });

        it(`When an error is raised when pasting an item
            Then the paste is rejected and the clipboard state is kept so the user can retry`, async () => {
            context.state.item_type = TYPE_EMPTY;
            const global_context = {
                commit: jest.fn(),
            };

            mockFetchError(jest.spyOn(rest_querier, "moveEmpty"), {
                status: 500,
            });

            await pasteItem(context, [{}, {}, global_context]);

            expect(context.commit).not.toHaveBeenCalledWith("emptyClipboard");
            expect(context.commit).toHaveBeenCalledWith("pastingHasFailed");
            expect(adjustItemToContentAfterItemCreationInAFolder).not.toHaveBeenCalled();
        });
    });

    describe("Paste item", () => {
        let context, state, adjustItemToContentAfterItemCreationInAFolder;
        const copied_item_id = 852;

        beforeEach(() => {
            state = {
                item_id: copied_item_id,
                item_type: null,
                pasting_in_progress: false,
                operation_type: CLIPBOARD_OPERATION_COPY,
            };
            context = {
                commit: jest.fn(),
                state,
            };

            adjustItemToContentAfterItemCreationInAFolder = jest
                .spyOn(
                    adjust_item_to_content_after_item_creation_in_folder,
                    "adjustItemToContentAfterItemCreationInAFolder"
                )
                .mockReturnValue(Promise.resolve());
        });

        const testPasteSuccess = async (type) => {
            context.state.item_type = type;

            const current_folder = { id: 147 };
            const destination_folder = { id: 147 };
            await pasteItem(context, [destination_folder, current_folder, {}]);
        };

        it("Paste a file", async () => {
            const copyFile = jest.spyOn(rest_querier, "copyFile");
            copyFile.mockReturnValue(Promise.resolve({ id: 123 }));

            await testPasteSuccess(TYPE_FILE);

            expect(copyFile).toHaveBeenCalledWith(copied_item_id, expect.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        });

        it("Paste a folder", async () => {
            const copyFolder = jest.spyOn(rest_querier, "copyFolder");
            copyFolder.mockReturnValue(Promise.resolve({ id: 123 }));

            await testPasteSuccess(TYPE_FOLDER);

            expect(copyFolder).toHaveBeenCalledWith(copied_item_id, expect.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        });

        it("Paste an empty document", async () => {
            const copyEmpty = jest.spyOn(rest_querier, "copyEmpty");
            copyEmpty.mockReturnValue(Promise.resolve({ id: 123 }));

            await testPasteSuccess(TYPE_EMPTY);

            expect(copyEmpty).toHaveBeenCalledWith(copied_item_id, expect.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        });

        it("Paste a wiki document", async () => {
            const copyWiki = jest.spyOn(rest_querier, "copyWiki");
            copyWiki.mockReturnValue(Promise.resolve({ id: 123 }));

            await testPasteSuccess(TYPE_WIKI);

            expect(copyWiki).toHaveBeenCalledWith(copied_item_id, expect.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        });

        it("Paste an embedded file", async () => {
            const copyEmbedded = jest.spyOn(rest_querier, "copyEmbedded");
            copyEmbedded.mockReturnValue(Promise.resolve({ id: 123 }));

            await testPasteSuccess(TYPE_EMBEDDED);

            expect(copyEmbedded).toHaveBeenCalledWith(copied_item_id, expect.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        });

        it("Paste a link", async () => {
            const copyLink = jest.spyOn(rest_querier, "copyLink");
            copyLink.mockReturnValue(Promise.resolve({ id: 123 }));

            await testPasteSuccess(TYPE_LINK);

            expect(copyLink).toHaveBeenCalledWith(copied_item_id, expect.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        });

        it(`When item to paste is of an unknown type
            Then the paste is rejected and the clipboard state restored`, async () => {
            context.state.item_type = "unknown_type";
            const global_context = {
                commit: jest.fn(),
            };

            await expect(pasteItem(context, [{}, {}, global_context])).rejects.toBeDefined();
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
            expect(adjustItemToContentAfterItemCreationInAFolder).not.toHaveBeenCalled();
        });

        it(`When an error is raised when pasting an item
            Then the paste is rejected and the clipboard state is kept so the user can retry`, async () => {
            context.state.item_type = TYPE_EMPTY;
            const global_context = {
                commit: jest.fn(),
            };

            mockFetchError(jest.spyOn(rest_querier, "copyEmpty"), {
                status: 500,
            });

            await pasteItem(context, [{}, {}, global_context]);

            expect(context.commit).not.toHaveBeenCalledWith("emptyClipboard");
            expect(context.commit).toHaveBeenCalledWith("pastingHasFailed");
            expect(adjustItemToContentAfterItemCreationInAFolder).not.toHaveBeenCalled();
        });
    });
});

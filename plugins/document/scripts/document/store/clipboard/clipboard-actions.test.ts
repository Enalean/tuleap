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

import type { PastePayload } from "./clipboard-actions";
import { pasteItem } from "./clipboard-actions";
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
    CLIPBOARD_OPERATION_CUT,
    CLIPBOARD_OPERATION_COPY,
} from "../../constants";
import * as rest_querier from "../../api/move-rest-querier";
import * as adjust_item_to_content_after_item_creation_in_folder from "../actions-helpers/adjust-item-to-content-after-item-creation-in-folder";
import type { ClipboardState } from "./module";
import type { ActionContext } from "vuex";
import type { Embedded, Empty, ItemFile, Link, State, Wiki } from "../../type";
import type { Folder } from "../../type";
import emitter from "../../helpers/emitter";

describe("Clipboard actions", () => {
    let context = {
        commit: jest.fn(),
    } as unknown as ActionContext<ClipboardState, State>;
    const global_context = {
        commit: jest.fn(),
    } as unknown as ActionContext<State, State>;

    const paste_payload: PastePayload = {
        destination_folder: {} as Folder,
        current_folder: {} as Folder,
        global_context: global_context,
    };

    it(`When an item is already being pasted
        Then it does nothing`, async () => {
        context = {
            state: { pasting_in_progress: true },
            commit: jest.fn(),
        } as unknown as ActionContext<ClipboardState, State>;

        await pasteItem(context, {} as PastePayload);

        expect(context.commit).not.toHaveBeenCalled();
    });

    it("Reject unknown paste operation", async () => {
        context = {
            state: { operation_type: "unknown_operation" },
            commit: jest.fn(),
        } as unknown as ActionContext<ClipboardState, State>;

        const adjustItemToContentAfterItemCreationInAFolder = jest.spyOn(
            adjust_item_to_content_after_item_creation_in_folder,
            "adjustItemToContentAfterItemCreationInAFolder"
        );

        await expect(pasteItem(context, paste_payload)).rejects.toBeDefined();
        expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        expect(adjustItemToContentAfterItemCreationInAFolder).not.toHaveBeenCalled();
    });

    describe("Cut item", () => {
        let state, adjustItemToContentAfterItemCreationInAFolder: jest.SpyInstance;
        const moved_item_id = 852;
        let emit: jest.SpyInstance;

        beforeEach(() => {
            state = {
                item_id: moved_item_id,
                item_type: null,
                pasting_in_progress: false,
                operation_type: CLIPBOARD_OPERATION_CUT,
            } as ClipboardState;
            context = {
                commit: jest.fn(),
                dispatch: jest.fn(),
                state,
            } as unknown as ActionContext<ClipboardState, State>;

            adjustItemToContentAfterItemCreationInAFolder = jest
                .spyOn(
                    adjust_item_to_content_after_item_creation_in_folder,
                    "adjustItemToContentAfterItemCreationInAFolder"
                )
                .mockReturnValue(Promise.resolve());
            emit = jest.spyOn(emitter, "emit");
        });

        const testPasteSuccess = async (type: string): Promise<void> => {
            context.state.item_type = type;

            const current_folder = { id: 147 } as Folder;
            const destination_folder = { id: 147 } as Folder;
            await pasteItem(context, { destination_folder, current_folder, global_context });
        };

        it("Paste a file", async () => {
            const moveFile = jest.spyOn(rest_querier, "moveFile");
            moveFile.mockReturnValue(Promise.resolve());

            await testPasteSuccess(TYPE_FILE);

            expect(moveFile).toHaveBeenCalledWith(moved_item_id, expect.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
            expect(emit).toHaveBeenCalledWith("new-item-has-just-been-created");
        });

        it("Paste a folder", async () => {
            const moveFolder = jest.spyOn(rest_querier, "moveFolder");
            moveFolder.mockReturnValue(Promise.resolve());

            await testPasteSuccess(TYPE_FOLDER);

            expect(moveFolder).toHaveBeenCalledWith(moved_item_id, expect.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
            expect(emit).toHaveBeenCalledWith("new-item-has-just-been-created");
        });

        it("Paste an empty document", async () => {
            const mocked_move = jest.spyOn(rest_querier, "moveEmpty");
            mocked_move.mockReturnValue(Promise.resolve());

            await testPasteSuccess(TYPE_EMPTY);

            expect(mocked_move).toHaveBeenCalledWith(moved_item_id, expect.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
            expect(emit).toHaveBeenCalledWith("new-item-has-just-been-created");
        });

        it("Paste a wiki document", async () => {
            const moveWiki = jest.spyOn(rest_querier, "moveWiki");
            moveWiki.mockReturnValue(Promise.resolve());

            await testPasteSuccess(TYPE_WIKI);

            expect(moveWiki).toHaveBeenCalledWith(moved_item_id, expect.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
            expect(emit).toHaveBeenCalledWith("new-item-has-just-been-created");
        });

        it("Paste an embedded file", async () => {
            const moveEmbedded = jest.spyOn(rest_querier, "moveEmbedded");
            moveEmbedded.mockReturnValue(Promise.resolve());

            await testPasteSuccess(TYPE_EMBEDDED);

            expect(moveEmbedded).toHaveBeenCalledWith(moved_item_id, expect.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
            expect(emit).toHaveBeenCalledWith("new-item-has-just-been-created");
        });

        it("Paste a link", async () => {
            const moveLink = jest.spyOn(rest_querier, "moveLink");
            moveLink.mockReturnValue(Promise.resolve());

            await testPasteSuccess(TYPE_LINK);

            expect(moveLink).toHaveBeenCalledWith(moved_item_id, expect.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
            expect(emit).toHaveBeenCalledWith("new-item-has-just-been-created");
        });

        it(`When item to paste is of an unknown type
            Then the paste is rejected and the clipboard state restored`, async () => {
            context.state.item_type = "unknown_type";

            await pasteItem(context, paste_payload);
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
            expect(adjustItemToContentAfterItemCreationInAFolder).not.toHaveBeenCalled();
            expect(emit).not.toHaveBeenCalledWith("new-item-has-just-been-created");
        });

        it(`When an error is raised when pasting an item
            Then the paste is rejected and the clipboard state is kept so the user can retry`, async () => {
            context.state.item_type = TYPE_EMPTY;

            const mocked_move = jest.spyOn(rest_querier, "moveEmpty");
            mocked_move.mockRejectedValue("Forbidden");

            await pasteItem(context, paste_payload);

            expect(context.commit).not.toHaveBeenCalledWith("emptyClipboard");
            expect(context.commit).toHaveBeenCalledWith("pastingHasFailed");
            expect(adjustItemToContentAfterItemCreationInAFolder).not.toHaveBeenCalled();
            expect(emit).not.toHaveBeenCalledWith("new-item-has-just-been-created");
        });
    });

    describe("Paste item", () => {
        let state: ClipboardState;
        let adjustItemToContentAfterItemCreationInAFolder: jest.SpyInstance;
        const copied_item_id = 852;
        let emit: jest.SpyInstance;

        beforeEach(() => {
            state = {
                item_id: copied_item_id,
                item_type: null,
                pasting_in_progress: false,
                operation_type: CLIPBOARD_OPERATION_COPY,
            } as ClipboardState;
            context = {
                commit: jest.fn(),
                dispatch: jest.fn(),
                state,
            } as unknown as ActionContext<ClipboardState, State>;

            adjustItemToContentAfterItemCreationInAFolder = jest
                .spyOn(
                    adjust_item_to_content_after_item_creation_in_folder,
                    "adjustItemToContentAfterItemCreationInAFolder"
                )
                .mockReturnValue(Promise.resolve());
            emit = jest.spyOn(emitter, "emit");
        });

        const testPasteSuccess = async (type: string): Promise<void> => {
            context.state.item_type = type;

            const current_folder = { id: 147 } as Folder;
            const destination_folder = { id: 147 } as Folder;
            await pasteItem(context, { destination_folder, current_folder, global_context });
        };

        it("Paste a file", async () => {
            const copyFile = jest.spyOn(rest_querier, "copyFile");
            copyFile.mockReturnValue(Promise.resolve({ id: 123 } as ItemFile));

            await testPasteSuccess(TYPE_FILE);

            expect(copyFile).toHaveBeenCalledWith(copied_item_id, expect.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
            expect(emit).toHaveBeenCalledWith("new-item-has-just-been-created");
        });

        it("Paste a folder", async () => {
            const copyFolder = jest.spyOn(rest_querier, "copyFolder");
            copyFolder.mockReturnValue(Promise.resolve({ id: 123 } as Folder));

            await testPasteSuccess(TYPE_FOLDER);

            expect(copyFolder).toHaveBeenCalledWith(copied_item_id, expect.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
            expect(emit).toHaveBeenCalledWith("new-item-has-just-been-created");
        });

        it("Paste an empty document", async () => {
            const copyEmpty = jest.spyOn(rest_querier, "copyEmpty");
            copyEmpty.mockReturnValue(Promise.resolve({ id: 123 } as Empty));

            await testPasteSuccess(TYPE_EMPTY);

            expect(copyEmpty).toHaveBeenCalledWith(copied_item_id, expect.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
            expect(emit).toHaveBeenCalledWith("new-item-has-just-been-created");
        });

        it("Paste a wiki document", async () => {
            const copyWiki = jest.spyOn(rest_querier, "copyWiki");
            copyWiki.mockReturnValue(Promise.resolve({ id: 123 } as Wiki));

            await testPasteSuccess(TYPE_WIKI);

            expect(copyWiki).toHaveBeenCalledWith(copied_item_id, expect.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
            expect(emit).toHaveBeenCalledWith("new-item-has-just-been-created");
        });

        it("Paste an embedded file", async () => {
            const copyEmbedded = jest.spyOn(rest_querier, "copyEmbedded");
            copyEmbedded.mockReturnValue(Promise.resolve({ id: 123 } as Embedded));

            await testPasteSuccess(TYPE_EMBEDDED);

            expect(copyEmbedded).toHaveBeenCalledWith(copied_item_id, expect.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
            expect(emit).toHaveBeenCalledWith("new-item-has-just-been-created");
        });

        it("Paste a link", async () => {
            const copyLink = jest.spyOn(rest_querier, "copyLink");
            copyLink.mockReturnValue(Promise.resolve({ id: 123 } as Link));

            await testPasteSuccess(TYPE_LINK);

            expect(copyLink).toHaveBeenCalledWith(copied_item_id, expect.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
            expect(emit).toHaveBeenCalledWith("new-item-has-just-been-created");
        });

        it(`When item to paste is of an unknown type
            Then the paste is rejected and the clipboard state restored`, async () => {
            context.state.item_type = "unknown_type";

            await pasteItem(context, paste_payload);
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
            expect(adjustItemToContentAfterItemCreationInAFolder).not.toHaveBeenCalled();
            expect(emit).not.toHaveBeenCalledWith("new-item-has-just-been-created");
        });

        it(`When an error is raised when pasting an item
            Then the paste is rejected and the clipboard state is kept so the user can retry`, async () => {
            context.state.item_type = TYPE_EMPTY;

            const mocked_move = jest.spyOn(rest_querier, "moveEmpty");
            mocked_move.mockRejectedValue("Forbidden");

            await pasteItem(context, paste_payload);

            expect(context.commit).not.toHaveBeenCalledWith("emptyClipboard");
            expect(context.commit).toHaveBeenCalledWith("pastingHasFailed");
            expect(adjustItemToContentAfterItemCreationInAFolder).not.toHaveBeenCalled();
            expect(emit).not.toHaveBeenCalledWith("new-item-has-just-been-created");
        });
    });
});

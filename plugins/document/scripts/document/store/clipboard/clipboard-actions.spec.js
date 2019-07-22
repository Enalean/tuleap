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
    restore as restoreRestQuerier,
    rewire$moveFile,
    rewire$moveEmpty,
    rewire$moveFolder,
    rewire$moveWiki,
    rewire$moveEmbedded,
    rewire$moveLink,
    rewire$copyFile,
    rewire$copyEmpty,
    rewire$copyFolder,
    rewire$copyWiki,
    rewire$copyEmbedded,
    rewire$copyLink
} from "../../api/rest-querier.js";
import { rewire$adjustItemToContentAfterItemCreationInAFolder } from "../actions-helpers/adjust-item-to-content-after-item-creation-in-folder.js";
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
    CLIPBOARD_OPERATION_CUT,
    CLIPBOARD_OPERATION_COPY
} from "../../constants.js";
import { mockFetchError } from "tlp-mocks";

describe("Clipboard actions", () => {
    afterEach(() => {
        restoreRestQuerier();
    });

    it(`When an item is already being pasted
        Then it does nothing`, async () => {
        const context = {
            commit: jasmine.createSpy("commit"),
            state: {
                pasting_in_progress: true
            }
        };

        await pasteItem(context, [{}, {}, {}]);

        expect(context.commit).not.toHaveBeenCalled();
    });

    it("Reject unknown paste operation", async () => {
        const context = {
            commit: jasmine.createSpy("commit"),
            state: {
                operation_type: "unknown_operation"
            }
        };
        const global_context = {
            commit: jasmine.createSpy("commit")
        };
        const adjustItemToContentAfterItemCreationInAFolder = jasmine.createSpy(
            "adjustItemToContentAfterItemCreationInAFolder"
        );
        rewire$adjustItemToContentAfterItemCreationInAFolder(
            adjustItemToContentAfterItemCreationInAFolder
        );

        await pasteItem(context, [{}, {}, global_context]);

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
                operation_type: CLIPBOARD_OPERATION_CUT
            };
            context = {
                commit: jasmine.createSpy("commit"),
                state
            };

            adjustItemToContentAfterItemCreationInAFolder = jasmine.createSpy(
                "adjustItemToContentAfterItemCreationInAFolder"
            );
            rewire$adjustItemToContentAfterItemCreationInAFolder(
                adjustItemToContentAfterItemCreationInAFolder
            );
        });

        const testPasteSuccess = async type => {
            context.state.item_type = type;

            const current_folder = { id: 147 };
            const destination_folder = { id: 147 };
            await pasteItem(context, [destination_folder, current_folder, {}]);
        };

        it("Paste a file", async () => {
            const moveFile = jasmine.createSpy("moveFile");
            rewire$moveFile(moveFile);
            moveFile.and.returnValue(Promise.resolve({ id: 123 }));

            await testPasteSuccess(TYPE_FILE);

            expect(moveFile).toHaveBeenCalledWith(moved_item_id, jasmine.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        });

        it("Paste a folder", async () => {
            const moveFolder = jasmine.createSpy("moveFolder");
            rewire$moveFolder(moveFolder);
            moveFolder.and.returnValue(Promise.resolve({ id: 123 }));

            await testPasteSuccess(TYPE_FOLDER);

            expect(moveFolder).toHaveBeenCalledWith(moved_item_id, jasmine.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        });

        it("Paste an empty document", async () => {
            const moveEmpty = jasmine.createSpy("moveEmpty");
            rewire$moveEmpty(moveEmpty);
            moveEmpty.and.returnValue(Promise.resolve({ id: 123 }));

            await testPasteSuccess(TYPE_EMPTY);

            expect(moveEmpty).toHaveBeenCalledWith(moved_item_id, jasmine.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        });

        it("Paste a wiki document", async () => {
            const moveWiki = jasmine.createSpy("moveWiki");
            rewire$moveWiki(moveWiki);
            moveWiki.and.returnValue(Promise.resolve({ id: 123 }));

            await testPasteSuccess(TYPE_WIKI);

            expect(moveWiki).toHaveBeenCalledWith(moved_item_id, jasmine.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        });

        it("Paste an embedded file", async () => {
            const moveEmbedded = jasmine.createSpy("moveEmbedded");
            rewire$moveEmbedded(moveEmbedded);
            moveEmbedded.and.returnValue(Promise.resolve({ id: 123 }));

            await testPasteSuccess(TYPE_EMBEDDED);

            expect(moveEmbedded).toHaveBeenCalledWith(moved_item_id, jasmine.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        });

        it("Paste a link", async () => {
            const moveLink = jasmine.createSpy("moveLink");
            rewire$moveLink(moveLink);
            moveLink.and.returnValue(Promise.resolve({ id: 123 }));

            await testPasteSuccess(TYPE_LINK);

            expect(moveLink).toHaveBeenCalledWith(moved_item_id, jasmine.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        });

        it(`When item to paste is of an unknown type
            Then the paste is rejected and the clipboard state restored`, async () => {
            context.state.item_type = "unknown_type";
            const global_context = {
                commit: jasmine.createSpy("commit")
            };

            await pasteItem(context, [{}, {}, global_context]);

            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
            expect(adjustItemToContentAfterItemCreationInAFolder).not.toHaveBeenCalled();
        });

        it(`When an error is raised when pasting an item
            Then the paste is rejected and the clipboard state is kept so the user can retry`, async () => {
            context.state.item_type = TYPE_EMPTY;
            const global_context = {
                commit: jasmine.createSpy("commit")
            };

            const moveEmpty = jasmine.createSpy("moveEmpty");
            rewire$moveEmpty(moveEmpty);
            mockFetchError(moveEmpty, {
                status: 500
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
                operation_type: CLIPBOARD_OPERATION_COPY
            };
            context = {
                commit: jasmine.createSpy("commit"),
                state
            };

            adjustItemToContentAfterItemCreationInAFolder = jasmine.createSpy(
                "adjustItemToContentAfterItemCreationInAFolder"
            );
            rewire$adjustItemToContentAfterItemCreationInAFolder(
                adjustItemToContentAfterItemCreationInAFolder
            );
        });

        const testPasteSuccess = async type => {
            context.state.item_type = type;

            const current_folder = { id: 147 };
            const destination_folder = { id: 147 };
            await pasteItem(context, [destination_folder, current_folder, {}]);
        };

        it("Paste a file", async () => {
            const copyFile = jasmine.createSpy("copyFile");
            rewire$copyFile(copyFile);
            copyFile.and.returnValue(Promise.resolve({ id: 123 }));

            await testPasteSuccess(TYPE_FILE);

            expect(copyFile).toHaveBeenCalledWith(copied_item_id, jasmine.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        });

        it("Paste a folder", async () => {
            const copyFolder = jasmine.createSpy("copyFolder");
            rewire$copyFolder(copyFolder);
            copyFolder.and.returnValue(Promise.resolve({ id: 123 }));

            await testPasteSuccess(TYPE_FOLDER);

            expect(copyFolder).toHaveBeenCalledWith(copied_item_id, jasmine.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        });

        it("Paste an empty document", async () => {
            const copyEmpty = jasmine.createSpy("copyEmpty");
            rewire$copyEmpty(copyEmpty);
            copyEmpty.and.returnValue(Promise.resolve({ id: 123 }));

            await testPasteSuccess(TYPE_EMPTY);

            expect(copyEmpty).toHaveBeenCalledWith(copied_item_id, jasmine.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        });

        it("Paste a wiki document", async () => {
            const copyWiki = jasmine.createSpy("copyWiki");
            rewire$copyWiki(copyWiki);
            copyWiki.and.returnValue(Promise.resolve({ id: 123 }));

            await testPasteSuccess(TYPE_WIKI);

            expect(copyWiki).toHaveBeenCalledWith(copied_item_id, jasmine.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        });

        it("Paste an embedded file", async () => {
            const copyEmbedded = jasmine.createSpy("copyEmbedded");
            rewire$copyEmbedded(copyEmbedded);
            copyEmbedded.and.returnValue(Promise.resolve({ id: 123 }));

            await testPasteSuccess(TYPE_EMBEDDED);

            expect(copyEmbedded).toHaveBeenCalledWith(copied_item_id, jasmine.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        });

        it("Paste a link", async () => {
            const copyLink = jasmine.createSpy("copyLink");
            rewire$copyLink(copyLink);
            copyLink.and.returnValue(Promise.resolve({ id: 123 }));

            await testPasteSuccess(TYPE_LINK);

            expect(copyLink).toHaveBeenCalledWith(copied_item_id, jasmine.any(Number));
            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
        });

        it(`When item to paste is of an unknown type
            Then the paste is rejected and the clipboard state restored`, async () => {
            context.state.item_type = "unknown_type";
            const global_context = {
                commit: jasmine.createSpy("commit")
            };

            await pasteItem(context, [{}, {}, global_context]);

            expect(context.commit).toHaveBeenCalledWith("emptyClipboard");
            expect(adjustItemToContentAfterItemCreationInAFolder).not.toHaveBeenCalled();
        });

        it(`When an error is raised when pasting an item
            Then the paste is rejected and the clipboard state is kept so the user can retry`, async () => {
            context.state.item_type = TYPE_EMPTY;
            const global_context = {
                commit: jasmine.createSpy("commit")
            };

            const copyEmpty = jasmine.createSpy("copyEmpty");
            rewire$copyEmpty(copyEmpty);
            mockFetchError(copyEmpty, {
                status: 500
            });

            await pasteItem(context, [{}, {}, global_context]);

            expect(context.commit).not.toHaveBeenCalledWith("emptyClipboard");
            expect(context.commit).toHaveBeenCalledWith("pastingHasFailed");
            expect(adjustItemToContentAfterItemCreationInAFolder).not.toHaveBeenCalled();
        });
    });
});

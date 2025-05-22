/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
import { cancelFileUpload, cancelFolderUpload, cancelVersionUpload } from "./actions-cancel";
import * as rest_querier from "../api/rest-querier";
import type { ActionContext } from "vuex";
import type { Folder, ItemFile, RootState } from "../type";
import type { ConfigurationState } from "./configuration";
import type { Upload } from "tus-js-client";

describe("actions-cancel", () => {
    let context: ActionContext<RootState, RootState>;

    beforeEach(() => {
        const project_id = "101";
        context = {
            commit: vi.fn(),
            dispatch: vi.fn(),
            state: {
                configuration: { project_id } as ConfigurationState,
                current_folder_ascendant_hierarchy: [],
            } as unknown as RootState,
        } as unknown as ActionContext<RootState, RootState>;
    });

    describe("cancelFileUpload", () => {
        let item: ItemFile;

        beforeEach(() => {
            item = {
                uploader: {
                    abort: vi.fn(),
                } as unknown as Upload,
            } as ItemFile;
        });

        it("asks to tus client to abort the upload", async () => {
            await cancelFileUpload(context, item);
            expect(item.uploader?.abort).toHaveBeenCalled();
        });
        it("asks to tus server to abort the upload, because tus client does not do it for us", async () => {
            const cancelUpload = vi.spyOn(rest_querier, "cancelUpload").mockImplementation();
            await cancelFileUpload(context, item);
            expect(cancelUpload).toHaveBeenCalledWith(item);
        });
        it("remove item from the store", async () => {
            await cancelFileUpload(context, item);
            expect(context.commit).toHaveBeenCalledWith("removeItemFromFolderContent", item);
        });
        it("remove item from the store even if there is an error on cancelUpload", async () => {
            vi.spyOn(rest_querier, "cancelUpload").mockImplementation(() => {
                throw new Error("Failed to fetch");
            });
            await cancelFileUpload(context, item);
            expect(context.commit).toHaveBeenCalledWith("removeItemFromFolderContent", item);
        });
    });

    describe("cancelVersionUpload", () => {
        let item: ItemFile;
        beforeEach(() => {
            item = {
                uploader: {
                    abort: vi.fn(),
                } as unknown as Upload,
            } as ItemFile;
        });

        it("asks to tus client to abort the upload", async () => {
            await cancelVersionUpload(context, item);
            expect(item.uploader?.abort).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("removeVersionUploadProgress", item);
        });
    });

    describe("cancelFolderUpload", () => {
        let folder: Folder, item: ItemFile, context: ActionContext<RootState, RootState>;

        beforeEach(() => {
            folder = {
                title: "My folder",
                id: 123,
            } as Folder;

            item = {
                parent_id: folder.id,
                is_uploading_new_version: false,
                uploader: {
                    abort: vi.fn(),
                } as unknown as Upload,
            } as ItemFile;

            context = {
                commit: vi.fn(),
                state: {
                    files_uploads_list: [item],
                } as RootState,
            } as unknown as ActionContext<RootState, RootState>;
        });

        it("should cancel the uploads of all the files being uploaded in the given folder.", async () => {
            await cancelFolderUpload(context, folder);

            expect(item.uploader?.abort).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith("removeItemFromFolderContent", item);
            expect(context.commit).toHaveBeenCalledWith("removeFileFromUploadsList", item);

            expect(context.commit).toHaveBeenCalledWith("resetFolderIsUploading", folder);
        });

        it("should cancel the new version uploads of files being updated in the given folder.", async () => {
            item.is_uploading_new_version = true;

            await cancelFolderUpload(context, folder);

            expect(item.uploader?.abort).toHaveBeenCalled();
            expect(context.commit).not.toHaveBeenCalledWith("removeItemFromFolderContent", item);
            expect(context.commit).not.toHaveBeenCalledWith("removeFileFromUploadsList", item);

            expect(context.commit).toHaveBeenCalledWith("removeVersionUploadProgress", item);

            expect(context.commit).toHaveBeenCalledWith("resetFolderIsUploading", folder);
        });
    });
});

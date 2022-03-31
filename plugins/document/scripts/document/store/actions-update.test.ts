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

import { createNewFileVersion } from "./actions-update";
import * as rest_querier from "../api/rest-querier";
import * as upload_file from "./actions-helpers/upload-file";
import type { ActionContext } from "vuex";
import type { Folder, ItemFile, RootState } from "../type";
import type { ConfigurationState } from "./configuration";

describe("actions-update", () => {
    let context: ActionContext<RootState, RootState>;

    beforeEach(() => {
        const project_id = "101";
        context = {
            commit: jest.fn(),
            dispatch: jest.fn(),
            state: {
                configuration: { project_id } as ConfigurationState,
                current_folder_ascendant_hierarchy: [],
            } as unknown as RootState,
        } as unknown as ActionContext<RootState, RootState>;
    });

    describe("createNewFileVersion", () => {
        let createNewVersion: jest.SpyInstance, uploadVersion: jest.SpyInstance;

        beforeEach(() => {
            createNewVersion = jest.spyOn(rest_querier, "createNewVersion");
            uploadVersion = jest.spyOn(upload_file, "uploadVersion");
        });

        it("does not trigger any upload if the file is empty", async () => {
            const dropped_file = { name: "filename.txt", size: 0, type: "text/plain" } as File;
            const item = {} as ItemFile;

            createNewVersion.mockReturnValue(Promise.resolve());

            await createNewFileVersion(context, [item, dropped_file]);

            expect(uploadVersion).not.toHaveBeenCalled();
        });

        it("uploads a new version of the file and releases the edition lock", async () => {
            const item = {
                id: 45,
                lock_info: null,
                title: "Electronic document management for dummies.pdf",
            } as ItemFile;
            const NO_LOCK = false;

            context.state.folder_content = [{ id: 45 } as Folder];
            const dropped_file = { name: "filename.txt", size: 123, type: "text/plain" } as File;

            const new_version = { upload_href: "/uploads/docman/version/42" };
            createNewVersion.mockReturnValue(Promise.resolve(new_version));

            const uploader = {};
            uploadVersion.mockReturnValue(uploader);

            await createNewFileVersion(context, [item, dropped_file]);

            expect(uploadVersion).toHaveBeenCalled();
            expect(createNewVersion).toHaveBeenCalledWith(
                item,
                "Electronic document management for dummies.pdf",
                "",
                dropped_file,
                NO_LOCK,
                null
            );
        });
    });
});

/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import { mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import * as rest_querier from "../../api/rest-querier";
import { loadFolderContent } from "./load-folder-content";
import type { ActionContext } from "vuex";
import type { Folder, ItemFile, RootState } from "../../type";

describe("loadFolderContent", () => {
    let context: ActionContext<RootState, RootState>, getFolderContent: jest.SpyInstance;

    beforeEach(() => {
        context = {
            commit: jest.fn(),
        } as unknown as ActionContext<RootState, RootState>;

        getFolderContent = jest.spyOn(rest_querier, "getFolderContent");
    });

    it("loads the folder content and sets loading flag", async () => {
        const folder_content = [
            {
                id: 1,
                title: "folder",
                owner: {
                    id: 101,
                },
                last_update_date: "2018-10-03T11:16:11+02:00",
            } as Folder,
            {
                id: 2,
                title: "item",
                owner: {
                    id: 101,
                },
                last_update_date: "2018-08-07T16:42:49+02:00",
            } as ItemFile,
        ];

        getFolderContent.mockReturnValue(folder_content);

        await loadFolderContent(context, 1, Promise.resolve({} as Folder));

        expect(context.commit).toHaveBeenCalledWith("beginLoading");
        expect(context.commit).toHaveBeenCalledWith("saveFolderContent", folder_content);
        expect(context.commit).toHaveBeenCalledWith("stopLoading");
    });

    it("When the folder can't be found, another error screen will be shown", async () => {
        const error_message = "The folder does not exist.";
        mockFetchError(getFolderContent, {
            status: 404,
            error_json: {
                error: {
                    i18n_error_message: error_message,
                },
            },
        });

        await loadFolderContent(context, 1, Promise.resolve({} as Folder));

        expect(context.commit).not.toHaveBeenCalledWith("saveFolderContent");
        expect(context.commit).toHaveBeenCalledWith("error/setFolderLoadingError", error_message);
        expect(context.commit).toHaveBeenCalledWith("stopLoading");
    });

    it("When the user does not have access to the folder, an error will be raised", async () => {
        mockFetchError(getFolderContent, {
            status: 403,
            error_json: {
                error: {
                    i18n_error_message: "No you cannot",
                },
            },
        });

        await loadFolderContent(context, 1, Promise.resolve({} as Folder));

        expect(context.commit).not.toHaveBeenCalledWith("saveFolderContent");
        expect(context.commit).toHaveBeenCalledWith("error/switchFolderPermissionError");
        expect(context.commit).toHaveBeenCalledWith("stopLoading");
    });
});

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
import { loadAscendantHierarchy } from "./load-ascendant-hierarchy";
import type { ActionContext } from "vuex";
import type { Folder, Item, RootState } from "../../type";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

describe("loadAscendantHierarchy", () => {
    let context: ActionContext<RootState, RootState>, getParents: jest.SpyInstance;

    beforeEach(() => {
        context = {
            commit: jest.fn(),
        } as unknown as ActionContext<RootState, RootState>;

        getParents = jest.spyOn(rest_querier, "getParents");
    });

    it("loads the folder parents and sets loading flag", async () => {
        const parents = [
            {
                id: 1,
                title: "Project documentation",
                owner: {
                    id: 101,
                },
                last_update_date: "2018-10-03T11:16:11+02:00",
            } as Folder,
            {
                id: 2,
                title: "folder A",
                owner: {
                    id: 101,
                },
                last_update_date: "2018-08-07T16:42:49+02:00",
            } as Folder,
        ];

        const item = {
            id: 3,
            title: "Current folder",
            owner: {
                id: 101,
                display_name: "user (login)",
            },
            last_update_date: "2018-08-21T17:01:49+02:00",
        } as Folder;

        const expected_parents = [
            {
                id: 2,
                title: "folder A",
                owner: {
                    id: 101,
                },
                last_update_date: "2018-08-07T16:42:49+02:00",
            },
            {
                id: 3,
                title: "Current folder",
                owner: {
                    id: 101,
                    display_name: "user (login)",
                },
                last_update_date: "2018-08-21T17:01:49+02:00",
            },
        ];

        getParents.mockReturnValue(parents);

        await loadAscendantHierarchy(context, 3, Promise.resolve(item));

        expect(context.commit).toHaveBeenCalledWith("beginLoadingAscendantHierarchy");
        expect(context.commit).toHaveBeenCalledWith("saveAscendantHierarchy", expected_parents);
        expect(context.commit).toHaveBeenCalledWith("stopLoadingAscendantHierarchy");
    });

    it("When the parents can't be found, another error screen will be shown", async () => {
        const item = {
            id: 3,
            title: "Current folder",
        } as Folder;

        const error_message = "The folder does not exist.";
        mockFetchError(getParents, {
            status: 404,
            error_json: {
                error: {
                    i18n_error_message: error_message,
                },
            },
        });

        await loadAscendantHierarchy(context, 3, Promise.resolve(item));

        expect(context.commit).not.toHaveBeenCalledWith("saveAscendantHierarchy");
        expect(context.commit).toHaveBeenCalledWith("error/setFolderLoadingError", error_message);
        expect(context.commit).toHaveBeenCalledWith("stopLoadingAscendantHierarchy");
    });

    it("When the item can't be found, another error screen will be shown", async () => {
        const error_message = "The folder does not exist.";

        await loadAscendantHierarchy(
            context,
            3,
            Promise.reject(
                new FetchWrapperError("Not Found", {
                    ok: false,
                    status: 404,
                    statusText: "",
                    json: () =>
                        Promise.resolve({
                            error: {
                                i18n_error_message: error_message,
                            },
                        }),
                } as Response),
            ),
        );

        expect(context.commit).not.toHaveBeenCalledWith("saveAscendantHierarchy");
        expect(context.commit).toHaveBeenCalledWith("error/setFolderLoadingError", error_message);
        expect(context.commit).toHaveBeenCalledWith("stopLoadingAscendantHierarchy");
    });

    it("When the user does not have access to the folder, an error will be raised", async () => {
        mockFetchError(getParents, {
            status: 403,
            error_json: {
                error: {
                    i18n_error_message: "No you cannot",
                },
            },
        });

        const item = {
            id: 3,
            title: "Current folder",
            owner: {
                id: 101,
                display_name: "user (login)",
            },
            last_update_date: "2018-08-21T17:01:49+02:00",
        } as Item;

        await loadAscendantHierarchy(context, 3, Promise.resolve(item));

        expect(context.commit).not.toHaveBeenCalledWith("saveAscendantHierarchy");
        expect(context.commit).toHaveBeenCalledWith("error/switchFolderPermissionError");
        expect(context.commit).toHaveBeenCalledWith("stopLoadingAscendantHierarchy");
    });
});

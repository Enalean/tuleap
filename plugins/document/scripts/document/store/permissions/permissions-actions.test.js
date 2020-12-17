/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
} from "../../constants";
import * as permissions_rest_querier from "../../api/permissions-rest-querier";
import * as rest_querier from "../../api/rest-querier";
import { mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper.js";
import * as error_handler from "../actions-helpers/handle-errors";
import * as permissions_groups from "../../helpers/permissions/ugroups";
import { loadProjectUserGroupsIfNeeded, updatePermissions } from "./permissions-actions";

describe("UpdatePermissions()", () => {
    const permissions = {
        can_read: [],
        can_write: [],
        can_manage: [],
    };

    let context;

    beforeEach(() => {
        context = {
            commit: jest.fn(),
            dispatch: jest.fn(),
            rootState: {
                current_folder: { id: 999, type: TYPE_FOLDER },
            },
        };
    });

    const testPermissionsUpdateSuccess = async (type) => {
        const item = {
            id: 123,
            type: type,
        };

        jest.spyOn(rest_querier, "getItem").mockReturnValue(Promise.resolve(item));

        await updatePermissions(context, [item, permissions]);
    };

    it("Can update file permissions", async () => {
        const putFilePermissions = jest
            .spyOn(permissions_rest_querier, "putFilePermissions")
            .mockReturnValue(Promise.resolve());

        await testPermissionsUpdateSuccess(TYPE_FILE);

        expect(putFilePermissions).toHaveBeenCalled();
        expect(context.commit).toHaveBeenCalledWith(
            "removeItemFromFolderContent",
            expect.any(Object),
            { root: true }
        );
        expect(context.commit).toHaveBeenCalledWith(
            "addJustCreatedItemToFolderContent",
            expect.any(Object),
            { root: true }
        );
        expect(context.commit).toHaveBeenCalledWith(
            "updateCurrentItemForQuickLokDisplay",
            expect.any(Object),
            { root: true }
        );
    });

    it("Can update embedded file permissions", async () => {
        const putEmbeddedFilePermissions = jest
            .spyOn(permissions_rest_querier, "putEmbeddedFilePermissions")
            .mockReturnValue(Promise.resolve());

        await testPermissionsUpdateSuccess(TYPE_EMBEDDED);

        expect(putEmbeddedFilePermissions).toHaveBeenCalled();
        expect(context.commit).toHaveBeenCalledWith(
            "removeItemFromFolderContent",
            expect.any(Object),
            { root: true }
        );
        expect(context.commit).toHaveBeenCalledWith(
            "addJustCreatedItemToFolderContent",
            expect.any(Object),
            { root: true }
        );
        expect(context.commit).toHaveBeenCalledWith(
            "updateCurrentItemForQuickLokDisplay",
            expect.any(Object),
            { root: true }
        );
    });

    it("Can update link permissions", async () => {
        const putLinkPermissions = jest
            .spyOn(permissions_rest_querier, "putLinkPermissions")
            .mockReturnValue(Promise.resolve());

        await testPermissionsUpdateSuccess(TYPE_LINK);

        expect(putLinkPermissions).toHaveBeenCalled();
        expect(context.commit).toHaveBeenCalledWith(
            "removeItemFromFolderContent",
            expect.any(Object),
            { root: true }
        );
        expect(context.commit).toHaveBeenCalledWith(
            "addJustCreatedItemToFolderContent",
            expect.any(Object),
            { root: true }
        );
        expect(context.commit).toHaveBeenCalledWith(
            "updateCurrentItemForQuickLokDisplay",
            expect.any(Object),
            { root: true }
        );
    });

    it("Can update wiki permissions", async () => {
        const putWikiPermissions = jest
            .spyOn(permissions_rest_querier, "putWikiPermissions")
            .mockReturnValue(Promise.resolve());

        await testPermissionsUpdateSuccess(TYPE_WIKI);

        expect(putWikiPermissions).toHaveBeenCalled();
        expect(context.commit).toHaveBeenCalledWith(
            "removeItemFromFolderContent",
            expect.any(Object),
            { root: true }
        );
        expect(context.commit).toHaveBeenCalledWith(
            "addJustCreatedItemToFolderContent",
            expect.any(Object),
            { root: true }
        );
        expect(context.commit).toHaveBeenCalledWith(
            "updateCurrentItemForQuickLokDisplay",
            expect.any(Object),
            { root: true }
        );
    });

    it("Can update empty document permissions", async () => {
        const putEmptyDocumentPermissions = jest
            .spyOn(permissions_rest_querier, "putEmptyDocumentPermissions")
            .mockReturnValue(Promise.resolve());

        await testPermissionsUpdateSuccess(TYPE_EMPTY);

        expect(putEmptyDocumentPermissions).toHaveBeenCalled();
        expect(context.commit).toHaveBeenCalledWith(
            "removeItemFromFolderContent",
            expect.any(Object),
            { root: true }
        );
        expect(context.commit).toHaveBeenCalledWith(
            "addJustCreatedItemToFolderContent",
            expect.any(Object),
            { root: true }
        );
        expect(context.commit).toHaveBeenCalledWith(
            "updateCurrentItemForQuickLokDisplay",
            expect.any(Object),
            { root: true }
        );
    });

    it("Can update folder permissions", async () => {
        const putFolderPermissions = jest
            .spyOn(permissions_rest_querier, "putFolderPermissions")
            .mockReturnValue(Promise.resolve());

        await testPermissionsUpdateSuccess(TYPE_FOLDER);

        expect(putFolderPermissions).toHaveBeenCalled();
        expect(context.commit).toHaveBeenCalledWith(
            "removeItemFromFolderContent",
            expect.any(Object),
            { root: true }
        );
        expect(context.commit).toHaveBeenCalledWith(
            "addJustCreatedItemToFolderContent",
            expect.any(Object),
            { root: true }
        );
        expect(context.commit).toHaveBeenCalledWith(
            "updateCurrentItemForQuickLokDisplay",
            expect.any(Object),
            { root: true }
        );
    });

    it("Can update folder permissions when it is the current folder", async () => {
        const putFolderPermissions = jest
            .spyOn(permissions_rest_querier, "putFolderPermissions")
            .mockReturnValue(Promise.resolve());

        const folder = { id: 123, type: TYPE_FOLDER };
        context.rootState.current_folder = folder;

        jest.spyOn(rest_querier, "getItem").mockReturnValue(Promise.resolve(folder));

        await updatePermissions(context, [folder, permissions]);

        expect(putFolderPermissions).toHaveBeenCalled();
        expect(context.dispatch).toHaveBeenCalledWith("loadFolder", folder.id, { root: true });
        expect(context.commit).toHaveBeenCalledWith("replaceCurrentFolder", expect.any(Object), {
            root: true,
        });
    });

    it("Set an error in modal when is raised while updating permissions", async () => {
        const putEmptyDocumentPermissions = jest.spyOn(
            permissions_rest_querier,
            "putEmptyDocumentPermissions"
        );
        mockFetchError(putEmptyDocumentPermissions, {
            status: 500,
        });
        const handleErrorsModal = jest.spyOn(error_handler, "handleErrorsForModal");

        const getItem = jest.spyOn(rest_querier, "getItem").mockReturnValue(Promise.resolve());

        await updatePermissions(context, [{ id: 123, type: TYPE_EMPTY }, permissions]);

        expect(getItem).not.toHaveBeenCalled();
        expect(handleErrorsModal).toHaveBeenCalled();
    });
});

describe("loadProjectUserGroupsIfNeeded", () => {
    let context;

    beforeEach(() => {
        context = {
            commit: jest.fn(),
            state: {},
        };
    });

    it("Retrieve the project user groups when they are never been loaded", async () => {
        const getProjectUserGroupsWithoutServiceSpecialUGroupsSpy = jest.spyOn(
            permissions_groups,
            "getProjectUserGroupsWithoutServiceSpecialUGroups"
        );
        const project_ugroups = [{ id: "102_3", label: "Project members" }];
        getProjectUserGroupsWithoutServiceSpecialUGroupsSpy.mockReturnValue(
            Promise.resolve(project_ugroups)
        );

        context.state.project_ugroups = null;

        await loadProjectUserGroupsIfNeeded(context);

        expect(getProjectUserGroupsWithoutServiceSpecialUGroupsSpy).toHaveBeenCalled();
        expect(context.commit).toHaveBeenCalledWith("setProjectUserGroups", project_ugroups);
    });

    it("Does not retrieve the project user groups when they have already been retrieved", async () => {
        const getProjectUserGroupsWithoutServiceSpecialUGroupsSpy = jest.spyOn(
            permissions_groups,
            "getProjectUserGroupsWithoutServiceSpecialUGroups"
        );

        context.state.project_ugroups = [{ id: "102_3", label: "Project members" }];

        await loadProjectUserGroupsIfNeeded(context);

        expect(getProjectUserGroupsWithoutServiceSpecialUGroupsSpy).not.toHaveBeenCalled();
    });
});

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
import * as permissions_groups from "../../helpers/permissions/ugroups";
import { loadProjectUserGroupsIfNeeded, updatePermissions } from "./permissions-actions";
import type { Empty, Folder, Item, RootState } from "../../type";
import type { ActionContext } from "vuex";
import type { PermissionsState } from "./permissions-default-state";
import emitter from "../../helpers/emitter";

jest.mock("../../helpers/emitter");

describe("UpdatePermissions()", () => {
    const updated_permissions = {
        apply_permissions_on_children: true,
        can_read: [],
        can_write: [],
        can_manage: [],
    };

    let context: ActionContext<PermissionsState, RootState>;

    beforeEach(() => {
        context = {
            commit: jest.fn(),
            dispatch: jest.fn(),
            rootState: {
                current_folder: { id: 999, type: TYPE_FOLDER } as Folder,
            } as RootState,
        } as unknown as ActionContext<PermissionsState, RootState>;
        jest.clearAllMocks();
    });

    const testPermissionsUpdateSuccess = async (type: string): Promise<void> => {
        const item = {
            id: 123,
            type: type,
        } as Item;

        jest.spyOn(rest_querier, "getItem").mockResolvedValue(item);

        await updatePermissions(context, { item, updated_permissions });
    };

    it("Can update file permissions", async () => {
        const putFilePermissions = jest
            .spyOn(permissions_rest_querier, "putFilePermissions")
            .mockResolvedValue();

        await testPermissionsUpdateSuccess(TYPE_FILE);

        expect(putFilePermissions).toHaveBeenCalled();
        expect(emitter.emit).toHaveBeenCalledWith("item-permissions-have-just-been-updated");
        expect(context.commit).toHaveBeenCalledWith(
            "removeItemFromFolderContent",
            expect.any(Object),
            { root: true },
        );
        expect(context.commit).toHaveBeenCalledWith(
            "addJustCreatedItemToFolderContent",
            expect.any(Object),
            { root: true },
        );
        expect(context.commit).toHaveBeenCalledWith(
            "updateCurrentItemForQuickLokDisplay",
            expect.any(Object),
            { root: true },
        );
    });

    it("Can update embedded file permissions", async () => {
        const putEmbeddedFilePermissions = jest
            .spyOn(permissions_rest_querier, "putEmbeddedFilePermissions")
            .mockResolvedValue();

        await testPermissionsUpdateSuccess(TYPE_EMBEDDED);

        expect(putEmbeddedFilePermissions).toHaveBeenCalled();
        expect(emitter.emit).toHaveBeenCalledWith("item-permissions-have-just-been-updated");
        expect(context.commit).toHaveBeenCalledWith(
            "removeItemFromFolderContent",
            expect.any(Object),
            { root: true },
        );
        expect(context.commit).toHaveBeenCalledWith(
            "addJustCreatedItemToFolderContent",
            expect.any(Object),
            { root: true },
        );
        expect(context.commit).toHaveBeenCalledWith(
            "updateCurrentItemForQuickLokDisplay",
            expect.any(Object),
            { root: true },
        );
    });

    it("Can update link permissions", async () => {
        const putLinkPermissions = jest
            .spyOn(permissions_rest_querier, "putLinkPermissions")
            .mockResolvedValue();

        await testPermissionsUpdateSuccess(TYPE_LINK);

        expect(putLinkPermissions).toHaveBeenCalled();
        expect(emitter.emit).toHaveBeenCalledWith("item-permissions-have-just-been-updated");
        expect(context.commit).toHaveBeenCalledWith(
            "removeItemFromFolderContent",
            expect.any(Object),
            { root: true },
        );
        expect(context.commit).toHaveBeenCalledWith(
            "addJustCreatedItemToFolderContent",
            expect.any(Object),
            { root: true },
        );
        expect(context.commit).toHaveBeenCalledWith(
            "updateCurrentItemForQuickLokDisplay",
            expect.any(Object),
            { root: true },
        );
    });

    it("Can update wiki permissions", async () => {
        const putWikiPermissions = jest
            .spyOn(permissions_rest_querier, "putWikiPermissions")
            .mockResolvedValue();

        await testPermissionsUpdateSuccess(TYPE_WIKI);

        expect(putWikiPermissions).toHaveBeenCalled();
        expect(emitter.emit).toHaveBeenCalledWith("item-permissions-have-just-been-updated");
        expect(context.commit).toHaveBeenCalledWith(
            "removeItemFromFolderContent",
            expect.any(Object),
            { root: true },
        );
        expect(context.commit).toHaveBeenCalledWith(
            "addJustCreatedItemToFolderContent",
            expect.any(Object),
            { root: true },
        );
        expect(context.commit).toHaveBeenCalledWith(
            "updateCurrentItemForQuickLokDisplay",
            expect.any(Object),
            { root: true },
        );
    });

    it("Can update empty document permissions", async () => {
        const putEmptyDocumentPermissions = jest
            .spyOn(permissions_rest_querier, "putEmptyDocumentPermissions")
            .mockResolvedValue();

        await testPermissionsUpdateSuccess(TYPE_EMPTY);

        expect(putEmptyDocumentPermissions).toHaveBeenCalled();
        expect(emitter.emit).toHaveBeenCalledWith("item-permissions-have-just-been-updated");
        expect(context.commit).toHaveBeenCalledWith(
            "removeItemFromFolderContent",
            expect.any(Object),
            { root: true },
        );
        expect(context.commit).toHaveBeenCalledWith(
            "addJustCreatedItemToFolderContent",
            expect.any(Object),
            { root: true },
        );
        expect(context.commit).toHaveBeenCalledWith(
            "updateCurrentItemForQuickLokDisplay",
            expect.any(Object),
            { root: true },
        );
    });

    it("Can update folder permissions", async () => {
        const putFolderPermissions = jest
            .spyOn(permissions_rest_querier, "putFolderPermissions")
            .mockResolvedValue();

        await testPermissionsUpdateSuccess(TYPE_FOLDER);

        expect(putFolderPermissions).toHaveBeenCalled();
        expect(emitter.emit).toHaveBeenCalledWith("item-permissions-have-just-been-updated");
        expect(context.commit).toHaveBeenCalledWith(
            "removeItemFromFolderContent",
            expect.any(Object),
            { root: true },
        );
        expect(context.commit).toHaveBeenCalledWith(
            "addJustCreatedItemToFolderContent",
            expect.any(Object),
            { root: true },
        );
        expect(context.commit).toHaveBeenCalledWith(
            "updateCurrentItemForQuickLokDisplay",
            expect.any(Object),
            { root: true },
        );
    });

    it("Can update folder permissions when it is the current folder", async () => {
        const putFolderPermissions = jest
            .spyOn(permissions_rest_querier, "putFolderPermissions")
            .mockResolvedValue();

        const folder = { id: 123, type: TYPE_FOLDER } as Folder;
        context.rootState.current_folder = folder;

        jest.spyOn(rest_querier, "getItem").mockReturnValue(Promise.resolve(folder));

        await updatePermissions(context, { item: folder, updated_permissions });

        expect(putFolderPermissions).toHaveBeenCalled();
        expect(emitter.emit).toHaveBeenCalledWith("item-permissions-have-just-been-updated");
        expect(context.dispatch).toHaveBeenCalledWith("loadFolder", folder.id, { root: true });
        expect(context.commit).toHaveBeenCalledWith("replaceCurrentFolder", expect.any(Object), {
            root: true,
        });
    });

    it("Set an error in modal when is raised while updating permissions", async () => {
        jest.spyOn(permissions_rest_querier, "putEmptyDocumentPermissions").mockRejectedValue({
            status: 500,
        });

        const item = { id: 123, type: TYPE_EMPTY } as Empty;
        const getItem = jest.spyOn(rest_querier, "getItem");

        await updatePermissions(context, { item, updated_permissions });

        expect(getItem).not.toHaveBeenCalled();
    });
});

describe("loadProjectUserGroupsIfNeeded", () => {
    let context: ActionContext<PermissionsState, RootState>;

    beforeEach(() => {
        context = {
            commit: jest.fn(),
            rootState: {
                current_folder: { id: 123, type: TYPE_FOLDER } as Folder,
                permissions: { project_ugroups: null } as PermissionsState,
            } as RootState,
        } as unknown as ActionContext<PermissionsState, RootState>;
    });

    it("Retrieve the project user groups when they are never been loaded", async () => {
        const getProjectUserGroupsWithoutServiceSpecialUGroupsSpy = jest.spyOn(
            permissions_groups,
            "getProjectUserGroupsWithoutServiceSpecialUGroups",
        );
        const project_ugroups = [{ id: "102_3", label: "Project members" }];
        getProjectUserGroupsWithoutServiceSpecialUGroupsSpy.mockReturnValue(
            Promise.resolve(project_ugroups),
        );

        await loadProjectUserGroupsIfNeeded(context, 102);

        expect(getProjectUserGroupsWithoutServiceSpecialUGroupsSpy).toHaveBeenCalled();
        expect(context.commit).toHaveBeenCalledWith("setProjectUserGroups", project_ugroups);
    });

    it("Does not retrieve the project user groups when they have already been retrieved", async () => {
        const getProjectUserGroupsWithoutServiceSpecialUGroupsSpy = jest.spyOn(
            permissions_groups,
            "getProjectUserGroupsWithoutServiceSpecialUGroups",
        );

        context.rootState.permissions.project_ugroups = [{ id: "102_3", label: "Project members" }];

        await loadProjectUserGroupsIfNeeded(context, 102);

        expect(getProjectUserGroupsWithoutServiceSpecialUGroupsSpy).not.toHaveBeenCalled();
    });
});

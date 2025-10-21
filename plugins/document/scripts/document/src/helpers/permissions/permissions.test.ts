/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
import type { RootState } from "../../type";
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
} from "../../constants";
import { FolderBuilder } from "../../../tests/builders/FolderBuilder";
import type { ActionContext } from "vuex";
import { ItemBuilder } from "../../../tests/builders/ItemBuilder";
import * as permissions_rest_querier from "../../api/permissions-rest-querier";
import * as rest_querier from "../../api/rest-querier";
import { updatePermissions } from "./permissions";
import emitter from "../../helpers/emitter";

vi.mock("../../helpers/emitter");

describe("permissions", () => {
    describe("updatePermissions", () => {
        const updated_permissions = {
            apply_permissions_on_children: true,
            can_read: [],
            can_write: [],
            can_manage: [],
        };

        let context: ActionContext<RootState, RootState>;

        beforeEach(() => {
            context = {
                commit: vi.fn(),
                dispatch: vi.fn(),
                state: {
                    current_folder: new FolderBuilder(999).build(),
                } as RootState,
            } as unknown as ActionContext<RootState, RootState>;
            vi.clearAllMocks();
        });

        const testPermissionsUpdateSuccess = async (type: string): Promise<void> => {
            const item = new ItemBuilder(123).withType(type).build();

            vi.spyOn(rest_querier, "getItem").mockResolvedValue(item);

            await updatePermissions(context, item, updated_permissions);
        };

        it("Can update file permissions", async () => {
            const putFilePermissions = vi
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
            const putEmbeddedFilePermissions = vi
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
            const putLinkPermissions = vi
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
            const putWikiPermissions = vi
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
            const putEmptyDocumentPermissions = vi
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

        it("Can update other type document permissions", async () => {
            const putOtherTypeDocumentPermissions = vi
                .spyOn(permissions_rest_querier, "putOtherTypeDocumentPermissions")
                .mockResolvedValue();

            await testPermissionsUpdateSuccess("whatever");

            expect(putOtherTypeDocumentPermissions).toHaveBeenCalled();
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
            const putFolderPermissions = vi
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
            const putFolderPermissions = vi
                .spyOn(permissions_rest_querier, "putFolderPermissions")
                .mockResolvedValue();

            const folder = new FolderBuilder(123).build();
            context.state.current_folder = folder;

            vi.spyOn(rest_querier, "getItem").mockReturnValue(Promise.resolve(folder));

            await updatePermissions(context, folder, updated_permissions);

            expect(putFolderPermissions).toHaveBeenCalled();
            expect(emitter.emit).toHaveBeenCalledWith("item-permissions-have-just-been-updated");
            expect(context.dispatch).toHaveBeenCalledWith("loadFolder", folder.id, { root: true });
            expect(context.commit).toHaveBeenCalledWith(
                "replaceCurrentFolder",
                expect.any(Object),
                {
                    root: true,
                },
            );
        });

        it("Set an error in modal when is raised while updating permissions", async () => {
            vi.spyOn(permissions_rest_querier, "putEmptyDocumentPermissions").mockRejectedValue({
                status: 500,
            });

            const item = new ItemBuilder(123).withType(TYPE_EMPTY).build();
            const getItem = vi.spyOn(rest_querier, "getItem");

            await updatePermissions(context, item, updated_permissions);

            expect(getItem).not.toHaveBeenCalled();
        });
    });
});

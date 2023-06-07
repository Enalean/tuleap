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
import {
    putEmbeddedFilePermissions,
    putEmptyDocumentPermissions,
    putFilePermissions,
    putFolderPermissions,
    putLinkPermissions,
    putWikiPermissions,
} from "../../api/permissions-rest-querier";
import type { ActionContext } from "vuex";
import type { Item, Permissions, RootState, State } from "../../type";
import type { PermissionsState } from "./permissions-default-state";
import { getItem } from "../../api/rest-querier";
import { getProjectUserGroupsWithoutServiceSpecialUGroups } from "../../helpers/permissions/ugroups";
import emitter from "../../helpers/emitter";

interface PermissionUpdatePayload {
    item: Item;
    updated_permissions: Permissions;
}
export const updatePermissions = async (
    context: ActionContext<PermissionsState, State>,
    payload: PermissionUpdatePayload
): Promise<void> => {
    try {
        const item_id = payload.item.id;
        switch (payload.item.type) {
            case TYPE_FILE:
                await putFilePermissions(item_id, payload.updated_permissions);
                break;
            case TYPE_EMBEDDED:
                await putEmbeddedFilePermissions(item_id, payload.updated_permissions);
                break;
            case TYPE_LINK:
                await putLinkPermissions(item_id, payload.updated_permissions);
                break;
            case TYPE_WIKI:
                await putWikiPermissions(item_id, payload.updated_permissions);
                break;
            case TYPE_EMPTY:
                await putEmptyDocumentPermissions(item_id, payload.updated_permissions);
                break;
            case TYPE_FOLDER:
                await putFolderPermissions(item_id, payload.updated_permissions);
                break;
            default:
                break;
        }
        const updated_item = await getItem(item_id);

        emitter.emit("item-permissions-have-just-been-updated");

        if (context.rootState.current_folder && item_id === context.rootState.current_folder.id) {
            context.commit("replaceCurrentFolder", updated_item, { root: true });
            await context.dispatch("loadFolder", item_id, { root: true });
        } else {
            updated_item.updated = true;
            context.commit("removeItemFromFolderContent", updated_item, { root: true });
            context.commit("addJustCreatedItemToFolderContent", updated_item, { root: true });
            context.commit("updateCurrentItemForQuickLokDisplay", updated_item, { root: true });
        }
    } catch (exception) {
        await context.dispatch("error/handleErrorsForModal", exception, { root: true });
    }
};

export const loadProjectUserGroupsIfNeeded = async (
    context: ActionContext<PermissionsState, RootState>,
    project_id: number
): Promise<void> => {
    if (context.rootState.permissions.project_ugroups !== null) {
        return;
    }

    const project_ugroups = await getProjectUserGroupsWithoutServiceSpecialUGroups(project_id);

    context.commit("setProjectUserGroups", project_ugroups);
};

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

import Vue from "vue";
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
import { getItem } from "../../api/rest-querier";
import { handleErrorsForModal } from "../actions-helpers/handle-errors";
import { getProjectUserGroupsWithoutServiceSpecialUGroups } from "../../helpers/permissions/ugroups.js";

export const updatePermissions = async (context, [item, updated_permissions]) => {
    try {
        switch (item.type) {
            case TYPE_FILE:
                await putFilePermissions(item.id, updated_permissions);
                break;
            case TYPE_EMBEDDED:
                await putEmbeddedFilePermissions(item.id, updated_permissions);
                break;
            case TYPE_LINK:
                await putLinkPermissions(item.id, updated_permissions);
                break;
            case TYPE_WIKI:
                await putWikiPermissions(item.id, updated_permissions);
                break;
            case TYPE_EMPTY:
                await putEmptyDocumentPermissions(item.id, updated_permissions);
                break;
            case TYPE_FOLDER:
                await putFolderPermissions(item.id, updated_permissions);
                break;
            default:
                break;
        }
        const updated_item = await getItem(item.id);

        if (item.id === context.rootState.current_folder.id) {
            context.commit("replaceCurrentFolder", updated_item, { root: true });
            await context.dispatch("loadFolder", item.id, { root: true });
        } else {
            Vue.set(updated_item, "updated", true);
            context.commit("removeItemFromFolderContent", updated_item, { root: true });
            context.commit("addJustCreatedItemToFolderContent", updated_item, { root: true });
            context.commit("updateCurrentItemForQuickLokDisplay", updated_item, { root: true });
        }
    } catch (exception) {
        await handleErrorsForModal(context, exception);
    }
};

export const loadProjectUserGroupsIfNeeded = async (context, project_id) => {
    if (context.state.project_ugroups !== null) {
        return;
    }

    const project_ugroups = await getProjectUserGroupsWithoutServiceSpecialUGroups(project_id);

    context.commit("setProjectUserGroups", project_ugroups);
};

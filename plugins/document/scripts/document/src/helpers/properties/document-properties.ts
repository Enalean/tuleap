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
import type { Folder, FolderProperties, RootState } from "../../type";
import type { ActionContext } from "vuex";
import { getItemWithSize } from "../../api/rest-querier";
import { formatCustomPropertiesForFolderUpdate } from "../properties-helpers/update-data-transformatter-helper";
import type { UpdatePropertiesPayload } from "../../store/properties/properties-actions";
import { updateProperties } from "../../store/properties/properties-actions";

export type DocumentProperties = {
    getFolderProperties(
        context: ActionContext<RootState, RootState>,
        folder_item: Folder,
    ): Promise<FolderProperties | null>;
    updateFolderProperties(
        context: ActionContext<RootState, RootState>,
        item: Folder,
        item_to_update: Folder,
        current_folder: Folder,
        properties_to_update: Array<string>,
        recursion_option: string,
        is_status_property_used: boolean,
    ): Promise<void>;
};

export const getDocumentProperties = (): DocumentProperties => ({
    async getFolderProperties(
        context: ActionContext<RootState, RootState>,
        folder_item: Folder,
    ): Promise<FolderProperties | null> {
        try {
            const folder = await getItemWithSize(folder_item.id);
            return folder.folder_properties;
        } catch (exception) {
            await context.dispatch("error/handleGlobalModalError", exception);
            return null;
        }
    },
    async updateFolderProperties(
        context: ActionContext<RootState, RootState>,
        item: Folder,
        item_to_update: Folder,
        current_folder: Folder,
        properties_to_update: Array<string>,
        recursion_option: string,
        is_status_property_used: boolean,
    ): Promise<void> {
        const updated_item = formatCustomPropertiesForFolderUpdate(
            item_to_update,
            properties_to_update,
            recursion_option,
        );
        const update_payload: UpdatePropertiesPayload = {
            item,
            item_to_update: updated_item,
            current_folder,
            is_status_property_used,
        };
        await updateProperties(context, update_payload);
    },
});

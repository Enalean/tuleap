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

import { addNewFile, getItem } from "../../api/rest-querier";
import { flagItemAsCreated } from "./flag-item-as-created";
import { TYPE_FILE } from "../../constants";
import { uploadFile } from "./upload-file";
import type { ActionContext } from "vuex";
import type { FakeItem, Folder, Property, RootState, CreatedItem } from "../../type";
import emitter from "../../helpers/emitter";

export async function createNewFile(
    context: ActionContext<RootState, RootState>,
    item_to_create: {
        readonly title: string;
        readonly description: string;
        readonly properties: Array<Property> | null;
        readonly file_properties: {
            readonly file: File;
        };
        readonly status?: string | null;
        readonly obsolescence_date?: string | null;
        readonly permissions_for_groups?: Permissions | null;
    },
    parent: Folder,
    should_display_fake_item: boolean
): Promise<CreatedItem> {
    const dropped_file = item_to_create.file_properties.file;
    const new_file = await addNewFile(
        {
            ...item_to_create,
            file_properties: {
                file_name: dropped_file.name,
                file_size: dropped_file.size,
            },
            metadata: item_to_create.properties,
        },
        parent.id
    );
    if (dropped_file.size === 0) {
        const created_item = await getItem(new_file.id);
        flagItemAsCreated(context, created_item);
        emitter.emit("new-item-has-just-been-created", created_item);

        await context.commit("addJustCreatedItemToFolderContent", created_item);
        return new_file;
    }
    if (context.state.folder_content.find(({ id }) => id === new_file.id)) {
        return new_file;
    }
    const fake_item: FakeItem = {
        id: new_file.id,
        title: item_to_create.title,
        parent_id: parent.id,
        type: TYPE_FILE,
        file_type: dropped_file.type,
        is_uploading: true,
        progress: 0,
        upload_error: null,
        is_uploading_in_collapsed_folder: false,
        is_uploading_new_version: false,
        is_approval_table_enabled: false,
        has_approval_table: false,
        approval_table: null,
    };

    fake_item.uploader = uploadFile(context, dropped_file, fake_item, new_file, parent);

    context.commit("addJustCreatedItemToFolderContent", fake_item);
    context.commit("addDocumentToFoldedFolder", [parent, fake_item, should_display_fake_item]);
    context.commit("addFileInUploadsList", fake_item);
    emitter.emit("item-is-being-uploaded");

    let display_progress_bar_on_folder = true;
    if (parent.is_expanded) {
        display_progress_bar_on_folder = false;
    }
    context.commit("toggleCollapsedFolderHasUploadingContent", {
        collapsed_folder: parent,
        toggle: display_progress_bar_on_folder,
    });

    return new_file;
}

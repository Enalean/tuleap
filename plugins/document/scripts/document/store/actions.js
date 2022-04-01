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

import {
    cancelUpload,
    getItem,
    getItemsReferencingSameWikiPage,
    getParents,
} from "../api/rest-querier";

import { buildItemPath } from "./actions-helpers/build-parent-paths";
import { USER_CANNOT_PROPAGATE_DELETION_TO_WIKI_SERVICE } from "../constants";

export * from "./actions-create";
export * from "./actions-retrieve";
export * from "./actions-update";
export * from "./actions-delete";
export * from "./actions-quicklook";

export const refreshLink = async (context, item_to_refresh) => {
    const up_to_date_item = await getItem(item_to_refresh.id);

    context.commit("replaceLinkWithNewVersion", [item_to_refresh, up_to_date_item]);
};

export const refreshWiki = async (context, item_to_refresh) => {
    const up_to_date_item = await getItem(item_to_refresh.id);

    context.commit("replaceWikiWithNewVersion", [item_to_refresh, up_to_date_item]);
};

export const refreshEmbeddedFile = async (context, item_to_refresh) => {
    const up_to_date_item = await getItem(item_to_refresh.id);

    context.commit("replaceEmbeddedFilesWithNewVersion", [item_to_refresh, up_to_date_item]);
};

export const cancelFileUpload = async (context, item) => {
    try {
        item.uploader.abort();
        await cancelUpload(item);
    } catch (e) {
        // do nothing
    } finally {
        context.commit("removeItemFromFolderContent", item);
        context.commit("removeFileFromUploadsList", item);
    }
};

export const cancelVersionUpload = async (context, item) => {
    try {
        item.uploader.abort();
        await cancelUpload(item);
    } catch (e) {
        // do nothing
    } finally {
        context.commit("removeVersionUploadProgress", item);
    }
};

export const cancelFolderUpload = (context, folder) => {
    try {
        const children = context.state.files_uploads_list.filter(
            (item) => item.parent_id === folder.id
        );

        children.forEach((child) => {
            if (child.is_uploading_new_version) {
                cancelVersionUpload(context, child);
            } else {
                cancelFileUpload(context, child);
            }
        });
    } catch (e) {
        // do nothing
    } finally {
        context.commit("resetFolderIsUploading", folder);
    }
};

export const cancelAllFileUploads = (context) => {
    return Promise.all(
        context.state.folder_content
            .filter((item) => item.is_uploading)
            .map((item) => cancelFileUpload(context, item))
    );
};

export const getWikisReferencingSameWikiPage = async (context, item) => {
    try {
        const wiki_page_referencers = await getItemsReferencingSameWikiPage(
            item.wiki_properties.page_id
        );

        return await Promise.all(
            wiki_page_referencers.map((item) =>
                getParents(item.item_id).then((parents) => buildItemPath(item, parents))
            )
        );
    } catch (exception) {
        return USER_CANNOT_PROPAGATE_DELETION_TO_WIKI_SERVICE;
    }
};

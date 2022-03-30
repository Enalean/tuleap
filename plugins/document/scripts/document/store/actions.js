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

import Vue from "vue";
import {
    addNewEmbedded,
    addNewEmpty,
    addNewLink,
    addNewWiki,
    cancelUpload,
    createNewVersion,
    getItem,
    getItemsReferencingSameWikiPage,
    getParents,
    postEmbeddedFile,
    postLinkVersion,
    postNewEmbeddedFileVersionFromEmpty,
    postNewFileVersionFromEmpty,
    postNewLinkVersionFromEmpty,
    postWiki,
} from "../api/rest-querier";

import { getErrorMessage } from "./actions-helpers/handle-errors";
import { uploadVersion, uploadVersionFromEmpty } from "./actions-helpers/upload-file";
import { adjustItemToContentAfterItemCreationInAFolder } from "./actions-helpers/adjust-item-to-content-after-item-creation-in-folder";
import { buildItemPath } from "./actions-helpers/build-parent-paths";
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
    USER_CANNOT_PROPAGATE_DELETION_TO_WIKI_SERVICE,
} from "../constants";
import { addNewFolder } from "../api/rest-querier";
import { handleErrorsForModal } from "./error/error-actions";
import { createNewFile } from "./actions-helpers/create-new-file";

export * from "./actions-retrieve";
export * from "./actions-delete";
export * from "./actions-quicklook";

export const createNewFiles = async (context, [items, parent, current_folder]) => {
    for (const item of items) {
        await createNewItem(context, [item, parent, current_folder]);
    }
};

export const createNewItem = async (context, [item, parent, current_folder]) => {
    try {
        let should_display_item = true;
        let item_reference;

        const item_to_create = JSON.parse(JSON.stringify(item));
        if (item_to_create.obsolescence_date === "") {
            item_to_create.obsolescence_date = null;
        }
        if (item_to_create.properties) {
            item_to_create.metadata = item_to_create.properties;
        }
        switch (item_to_create.type) {
            case TYPE_FILE:
                if (!parent.is_expanded && parent.id !== current_folder.id) {
                    should_display_item = false;
                }

                item_to_create.file_properties = item.file_properties;
                await createNewFile(context, item_to_create, parent, should_display_item);
                break;
            case TYPE_FOLDER:
                item_reference = await addNewFolder(item_to_create, parent.id);

                return adjustItemToContentAfterItemCreationInAFolder(
                    context,
                    parent,
                    current_folder,
                    item_reference.id
                );
            case TYPE_EMPTY:
                item_reference = await addNewEmpty(item_to_create, parent.id);

                return adjustItemToContentAfterItemCreationInAFolder(
                    context,
                    parent,
                    current_folder,
                    item_reference.id
                );
            case TYPE_WIKI:
                item_reference = await addNewWiki(item_to_create, parent.id);

                return adjustItemToContentAfterItemCreationInAFolder(
                    context,
                    parent,
                    current_folder,
                    item_reference.id
                );
            case TYPE_EMBEDDED:
                item_reference = await addNewEmbedded(item_to_create, parent.id);

                return adjustItemToContentAfterItemCreationInAFolder(
                    context,
                    parent,
                    current_folder,
                    item_reference.id
                );
            case TYPE_LINK:
                item_reference = await addNewLink(item_to_create, parent.id);

                return adjustItemToContentAfterItemCreationInAFolder(
                    context,
                    parent,
                    current_folder,
                    item_reference.id
                );
            default:
                throw new Error(
                    "Item type " + item_to_create.type + " is not supported for creation"
                );
        }
    } catch (exception) {
        return handleErrorsForModal(context, exception);
    }
};

export async function createNewFileVersion(context, [item, dropped_file]) {
    try {
        await uploadNewVersion(context, [item, dropped_file, item.title, "", false]);
        Vue.set(item, "updated", true);
    } catch (exception) {
        context.commit("toggleCollapsedFolderHasUploadingContent", [parent, false]);
        const error_json = await exception.response.json();
        throw getErrorMessage(error_json);
    }
}

export const createNewFileVersionFromModal = async (
    context,
    [item, uploaded_file, version_title, changelog, is_file_locked, approval_table_action]
) => {
    try {
        await uploadNewVersion(context, [
            item,
            uploaded_file,
            version_title,
            changelog,
            is_file_locked,
            approval_table_action,
        ]);
        Vue.set(item, "updated", true);
    } catch (exception) {
        return handleErrorsForModal(context, exception);
    }
};

export const createNewEmbeddedFileVersionFromModal = async (
    context,
    [item, new_html_content, version_title, changelog, is_file_locked, approval_table_action]
) => {
    try {
        await postEmbeddedFile(
            item,
            new_html_content,
            version_title,
            changelog,
            is_file_locked,
            approval_table_action
        );
        Vue.set(item, "updated", true);
    } catch (exception) {
        return handleErrorsForModal(context, exception);
    }
};

export const createNewWikiVersionFromModal = async (
    context,
    [item, new_wiki_page, version_title, changelog, is_file_locked]
) => {
    try {
        await postWiki(item, new_wiki_page, version_title, changelog, is_file_locked);
        Vue.set(item, "updated", true);
    } catch (exception) {
        return handleErrorsForModal(context, exception);
    }
};

export const createNewLinkVersionFromModal = async (
    context,
    [item, new_link_url, version_title, changelog, is_file_locked, approval_table_action]
) => {
    try {
        await postLinkVersion(
            item,
            new_link_url,
            version_title,
            changelog,
            is_file_locked,
            approval_table_action
        );
        Vue.set(item, "updated", true);
    } catch (exception) {
        return handleErrorsForModal(context, exception);
    }
};

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

async function uploadNewVersion(
    context,
    [item, uploaded_file, version_title, changelog, is_file_locked, approval_table_action]
) {
    const new_version = await createNewVersion(
        item,
        version_title,
        changelog,
        uploaded_file,
        is_file_locked,
        approval_table_action
    );

    if (uploaded_file.size === 0) {
        return;
    }

    context.commit("addFileInUploadsList", item);
    Vue.set(item, "progress", null);
    Vue.set(item, "upload_error", null);
    Vue.set(item, "is_uploading_new_version", true);

    uploadVersionAndAssignUploader(item, context, uploaded_file, new_version);
}

async function uploadNewFileVersionFromEmptyDocument(context, [item, uploaded_file]) {
    const new_version = await postNewFileVersionFromEmpty(item.id, uploaded_file);
    if (uploaded_file.size === 0) {
        return;
    }

    const updated_item = context.state.folder_content.find(({ id }) => id === item.id);

    context.commit("addFileInUploadsList", updated_item);
    Vue.set(updated_item, "progress", null);
    Vue.set(updated_item, "upload_error", null);
    Vue.set(updated_item, "is_uploading_new_version", true);

    uploadVersionAndAssignUploaderFroEmpty(item, context, uploaded_file, new_version);
}

function uploadVersionAndAssignUploaderFroEmpty(item, context, uploaded_file, new_version) {
    item.uploader = uploadVersionFromEmpty(context, uploaded_file, item, new_version);
}

function uploadVersionAndAssignUploader(item, context, uploaded_file, new_version) {
    item.uploader = uploadVersion(context, uploaded_file, item, new_version);
}

export const addNewUploadFile = async (
    context,
    [dropped_file, parent, title, description, should_display_fake_item]
) => {
    try {
        const item = { title, description, file_properties: { file: dropped_file } };
        await createNewFile(context, item, parent, should_display_fake_item);
    } catch (exception) {
        context.commit("toggleCollapsedFolderHasUploadingContent", [parent, false]);
        const error_json = await exception.response.json();
        throw getErrorMessage(error_json);
    }
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

export const createNewVersionFromEmpty = async (context, [selected_type, item, item_to_update]) => {
    try {
        switch (selected_type) {
            case TYPE_LINK:
                await postNewLinkVersionFromEmpty(item.id, item_to_update.link_properties.link_url);
                break;
            case TYPE_EMBEDDED:
                await postNewEmbeddedFileVersionFromEmpty(
                    item.id,
                    item_to_update.embedded_properties.content
                );
                break;
            case TYPE_FILE:
                await uploadNewFileVersionFromEmptyDocument(context, [
                    item,
                    item_to_update.file_properties.file,
                ]);
                break;
            default:
                await handleErrorsForModal(context, "The wanted type is not supported");
                break;
        }
        const updated_item = await getItem(item.id);
        Vue.set(updated_item, "updated", true);
        context.commit("removeItemFromFolderContent", updated_item);
        context.commit("addJustCreatedItemToFolderContent", updated_item);
        context.commit("updateCurrentItemForQuickLokDisplay", updated_item);
    } catch (exception) {
        await handleErrorsForModal(context, exception);
    }
};

/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
    addNewEmpty,
    addNewWiki,
    addNewFile,
    addNewEmbedded,
    addNewLink,
    cancelUpload,
    createNewVersion,
    deleteUserPreferenciesForFolderInProject,
    addUserLegacyUIPreferency,
    deleteUserPreferenciesForUnderConstructionModal,
    getFolderContent,
    getItem,
    getProject,
    patchUserPreferenciesForFolderInProject,
    patchEmbeddedFile,
    patchWiki,
    patchLink,
    deleteFile,
    deleteLink,
    deleteEmbeddedFile
} from "../api/rest-querier.js";

import {
    getErrorMessage,
    handleErrors,
    handleErrorsForModal,
    handleErrorsForDeletionModal,
    handleErrorsForDocument
} from "./actions-helpers/handle-errors.js";
import { loadFolderContent } from "./actions-helpers/load-folder-content.js";
import { loadAscendantHierarchy } from "./actions-helpers/load-ascendant-hierarchy.js";
import { uploadFile, uploadVersion } from "./actions-helpers/upload-file.js";
import { flagItemAsCreated } from "./actions-helpers/flag-item-as-created.js";
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_WIKI,
    TYPE_LINK
} from "../constants.js";
import { addNewFolder } from "../api/rest-querier";

export const loadRootFolder = async context => {
    try {
        context.commit("beginLoading");
        const project = await getProject(context.state.project_id);
        const root = project.additional_informations.docman.root_item;

        context.commit("setCurrentFolder", root);

        return await loadFolderContent(context, root.id, Promise.resolve(root));
    } catch (exception) {
        return handleErrors(context, exception);
    } finally {
        context.commit("stopLoading");
    }
};

export const getSubfolderContent = async (context, folder_id) => {
    try {
        const sub_items = await getFolderContent(folder_id);

        context.commit("appendSubFolderContent", [folder_id, sub_items]);
    } catch (exception) {
        return handleErrors(context, exception);
    }
};

export const createNewItem = async (context, [item, parent, current_folder]) => {
    async function adjustFileToContentAfterItemCreation(item_id) {
        const created_item = await getItem(item_id);

        flagItemAsCreated(context, created_item);

        if (!parent.is_expanded && parent.id !== current_folder.id) {
            context.commit("addDocumentToFoldedFolder", [parent, created_item, false]);
        }
        return Promise.resolve(context.commit("addJustCreatedItemToFolderContent", created_item));
    }

    try {
        let should_display_item = true;
        let item_reference;
        switch (item.type) {
            case TYPE_FILE:
                if (!parent.is_expanded && parent.id !== current_folder.id) {
                    should_display_item = false;
                }
                await createNewFile(context, item, parent, should_display_item);
                break;
            case TYPE_FOLDER:
                item_reference = await addNewFolder(item, parent.id);

                return adjustFileToContentAfterItemCreation(item_reference.id);
            case TYPE_EMPTY:
                item_reference = await addNewEmpty(item, parent.id);

                return adjustFileToContentAfterItemCreation(item_reference.id);
            case TYPE_WIKI:
                item_reference = await addNewWiki(item, parent.id);

                return adjustFileToContentAfterItemCreation(item_reference.id);
            case TYPE_EMBEDDED:
                item_reference = await addNewEmbedded(item, parent.id);

                return adjustFileToContentAfterItemCreation(item_reference.id);
            case TYPE_LINK:
                item_reference = await addNewLink(item, parent.id);

                return adjustFileToContentAfterItemCreation(item_reference.id);
            default:
                throw new Error("Item type " + item.type + " is not supported for creation");
        }
    } catch (exception) {
        return handleErrorsForModal(context, exception);
    }
};

export const loadDocumentWithAscendentHierarchy = async (context, item_id) => {
    try {
        const item = await getItem(item_id);
        const loading_current_folder_promise = getItem(item.parent_id);
        loadAscendantHierarchy(context, item.parent_id, loading_current_folder_promise);

        return item;
    } catch (exception) {
        return handleErrorsForDocument(context, exception);
    }
};

export const loadFolder = (context, folder_id) => {
    const { is_folder_found_in_hierarchy, current_folder } = getCurrentFolder();
    const loading_current_folder_promise = getLoadingCurrentFolderPromise(current_folder);

    const promises = [loadFolderContent(context, folder_id, loading_current_folder_promise)];
    if (!is_folder_found_in_hierarchy) {
        promises.push(loadAscendantHierarchy(context, folder_id, loading_current_folder_promise));
    }

    return Promise.all(promises);

    function getCurrentFolder() {
        const index_of_folder_in_hierarchy = context.state.current_folder_ascendant_hierarchy.findIndex(
            item => item.id === folder_id
        );
        const is_folder_found_in_hierarchy = index_of_folder_in_hierarchy !== -1;
        const current_folder = is_folder_found_in_hierarchy
            ? switchToFolderWeFoundInHierarchy(index_of_folder_in_hierarchy)
            : context.state.current_folder;

        return {
            is_folder_found_in_hierarchy,
            current_folder
        };
    }

    function switchToFolderWeFoundInHierarchy(index_of_folder_in_hierarchy) {
        context.commit(
            "saveAscendantHierarchy",
            context.state.current_folder_ascendant_hierarchy.slice(
                0,
                index_of_folder_in_hierarchy + 1
            )
        );

        const folder_in_store = context.state.current_folder;
        if (
            folder_in_store !==
            context.state.current_folder_ascendant_hierarchy[index_of_folder_in_hierarchy]
        ) {
            const found_folder =
                context.state.current_folder_ascendant_hierarchy[index_of_folder_in_hierarchy];
            context.commit("setCurrentFolder", found_folder);

            return found_folder;
        }

        return folder_in_store;
    }

    function getLoadingCurrentFolderPromise(current_folder) {
        if (shouldWeRemotelyLoadTheFolder(current_folder, folder_id)) {
            return getItem(folder_id).then(folder => {
                context.commit("setCurrentFolder", folder);

                return folder;
            });
        }

        return Promise.resolve(context.state.current_folder);
    }

    function shouldWeRemotelyLoadTheFolder(current_folder) {
        return !current_folder || current_folder.id !== folder_id;
    }
};

export async function updateFile(context, [item, dropped_file]) {
    try {
        await uploadNewVersion(context, [
            item,
            dropped_file,
            item.title,
            "",
            item.lock_info !== null
        ]);
        Vue.set(item, "updated", true);
    } catch (exception) {
        context.commit("toggleCollapsedFolderHasUploadingContent", [parent, false]);
        const error_json = await exception.response.json();
        throw getErrorMessage(error_json);
    }
}

export const updateFileFromModal = async (
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
            approval_table_action
        ]);
        Vue.set(item, "updated", true);
    } catch (exception) {
        return handleErrorsForModal(context, exception);
    }
};

export const updateEmbeddedFileFromModal = async (
    context,
    [item, new_html_content, version_title, changelog, is_file_locked, approval_table_action]
) => {
    try {
        await patchEmbeddedFile(
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

export const updateWikiFromModal = async (
    context,
    [item, new_wiki_page, version_title, changelog, is_file_locked]
) => {
    try {
        await patchWiki(item, new_wiki_page, version_title, changelog, is_file_locked);
        Vue.set(item, "updated", true);
    } catch (exception) {
        return handleErrorsForModal(context, exception);
    }
};

export const updateLinkFromModal = async (
    context,
    [item, new_link_url, version_title, changelog, is_file_locked, approval_table_action]
) => {
    try {
        await patchLink(
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

    const updated_item = context.state.folder_content.find(({ id }) => id === item.id);
    context.commit("addFileInUploadsList", updated_item);
    Vue.set(updated_item, "progress", null);
    Vue.set(updated_item, "upload_error", null);
    Vue.set(updated_item, "is_uploading_new_version", true);

    item.uploader = uploadVersion(context, uploaded_file, item, new_version);
}

export const setUserPreferenciesForFolder = (context, [folder_id, should_be_closed]) => {
    if (context.state.user_id === 0) {
        return;
    }

    try {
        if (should_be_closed) {
            return deleteUserPreferenciesForFolderInProject(
                context.state.user_id,
                context.state.project_id,
                folder_id
            );
        }

        return patchUserPreferenciesForFolderInProject(
            context.state.user_id,
            context.state.project_id,
            folder_id
        );
    } catch (exception) {
        return handleErrors(context, exception);
    }
};

async function createNewFile(
    context,
    { title, description, file_properties, status, obsolescence_date },
    parent,
    should_display_fake_item
) {
    const dropped_file = file_properties.file;
    const new_file = await addNewFile(
        {
            title: title,
            description: description,
            file_properties: {
                file_name: dropped_file.name,
                file_size: dropped_file.size
            },
            status: status,
            obsolescence_date: obsolescence_date
        },
        parent.id
    );
    if (dropped_file.size === 0) {
        const created_item = await getItem(new_file.id);
        flagItemAsCreated(context, created_item);

        return Promise.resolve(context.commit("addJustCreatedItemToFolderContent", created_item));
    }
    if (context.state.folder_content.find(({ id }) => id === new_file.id)) {
        return;
    }
    const fake_item = {
        id: new_file.id,
        title: dropped_file.name,
        parent_id: parent.id,
        type: TYPE_FILE,
        file_type: dropped_file.type,
        is_uploading: true,
        progress: 0,
        uploader: null,
        upload_error: null
    };

    fake_item.uploader = uploadFile(context, dropped_file, fake_item, new_file, parent);

    context.commit("addJustCreatedItemToFolderContent", fake_item);
    context.commit("addDocumentToFoldedFolder", [parent, fake_item, should_display_fake_item]);
    context.commit("addFileInUploadsList", fake_item);

    let display_progress_bar_on_folder = true;
    if (parent.is_expanded) {
        display_progress_bar_on_folder = false;
    }
    context.commit("toggleCollapsedFolderHasUploadingContent", [
        parent,
        display_progress_bar_on_folder
    ]);
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
            item => item.parent_id === folder.id
        );

        children.forEach(child => {
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

export const cancelAllFileUploads = context => {
    return Promise.all(
        context.state.folder_content
            .filter(item => item.is_uploading)
            .map(item => cancelFileUpload(context, item))
    );
};

export const setUserPreferenciesForUI = async context => {
    try {
        return await addUserLegacyUIPreferency(context.state.user_id, context.state.project_id);
    } catch (exception) {
        return handleErrors(context, exception);
    }
};

export const unsetUnderConstructionUserPreference = async context => {
    try {
        return await deleteUserPreferenciesForUnderConstructionModal(
            context.state.user_id,
            context.state.project_id
        );
    } catch (exception) {
        return handleErrors(context, exception);
    } finally {
        context.commit("removeIsUnderConstruction");
    }
};

export const deleteItem = async (context, item) => {
    try {
        switch (item.type) {
            case TYPE_FILE:
                await deleteFile(item);
                break;
            case TYPE_LINK:
                await deleteLink(item);
                break;
            case TYPE_EMBEDDED:
                await deleteEmbeddedFile(item);
                break;
        }

        if (
            context.state.currently_previewed_item &&
            item.id === context.state.currently_previewed_item.id
        ) {
            context.commit("updateCurrentlyPreviewedItem", null);
        }

        context.commit("removeItemFromFolderContent", item);
        context.commit("showPostDeletionNotification");
    } catch (exception) {
        return handleErrorsForDeletionModal(context, exception, item);
    }
};

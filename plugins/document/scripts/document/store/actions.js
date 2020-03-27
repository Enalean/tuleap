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
    addNewFile,
    addNewLink,
    addNewWiki,
    addUserLegacyUIPreferency,
    cancelUpload,
    createNewVersion,
    deleteEmbeddedFile,
    deleteEmptyDocument,
    deleteFile,
    deleteFolder,
    deleteLink,
    deleteLockEmbedded,
    deleteLockEmpty,
    deleteLockFile,
    deleteLockLink,
    deleteLockWiki,
    deleteUserPreferenciesForFolderInProject,
    deleteWiki,
    getDocumentManagerServiceInformation,
    getFolderContent,
    getItem,
    getItemsReferencingSameWikiPage,
    getParents,
    getPreferenceForEmbeddedDisplay,
    patchUserPreferenciesForFolderInProject,
    postEmbeddedFile,
    postLinkVersion,
    postLockEmbedded,
    postLockEmpty,
    postLockFile,
    postLockLink,
    postLockWiki,
    postNewEmbeddedFileVersionFromEmpty,
    postNewFileVersionFromEmpty,
    postNewLinkVersionFromEmpty,
    postWiki,
    putEmbeddedFileMetadata,
    putEmbeddedFilePermissions,
    putEmptyDocumentMetadata,
    putEmptyDocumentPermissions,
    putFileMetadata,
    putFilePermissions,
    putFolderDocumentMetadata,
    putFolderPermissions,
    putLinkMetadata,
    putLinkPermissions,
    putWikiMetadata,
    putWikiPermissions,
    removeUserPreferenceForEmbeddedDisplay,
    setNarrowModeForEmbeddedDisplay,
} from "../api/rest-querier.js";

import {
    getErrorMessage,
    handleErrors,
    handleErrorsForDeletionModal,
    handleErrorsForDocument,
    handleErrorsForLock,
    handleErrorsForModal,
} from "./actions-helpers/handle-errors.js";
import { loadFolderContent } from "./actions-helpers/load-folder-content.js";
import { loadAscendantHierarchy } from "./actions-helpers/load-ascendant-hierarchy.js";
import {
    uploadFile,
    uploadVersion,
    uploadVersionFromEmpty,
} from "./actions-helpers/upload-file.js";
import { flagItemAsCreated } from "./actions-helpers/flag-item-as-created.js";
import { adjustItemToContentAfterItemCreationInAFolder } from "./actions-helpers/adjust-item-to-content-after-item-creation-in-folder.js";
import { buildItemPath } from "./actions-helpers/build-parent-paths.js";
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
    USER_CANNOT_PROPAGATE_DELETION_TO_WIKI_SERVICE,
} from "../constants.js";
import { addNewFolder } from "../api/rest-querier";
import { getCustomMetadata } from "../helpers/metadata-helpers/custom-metadata-helper.js";
import { formatCustomMetadataForFolderUpdate } from "../helpers/metadata-helpers/data-transformatter-helper.js";
import { getProjectUserGroupsWithoutServiceSpecialUGroups } from "../helpers/permissions/ugroups.js";

export const loadRootFolder = async (context) => {
    try {
        context.commit("beginLoading");
        const service = await getDocumentManagerServiceInformation(context.state.project_id);
        const root = service.root_item;

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
    try {
        let should_display_item = true;
        let item_reference;

        const item_to_create = JSON.parse(JSON.stringify(item));
        if (item_to_create.obsolescence_date === "") {
            item_to_create.obsolescence_date = null;
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

export const loadDocument = async (context, item_id) => {
    try {
        return await getItem(item_id);
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
            (item) => item.id === folder_id
        );
        const is_folder_found_in_hierarchy = index_of_folder_in_hierarchy !== -1;
        const current_folder = is_folder_found_in_hierarchy
            ? switchToFolderWeFoundInHierarchy(index_of_folder_in_hierarchy)
            : context.state.current_folder;

        return {
            is_folder_found_in_hierarchy,
            current_folder,
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
            return getItem(folder_id).then((folder) => {
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

export async function createNewFileVersion(context, [item, dropped_file]) {
    try {
        await uploadNewVersion(context, [
            item,
            dropped_file,
            item.title,
            "",
            item.lock_info !== null,
        ]);
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

    const updated_item = context.state.folder_content.find(({ id }) => id === item.id);
    context.commit("addFileInUploadsList", updated_item);
    Vue.set(updated_item, "progress", null);
    Vue.set(updated_item, "upload_error", null);
    Vue.set(updated_item, "is_uploading_new_version", true);

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
    { title, description, file_properties, status, obsolescence_date, metadata },
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
                file_size: dropped_file.size,
            },
            status: status,
            obsolescence_date: obsolescence_date,
            metadata: metadata,
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
        title: title,
        parent_id: parent.id,
        type: TYPE_FILE,
        file_type: dropped_file.type,
        is_uploading: true,
        progress: 0,
        uploader: null,
        upload_error: null,
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
        display_progress_bar_on_folder,
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

export const setUserPreferenciesForUI = async (context) => {
    try {
        return await addUserLegacyUIPreferency(context.state.user_id, context.state.project_id);
    } catch (exception) {
        return handleErrors(context, exception);
    }
};

export const deleteItem = async (context, [item, additional_options]) => {
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
            case TYPE_WIKI:
                await deleteWiki(item, additional_options);
                break;
            case TYPE_EMPTY:
                await deleteEmptyDocument(item);
                break;
            case TYPE_FOLDER:
                await deleteFolder(item, additional_options);
                break;
        }

        if (
            context.state.currently_previewed_item &&
            item.id === context.state.currently_previewed_item.id
        ) {
            context.commit("updateCurrentlyPreviewedItem", null);
        }

        context.commit("clipboard/emptyClipboardAfterItemDeletion", item);
        context.commit("removeItemFromFolderContent", item);
        context.commit("showPostDeletionNotification");
    } catch (exception) {
        return handleErrorsForDeletionModal(context, exception, item);
    }
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

export const lockDocument = async (context, item) => {
    try {
        switch (item.type) {
            case TYPE_FILE:
                await postLockFile(item);
                break;
            case TYPE_EMBEDDED:
                await postLockEmbedded(item);
                break;
            case TYPE_WIKI:
                await postLockWiki(item);
                break;
            case TYPE_LINK:
                await postLockLink(item);
                break;
            case TYPE_EMPTY:
                await postLockEmpty(item);
                break;
            default:
                break;
        }

        const updated_item = await getItem(item.id);
        context.commit("replaceLockInfoWithNewVersion", [item, updated_item.lock_info]);
    } catch (exception) {
        return handleErrorsForLock(context, exception);
    }
};

export const unlockDocument = async (context, item) => {
    try {
        switch (item.type) {
            case TYPE_FILE:
                await deleteLockFile(item);
                break;
            case TYPE_EMBEDDED:
                await deleteLockEmbedded(item);
                break;
            case TYPE_WIKI:
                await deleteLockWiki(item);
                break;
            case TYPE_LINK:
                await deleteLockLink(item);
                break;
            case TYPE_EMPTY:
                await deleteLockEmpty(item);
                break;
            default:
                break;
        }
        const updated_item = await getItem(item.id);
        context.commit("replaceLockInfoWithNewVersion", [item, updated_item.lock_info]);
    } catch (exception) {
        return handleErrorsForLock(context, exception);
    }
};

export const displayEmbeddedInNarrowMode = async (context, item) => {
    try {
        await setNarrowModeForEmbeddedDisplay(
            context.state.user_id,
            context.state.project_id,
            item.id
        );
        context.commit("shouldDisplayEmbeddedInLargeMode", false);
    } catch (exception) {
        return handleErrors(context, exception);
    }
};

export const displayEmbeddedInLargeMode = async (context, item) => {
    try {
        await removeUserPreferenceForEmbeddedDisplay(
            context.state.user_id,
            context.state.project_id,
            item.id
        );
        context.commit("shouldDisplayEmbeddedInLargeMode", true);
    } catch (exception) {
        return handleErrors(context, exception);
    }
};

export const getEmbeddedFileDisplayPreference = async (context, item) => {
    try {
        return await getPreferenceForEmbeddedDisplay(
            context.state.user_id,
            context.state.project_id,
            item.id
        );
    } catch (exception) {
        return handleErrors(context, exception);
    }
};

export const updateMetadata = async (context, [item, item_to_update, current_folder]) => {
    const custom_metadata = getCustomMetadata(item_to_update.metadata);
    let obsolescence_date = item_to_update.obsolescence_date;
    if (obsolescence_date === "") {
        obsolescence_date = null;
    }
    try {
        switch (item_to_update.type) {
            case TYPE_FILE:
                await putFileMetadata(
                    item_to_update.id,
                    item_to_update.title,
                    item_to_update.description,
                    item_to_update.owner.id,
                    item_to_update.status,
                    obsolescence_date,
                    custom_metadata
                );
                break;
            case TYPE_EMBEDDED:
                await putEmbeddedFileMetadata(
                    item_to_update.id,
                    item_to_update.title,
                    item_to_update.description,
                    item_to_update.owner.id,
                    item_to_update.status,
                    obsolescence_date,
                    custom_metadata
                );
                break;
            case TYPE_LINK:
                await putLinkMetadata(
                    item_to_update.id,
                    item_to_update.title,
                    item_to_update.description,
                    item_to_update.owner.id,
                    item_to_update.status,
                    obsolescence_date,
                    custom_metadata
                );
                break;
            case TYPE_WIKI:
                await putWikiMetadata(
                    item_to_update.id,
                    item_to_update.title,
                    item_to_update.description,
                    item_to_update.owner.id,
                    item_to_update.status,
                    obsolescence_date,
                    custom_metadata
                );
                break;
            case TYPE_EMPTY:
                await putEmptyDocumentMetadata(
                    item_to_update.id,
                    item_to_update.title,
                    item_to_update.description,
                    item_to_update.owner.id,
                    item_to_update.status,
                    obsolescence_date,
                    custom_metadata
                );
                break;
            case TYPE_FOLDER:
                await putFolderDocumentMetadata(
                    item_to_update.id,
                    item_to_update.title,
                    item_to_update.description,
                    item_to_update.owner.id,
                    {
                        value: item_to_update.status.value,
                        recursion: item_to_update.status.recursion,
                    },
                    obsolescence_date,
                    custom_metadata
                );
                break;
            default:
                break;
        }
        const updated_item = await getItem(item.id);

        if (item.id === current_folder.id) {
            context.commit("replaceCurrentFolder", updated_item);
            await context.dispatch("loadFolder", item.id);
        } else {
            Vue.set(updated_item, "updated", true);
            context.commit("removeItemFromFolderContent", updated_item);
            context.commit("addJustCreatedItemToFolderContent", updated_item);
            context.commit("updateCurrentItemForQuickLokDisplay", updated_item);
        }
    } catch (exception) {
        await handleErrorsForModal(context, exception);
    }
};

export const updateFolderMetadata = async (
    context,
    [item, item_to_update, current_folder, metadata_list_to_update, recursion_option]
) => {
    formatCustomMetadataForFolderUpdate(item_to_update, metadata_list_to_update, recursion_option);
    await updateMetadata(context, [item, item_to_update, current_folder]);
};

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

        if (item.id === context.state.current_folder.id) {
            context.commit("replaceCurrentFolder", updated_item);
            await context.dispatch("loadFolder", item.id);
        } else {
            Vue.set(updated_item, "updated", true);
            context.commit("removeItemFromFolderContent", updated_item);
            context.commit("addJustCreatedItemToFolderContent", updated_item);
            context.commit("updateCurrentItemForQuickLokDisplay", updated_item);
        }
    } catch (exception) {
        await handleErrorsForModal(context, exception);
    }
};

export const loadProjectUserGroupsIfNeeded = async (context) => {
    if (context.state.project_ugroups !== null) {
        return;
    }

    const project_ugroups = await getProjectUserGroupsWithoutServiceSpecialUGroups(
        context.state.project_id
    );

    context.commit("setProjectUserGroups", project_ugroups);
};

export const toggleQuickLook = async (context, item_id) => {
    try {
        context.commit("beginLoadingCurrentlyPreviewedItem");
        const item = await getItem(item_id);

        context.commit("updateCurrentlyPreviewedItem", item);
        context.commit("toggleQuickLook", true);
    } catch (exception) {
        await handleErrorsForDocument(context, exception);
    } finally {
        context.commit("stopLoadingCurrentlyPreviewedItem");
    }
};

export const removeQuickLook = (context) => {
    context.commit("updateCurrentlyPreviewedItem", null);
    context.commit("toggleQuickLook", false);
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

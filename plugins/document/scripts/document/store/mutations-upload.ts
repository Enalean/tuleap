/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

import type { FakeItem, Folder, State, ApprovableDocument, ItemFile, Item } from "../type";
import { isFakeItem, isFolder } from "../helpers/type-check-helper";

export function addFileInUploadsList(state: State, file: FakeItem): void {
    removeFileFromUploadsList(state, file);
    state.files_uploads_list.unshift(file);
}

export function removeVersionUploadProgress(state: State, uploaded_item: FakeItem): void {
    uploaded_item.progress = null;
    uploaded_item.is_uploading_new_version = false;
    removeFileFromUploadsList(state, uploaded_item);
}

export function removeFileFromUploadsList(state: State, uploaded_file: FakeItem | Item): void {
    const file_index = state.files_uploads_list.findIndex((file) => file.id === uploaded_file.id);
    if (file_index === -1) {
        return;
    }

    state.files_uploads_list.splice(file_index, 1);

    if (state.files_uploads_list.length === 0) {
        const folder_index = state.folder_content.findIndex(
            (item) => item.id === uploaded_file.parent_id
        );
        if (folder_index === -1) {
            const folder_content_item = state.folder_content[folder_index];
            if (folder_content_item && isFolder(folder_content_item)) {
                toggleCollapsedFolderHasUploadingContent(state, {
                    collapsed_folder: folder_content_item,
                    toggle: false,
                });
            }
        }
    }
}

export function emptyFilesUploadsList(state: State): void {
    state.files_uploads_list = [];
}

export function initializeFolderProperties(state: State, folder: Folder): void {
    const item = state.folder_content.find((item) => item.id === folder.id);
    if (item === undefined) {
        return;
    }

    if (isFakeItem(item) || isFolder(item)) {
        item.is_uploading_in_collapsed_folder = false;
        item.progress = null;
    }
}

export interface CollapseFolderPayload {
    collapsed_folder: Folder;
    toggle: boolean;
}

export function toggleCollapsedFolderHasUploadingContent(
    state: State,
    payload: CollapseFolderPayload
): void {
    const folder_index = state.folder_content.findIndex(
        (item) => item.id === payload.collapsed_folder.id
    );
    if (folder_index === -1) {
        return;
    }

    payload.collapsed_folder.is_uploading_in_collapsed_folder = payload.toggle;
    payload.collapsed_folder.progress = 0;

    state.folder_content.splice(folder_index, 1, payload.collapsed_folder);
}

export function updateFolderProgressbar(state: State, collapsed_folder: Folder): void {
    const folder_index = state.folder_content.findIndex((item) => item.id === collapsed_folder.id);
    if (folder_index === -1) {
        return;
    }

    const children = state.files_uploads_list.reduce(function (progresses: Array<number>, item) {
        if (item.parent_id === collapsed_folder.id && item.progress) {
            progresses.push(item.progress);
        }
        return progresses;
    }, []);

    if (!children.length) {
        return;
    }

    const total = children.reduce((total, item_progress) => total + item_progress, 0);
    collapsed_folder.progress = Math.trunc(total / children.length);

    state.folder_content.splice(folder_index, 1, collapsed_folder);
}

export function resetFolderIsUploading(state: State, folder: Folder): void {
    const folder_index = state.folder_content.findIndex((item) => item.id === folder.id);
    if (folder_index === -1) {
        return;
    }

    folder.is_uploading_in_collapsed_folder = false;
    folder.progress = 0;

    state.folder_content.splice(folder_index, 1, folder);
}

export interface ReplaceFilePayload {
    existing_item: ItemFile;
    new_version: ItemFile;
}

export function replaceFileWithNewVersion(state: State, payload: ReplaceFilePayload): void {
    payload.existing_item.file_properties = payload.new_version.file_properties;
    payload.existing_item.lock_info = payload.new_version.lock_info;

    replaceApprovalTables(payload.existing_item, payload.new_version);
}

function replaceApprovalTables(
    existing_item: ApprovableDocument,
    new_version: ApprovableDocument
): void {
    existing_item.has_approval_table = new_version.has_approval_table;
    existing_item.is_approval_table_enabled = new_version.is_approval_table_enabled;
    existing_item.approval_table = new_version.approval_table;
}

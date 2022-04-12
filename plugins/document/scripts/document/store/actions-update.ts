/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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
import type { ActionContext } from "vuex";
import type { ApprovalTable, Embedded, Empty, ItemFile, Link, RootState, Wiki } from "../type";
import { uploadNewVersion } from "./actions-helpers/upload-new-version";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import {
    getItem,
    postEmbeddedFile,
    postLinkVersion,
    postNewEmbeddedFileVersionFromEmpty,
    postNewFileVersionFromEmpty,
    postNewLinkVersionFromEmpty,
    postWiki,
} from "../api/rest-querier";
import { TYPE_EMBEDDED, TYPE_FILE, TYPE_LINK } from "../constants";
import { uploadVersionFromEmpty } from "./actions-helpers/upload-file";
import type { CreatedItemFileProperties } from "../type";
import { isEmpty, isFakeItem } from "../helpers/type-check-helper";
import emitter from "../helpers/emitter";
import { getErrorMessage } from "../helpers/properties-helpers/error-handler-helper";

export async function createNewFileVersion(
    context: ActionContext<RootState, RootState>,
    [item, dropped_file]: [ItemFile, File]
): Promise<void> {
    try {
        await uploadNewVersion(context, [item, dropped_file, item.title, "", false, null]);
        Vue.set(item, "updated", true);
    } catch (exception) {
        context.commit("toggleCollapsedFolderHasUploadingContent", [parent, false]);
        if (exception instanceof FetchWrapperError) {
            const error_json = await exception.response.json();
            throw getErrorMessage(error_json);
        }
        throw exception;
    }
}

export const createNewFileVersionFromModal = async (
    context: ActionContext<RootState, RootState>,
    [item, uploaded_file, version_title, changelog, is_file_locked, approval_table_action]: [
        ItemFile,
        File,
        string,
        string,
        boolean,
        ApprovalTable | null
    ]
): Promise<void> => {
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
        emitter.emit("item-is-being-uploaded");
    } catch (exception) {
        await context.dispatch("error/handleErrorsForModal", exception);
    }
};

export const createNewEmbeddedFileVersionFromModal = async (
    context: ActionContext<RootState, RootState>,
    [item, new_html_content, version_title, changelog, is_file_locked, approval_table_action]: [
        Embedded,
        string,
        string,
        string,
        boolean,
        ApprovalTable | null
    ]
): Promise<void> => {
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
        emitter.emit("item-has-just-been-updated");
    } catch (exception) {
        await context.dispatch("error/handleErrorsForModal", exception);
    }
};

export const createNewWikiVersionFromModal = async (
    context: ActionContext<RootState, RootState>,
    [item, new_wiki_page, version_title, changelog, is_file_locked]: [
        Wiki,
        string,
        string,
        string,
        boolean
    ]
): Promise<void> => {
    try {
        await postWiki(item, new_wiki_page, version_title, changelog, is_file_locked);
        Vue.set(item, "updated", true);
        emitter.emit("item-has-just-been-updated");
    } catch (exception) {
        await context.dispatch("error/handleErrorsForModal", exception);
    }
};

export const createNewLinkVersionFromModal = async (
    context: ActionContext<RootState, RootState>,
    [item, new_link_url, version_title, changelog, is_file_locked, approval_table_action]: [
        Link,
        string,
        string,
        string,
        boolean,
        ApprovalTable | null
    ]
): Promise<void> => {
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
        emitter.emit("item-has-just-been-updated");
    } catch (exception) {
        await context.dispatch("error/handleErrorsForModal", exception);
    }
};

export interface NewVersionFromEmptyInformation {
    link_properties: {
        link_url: string;
    };
    file_properties: {
        file: File;
    };
    embedded_properties: {
        content: string;
    };
}

export const createNewVersionFromEmpty = async (
    context: ActionContext<RootState, RootState>,
    [selected_type, item, item_to_update]: [string, Empty, NewVersionFromEmptyInformation]
): Promise<void> => {
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
                await context.dispatch(
                    "error/handleErrorsForModal",
                    "The wanted type is not supported"
                );
                break;
        }
        const updated_item = await getItem(item.id);
        Vue.set(updated_item, "updated", true);
        if (selected_type === TYPE_LINK || selected_type === TYPE_EMBEDDED) {
            emitter.emit("item-has-just-been-updated");
        } else {
            emitter.emit("item-is-being-uploaded");
        }
        context.commit("removeItemFromFolderContent", updated_item);
        context.commit("addJustCreatedItemToFolderContent", updated_item);
        context.commit("updateCurrentItemForQuickLokDisplay", updated_item);
    } catch (exception) {
        await context.dispatch("error/handleErrorsForModal", exception);
    }
};

async function uploadNewFileVersionFromEmptyDocument(
    context: ActionContext<RootState, RootState>,
    [item, uploaded_file]: [Empty, File]
): Promise<void> {
    const new_version = await postNewFileVersionFromEmpty(item.id, uploaded_file);
    if (uploaded_file.size === 0) {
        return;
    }

    const updated_item = context.state.folder_content.find(({ id }) => id === item.id);

    if (updated_item && (isFakeItem(updated_item) || isEmpty(updated_item))) {
        context.commit("addFileInUploadsList", updated_item);
        Vue.set(updated_item, "progress", null);
        Vue.set(updated_item, "upload_error", null);
        Vue.set(updated_item, "is_uploading_new_version", true);
    }

    uploadVersionAndAssignUploaderFromEmpty(item, context, uploaded_file, new_version);
}

function uploadVersionAndAssignUploaderFromEmpty(
    item: Empty,
    context: ActionContext<RootState, RootState>,
    uploaded_file: File,
    new_version: CreatedItemFileProperties
): void {
    item.uploader = uploadVersionFromEmpty(context, uploaded_file, item, new_version);
}

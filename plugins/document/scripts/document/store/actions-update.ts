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

import Vue from "vue";
import { getErrorMessage } from "./actions-helpers/handle-errors";
import type { ActionContext } from "vuex";
import type { ApprovalTable, Embedded, ItemFile, Link, RootState, Wiki } from "../type";
import { uploadNewVersion } from "./actions-helpers/upload-new-version";
import { FetchWrapperError } from "tlp";
import { postEmbeddedFile, postLinkVersion, postWiki } from "../api/rest-querier";

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
    } catch (exception) {
        await context.dispatch("error/handleErrorsForModal", exception);
    }
};

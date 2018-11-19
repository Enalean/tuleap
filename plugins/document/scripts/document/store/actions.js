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

import { getProject, getFolderContent } from "../api/rest-querier.js";

export const loadRootDocumentId = async context => {
    try {
        context.commit("switchLoadingRootDocument", true);
        const project = await getProject(context.state.project_id);

        context.commit(
            "saveDocumentRootId",
            project.additional_informations.docman.root_item.item_id
        );

        const folder_content = await getFolderContent(context.state.project_root_document_id);
        context.commit("saveFolderContent", folder_content);
    } catch (e) {
        const { error } = await e.response.json();
        context.commit("setErrorMessage", error.message);
    } finally {
        context.commit("switchLoadingRootDocument", false);
    }
};

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

import { TYPE_EMBEDDED, TYPE_EMPTY, TYPE_FOLDER, TYPE_WIKI } from "../constants.js";

export const is_folder_empty = (state) => state.folder_content.length === 0;

export const current_folder_title = (state) => {
    const hierarchy = state.current_folder_ascendant_hierarchy;

    if (hierarchy.length === 0) {
        return state.root_title;
    }

    return hierarchy[hierarchy.length - 1] ? hierarchy[hierarchy.length - 1].title : "";
};

export const user_can_dragndrop = (state) => state.max_files_dragndrop > 0;

export const global_upload_progress = (state) => {
    const ongoing_uploads = state.folder_content.filter((item) => {
        return Object.prototype.hasOwnProperty.call(item, "progress") && item.upload_error === null;
    });

    if (ongoing_uploads.length === 0) {
        return 0;
    }

    const total_progress = ongoing_uploads.reduce((sum, item) => {
        return sum + item.progress;
    }, 0);

    return Math.trunc(total_progress / ongoing_uploads.length);
};

export const is_uploading = (state) => {
    return Boolean(state.folder_content.find((item) => item.is_uploading));
};

export const is_item_a_wiki = () => (item) => {
    return item.type === TYPE_WIKI;
};

export const is_item_a_folder = () => (item) => {
    return item.type === TYPE_FOLDER;
};

export const is_item_an_empty_document = () => (item) => {
    return item.type === TYPE_EMPTY;
};

export const is_item_an_embedded_file = () => (item) => {
    return item.type === TYPE_EMBEDDED;
};

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

export interface RootGetter {
    is_folder_empty: boolean;
    current_folder_title: string;
    global_upload_progress: number;
    is_uploading: boolean;
}

import type { FakeItem, Item, State } from "../type";
import { isFakeItem } from "../helpers/type-check-helper";

export const is_folder_empty = (state: State): boolean => state.folder_content.length === 0;

export const current_folder_title = (state: State): string => {
    const hierarchy = state.current_folder_ascendant_hierarchy;

    if (hierarchy.length === 0) {
        return state.root_title;
    }

    return hierarchy[hierarchy.length - 1] ? hierarchy[hierarchy.length - 1].title : "";
};

export const global_upload_progress = (state: State): number => {
    const ongoing_uploads = state.folder_content.filter((item: Item | FakeItem) => {
        return isFakeItem(item) && item.upload_error === null;
    });

    if (ongoing_uploads.length === 0) {
        return 0;
    }

    const total_progress = ongoing_uploads.reduce((sum: number, item: Item | FakeItem): number => {
        if (isFakeItem(item) && item.progress) {
            return sum + item.progress;
        }

        return sum;
    }, 0);

    return Math.trunc(total_progress / ongoing_uploads.length);
};

export const is_uploading = (state: State): boolean => {
    return Boolean(
        state.folder_content.find((item: Item | FakeItem) => isFakeItem(item) && item.is_uploading),
    );
};

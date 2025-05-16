/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import type { ItemFile } from "../type";

export function hasNoUploadingContent(item: ItemFile): boolean {
    return (
        !item.is_uploading_in_collapsed_folder &&
        !item.is_uploading &&
        !item.is_uploading_new_version
    );
}

export function isItemUploadingInQuickLookMode(
    item: ItemFile,
    is_quicklook_displayed: boolean,
): boolean {
    if (!is_quicklook_displayed) {
        return false;
    }
    return item.is_uploading_in_collapsed_folder || item.is_uploading_new_version;
}

export function isItemUploadingInTreeView(
    item: ItemFile,
    is_quicklook_displayed: boolean,
): boolean {
    if (is_quicklook_displayed) {
        return false;
    }

    return item.is_uploading_in_collapsed_folder || item.is_uploading_new_version;
}

export function isItemInTreeViewWithoutUpload(
    item: ItemFile,
    is_quicklook_displayed: boolean,
): boolean {
    if (is_quicklook_displayed) {
        return false;
    }
    return !item.is_uploading && !item.is_uploading_new_version;
}

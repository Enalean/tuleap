/*
 * Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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

import type { Lazybox, GroupOfItems, LazyboxItem } from "@tuleap/lazybox";
import { isPullRequestFile } from "./is-pull-request-file";

export type FilesFilter = {
    filterFiles(file_selector: Lazybox, query: string): void;
};

export const getFilesFilter = (
    group_of_items: GroupOfItems,
    items: LazyboxItem[],
): FilesFilter => ({
    filterFiles(file_selector: Lazybox, query: string): void {
        const trimmed_query = query.trim();
        if (trimmed_query === "") {
            file_selector.replaceDropdownContent([{ ...group_of_items, items }]);
            return;
        }

        file_selector.replaceDropdownContent([
            {
                ...group_of_items,
                items: items.filter(
                    (file) =>
                        isPullRequestFile(file.value) && file.value.path.includes(trimmed_query),
                ),
            },
        ]);
    },
});

/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import type { GroupOfItems, LazyboxItem } from "@tuleap/lazybox";

export interface BuildGroupOfLabels {
    buildWithLabels(labels: ReadonlyArray<LazyboxItem>): GroupOfItems;
}
export const GroupOfLabelsBuilder = ($gettext: (msgid: string) => string): BuildGroupOfLabels => {
    return {
        buildWithLabels(labels: ReadonlyArray<LazyboxItem>): GroupOfItems {
            return {
                label: $gettext("Existing labels"),
                empty_message: $gettext("No matching labels found"),
                footer_message: "",
                is_loading: false,
                items: [...labels],
            };
        },
    };
};

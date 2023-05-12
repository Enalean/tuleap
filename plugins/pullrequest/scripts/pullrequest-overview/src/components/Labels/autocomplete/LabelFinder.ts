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

import type { LazyboxItem } from "@tuleap/lazybox";
import { isAssignableLabel } from "./AssignableLabelTemplate";

export const findLabelsWithIds = (
    items: ReadonlyArray<LazyboxItem>,
    ids: ReadonlyArray<number>
): ReadonlyArray<LazyboxItem> => {
    return items.filter((item) => {
        if (!isAssignableLabel(item.value)) {
            return false;
        }

        return ids.includes(item.value.id);
    });
};

export const findLabelMatchingValue = (
    items: ReadonlyArray<LazyboxItem>,
    value: string
): ReadonlyArray<LazyboxItem> => {
    return items.filter((item) => {
        if (!isAssignableLabel(item.value)) {
            return false;
        }

        return item.value.label.toLowerCase().includes(value.toLowerCase());
    });
};

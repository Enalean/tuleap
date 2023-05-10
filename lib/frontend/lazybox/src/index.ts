/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import type { html as HTMLTemplateStringProcessor } from "hybrids";
import type { Lazybox, LazyboxOptions } from "./type";
import * as creator from "./lazybox";
import "../themes/style.scss";

export type { HTMLTemplateStringProcessor, Lazybox, LazyboxOptions };
export type {
    LazyboxSelectionCallback,
    LazyboxSearchInputCallback,
    HTMLTemplateResult,
} from "./type";
export type { GroupCollection, GroupOfItems, LazyboxItem } from "./items/GroupCollection";
export type { SelectionBadge } from "./selection/SelectionBadge";
export { createSelectionBadge } from "./selection/CreateSelectionBadge";

export function createLazybox(
    source_select_box: HTMLSelectElement,
    options: LazyboxOptions
): Lazybox {
    return creator.createLazybox(source_select_box, options);
}

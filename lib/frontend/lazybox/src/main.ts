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

import "../themes/style.scss";
import "./LazyboxElement";

export type { html as HTMLTemplateStringProcessor } from "hybrids";
export type {
    LazyboxSelectionCallback,
    LazyboxSearchInputCallback,
    LazyboxTemplatingCallback,
    HTMLTemplateResult,
    LazyboxOptions,
    LazyAutocompleterSelectionCallback,
} from "./Options";
export type { GroupCollection, GroupOfItems, LazyboxItem } from "./GroupCollection";
export { TAG as LAZYBOX_TAG } from "./LazyboxElement";
export type { Lazybox } from "./LazyboxElement";
export { createLazybox } from "./CreateLazybox";
export { createLazyAutocompleter } from "./CreateLazyAutocompleter";
export { TAG as LAZYBOX_SELECTION_BADGE_TAG } from "./selection/SelectionBadge";
export type { LazyAutocompleter } from "./LazyAutocompleterElement";
export type { SelectionBadge } from "./selection/SelectionBadge";
export { createSelectionBadge } from "./selection/CreateSelectionBadge";

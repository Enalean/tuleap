/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import type { LazyboxItem } from "./GroupCollection";
import type { UpdateFunction } from "hybrids";
import type { HTMLTemplateStringProcessor } from "./main";
import type { SelectionBadge } from "./selection/SelectionBadge";

export type LazyboxSearchInputCallback = (query: string) => void;

export type LazyboxSelectionCallback = (selected_value: unknown | null) => void;

export type HTMLTemplateResult = UpdateFunction<HTMLElement>;
export type LazyboxTemplatingCallback = (
    html: typeof HTMLTemplateStringProcessor,
    item: LazyboxItem
) => HTMLTemplateResult;

export type LazyboxNewItemClickedCallback = (item_name: string) => void;
export type LazyboxNewItemLabelCallback = (item_name: string) => string;

export type LazyboxSelectionBadgeCallback = (item: LazyboxItem) => SelectionBadge & HTMLElement;

type LazyboxWithMultipleSelection = {
    readonly is_multiple: true;
    readonly selection_badge_callback?: LazyboxSelectionBadgeCallback;
};

type LazyboxWithSingleSelection = {
    readonly is_multiple: false;
    readonly search_input_placeholder: string;
};

type LazyboxWithNewItemButton = {
    readonly new_item_clicked_callback: LazyboxNewItemClickedCallback;
    readonly new_item_label_callback: LazyboxNewItemLabelCallback;
};

type LazyboxWithoutNewItemButton = {
    readonly new_item_clicked_callback?: undefined;
};

export type LazyboxOptions = (LazyboxWithSingleSelection | LazyboxWithMultipleSelection) &
    (LazyboxWithoutNewItemButton | LazyboxWithNewItemButton) & {
        readonly placeholder: string;
        readonly templating_callback: LazyboxTemplatingCallback;
        readonly selection_callback: LazyboxSelectionCallback;
        readonly search_input_callback: LazyboxSearchInputCallback;
    };

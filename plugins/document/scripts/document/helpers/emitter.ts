/*
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

import mitt from "mitt";
import type { Item, ListValue } from "../type";

export default mitt<{
    "update-status-property": string;
    "update-title-property": string;
    "update-version-title": string;
    "update-description-property": string;
    "update-owner-property": number;
    "update-changelog-property": string;
    "toggle-quick-look": { details: { item: Item } };
    "show-update-item-properties-modal": { detail: { current_item: Item } };
    "show-update-permissions-modal": { detail: { current_item: Item } };
    "show-create-new-item-version-modal": { detail: { current_item: Item } };
    "set-dropdown-shown": { is_dropdown_shown: boolean };
    "show-max-archive-size-threshold-exceeded-modal": { detail: { current_folder_size: number } };
    "show-archive-size-warning-modal": {
        detail: { current_folder_size: number; folder_href: string; should_warn_osx_user: boolean };
    };
    "show-new-folder-modal": { detail: { parent: Item } };
    "hide-action-menu": void;
    "update-multiple-properties-list-value": {
        detail: { value: number[] | [] | ListValue[] | null; id: string };
    };
    createItem: { item: Item };
    deleteItem: { item: Item };
}>();

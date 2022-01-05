/*
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
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

import * as mutations from "./metadata-mutations";
import * as actions from "./metadata-actions";

export interface MetadataState {
    project_metadata_list: Array<Metadata>;
    has_loaded_metadata: true;
}

/**
 * Note of metadata usage:
 *
 * For single and multiple list when data comes from rest route, list_value has Array<ListValue>
 * For single metadata, after transformation, list_value is null, value is a number (chosen option)
 * For multiple value metadata, after transformation, value is null, list value is and Array<number>
 *
 * Please also note that value is used for dates/string
 */
export interface Metadata {
    short_name: string;
    name: string;
    description: string | null;
    type: string;
    is_required: boolean;
    is_multiple_value_allowed: boolean;
    is_used: boolean;
    list_value: Array<number> | Array<ListValue> | null | [];
    value: number | string | null;
    allowed_list_values: Array<ListValue> | null;
}

export interface FolderMetadata extends Metadata {
    recursion: string | null;
}

export interface ListValue {
    id: number;
    value: string | number;
}

export interface FolderStatus {
    value: string;
    recursion: string;
}

export default {
    namespaced: true,
    state: {
        project_metadata_list: [],
        has_loaded_metadata: false,
    },
    mutations,
    actions,
};

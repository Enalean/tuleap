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

import * as mutations from "./properties-mutations";
import * as actions from "./properties-actions";

export interface PropertiesState {
    project_properties: Array<Property>;
    has_loaded_properties: boolean;
}

/**
 * Note of properties usage:
 *
 * For single and multiple list when data comes from rest route, list_value has Array<ListValue>
 * For single property, after transformation, list_value is null, value is a number (chosen option)
 * For multiple value property, after transformation, value is null, list value is and Array<number>
 *
 * Please also note that value is used for dates/string
 */
export interface Property {
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

export interface FolderProperty extends Property {
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
        project_properties: [],
        has_loaded_properties: false,
    },
    mutations,
    actions,
};

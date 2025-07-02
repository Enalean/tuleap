/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
import type { ConfigurationFieldDisplayType } from "@/sections/readonly-fields/AvailableReadonlyFields";
import type { ColorName } from "@tuleap/plugin-tracker-constants";

export const STRING_FIELD = "string";
export const USER_GROUP_LIST_FIELD = "user_groups_list";
export const STATIC_LIST_FIELD = "static_list";
export const USER_LIST_FIELD = "user_list";

export type ReadonlyFieldString = Readonly<{
    type: typeof STRING_FIELD;
    label: string;
    value: string;
    display_type: ConfigurationFieldDisplayType;
}>;

export type ReadonlyFieldUserGroupsListValue = Readonly<{
    label: string;
}>;

export type ReadonlyFieldUserGroupsList = Readonly<{
    type: typeof USER_GROUP_LIST_FIELD;
    label: string;
    value: ReadonlyFieldUserGroupsListValue[];
    display_type: ConfigurationFieldDisplayType;
}>;

export type ReadonlyFieldStaticListValue = Readonly<{
    label: string;
    tlp_color: ColorName | "";
}>;

export type ReadonlyFieldStaticList = Readonly<{
    type: typeof STATIC_LIST_FIELD;
    label: string;
    value: ReadonlyFieldStaticListValue[];
    display_type: ConfigurationFieldDisplayType;
}>;

export type ReadonlyFieldUserListValue = Readonly<{
    display_name: string;
    avatar_url: string;
}>;

export type ReadonlyFieldUserList = Readonly<{
    type: typeof USER_LIST_FIELD;
    label: string;
    value: ReadonlyFieldUserListValue[];
    display_type: ConfigurationFieldDisplayType;
}>;

export type ReadonlyField =
    | ReadonlyFieldString
    | ReadonlyFieldUserGroupsList
    | ReadonlyFieldStaticList
    | ReadonlyFieldUserList;

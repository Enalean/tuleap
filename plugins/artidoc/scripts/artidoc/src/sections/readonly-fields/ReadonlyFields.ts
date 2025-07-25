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
import type {
    ConfigurationFieldDisplayType,
    DISPLAY_TYPE_BLOCK,
} from "@/sections/readonly-fields/AvailableReadonlyFields";
import type { ColorName } from "@tuleap/plugin-tracker-constants";

export const STRING_FIELD = "string";
export const USER_GROUP_LIST_FIELD = "user_groups_list";
export const STATIC_LIST_FIELD = "static_list";
export const USER_LIST_FIELD = "user_list";
export const LINKS_FIELD = "links";
export const NUMERIC_FIELD = "numeric";
export const USER_FIELD = "user";

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

export type ReadonlyFieldLinks = Readonly<{
    type: typeof LINKS_FIELD;
    label: string;
    display_type: typeof DISPLAY_TYPE_BLOCK;
    value: ReadonlyFieldLinkedArtifact[];
}>;

export type ReadonlyFieldLinkedArtifact = Readonly<{
    link_label: string;
    tracker_shortname: string;
    tracker_color: ColorName;
    project: LinkedArtifactProject;
    artifact_id: number;
    title: string;
    html_uri: string;
    status: LinkedArtifactStatus | null;
}>;

export type LinkedArtifactStatus = Readonly<{
    label: string;
    color: ColorName | "";
    is_open: boolean;
}>;

export type LinkedArtifactProject = Readonly<{
    id: number;
    label: string;
    icon: string;
}>;

export type ReadonlyFieldNumeric = Readonly<{
    type: typeof NUMERIC_FIELD;
    label: string;
    value: number | null;
    display_type: ConfigurationFieldDisplayType;
}>;

export type ReadonlyFieldUser = Readonly<{
    type: typeof USER_FIELD;
    label: string;
    value: ReadonlyFieldUserListValue;
    display_type: ConfigurationFieldDisplayType;
}>;

export type ReadonlyField =
    | ReadonlyFieldString
    | ReadonlyFieldUserGroupsList
    | ReadonlyFieldStaticList
    | ReadonlyFieldUserList
    | ReadonlyFieldLinks
    | ReadonlyFieldNumeric
    | ReadonlyFieldUser;

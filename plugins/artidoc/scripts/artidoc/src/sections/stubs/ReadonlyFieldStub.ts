/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
    ReadonlyFieldLinkedArtifact,
    ReadonlyFieldLinks,
    ReadonlyFieldNumeric,
    ReadonlyFieldStaticList,
    ReadonlyFieldStaticListValue,
    ReadonlyFieldString,
    ReadonlyFieldUser,
    ReadonlyFieldUserGroupsList,
    ReadonlyFieldUserGroupsListValue,
    ReadonlyFieldUserList,
    ReadonlyFieldUserListValue,
} from "@/sections/readonly-fields/ReadonlyFields";
import type { ConfigurationFieldDisplayType } from "@/sections/readonly-fields/AvailableReadonlyFields";
import { DISPLAY_TYPE_BLOCK } from "@/sections/readonly-fields/AvailableReadonlyFields";

export const ReadonlyFieldStub = {
    string: (value: string, display_type: ConfigurationFieldDisplayType): ReadonlyFieldString => ({
        type: "string",
        label: `Readonly string field`,
        display_type,
        value,
    }),
    userGroupsList: (
        value: ReadonlyFieldUserGroupsListValue[],
        display_type: ConfigurationFieldDisplayType,
    ): ReadonlyFieldUserGroupsList => ({
        type: "user_groups_list",
        label: `Readonly user-groups-list field`,
        value,
        display_type,
    }),
    staticList: (
        value: ReadonlyFieldStaticListValue[],
        display_type: ConfigurationFieldDisplayType,
    ): ReadonlyFieldStaticList => ({
        type: "static_list",
        label: "Readonly static list field",
        value,
        display_type,
    }),
    userList: (
        value: ReadonlyFieldUserListValue[],
        display_type: ConfigurationFieldDisplayType,
    ): ReadonlyFieldUserList => ({
        type: "user_list",
        label: "Readonly user list field",
        value,
        display_type,
    }),
    linkField: (value: ReadonlyFieldLinkedArtifact[]): ReadonlyFieldLinks => ({
        type: "links",
        label: "Readonly links field",
        display_type: DISPLAY_TYPE_BLOCK,
        value,
    }),
    numericField: (
        value: number | null,
        display_type: ConfigurationFieldDisplayType,
    ): ReadonlyFieldNumeric => ({
        type: "numeric",
        label: "Readonly numeric field",
        value,
        display_type,
    }),
    userField: (
        value: ReadonlyFieldUserListValue,
        display_type: ConfigurationFieldDisplayType,
    ): ReadonlyFieldUser => ({
        type: "user",
        label: "Readonly user field",
        value,
        display_type,
    }),
};

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
import type { ColorName, LinkDirection } from "@tuleap/plugin-tracker-constants";

export const TEXT_FIELD = "text";
export const USER_GROUP_LIST_FIELD = "user_groups_list";
export const STATIC_LIST_FIELD = "static_list";
export const USER_LIST_FIELD = "user_list";
export const LINKS_FIELD = "links";
export const NUMERIC_FIELD = "numeric";
export const USER_FIELD = "user";
export const DATE_FIELD = "date";
export const PERMISSIONS_FIELD = "permissions";
export const STEPS_DEFINITION_FIELD = "steps_definition";
export const STEPS_EXECUTION_FIELD = "steps_execution";

export const STEP_NOT_RUN: StepExecutionStatus = "notrun";
export const STEP_BLOCKED: StepExecutionStatus = "blocked";
export const STEP_PASSED: StepExecutionStatus = "passed";
export const STEP_FAILED: StepExecutionStatus = "failed";

export type ReadonlyFieldText = Readonly<{
    type: typeof TEXT_FIELD;
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

export type LinkType = {
    readonly shortname: string;
    readonly direction: LinkDirection;
};

export type ReadonlyFieldLinkedArtifact = Readonly<{
    link_label: string;
    tracker_shortname: string;
    tracker_color: ColorName;
    project: LinkedArtifactProject;
    artifact_id: number;
    title: string;
    html_uri: string;
    status: LinkedArtifactStatus | null;
    link_type: LinkType;
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

export type ReadonlyFieldDate = Readonly<{
    type: typeof DATE_FIELD;
    label: string;
    display_type: ConfigurationFieldDisplayType;
    value: string | null;
    with_time: boolean;
}>;

export type ReadonlyFieldPermissions = Readonly<{
    type: typeof PERMISSIONS_FIELD;
    label: string;
    display_type: ConfigurationFieldDisplayType;
    value: ReadonlyFieldUserGroupsListValue[];
}>;

export type ReadonlyFieldStepDefinitionValue = Readonly<{
    description: string;
    expected_results: string;
}>;

export type ReadonlyFieldStepsDefinition = Readonly<{
    type: typeof STEPS_DEFINITION_FIELD;
    label: string;
    display_type: ConfigurationFieldDisplayType;
    value: ReadonlyFieldStepDefinitionValue[];
}>;

export type StepExecutionStatus = "notrun" | "blocked" | "passed" | "failed";

export type ReadonlyFieldStepExecutionValue = Readonly<{
    description: string;
    expected_results: string;
    status: StepExecutionStatus;
}>;

export type ReadonlyFieldStepsExecution = Readonly<{
    type: typeof STEPS_EXECUTION_FIELD;
    label: string;
    display_type: ConfigurationFieldDisplayType;
    value: ReadonlyFieldStepExecutionValue[];
}>;

export type ReadonlyField =
    | ReadonlyFieldText
    | ReadonlyFieldUserGroupsList
    | ReadonlyFieldStaticList
    | ReadonlyFieldUserList
    | ReadonlyFieldLinks
    | ReadonlyFieldNumeric
    | ReadonlyFieldUser
    | ReadonlyFieldDate
    | ReadonlyFieldPermissions
    | ReadonlyFieldStepsDefinition
    | ReadonlyFieldStepsExecution;

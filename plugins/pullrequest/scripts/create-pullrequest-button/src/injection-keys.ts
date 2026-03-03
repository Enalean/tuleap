/*
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

import type { InjectionKey, Ref, ComputedRef } from "vue";
import type { ExtendedBranch } from "./helpers/pullrequest-helper.ts";

export type InitPullrequestData = (params: {
    repository_id: number;
    project_id: number;
    parent_repository_id: number;
    parent_repository_name: string;
    parent_project_id: number;
    user_can_see_parent_repository: boolean;
}) => Promise<void>;

export type CreatePullrequest = () => Promise<void>;
export type ResetSelection = () => void;

export const SOURCE_BRANCHES: InjectionKey<Ref<ExtendedBranch[]>> = Symbol("source-branches");
export const DESTINATION_BRANCHES: InjectionKey<Ref<ExtendedBranch[]>> =
    Symbol("destination-branches");
export const SELECTED_SOURCE_BRANCH: InjectionKey<Ref<ExtendedBranch | "">> =
    Symbol("selected-source-branch");
export const SELECTED_DESTINATION_BRANCH: InjectionKey<Ref<ExtendedBranch | "">> = Symbol(
    "selected-destination-branch",
);
export const CREATE_ERROR_MESSAGE: InjectionKey<Ref<string>> = Symbol("create-error-message");
export const HAS_ERROR_WHILE_LOADING_BRANCHES: InjectionKey<Ref<boolean>> = Symbol(
    "has-error-while-loading-branches",
);
export const IS_CREATING_PULLREQUEST: InjectionKey<Ref<boolean>> =
    Symbol("is-creating-pullrequest");

export const CAN_CREATE_PULLREQUEST: InjectionKey<ComputedRef<boolean>> =
    Symbol("can-create-pullrequest");

export const INIT_PULLREQUEST_DATA: InjectionKey<InitPullrequestData> =
    Symbol("init-pullrequest-data");
export const CREATE_PULLREQUEST: InjectionKey<CreatePullrequest> = Symbol("create-pullrequest");
export const RESET_SELECTION: InjectionKey<ResetSelection> = Symbol("reset-selection");

/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";
import type { Fault } from "@tuleap/fault";
import type { PullRequestCommentPresenter } from "@tuleap/plugin-pullrequest-comments";
import type { StrictInjectionKey } from "@tuleap/vue-strict-inject";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";

export type DisplayErrorCallback = (fault: Fault) => void;
type DisplayNewlyCreatedGlobalCommentCallback = (comment: PullRequestCommentPresenter) => void;
export type PostPullRequestUpdateCallback = (updated_pull_request: PullRequest) => void;

export const OVERVIEW_APP_BASE_URL_KEY: StrictInjectionKey<URL> = Symbol("overview_app_base_url");
export const PULL_REQUEST_ID_KEY: StrictInjectionKey<number> = Symbol("pull_request_id");
export const USER_LOCALE_KEY: StrictInjectionKey<string> = Symbol("user_local");
export const USER_DATE_TIME_FORMAT_KEY: StrictInjectionKey<string> =
    Symbol("user_date_time_format");
export const USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY: StrictInjectionKey<RelativeDatesDisplayPreference> =
    Symbol("user_relative_date_display_preference");
export const CURRENT_USER_ID: StrictInjectionKey<number> = Symbol("current_user");
export const PROJECT_ID: StrictInjectionKey<number> = Symbol("project_id");
export const CURRENT_USER_AVATAR_URL: StrictInjectionKey<string> = Symbol("current_user_avatar");
export const DISPLAY_TULEAP_API_ERROR: StrictInjectionKey<DisplayErrorCallback> =
    Symbol("display_tuleap_api");

export const DISPLAY_NEWLY_CREATED_GLOBAL_COMMENT: StrictInjectionKey<DisplayNewlyCreatedGlobalCommentCallback> =
    Symbol("display_newly_created_global_comment");
export const ARE_MERGE_COMMITS_ALLOWED_IN_REPOSITORY: StrictInjectionKey<boolean> = Symbol(
    "are_merge_commits_allowed_in_repository",
);
export const POST_PULL_REQUEST_UPDATE_CALLBACK: StrictInjectionKey<PostPullRequestUpdateCallback> =
    Symbol("post_pull_request_update_callback");

export const VIEW_OVERVIEW_NAME = "overview";

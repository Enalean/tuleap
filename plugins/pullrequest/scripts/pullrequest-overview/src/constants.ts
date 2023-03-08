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

import type { InjectionKey } from "vue";
import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";
import type { Fault } from "@tuleap/fault";
import type { PullRequestCommentPresenter } from "@tuleap/plugin-pullrequest-comments";

type DisplayErrorCallback = (fault: Fault) => void;
type DisplayNewlyCreatedGlobalCommentCallback = (comment: PullRequestCommentPresenter) => void;

export const OVERVIEW_APP_BASE_URL_KEY: InjectionKey<URL> = Symbol();
export const PULL_REQUEST_ID_KEY: InjectionKey<string> = Symbol();
export const USER_LOCALE_KEY: InjectionKey<string> = Symbol();
export const USER_DATE_TIME_FORMAT_KEY: InjectionKey<string> = Symbol();
export const USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY: InjectionKey<RelativeDatesDisplayPreference> =
    Symbol();
export const CURRENT_USER_ID: InjectionKey<number> = Symbol();
export const CURRENT_USER_AVATAR_URL: InjectionKey<string> = Symbol();
export const DISPLAY_TULEAP_API_ERROR: InjectionKey<DisplayErrorCallback> = Symbol();
export const DISPLAY_NEWLY_CREATED_GLOBAL_COMMENT: InjectionKey<DisplayNewlyCreatedGlobalCommentCallback> =
    Symbol();

export const VIEW_OVERVIEW_NAME = "overview";

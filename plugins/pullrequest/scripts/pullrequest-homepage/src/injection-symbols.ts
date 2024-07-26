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

import type { Ref } from "vue";
import type { LocaleString } from "@tuleap/date-helper";
import type { StrictInjectionKey } from "@tuleap/vue-strict-inject";
import type { Fault } from "@tuleap/fault";
import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";

export type DisplayErrorCallback = (fault: Fault) => void;

type SortAscendant = "asc";
type SortDescendant = "desc";

export const SORT_ASCENDANT: SortAscendant = "asc";
export const SORT_DESCENDANT: SortDescendant = "desc";

export type PullRequestSortOrder = SortAscendant | SortDescendant;

export const REPOSITORY_ID: StrictInjectionKey<number> = Symbol("repository_id");
export const PROJECT_ID: StrictInjectionKey<number> = Symbol("project_id");
export const CURRENT_USER_ID: StrictInjectionKey<number> = Symbol("current_user_id");
export const BASE_URL: StrictInjectionKey<URL> = Symbol("base_url");
export const USER_LOCALE_KEY: StrictInjectionKey<LocaleString> = Symbol("user_locale");
export const USER_TIMEZONE_KEY: StrictInjectionKey<string> = Symbol("user_timezone");
export const USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY: StrictInjectionKey<RelativeDatesDisplayPreference> =
    Symbol("user_relative_date_display_preference");
export const DISPLAY_TULEAP_API_ERROR: StrictInjectionKey<DisplayErrorCallback> =
    Symbol("display_tuleap_api");
export const SHOW_CLOSED_PULL_REQUESTS: StrictInjectionKey<Ref<boolean>> = Symbol(
    "show_closed_pull_requests",
);
export const SHOW_PULL_REQUESTS_RELATED_TO_ME: StrictInjectionKey<Ref<boolean>> = Symbol(
    "show_pull_requests_related_to_me",
);
export const PULL_REQUEST_SORT_ORDER: StrictInjectionKey<Ref<PullRequestSortOrder>> = Symbol(
    "pull_requests_sort_order",
);

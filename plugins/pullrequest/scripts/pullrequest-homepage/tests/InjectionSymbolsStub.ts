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
import { ref } from "vue";
import { PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN } from "@tuleap/tlp-relative-date";
import type { DisplayErrorCallback, PullRequestSortOrder } from "../src/injection-symbols";
import {
    BASE_URL,
    CURRENT_USER_ID,
    DISPLAY_TULEAP_API_ERROR,
    PROJECT_ID,
    PULL_REQUEST_SORT_ORDER,
    REPOSITORY_ID,
    SHOW_CLOSED_PULL_REQUESTS,
    SHOW_PULL_REQUESTS_RELATED_TO_ME,
    SORT_DESCENDANT,
    USER_DATE_TIME_FORMAT_KEY,
    USER_LOCALE_KEY,
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
} from "../src/injection-symbols";

const noop = (): void => {
    // Do nothing
};

export const injected_repository_id = 2;
const injected_project_id = 102;
export const injected_current_user_id = 113;
export const injected_base_url = new URL("https://example.com");
export const injected_user_locale = "fr_FR";
export let injected_show_closed_pull_requests = ref(false);
export let injected_show_pull_requests_related_to_me = ref(false);
export let injected_pull_requests_sort_order: Ref<PullRequestSortOrder> = ref(SORT_DESCENDANT);

type ProvideRecord = Record<symbol, unknown>;

const buildDefaultProvide = (tuleap_api_error_callback: DisplayErrorCallback): ProvideRecord => {
    injected_show_closed_pull_requests = ref(false);
    injected_show_pull_requests_related_to_me = ref(false);
    injected_pull_requests_sort_order = ref(SORT_DESCENDANT);

    return {
        [REPOSITORY_ID.valueOf()]: injected_repository_id,
        [PROJECT_ID.valueOf()]: injected_project_id,
        [CURRENT_USER_ID.valueOf()]: injected_current_user_id,
        [BASE_URL.valueOf()]: injected_base_url,
        [USER_LOCALE_KEY.valueOf()]: injected_user_locale,
        [USER_DATE_TIME_FORMAT_KEY.valueOf()]: "d/m/Y H:i",
        [USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY.valueOf()]:
            PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN,
        [DISPLAY_TULEAP_API_ERROR.valueOf()]: tuleap_api_error_callback,
        [SHOW_CLOSED_PULL_REQUESTS.valueOf()]: injected_show_closed_pull_requests,
        [SHOW_PULL_REQUESTS_RELATED_TO_ME.valueOf()]: injected_show_pull_requests_related_to_me,
        [PULL_REQUEST_SORT_ORDER.valueOf()]: injected_pull_requests_sort_order,
    };
};

export const InjectionSymbolsStub = {
    withDefaults: (): ProvideRecord => buildDefaultProvide(noop),

    withTuleapApiErrorCallback: (callback: DisplayErrorCallback): ProvideRecord =>
        buildDefaultProvide(callback),
};

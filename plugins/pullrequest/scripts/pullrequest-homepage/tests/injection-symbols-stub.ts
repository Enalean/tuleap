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

import { ref } from "vue";
import type { Ref } from "vue";
import type { StrictInjectionKey } from "@tuleap/vue-strict-inject";
import { PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN } from "@tuleap/tlp-relative-date";
import {
    PULL_REQUEST_SORT_ORDER,
    SORT_DESCENDANT,
    PROJECT_ID,
    SHOW_CLOSED_PULL_REQUESTS,
    BASE_URL,
    DISPLAY_TULEAP_API_ERROR,
    REPOSITORY_ID,
    USER_DATE_TIME_FORMAT_KEY,
    USER_LOCALE_KEY,
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
} from "../src/injection-symbols";
import type { DisplayErrorCallback, PullRequestSortOrder } from "../src/injection-symbols";

const noop = (): void => {
    // Do nothing
};

export const injected_repository_id = 2;
const injected_project_id = 102;
export const injected_base_url = new URL("https://example.com");
export const injected_user_locale = "fr_FR";
export let injected_show_closed_pull_requests = ref(false);
export let injected_pull_requests_sort_order: Ref<PullRequestSortOrder> = ref(SORT_DESCENDANT);
export let injected_tuleap_error_api_callback: DisplayErrorCallback = noop;

type StrictInjectImplementation = (key: StrictInjectionKey<unknown>) => unknown;

const injection_symbols: StrictInjectImplementation = (key): unknown => {
    switch (key) {
        case REPOSITORY_ID:
            return injected_repository_id;
        case PROJECT_ID:
            return injected_project_id;
        case BASE_URL:
            return injected_base_url;
        case USER_LOCALE_KEY:
            return injected_user_locale;
        case USER_DATE_TIME_FORMAT_KEY:
            return "d/m/Y H:i";
        case USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY:
            return PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN;
        case DISPLAY_TULEAP_API_ERROR:
            return injected_tuleap_error_api_callback;
        case SHOW_CLOSED_PULL_REQUESTS:
            return injected_show_closed_pull_requests;
        case PULL_REQUEST_SORT_ORDER:
            return injected_pull_requests_sort_order;
        default:
            throw new Error("Tried to strictInject a value while it was not mocked");
    }
};

export const StubInjectionSymbols = {
    withDefaults: (): StrictInjectImplementation => {
        injected_show_closed_pull_requests = ref(false);
        injected_pull_requests_sort_order = ref(SORT_DESCENDANT);

        return injection_symbols;
    },
    withTuleapApiErrorCallback: (callback: DisplayErrorCallback): StrictInjectImplementation => {
        injected_tuleap_error_api_callback = callback;

        return injection_symbols;
    },
};

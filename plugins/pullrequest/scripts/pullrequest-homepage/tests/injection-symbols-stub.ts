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

import type { StrictInjectionKey } from "@tuleap/vue-strict-inject";
import { PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN } from "@tuleap/tlp-relative-date";
import type { DisplayErrorCallback } from "../src/injection-symbols";
import {
    BASE_URL,
    DISPLAY_TULEAP_API_ERROR,
    REPOSITORY_ID,
    USER_DATE_TIME_FORMAT_KEY,
    USER_LOCALE_KEY,
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
} from "../src/injection-symbols";

export const injected_repository_id = 2;
export const injected_base_url = new URL("https://example.com");
export const injected_user_locale = "fr_FR";
export let injected_tuleap_error_api_callback: DisplayErrorCallback = () => {};

type StrictInjectImplementation = (key: StrictInjectionKey<unknown>) => unknown;

const injection_symbols: StrictInjectImplementation = (key): unknown => {
    switch (key) {
        case REPOSITORY_ID:
            return injected_repository_id;
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
        default:
            throw new Error("Tried to strictInject a value while it was not mocked");
    }
};

export const StubInjectionSymbols = {
    withDefaults: (): StrictInjectImplementation => injection_symbols,
    withTuleapApiErrorCallback: (callback: DisplayErrorCallback): StrictInjectImplementation => {
        injected_tuleap_error_api_callback = callback;

        return injection_symbols;
    },
};

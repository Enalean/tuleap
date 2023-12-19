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
import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";

export const REPOSITORY_ID: StrictInjectionKey<number> = Symbol("repository_id");
export const BASE_URL: StrictInjectionKey<URL> = Symbol("base_url");
export const USER_LOCALE_KEY: StrictInjectionKey<string> = Symbol("user_locale");
export const USER_DATE_TIME_FORMAT_KEY: StrictInjectionKey<string> =
    Symbol("user_date_time_format");
export const USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY: StrictInjectionKey<RelativeDatesDisplayPreference> =
    Symbol("user_relative_date_display_preference");

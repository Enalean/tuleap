/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
import type { LocaleString } from "@tuleap/date-helper";
import { getLocaleOrThrow, getTimezoneOrThrow } from "@tuleap/date-helper";
import { getAttributeOrThrow } from "@tuleap/dom";

export interface UserPreferences {
    readonly locale: LocaleString;
    readonly timezone: string;
    readonly relative_date_display: RelativeDatesDisplayPreference;
}

export const USER_PREFERENCES: StrictInjectionKey<UserPreferences> = Symbol("user-preferences");

export function buildUserPreferences(doc: Document, mount_point: HTMLElement): UserPreferences {
    return {
        locale: getLocaleOrThrow(doc),
        timezone: getTimezoneOrThrow(doc),
        // eslint-disable-next-line @typescript-eslint/consistent-type-assertions -- Assume that the backend does not send any string
        relative_date_display: getAttributeOrThrow(
            mount_point,
            "data-relative-date-display",
        ) as RelativeDatesDisplayPreference,
    };
}

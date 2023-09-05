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

import { describe, it, expect } from "vitest";
import {
    PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN,
    PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP,
    PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN,
    PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP,
} from "@tuleap/tlp-relative-date";
import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";
import { isPreferenceAbsoluteDateFirst } from "./relative-dates-preference-helper";

describe("relative-dates-preference-helper", () => {
    it.each([
        [true, PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP as RelativeDatesDisplayPreference],
        [true, PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN as RelativeDatesDisplayPreference],
        [false, PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP as RelativeDatesDisplayPreference],
        [false, PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN as RelativeDatesDisplayPreference],
    ])(
        "isPreferenceAbsoluteDateFirst() should return %s when the preference is %s",
        (expected_result, preference) => {
            expect(isPreferenceAbsoluteDateFirst(preference)).toStrictEqual(expected_result);
        },
    );
});

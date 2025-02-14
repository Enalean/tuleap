/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import {
    en_US_DATE_FORMAT,
    en_US_DATE_TIME_FORMAT,
    fr_FR_DATE_FORMAT,
    fr_FR_DATE_TIME_FORMAT,
} from "@tuleap/core-constants";
import { formatFromPhpToMoment } from "./for-moment";

describe("formatFromPhpToMoment", () => {
    it.each([
        ["french date", fr_FR_DATE_FORMAT, "DD/MM/YYYY"],
        ["english date", en_US_DATE_FORMAT, "YYYY-MM-DD"],
        ["random foreign date", "Y/m/d", "YYYY-MM-DD"],
        ["french time", fr_FR_DATE_TIME_FORMAT, "DD/MM/YYYY HH:mm"],
        ["english time", en_US_DATE_TIME_FORMAT, "YYYY-MM-DD HH:mm"],
        ["random foreign time", "Y/m/d H:i", "YYYY-MM-DD HH:mm"],
    ])(
        `Given %s is provided, then it returns the moment format`,
        (format_title, php_date_format, expected_moment_format) => {
            expect(formatFromPhpToMoment(php_date_format)).toBe(expected_moment_format);
        },
    );
});

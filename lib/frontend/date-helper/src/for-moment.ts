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

import {
    en_US_DATE_FORMAT,
    en_US_DATE_TIME_FORMAT,
    fr_FR_DATE_FORMAT,
    fr_FR_DATE_TIME_FORMAT,
} from "@tuleap/core-constants";

/**
 * @deprecated Consider using `IntlFormatter` instead.
 */
export function formatFromPhpToMoment(php_date_format: string): string {
    switch (php_date_format) {
        case fr_FR_DATE_FORMAT:
            return "DD/MM/YYYY";
        case fr_FR_DATE_TIME_FORMAT:
            return "DD/MM/YYYY HH:mm";
        case en_US_DATE_FORMAT:
            return "YYYY-MM-DD";
        case en_US_DATE_TIME_FORMAT:
            return "YYYY-MM-DD HH:mm";
        default:
            return php_date_format.includes("H") && php_date_format.includes("i")
                ? "YYYY-MM-DD HH:mm"
                : "YYYY-MM-DD";
    }
}

/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 *
 */

import moment from "moment";
import "moment-timezone";
import "moment/locale/fr";
import { formatFromPhpToMoment } from "@tuleap/date-helper";
import type { UserPreferences } from "../type";

let time_zone = "CET";
let format = "d/m/Y H:i";

export default {
    setOptions(preferences: UserPreferences): void {
        const locale = preferences.user_locale.replace(/_/g, "-");
        moment.locale(locale);
        time_zone = preferences.user_timezone;
        format = formatFromPhpToMoment(preferences.format);
    },

    format(date: string): string {
        return moment(date).tz(time_zone).format(format);
    },

    humanFormat(date: string): string {
        return moment(date).tz(time_zone).format("LLL");
    },

    getFromNow(date: string): string {
        return moment(date).tz(time_zone).fromNow();
    },

    formatToISO(date: string): string {
        return moment.tz(date, time_zone).format();
    },
};

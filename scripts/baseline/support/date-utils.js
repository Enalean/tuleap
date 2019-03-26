/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
import phptomoment from "phptomoment";
import "moment-timezone";
import "moment/locale/fr";

let format = "d/m/Y H:i";

export default {
    setOptions(preferences) {
        let user_locale_for_moment = preferences.user_locale.replace(/_/g, "-");
        moment.tz(preferences.user_timezone).locale(user_locale_for_moment);
        format = preferences.format;
    },

    format(date) {
        return moment(date).format(phptomoment(format));
    },

    getFromNow(date) {
        return moment(date).fromNow();
    }
};

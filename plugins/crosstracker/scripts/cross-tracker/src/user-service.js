/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

import phptomoment from "phptomoment";
import moment from "moment";

export { init, isAnonymous, getUserPreferredDateFormat };

let user_is_anonymous;
let date_format;

function init(is_anonymous, localized_php_date_format, locale) {
    user_is_anonymous = is_anonymous;
    date_format = phptomoment(localized_php_date_format);
    moment.locale(locale);
}

function isAnonymous() {
    return user_is_anonymous;
}

function getUserPreferredDateFormat() {
    return date_format;
}

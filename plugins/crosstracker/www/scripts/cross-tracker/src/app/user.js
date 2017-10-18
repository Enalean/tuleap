/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

import moment from 'moment';
import phptomoment from 'phptomoment';

export default class User {
    constructor(
        locale,
        localized_php_date_format,
        is_anonymous
    ) {
        this.locale      = locale;
        this.date_format = phptomoment(localized_php_date_format);
        moment.locale(locale);
        this.is_anonymous = is_anonymous;
    }

    getUserPreferredDateFormat() {
        return this.date_format;
    }

    isAnonymous() {
        return this.is_anonymous;
    }
}

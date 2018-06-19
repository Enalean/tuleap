/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import { post } from 'tlp-fetch';

export {
    askToBeCalledBack
}

function askToBeCalledBack(call_me_back_phone, call_back_me_date) {
    const headers = {
        "content-type": "application/json"
    };

    const body = JSON.stringify({
        phone: call_me_back_phone,
        date: call_back_me_date
    });

    return post('/api/call_me_back', {
        headers,
        body
    });
}

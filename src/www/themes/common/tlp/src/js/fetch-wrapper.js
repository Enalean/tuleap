
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

export const get = (input, init = {}) => {
    const method = 'GET',
        { credentials = 'same-origin' } = init;

    return fetch(input, {method, credentials, ...init}).then(checkResponse);
};

export const put = (input, init = {}) => {
    const method = 'PUT',
          { credentials = 'same-origin' } = init;

    return fetch(input, {method, credentials, ...init}).then(checkResponse);
};

function checkResponse(response) {
    if (response.ok) {
        return response;
    } else {
        const error = new Error(response.statusText);
        error.response = response;
        throw error;
    }
}

/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

export function expiringLocalStorage(expirationTimeoutSeconds) {
    function getExpirationKey(key) {
        return key + "_expiration";
    }
    return {
        getItem(key) {
            const expiration = window.localStorage.getItem(getExpirationKey(key));
            if (expiration === null || Date.now() > expiration) {
                this.removeItem(key);
            }
            return window.localStorage.getItem(key);
        },
        setItem(key, value) {
            window.localStorage.setItem(
                getExpirationKey(key),
                Date.now() + expirationTimeoutSeconds * 1000
            );
            window.localStorage.setItem(key, value);
        },
        removeItem(key) {
            window.localStorage.removeItem(getExpirationKey(key));
            window.localStorage.removeItem(key);
        },
    };
}

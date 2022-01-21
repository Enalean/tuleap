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

export interface localStorage {
    getItem(key: string): string | null;
    setItem(key: string, value: string): void;
    removeItem(key: string): void;
}

export function expiringLocalStorage(expirationTimeoutSeconds: number): localStorage {
    function getExpirationKey(key: string): string {
        return key + "_expiration";
    }
    return {
        getItem(key: string): string | null {
            const item_expiration_key = window.localStorage.getItem(getExpirationKey(key));
            if (!item_expiration_key) {
                return null;
            }
            if (String(Date.now()) > item_expiration_key) {
                this.removeItem(key);
            }
            return window.localStorage.getItem(key);
        },
        setItem(key: string, value: string): void {
            window.localStorage.setItem(
                getExpirationKey(key),
                String(Date.now() + expirationTimeoutSeconds * 1000)
            );
            window.localStorage.setItem(key, value);
        },
        removeItem(key: string): void {
            window.localStorage.removeItem(getExpirationKey(key));
            window.localStorage.removeItem(key);
        },
    };
}

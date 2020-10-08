/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

const STORAGE_KEY = "last_ack_browser_deprecation";
const ACK_EXPIRATION_MS = 4 * 24 * 60 * 60 * 1000; // 4 days

export function markAndCheckBrowserDeprecationAcknowledgement(storage: Storage): boolean {
    const last_ack = parseInt(storage.getItem(STORAGE_KEY) ?? "", 10) ?? Infinity;
    const current_time = Date.now();
    const delay_since_last_ack_ms = current_time - last_ack;

    if (ACK_EXPIRATION_MS > delay_since_last_ack_ms) {
        return true;
    }

    storage.setItem(STORAGE_KEY, String(current_time));
    return false;
}

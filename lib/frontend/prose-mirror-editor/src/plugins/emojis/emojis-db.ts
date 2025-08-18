/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
import unicode_db from "./unicode-db.json";

type UnicodeDBKey = keyof typeof unicode_db;

const map: Map<string, string> = new Map();

export function getEmojiDB(): ReadonlyMap<string, string> {
    if (map.size > 0) {
        return map;
    }

    Object.keys(unicode_db).forEach((key) => {
        const underscore_key = key.trim().replaceAll(" ", "_").replaceAll(":", "").toLowerCase();
        const dash_key = key.trim().replaceAll(" ", "-").replaceAll(":", "").toLowerCase();
        // eslint-disable-next-line @typescript-eslint/consistent-type-assertions
        map.set(underscore_key, unicode_db[key as UnicodeDBKey]);
        // eslint-disable-next-line @typescript-eslint/consistent-type-assertions
        map.set(dash_key, unicode_db[key as UnicodeDBKey]);
    });
    // Add some alias
    map.set("+1", "👍");
    map.set("smile", "😃");
    map.set("poo", "💩");
    return map;
}

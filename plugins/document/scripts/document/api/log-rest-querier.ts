/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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
import type { RestUser } from "./rest-querier";
import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { getAllJSON, uri } from "@tuleap/fetch-result";

export interface LogEntry {
    readonly when: string;
    readonly who: RestUser;
    readonly what: string;
    readonly old_value: string | null;
    readonly new_value: string | null;
    readonly diff_link: string | null;
}

export const getLogs = (item_id: number): ResultAsync<readonly LogEntry[], Fault> => {
    return getAllJSON<LogEntry>(uri`/api/v1/docman_items/${item_id}/logs`, {
        params: {
            limit: 50,
        },
    });
};

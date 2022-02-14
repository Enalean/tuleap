/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { AdvancedSearchParams } from "../type";

export function getRestBodyFromSearchParams(
    search: AdvancedSearchParams
): Record<string, string | Record<string, string>> {
    return {
        ...(search.global_search && { global_search: search.global_search }),
        ...(search.type && { type: search.type }),
        ...(search.title && { title: search.title }),
        ...(search.description && { description: search.description }),
        ...(search.owner && { owner: search.owner }),
        ...(search.create_date &&
            search.create_date.date.length > 0 && {
                create_date: {
                    date: search.create_date.date,
                    operator: search.create_date.operator,
                },
            }),
        ...(search.update_date &&
            search.update_date.date.length > 0 && {
                update_date: {
                    date: search.update_date.date,
                    operator: search.update_date.operator,
                },
            }),
    };
}

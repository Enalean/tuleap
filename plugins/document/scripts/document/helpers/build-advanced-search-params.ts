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

export function buildAdvancedSearchParams(
    params: Partial<AdvancedSearchParams> = {},
): AdvancedSearchParams {
    const empty: AdvancedSearchParams = {
        global_search: "",
        id: "",
        type: "",
        filename: "",
        title: "",
        description: "",
        owner: "",
        create_date: null,
        update_date: null,
        obsolescence_date: null,
        status: "",
        sort: { name: "update_date", order: "desc" },
    };

    return {
        ...empty,
        ...params,
    };
}

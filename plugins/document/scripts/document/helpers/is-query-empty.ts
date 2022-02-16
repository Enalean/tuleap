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
import { isAdditionalFieldNumber } from "./additional-custom-properties";
import type { SearchDate } from "../type";

export function isQueryEmpty(query_params: AdvancedSearchParams): boolean {
    return (
        query_params.global_search.length === 0 &&
        query_params.type.length === 0 &&
        query_params.title.length === 0 &&
        query_params.description.length === 0 &&
        query_params.owner.length === 0 &&
        query_params.create_date === null &&
        query_params.update_date === null &&
        query_params.obsolescence_date === null &&
        query_params.status.length === 0 &&
        areAllAdditionalParamsEmpty(query_params)
    );
}

function areAllAdditionalParamsEmpty(query_params: AdvancedSearchParams): boolean {
    for (const key of Object.keys(query_params)) {
        if (!isAdditionalFieldNumber(key)) {
            continue;
        }

        const search_param = query_params[key];
        if (!search_param) {
            continue;
        }

        if (isSearchDate(search_param)) {
            if (search_param.date.length > 0) {
                return false;
            }
        } else {
            if (search_param.length > 0) {
                return false;
            }
        }
    }

    return true;
}

function isSearchDate(search_param: string | SearchDate): search_param is SearchDate {
    return typeof search_param !== "string";
}

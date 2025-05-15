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

import type {
    SearchBodyRest,
    SearchDate,
    AdvancedSearchParams,
    SearchBodyPropertyDate,
    SearchBodyPropertySimple,
    AllowedSearchBodyPropertyName,
    SortParams,
} from "../type";
import { HardcodedPropertyName } from "../type";
import { isAdditionalFieldNumber } from "./additional-custom-properties";

export function getRestBodyFromSearchParams(search: AdvancedSearchParams): SearchBodyRest {
    const sort_params: SortParams | null = search.sort
        ? search.sort
        : { name: "update_date", order: "desc" };
    return {
        ...(search.global_search && { global_search: search.global_search }),
        ...getProperties(search),
        ...(sort_params && { sort: [sort_params] }),
    };
}

function getProperties(search: AdvancedSearchParams): Pick<SearchBodyRest, "properties"> {
    const properties: Array<SearchBodyPropertySimple | SearchBodyPropertyDate> = [];

    for (const key of Object.keys(search)) {
        if (!isAllowedSearchBodyPropertyName(key)) {
            continue;
        }

        const search_param = search[key];
        if (!search_param) {
            continue;
        }

        if (isSearchDate(search_param)) {
            if (search_param.date.length > 0) {
                properties.push({
                    name: key,
                    value_date: {
                        date: search_param.date,
                        operator: search_param.operator,
                    },
                });
            }
        } else {
            properties.push({
                name: key,
                value: search_param,
            });
        }
    }

    if (properties.length === 0) {
        return {};
    }

    return { properties: [...properties] };
}

function isAllowedSearchBodyPropertyName(name: string): name is AllowedSearchBodyPropertyName {
    return (
        HardcodedPropertyName.some((hardcoded) => hardcoded === name) ||
        isAdditionalFieldNumber(name)
    );
}

function isSearchDate(search_param: string | SearchDate): search_param is SearchDate {
    return typeof search_param !== "string";
}

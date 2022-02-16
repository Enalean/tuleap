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
import type { SearchDate } from "../type";
import { isAdditionalFieldNumber } from "./additional-custom-properties";

export function getRestBodyFromSearchParams(search: AdvancedSearchParams): Record<
    string,
    | string
    | Record<string, string>
    | ReadonlyArray<{
          name: string;
          value?: string;
          value_date?: SearchDate;
      }>
> {
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
        ...(search.obsolescence_date &&
            search.obsolescence_date.date.length > 0 && {
                obsolescence_date: {
                    date: search.obsolescence_date.date,
                    operator: search.obsolescence_date.operator,
                },
            }),
        ...(search.status && { status: search.status }),
        ...getAdditionalParams(search),
    };
}

function getAdditionalParams(search: AdvancedSearchParams):
    | Record<string, never>
    | {
          custom_properties: ReadonlyArray<{
              name: string;
              value?: string;
              value_date?: SearchDate;
          }>;
      } {
    const custom_properties: Array<{
        name: string;
        value?: string;
        value_date?: SearchDate;
    }> = [];

    for (const key of Object.keys(search)) {
        if (!isAdditionalFieldNumber(key)) {
            continue;
        }

        const search_param = search[key];
        if (!search_param) {
            continue;
        }

        if (isSearchDate(search_param)) {
            if (search_param.date.length > 0) {
                custom_properties.push({
                    name: key,
                    value_date: {
                        date: search_param.date,
                        operator: search_param.operator,
                    },
                });
            }
        } else {
            custom_properties.push({
                name: key,
                value: search_param,
            });
        }
    }

    if (custom_properties.length === 0) {
        return {};
    }

    return { custom_properties };
}

function isSearchDate(search_param: string | SearchDate): search_param is SearchDate {
    return typeof search_param !== "string";
}

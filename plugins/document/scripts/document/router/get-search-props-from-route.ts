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
import type { Route } from "vue-router/types/router";
import type { AdvancedSearchParams, SearchDate, AdditionalFieldNumber } from "../type";
import { AllowedSearchDateOperator, AllowedSearchType } from "../type";
import {
    isAdditionalFieldNumber,
    isAdditionalPropertySeemsToBeADate,
} from "../helpers/additional-custom-properties";

export function getSearchPropsFromRoute(
    route: Route,
    root_id: number
): {
    folder_id: number;
    query: AdvancedSearchParams;
    offset: number;
} {
    const user_submitted_type = String(route.query.type || "");

    const type = isAllowedType(user_submitted_type) ? user_submitted_type : "";

    return {
        folder_id: route.params.folder_id ? Number(route.params.folder_id) : root_id,
        query: {
            global_search: String(route.query.q || ""),
            id: String(route.query.id || ""),
            type,
            filename: String(route.query.filename || ""),
            title: String(route.query.title || ""),
            description: String(route.query.description || ""),
            owner: String(route.query.owner || ""),
            update_date: getUpdateDate(route),
            create_date: getCreateDate(route),
            obsolescence_date: getObsolescenceDate(route),
            status: String(route.query.status || ""),
            ...getCustomProperties(route),
        },
        offset: Number(route.query.offset || "0"),
    };
}

function getCustomProperties(
    route: Route
): Record<AdditionalFieldNumber, string | SearchDate | null> {
    const additional: Record<AdditionalFieldNumber, string | SearchDate | null> = {};

    for (const key of Object.keys(route.query)) {
        if (!isAdditionalFieldNumber(key)) {
            continue;
        }

        if (isAdditionalPropertySeemsToBeADate(route, key)) {
            additional[key] = getSearchDate(
                String(route.query[key] || ""),
                String(route.query[key + "_op"] || "")
            );
        } else {
            additional[key] = String(route.query[key] || "");
        }
    }

    return additional;
}

function isAllowedType(type: string): type is AllowedSearchType {
    return AllowedSearchType.some((allowed_type) => type === allowed_type);
}

function getUpdateDate(route: Route): SearchDate | null {
    const date = String(route.query.update_date || "");
    const operator = String(route.query.update_date_op || "");

    return getSearchDate(date, operator);
}

function getCreateDate(route: Route): SearchDate | null {
    const date = String(route.query.create_date || "");
    const operator = String(route.query.create_date_op || "");

    return getSearchDate(date, operator);
}

function getObsolescenceDate(route: Route): SearchDate | null {
    const date = String(route.query.obsolescence_date || "");
    const operator = String(route.query.obsolescence_date_op || "");

    return getSearchDate(date, operator);
}

function getSearchDate(date: string, operator: string): SearchDate | null {
    if (!date || !isAllowedDateOperator(operator)) {
        return null;
    }

    return { date, operator };
}

function isAllowedDateOperator(operator: string): operator is AllowedSearchDateOperator {
    if (!operator) {
        return false;
    }

    return AllowedSearchDateOperator.some((allowed_operator) => operator === allowed_operator);
}

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
import type { Dictionary } from "vue-router/types/router";

export function getRouterQueryFromSearchParams(params: AdvancedSearchParams): Dictionary<string> {
    const query: Dictionary<string> = {};
    if (params.global_search.length > 0) {
        query.q = params.global_search;
    }
    if (params.type.length > 0) {
        query.type = params.type;
    }
    if (params.title.length > 0) {
        query.title = params.title;
    }
    if (params.description.length > 0) {
        query.description = params.description;
    }
    if (params.owner.length > 0) {
        query.owner = params.owner;
    }
    if (params.create_date !== null && params.create_date.date.length > 0) {
        query.create_date = params.create_date.date;
        query.create_date_op = params.create_date.operator;
    }
    if (params.update_date !== null && params.update_date.date.length > 0) {
        query.update_date = params.update_date.date;
        query.update_date_op = params.update_date.operator;
    }

    return query;
}

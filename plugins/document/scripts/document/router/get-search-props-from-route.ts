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
import { AllowedSearchType } from "../type";
import type { AdvancedSearchParams } from "../type";

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
            query: String(route.query.q || ""),
            type,
            title: String(route.query.title || ""),
            description: String(route.query.description || ""),
        },
        offset: Number(route.query.offset || "0"),
    };
}

function isAllowedType(type: string): type is AllowedSearchType {
    return AllowedSearchType.some((allowed_type) => type === allowed_type);
}

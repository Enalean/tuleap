/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import type { RouteParams } from "vue-router";

export const extractPullRequestIdFromRouteParams = (route_params: RouteParams): number => {
    if (!(typeof route_params.id === "string")) {
        return 0;
    }

    const parsed_pull_request_id = Number.parseInt(route_params.id, 10);
    if (isNaN(parsed_pull_request_id)) {
        return 0;
    }

    return parsed_pull_request_id;
};

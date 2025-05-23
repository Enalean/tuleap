/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

import { del, getJSON, uri } from "@tuleap/fetch-result";
import { type ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import type { Query } from "../type";
import type { WidgetRepresentation } from "./cross-tracker-rest-api-types";

export function getQueries(widget_id: number): ResultAsync<ReadonlyArray<Query>, Fault> {
    return getJSON<WidgetRepresentation>(uri`/api/v1/crosstracker_widget/${widget_id}`).map(
        (widget): ReadonlyArray<Query> => {
            return widget.queries.map((query) => {
                return {
                    id: query.id,
                    tql_query: query.tql_query,
                    title: query.title,
                    description: query.description,
                    is_default: query.is_default,
                };
            });
        },
    );
}

export function deleteQuery(query: Query): ResultAsync<null, Fault> {
    return del(uri`/api/v1/crosstracker_query/${query.id}`).map(() => null);
}

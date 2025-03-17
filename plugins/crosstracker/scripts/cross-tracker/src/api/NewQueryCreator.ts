/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { PostNewQuery } from "../domain/PostNewQuery";
import { postJSON, uri } from "@tuleap/fetch-result";
import type { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import type { PostQueryRepresentation, QueryRepresentation } from "./cross-tracker-rest-api-types";

export const NewQueryCreator = (): PostNewQuery => {
    return {
        postNewQuery(
            query_to_post: PostQueryRepresentation,
        ): ResultAsync<QueryRepresentation, Fault> {
            return postJSON(uri`/api/v1/crosstracker_query`, {
                widget_id: query_to_post.widget_id,
                tql_query: query_to_post.tql_query,
                title: query_to_post.title,
                description: query_to_post.description,
                is_default: query_to_post.is_default,
            });
        },
    };
};

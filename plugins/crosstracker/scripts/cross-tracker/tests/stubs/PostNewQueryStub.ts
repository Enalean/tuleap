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

import type { PostNewQuery } from "../../src/domain/PostNewQuery";
import type { ResultAsync } from "neverthrow";
import { errAsync, okAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import type {
    PostQueryRepresentation,
    QueryRepresentation,
} from "../../src/api/cross-tracker-rest-api-types";

export const PostNewQueryStub = {
    withDefaultContent(): PostNewQuery {
        return {
            postNewQuery: () =>
                okAsync({
                    id: "158",
                    title: "My new query",
                    description: "description",
                    tql_query: "SELECT @id FROM @project = 'self' WHERE @id > 1",
                    is_default: false,
                }),
        };
    },
    withCallback(callback: (query_to_post: PostQueryRepresentation) => void): PostNewQuery {
        return {
            postNewQuery(
                query_to_post: PostQueryRepresentation,
            ): ResultAsync<QueryRepresentation, Fault> {
                callback(query_to_post);
                return okAsync({ ...query_to_post, id: "51151" });
            },
        };
    },
    withFault(fault: Fault): PostNewQuery {
        return {
            postNewQuery: () => errAsync(fault),
        };
    },
};

/*
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

import type { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import { okAsync } from "neverthrow";
import { get } from "@tuleap/plugin-git-gitlab-api-querier";
import type { GitlabGroup, GitlabCredentials } from "../stores/types";
import { extractNextUrl } from "./link-header-helper";
import { decodeJSON, rawUri, uri } from "@tuleap/fetch-result";

export const LINK_HEADER = "link";

export interface GitlabApi {
    getGitlabGroups: (credentials: GitlabCredentials) => ResultAsync<readonly GitlabGroup[], Fault>;
}

export const createGitlabApiQuerier = (): GitlabApi => {
    const continueFetching = (
        response: Response,
        credentials: GitlabCredentials,
    ): ResultAsync<readonly GitlabGroup[], Fault> => {
        const next_page_url = extractNextUrl(getLinkHeaderFromResponse(response));

        return decodeJSON<readonly GitlabGroup[]>(response).andThen((groups) => {
            if (next_page_url !== null) {
                return get(uri`${rawUri(next_page_url)}`, credentials).andThen((response) =>
                    continueFetching(response, credentials).map((new_groups) =>
                        groups.concat(new_groups),
                    ),
                );
            }
            return okAsync(groups);
        });
    };

    return {
        getGitlabGroups(credentials): ResultAsync<readonly GitlabGroup[], Fault> {
            const next_page_url = new URL(
                "/api/v4/groups?pagination=keyset&order_by=id&sort=asc",
                credentials.server_url,
            );

            return get(uri`${rawUri(String(next_page_url))}`, credentials).andThen((response) =>
                continueFetching(response, credentials),
            );
        },
    };
};

function getLinkHeaderFromResponse(response: Response): string {
    const link_header = response.headers.get(LINK_HEADER);
    if (link_header === null) {
        throw Error(`Missing header link`);
    }
    return link_header;
}

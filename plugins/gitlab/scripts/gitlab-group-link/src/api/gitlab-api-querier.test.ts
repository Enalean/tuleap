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

import { LINK_HEADER, createGitlabApiQuerier } from "./gitlab-api-querier";
import type { GitlabGroup } from "../stores/types";
import { FetchInterfaceStub } from "../tests/stubs/FetchInterfaceStub";
import { isGitlabApiFault } from "./GitlabApiFault";
import { isGitLabCredentialsFault } from "./GitLabCredentialsFault";

function buildResponse<TypeOfJSONPayload>(
    will_succeed: boolean,
    status: number,
    payload: TypeOfJSONPayload,
    next_url: string | null
): Response {
    return {
        ok: will_succeed,
        status,
        headers: {
            get: (name: string): string | null => {
                if (name !== LINK_HEADER) {
                    return null;
                }

                if (next_url === null) {
                    return "";
                }

                return `
                    <${next_url}>; rel="next",
                    <https://example.com/api/stuff?page=1&per_page=50>; rel="first",
                    <https://example.com/api/stuff?page=2&per_page=50>; rel="last"
                `;
            },
        },
        json: (): Promise<TypeOfJSONPayload> => Promise.resolve(payload),
    } as unknown as Response;
}

function getRequestInit(fetcher: FetchInterfaceStub, call: number): RequestInit {
    const request_init = fetcher.getRequestInit(call);
    if (request_init === undefined) {
        throw new Error("Expected request init to be defined");
    }
    return request_init;
}

const credentials = {
    server_url: new URL("https://example.com"),
    token: "glpat-a1e2i3o4u5y6",
};

const group_1 = {
    id: 818532,
    name: "R&D fellows",
} as GitlabGroup;

const group_2 = {
    id: 984142,
    name: "QA folks",
} as GitlabGroup;

describe("gitlab-api-querier", () => {
    describe("getGitlabGroups", () => {
        it("should query all the groups user can see on GitLab by fetching next pages urls extracted from the link header", async () => {
            const fetcher = FetchInterfaceStub.withSuccessiveResponses(
                buildResponse<readonly GitlabGroup[]>(
                    true,
                    200,
                    [group_1],
                    "https://example.com/api/v4/groups?pagination=keyset&order_by=id&sort=asc&page=2"
                ),
                buildResponse<readonly GitlabGroup[]>(true, 200, [group_2], null)
            );

            const expected_authentication_headers = new Headers();
            expected_authentication_headers.append("Authorization", "Bearer glpat-a1e2i3o4u5y6");

            const querier = createGitlabApiQuerier(fetcher);
            const result = await querier.getGitlabGroups(credentials);

            expect(fetcher.getCallsNumber()).toBe(2);

            expect(getRequestInit(fetcher, 0).headers).toStrictEqual(
                expected_authentication_headers
            );
            expect(fetcher.getRequestInfo(0)).toStrictEqual(
                new URL("https://example.com/api/v4/groups?pagination=keyset&order_by=id&sort=asc")
            );

            expect(getRequestInit(fetcher, 1).headers).toStrictEqual(
                expected_authentication_headers
            );
            expect(fetcher.getRequestInfo(1)).toStrictEqual(
                new URL(
                    "https://example.com/api/v4/groups?pagination=keyset&order_by=id&sort=asc&page=2"
                )
            );

            expect(result._unsafeUnwrap()).toStrictEqual([group_1, group_2]);
        });

        it("should stop querying if a fetch operation has failed", async () => {
            const fetcher = FetchInterfaceStub.withSuccessiveResponses(
                buildResponse<readonly GitlabGroup[]>(
                    true,
                    200,
                    [group_1],
                    "https://example.com/api/v4/groups?pagination=keyset&order_by=id&sort=asc"
                ),
                buildResponse<readonly GitlabGroup[]>(
                    false,
                    500,
                    [group_2],
                    "https://example.com/api/v4/groups?pagination=keyset&order_by=id&sort=asc&page=2"
                ),
                buildResponse<readonly GitlabGroup[]>(
                    true,
                    200,
                    [],
                    "https://example.com/api/v4/groups?pagination=keyset&order_by=id&sort=asc&page=3"
                )
            );

            const querier = createGitlabApiQuerier(fetcher);
            const result = await querier.getGitlabGroups(credentials);

            if (!result.isErr()) {
                throw new Error("Expected an Err");
            }

            expect(fetcher.getCallsNumber()).toBe(2);
            expect(isGitlabApiFault(result.error)).toBeTruthy();
        });

        it("should stop fetching groups when api sends a 401 error and should return a GitlabCredentialsFault", async () => {
            const fetcher = FetchInterfaceStub.withSuccessiveResponses(
                buildResponse<readonly GitlabGroup[]>(false, 401, [], null),
                buildResponse<readonly GitlabGroup[]>(false, 401, [], null)
            );

            const querier = createGitlabApiQuerier(fetcher);
            const result = await querier.getGitlabGroups(credentials);

            if (!result.isErr()) {
                throw new Error("Expected an Err");
            }

            expect(fetcher.getCallsNumber()).toBe(1);
            expect(isGitLabCredentialsFault(result.error)).toBeTruthy();
        });
    });
});

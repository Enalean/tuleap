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

import { describe, it, expect, vi } from "vitest";
import * as gitlab_querier from "@tuleap/plugin-git-gitlab-api-querier";
import { Fault } from "@tuleap/fault";
import { okAsync, errAsync } from "neverthrow";
import type { ResultAsync } from "neverthrow";
import { LINK_HEADER, createGitlabApiQuerier } from "./gitlab-api-querier";
import type { GitlabGroup } from "../stores/types";
import { rawUri, uri } from "@tuleap/fetch-result";

vi.mock("@tuleap/plugin-git-gitlab-api-querier");

function buildResponse<TypeOfJSONPayload>(
    payload: TypeOfJSONPayload,
    next_url: string | null,
): Response {
    return {
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
        const getGitlabGroups = (): ResultAsync<readonly GitlabGroup[], Fault> => {
            const querier = createGitlabApiQuerier();
            return querier.getGitlabGroups(credentials);
        };

        it("should query all the groups user can see on GitLab by fetching next pages urls extracted from the link header", async () => {
            let number_of_calls = 0;
            const getSpy = vi.spyOn(gitlab_querier, "get").mockImplementation(() => {
                number_of_calls++;
                if (number_of_calls === 1) {
                    return okAsync(
                        buildResponse<readonly GitlabGroup[]>(
                            [group_1],
                            "https://example.com/api/v4/groups?pagination=keyset&order_by=id&sort=asc&page=2",
                        ),
                    );
                }
                return okAsync(buildResponse<readonly GitlabGroup[]>([group_2], null));
            });

            const result = await getGitlabGroups();

            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }

            expect(getSpy.mock.calls).toHaveLength(2);
            const [first_call, second_call] = getSpy.mock.calls;
            expect(first_call[0]).toStrictEqual(
                uri`${rawUri(
                    "https://example.com/api/v4/groups?pagination=keyset&order_by=id&sort=asc",
                )}`,
            );
            expect(first_call[1]).toBe(credentials);
            expect(second_call[0]).toStrictEqual(
                uri`${rawUri(
                    "https://example.com/api/v4/groups?pagination=keyset&order_by=id&sort=asc&page=2",
                )}`,
            );
            expect(second_call[1]).toBe(credentials);

            expect(result.value).toStrictEqual([group_1, group_2]);
        });

        it("should stop querying if a fetch operation has failed", async () => {
            let number_of_calls = 0;
            const getSpy = vi.spyOn(gitlab_querier, "get").mockImplementation(() => {
                number_of_calls++;
                if (number_of_calls === 1) {
                    return okAsync(
                        buildResponse<readonly GitlabGroup[]>(
                            [group_1],
                            "https://example.com/api/v4/groups?pagination=keyset&order_by=id&sort=asc",
                        ),
                    );
                }
                if (number_of_calls === 2) {
                    return errAsync(Fault.fromMessage("Internal Server Error"));
                }
                return okAsync(
                    buildResponse<readonly GitlabGroup[]>(
                        [],
                        "https://example.com/api/v4/groups?pagination=keyset&order_by=id&sort=asc&page=3",
                    ),
                );
            });

            const result = await getGitlabGroups();

            expect(getSpy.mock.calls).toHaveLength(2);
            expect(result.isErr()).toBe(true);
        });
    });
});

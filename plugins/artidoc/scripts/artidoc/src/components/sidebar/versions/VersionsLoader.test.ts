/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
import { okAsync } from "neverthrow";
import type { Result } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import * as fetch_result from "@tuleap/fetch-result";
import { uri } from "@tuleap/fetch-result";
import {
    type PaginatedVersions,
    type VersionPayload,
    getVersionsLoader,
} from "@/components/sidebar/versions/VersionsLoader";
import type { User } from "@tuleap/core-rest-api-types";

const DOCUMENT_ID = 231;

const createBatchOfVersionsPayloads = (starting_from_id: number): VersionPayload[] => {
    return [...Array(50).keys()].map(
        (index: number): VersionPayload => ({
            id: starting_from_id + index,
            created_by: {} as User,
            created_on: "2025-11-17T12:20:00+01:00",
        }),
    );
};

const checkPaginatedVersionsResult = (
    result: Result<PaginatedVersions, Fault>,
    from_payloads: VersionPayload[],
    is_more_to_load_expected: boolean,
): void => {
    if (!result.isOk()) {
        throw new Error("Expected an Ok");
    }
    expect(result.value.versions.map(({ id }) => id)).toStrictEqual(
        from_payloads.map(({ id }) => id),
    );
    expect(result.value.has_more).toBe(is_more_to_load_expected);
};

describe("VersionsLoader", () => {
    it("each time loadNextBatchOfVersions() is called, Then it should retrieve the next page of versions", async () => {
        const getResponse = vi.spyOn(fetch_result, "getResponse");
        const first_batch_of_versions = createBatchOfVersionsPayloads(0);
        const second_batch_of_versions = createBatchOfVersionsPayloads(50);
        const third_batch_of_versions = createBatchOfVersionsPayloads(100);

        const mockGetResponse = (return_json: VersionPayload[]): void => {
            getResponse.mockReturnValueOnce(
                okAsync({
                    headers: new Headers({ "X-PAGINATION-SIZE": String(102) }),
                    json: () => Promise.resolve(return_json),
                } as Response),
            );
        };

        mockGetResponse(first_batch_of_versions);
        mockGetResponse(second_batch_of_versions);
        mockGetResponse(third_batch_of_versions);

        const loader = getVersionsLoader(DOCUMENT_ID);
        const expected_uri = uri`/api/v1/artidoc/${DOCUMENT_ID}/versions`;

        const first_batch = await loader.loadNextBatchOfVersions();
        expect(getResponse).toHaveBeenCalledWith(expected_uri, {
            params: { limit: 50, offset: 0 },
        });
        checkPaginatedVersionsResult(first_batch, first_batch_of_versions, true);

        const second_batch = await loader.loadNextBatchOfVersions();
        expect(getResponse).toHaveBeenCalledWith(expected_uri, {
            params: { limit: 50, offset: 50 },
        });
        checkPaginatedVersionsResult(second_batch, second_batch_of_versions, true);

        const third_batch = await loader.loadNextBatchOfVersions();
        expect(getResponse).toHaveBeenCalledWith(expected_uri, {
            params: { limit: 50, offset: 100 },
        });
        checkPaginatedVersionsResult(third_batch, third_batch_of_versions, false);
    });
});

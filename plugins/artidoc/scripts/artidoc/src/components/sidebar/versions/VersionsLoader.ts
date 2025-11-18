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

import { type ResultAsync } from "neverthrow";
import type { User } from "@tuleap/core-rest-api-types";
import type { Fault } from "@tuleap/fault";
import { getResponse, decodeJSON, uri } from "@tuleap/fetch-result";
import { Option } from "@tuleap/option";
import type { Version } from "@/components/sidebar/versions/fake-list-of-versions";

const LIMIT = 50;

export type VersionsLoader = {
    loadNextBatchOfVersions: () => ResultAsync<PaginatedVersions, Fault>;
};

export type PaginatedVersions = Readonly<{
    versions: Version[];
    has_more: boolean;
}>;

export type VersionPayload = Readonly<{
    id: number;
    created_on: string;
    created_by: User;
}>;

export const getVersionsLoader = (document_id: number): VersionsLoader => {
    let offset = 0;
    return {
        loadNextBatchOfVersions(): ResultAsync<PaginatedVersions, Fault> {
            return getResponse(uri`/api/v1/artidoc/${document_id}/versions`, {
                params: {
                    limit: LIMIT,
                    offset,
                },
            }).andThen((response) => {
                offset += LIMIT;
                const total = Number.parseInt(response.headers.get("X-PAGINATION-SIZE") ?? "0", 10);

                return decodeJSON<ReadonlyArray<VersionPayload>>(response).map((versions) => {
                    return {
                        versions: versions.map(
                            (version: VersionPayload): Version => ({
                                ...version,
                                title: Option.nothing(),
                                description: Option.nothing(),
                                created_on: new Date(version.created_on),
                            }),
                        ),
                        has_more: total >= offset,
                    };
                });
            });
        },
    };
};

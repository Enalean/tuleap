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

import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { getJSON, uri } from "@tuleap/fetch-result";
import { Option } from "@tuleap/option";

export type PullRequestFileStatus = "M" | "A" | "D";

export const FILE_STATUS_MODIFIED: PullRequestFileStatus = "M";
export const FILE_STATUS_ADDED: PullRequestFileStatus = "A";
export const FILE_STATUS_DELETED: PullRequestFileStatus = "D";

export type PullRequestFileStatEmpty = "-";
export const FILE_STAT_EMPTY: PullRequestFileStatEmpty = "-";

export type PullRequestFilePayload = {
    path: string;
    status: PullRequestFileStatus;
    lines_added: string | PullRequestFileStatEmpty;
    lines_removed: string | PullRequestFileStatEmpty;
};

export type PullRequestFile = {
    path: string;
    status: PullRequestFileStatus;
    lines_added: Option<number>;
    lines_removed: Option<number>;
};

export const getFiles = (
    pull_request_id: number,
): ResultAsync<readonly PullRequestFile[], Fault> => {
    const buildLinesStatOption = (
        stat_value: string | PullRequestFileStatEmpty,
    ): Option<number> => {
        if (stat_value === FILE_STAT_EMPTY) {
            return Option.nothing<number>();
        }

        return Option.fromValue<number>(Number.parseInt(stat_value, 10));
    };

    return getJSON<PullRequestFilePayload[]>(
        uri`/api/v1/pull_requests/${pull_request_id}/files`,
    ).map((file_payloads): readonly PullRequestFile[] => {
        return file_payloads.map(
            (file): PullRequestFile => ({
                ...file,
                lines_added: buildLinesStatOption(file.lines_added),
                lines_removed: buildLinesStatOption(file.lines_removed),
            }),
        );
    });
};

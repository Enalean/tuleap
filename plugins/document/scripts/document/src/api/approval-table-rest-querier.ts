/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
import type { ApprovalTable } from "../type";
import type { Fault } from "@tuleap/fault";
import {
    del,
    getAllJSON,
    getJSON,
    patchResponse,
    postResponse,
    putResponse,
    uri,
} from "@tuleap/fetch-result";

export function getDocumentApprovalTable(
    item_id: number,
    version: number,
): ResultAsync<ApprovalTable, Fault> {
    return getJSON<ApprovalTable>(uri`/api/docman_items/${item_id}/approval_table/${version}`);
}

export function getAllDocumentApprovalTables(
    item_id: number,
): ResultAsync<ReadonlyArray<ApprovalTable>, Fault> {
    return getAllJSON<ApprovalTable>(uri`/api/docman_items/${item_id}/approval_tables`, {
        params: {
            limit: 50,
        },
    });
}

export function postApprovalTable(
    item_id: number,
    users: ReadonlyArray<number>,
    user_groups: ReadonlyArray<number>,
): ResultAsync<null, Fault> {
    return postResponse(
        uri`/api/docman_items/${item_id}/approval_table`,
        {},
        {
            users,
            user_groups,
        },
    ).map(() => null);
}

export function updateApprovalTable(
    item_id: number,
    owner: number,
    status: string,
    comment: string,
    notification_type: string,
    reviewers: Array<number>,
    reviewers_to_add: Array<number>,
    reviewers_group_to_add: Array<number>,
    reminder_occurence: number,
): ResultAsync<null, Fault> {
    return putResponse(
        uri`/api/docman_items/${item_id}/approval_table`,
        {},
        {
            owner,
            status,
            comment,
            notification_type,
            reviewers: [...reviewers, ...reviewers_to_add],
            reviewers_group_to_add,
            reminder_occurence: Math.max(reminder_occurence, 0),
        },
    ).map(() => null);
}

export function patchApprovalTable(item_id: number, action: string): ResultAsync<null, Fault> {
    return patchResponse(uri`/api/docman_items/${item_id}/approval_table`, {}, { action }).map(
        () => null,
    );
}

export function deleteApprovalTable(item_id: number): ResultAsync<null, Fault> {
    return del(uri`/api/docman_items/${item_id}/approval_table`).map(() => null);
}

export function putReview(
    item_id: number,
    review: string,
    comment: string,
    notification: boolean,
): ResultAsync<null, Fault> {
    return putResponse(
        uri`/api/docman_items/${item_id}/approval_table/review`,
        {},
        {
            review,
            comment,
            notification,
        },
    ).map(() => null);
}

export function postApprovalTableReminder(item_id: number): ResultAsync<null, Fault> {
    return postResponse(uri`/api/docman_items/${item_id}/approval_table/reminder`, {}, {}).map(
        () => null,
    );
}

export function postApprovalTableReviewerReminder(
    item_id: number,
    reviewer_id: number,
): ResultAsync<null, Fault> {
    return postResponse(
        uri`/api/docman_items/${item_id}/approval_table/reminder/${reviewer_id}`,
        {},
        {},
    ).map(() => null);
}

/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import { get, recursiveGet, put, post, options } from "@tuleap/tlp-fetch";
import type { FetchWrapperError } from "@tuleap/tlp-fetch";

import { resetError, setError } from "./rest-error-state";
import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";

const headers = {
    "content-type": "application/json",
};

interface TrackerRepresentation {
    readonly id: number;
}

export function getTracker(tracker_id: number): Promise<TrackerRepresentation> {
    return get(encodeURI(`/api/v1/trackers/${tracker_id}`)).then((response) => {
        resetError();
        return response.json();
    }, errorHandler);
}

export interface ArtifactRepresentation {
    readonly id: number;
    readonly title: string;
}

export function getArtifact(artifact_id: number): Promise<ArtifactRepresentation> {
    return get(encodeURI(`/api/v1/artifacts/${artifact_id}`)).then((response) => {
        resetError();
        return response.json();
    }, errorHandler);
}

type EtagValue = string | null;
type LastModifiedTimestamp = string | null;

interface ArtifactRepresentationWithConcurrencyHeaders extends ArtifactRepresentation {
    readonly Etag: EtagValue;
    readonly "Last-Modified": LastModifiedTimestamp;
}

export function getArtifactWithCompleteTrackerStructure(
    artifact_id: number
): Promise<ArtifactRepresentationWithConcurrencyHeaders> {
    return get(
        encodeURI(`/api/v1/artifacts/${artifact_id}?tracker_structure_format=complete`)
    ).then(async (response) => {
        resetError();
        return {
            Etag: response.headers.get("Etag"),
            "Last-Modified": response.headers.get("Last-Modified"),
            ...(await response.json()),
        };
    }, errorHandler);
}

export function getAllOpenParentArtifacts(
    tracker_id: number,
    limit: number,
    offset: number
): Promise<ArtifactRepresentation[]> {
    return recursiveGet<ArtifactRepresentation[], ArtifactRepresentation>(
        encodeURI(`/api/v1/trackers/${tracker_id}/parent_artifacts`),
        { params: { limit, offset } }
    ).then((parent_artifacts) => {
        resetError();
        return parent_artifacts;
    }, errorHandler);
}

interface JustCreatedArtifact {
    readonly id: number;
}
interface BaseFieldValue {
    readonly field_id: number;
}
interface ValueOfFieldWithSingleSelection extends BaseFieldValue {
    readonly value: unknown;
}
interface ListFieldValue {
    readonly bind_value_ids: number[];
}
type FieldValue = ValueOfFieldWithSingleSelection | ListFieldValue;
type FieldValuesMap = ReadonlyArray<FieldValue>;

export async function createArtifact(
    tracker_id: number,
    field_values: FieldValuesMap
): Promise<JustCreatedArtifact> {
    const body = JSON.stringify({
        tracker: {
            id: tracker_id,
        },
        values: field_values,
    });

    const response = await post("/api/v1/artifacts", {
        headers,
        body,
    });
    resetError();
    const { id } = await response.json();
    return { id };
}

interface FollowupComment {
    readonly body: string;
    readonly format: TextFieldFormat;
}
type JustEditedArtifact = JustCreatedArtifact;

export async function editArtifact(
    artifact_id: number,
    field_values: FieldValuesMap,
    followup_comment: FollowupComment
): Promise<JustEditedArtifact> {
    const body = JSON.stringify({
        values: field_values,
        comment: followup_comment,
    });

    await put(encodeURI(`/api/v1/artifacts/${artifact_id}`), {
        headers,
        body,
    });
    resetError();
    return { id: artifact_id };
}

export async function editArtifactWithConcurrencyChecking(
    artifact_id: number,
    field_values: FieldValuesMap,
    followup_comment: FollowupComment,
    etag: EtagValue,
    last_modified: LastModifiedTimestamp
): Promise<JustEditedArtifact> {
    const body = JSON.stringify({
        values: field_values,
        comment: followup_comment,
    });

    await put(encodeURI(`/api/v1/artifacts/${artifact_id}`), {
        headers: getEditHeaders(etag, last_modified),
        body,
    });
    resetError();
    return { id: artifact_id };
}

function getEditHeaders(
    etag: EtagValue,
    last_modified: LastModifiedTimestamp
): Record<string, string> {
    const returned_headers: Record<string, string> = { ...headers };
    if (last_modified !== null) {
        returned_headers["If-Unmodified-Since"] = last_modified;
    }
    if (etag !== null) {
        returned_headers["If-match"] = etag;
    }
    return returned_headers;
}

interface UserRepresentation {
    readonly id: number;
}

interface SearchedUsers {
    readonly results: UserRepresentation[];
}

export function searchUsers(query: string): Promise<SearchedUsers> {
    return get("/api/v1/users", {
        params: { query },
    }).then(async (response) => {
        resetError();
        const results = await response.json();
        return { results };
    }, errorHandler);
}

type FollowupsOrder = "asc" | "desc";

interface FollowupCommentRepresentation {
    readonly id: number;
}

interface FollowupsCommentsCollection {
    readonly results: FollowupCommentRepresentation[];
    total: string;
}

export function getFollowupsComments(
    artifact_id: number,
    limit: number,
    offset: number,
    order: FollowupsOrder
): Promise<FollowupsCommentsCollection> {
    return get(encodeURI(`/api/v1/artifacts/${artifact_id}/changesets`), {
        params: {
            fields: "comments",
            limit,
            offset,
            order,
        },
    }).then(async (response) => {
        resetError();
        const followup_comments = await response.json();
        return {
            results: followup_comments,
            total: response.headers.get("X-PAGINATION-SIZE") ?? "0",
        };
    }, errorHandler);
}

type UploadedTemporaryFileIdentifier = number;

export function uploadTemporaryFile(
    file_name: string,
    file_type: string,
    first_chunk: string,
    description: string
): Promise<UploadedTemporaryFileIdentifier> {
    const body = JSON.stringify({
        name: file_name,
        mimetype: file_type,
        content: first_chunk,
        description,
    });

    return post("/api/v1/artifact_temporary_files", {
        headers,
        body,
    }).then(async (response) => {
        resetError();
        const { id } = await response.json();
        return id;
    }, errorHandler);
}

export function uploadAdditionalChunk(
    temporary_file_id: UploadedTemporaryFileIdentifier,
    chunk: string,
    chunk_offset: number
): Promise<unknown> {
    const body = JSON.stringify({
        content: chunk,
        offset: chunk_offset,
    });

    return put(encodeURI(`/api/v1/artifact_temporary_files/${temporary_file_id}`), {
        headers,
        body,
    }).catch(errorHandler);
}

type CommentOrderInvertPreferenceKey = `tracker_comment_invertorder_${number}`;
type DefaultTextFormatPreferenceKey = "user_edition_default_format";
type RelativeDateDisplayPreferenceKey = "relative_dates_display";
type PreferenceKey =
    | CommentOrderInvertPreferenceKey
    | DefaultTextFormatPreferenceKey
    | RelativeDateDisplayPreferenceKey;

interface PreferenceRepresentation {
    readonly value: unknown;
}

export function getUserPreference(
    user_id: number,
    preference_key: PreferenceKey
): Promise<PreferenceRepresentation> {
    return get(encodeURI(`/api/v1/users/${user_id}/preferences`), {
        cache: "force-cache",
        params: { key: preference_key },
    }).then((response) => {
        resetError();
        return response.json();
    }, errorHandler);
}

interface FileUploadRulesRepresentation {
    readonly disk_quota: number;
    readonly disk_usage: number;
    readonly max_chunk_size: number;
}

export async function getFileUploadRules(): Promise<FileUploadRulesRepresentation> {
    const response = await options("/api/v1/artifact_temporary_files");
    const disk_quota = parseInt(response.headers.get("X-QUOTA") ?? "0", 10);
    const disk_usage = parseInt(response.headers.get("X-DISK-USAGE") ?? "0", 10);
    const max_chunk_size = parseInt(response.headers.get("X-UPLOAD-MAX-FILE-CHUNKSIZE") ?? "0", 10);

    return {
        disk_quota,
        disk_usage,
        max_chunk_size,
    };
}

export function getFirstReverseIsChildLink(artifact_id: number): Promise<ArtifactRepresentation[]> {
    return get(encodeURI(`/api/v1/artifacts/${artifact_id}/linked_artifacts`), {
        params: {
            direction: "reverse",
            nature: "_is_child",
            limit: 1,
            offset: 0,
        },
    }).then(async (response) => {
        resetError();
        const { collection } = await response.json();
        return collection;
    }, errorHandler);
}

async function errorHandler(error: FetchWrapperError): Promise<never> {
    const error_json = await error.response.json();
    if (error_json !== undefined && error_json.error && error_json.error.message) {
        setError(error_json.error.message);
        return Promise.reject(new Error(error_json.error.message));
    }
    const status_and_text = error.response.status + " " + error.response.statusText;
    setError(status_and_text);
    return Promise.reject(new Error(status_and_text));
}

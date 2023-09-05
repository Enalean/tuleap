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

import type { FetchWrapperError } from "@tuleap/tlp-fetch";
import { get, put } from "@tuleap/tlp-fetch";
import { resetError, setError } from "./rest-error-state";
import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";
import type {
    ArtifactResponseNoInstance,
    JustCreatedArtifactResponse,
    NewChangesetValue,
} from "@tuleap/plugin-tracker-rest-api-types";

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

type EtagValue = string | null;
type LastModifiedTimestamp = string | null;

type ArtifactRepresentationWithConcurrencyHeaders = Pick<
    ArtifactResponseNoInstance,
    "id" | "title" | "xref"
> & {
    readonly Etag: EtagValue;
    readonly "Last-Modified": LastModifiedTimestamp;
};

export function getArtifactWithCompleteTrackerStructure(
    artifact_id: number,
): Promise<ArtifactRepresentationWithConcurrencyHeaders> {
    return get(
        encodeURI(`/api/v1/artifacts/${artifact_id}?tracker_structure_format=complete`),
    ).then(async (response) => {
        resetError();
        return {
            Etag: response.headers.get("Etag"),
            "Last-Modified": response.headers.get("Last-Modified"),
            ...(await response.json()),
        };
    }, errorHandler);
}

type FieldValuesMap = ReadonlyArray<NewChangesetValue>;

interface FollowupComment {
    readonly body: string;
    readonly format: TextFieldFormat;
}
type JustEditedArtifact = JustCreatedArtifactResponse;

export function editArtifact(
    artifact_id: number,
    field_values: FieldValuesMap,
    followup_comment: FollowupComment,
): Promise<JustEditedArtifact> {
    const body = JSON.stringify({
        values: field_values,
        comment: followup_comment,
    });

    return put(encodeURI(`/api/v1/artifacts/${artifact_id}`), {
        headers,
        body,
    }).then(() => {
        resetError();
        return { id: artifact_id };
    }, errorHandler);
}

export function editArtifactWithConcurrencyChecking(
    artifact_id: number,
    field_values: FieldValuesMap,
    followup_comment: FollowupComment,
    etag: EtagValue,
    last_modified: LastModifiedTimestamp,
): Promise<JustEditedArtifact> {
    const body = JSON.stringify({
        values: field_values,
        comment: followup_comment,
    });

    return put(encodeURI(`/api/v1/artifacts/${artifact_id}`), {
        headers: getEditHeaders(etag, last_modified),
        body,
    }).then(() => {
        resetError();
        return { id: artifact_id };
    }, errorHandler);
}

function getEditHeaders(
    etag: EtagValue,
    last_modified: LastModifiedTimestamp,
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
    preference_key: PreferenceKey,
): Promise<PreferenceRepresentation> {
    return get(encodeURI(`/api/v1/users/${user_id}/preferences`), {
        cache: "force-cache",
        params: { key: preference_key },
    }).then((response) => {
        resetError();
        return response.json();
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

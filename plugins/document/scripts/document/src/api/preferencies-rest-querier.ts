/**
 *  Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { DOCMAN_FOLDER_EXPANDED_VALUE } from "../constants";
import { del, patch } from "@tuleap/tlp-fetch";
import { del as del_result, getJSON, patchResponse, uri } from "@tuleap/fetch-result";
import type { EmbeddedFileDisplayPreference } from "../type";
import { EMBEDDED_FILE_DISPLAY_LARGE, EMBEDDED_FILE_DISPLAY_NARROW } from "../type";
import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";

export async function patchUserPreferenciesForFolderInProject(
    user_id: number,
    project_id: number,
    folder_id: number,
): Promise<void> {
    await patch(`/api/users/${encodeURIComponent(user_id)}/preferences`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            key: `plugin_docman_hide_${project_id}_${folder_id}`,
            value: DOCMAN_FOLDER_EXPANDED_VALUE,
        }),
    });
}

export async function deleteUserPreference(user_id: number, key: string): Promise<void> {
    await del(
        `/api/users/${encodeURIComponent(user_id)}/preferences?key=${encodeURIComponent(key)}`,
    );
}

export async function deleteUserPreferenciesForFolderInProject(
    user_id: number,
    project_id: number,
    folder_id: number,
): Promise<void> {
    const key = `plugin_docman_hide_${project_id}_${folder_id}`;

    await deleteUserPreference(user_id, key);
}

export function setNarrowModeForEmbeddedDisplay(
    user_id: number,
    project_id: number,
    document_id: number,
): ResultAsync<typeof EMBEDDED_FILE_DISPLAY_NARROW, Fault> {
    return patchResponse(
        uri`/api/users/${user_id}/preferences`,
        {},
        {
            key: `plugin_docman_display_embedded_${project_id}_${document_id}`,
            value: "narrow",
        },
    ).map(() => EMBEDDED_FILE_DISPLAY_NARROW);
}

export function removeUserPreferenceForEmbeddedDisplay(
    user_id: number,
    project_id: number,
    document_id: number,
): ResultAsync<typeof EMBEDDED_FILE_DISPLAY_LARGE, Fault> {
    const key = `plugin_docman_display_embedded_${project_id}_${document_id}`;
    return del_result(uri`/api/users/${user_id}/preferences?key=${key}`).map(
        () => EMBEDDED_FILE_DISPLAY_LARGE,
    );
}

export function getPreferenceForEmbeddedDisplay(
    user_id: number,
    project_id: number,
    document_id: number,
): ResultAsync<EmbeddedFileDisplayPreference, Fault> {
    return getJSON<{ key: string; value: "narrow" | false }>(
        uri`/api/users/${user_id}/preferences?key=plugin_docman_display_embedded_${project_id}_${document_id}`,
    ).map((result): EmbeddedFileDisplayPreference => {
        return result.value === "narrow"
            ? EMBEDDED_FILE_DISPLAY_NARROW
            : EMBEDDED_FILE_DISPLAY_LARGE;
    });
}

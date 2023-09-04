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
import { del, get, patch } from "@tuleap/tlp-fetch";

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

export async function setNarrowModeForEmbeddedDisplay(
    user_id: number,
    project_id: number,
    document_id: number,
): Promise<void> {
    await patch(`/api/users/${encodeURIComponent(user_id)}/preferences`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            key: `plugin_docman_display_embedded_${project_id}_${document_id}`,
            value: "narrow",
        }),
    });
}

export async function removeUserPreferenceForEmbeddedDisplay(
    user_id: number,
    project_id: number,
    document_id: number,
): Promise<void> {
    const key = `plugin_docman_display_embedded_${project_id}_${document_id}`;

    await del(
        `/api/users/${encodeURIComponent(user_id)}/preferences?key=${encodeURIComponent(key)}`,
    );
}

export async function getPreferenceForEmbeddedDisplay(
    user_id: number,
    project_id: number,
    document_id: number,
): Promise<"narrow" | false> {
    const escaped_user_id = encodeURIComponent(user_id);
    const escaped_preference_key = encodeURIComponent(
        `plugin_docman_display_embedded_${project_id}_${document_id}`,
    );
    const response = await get(
        `/api/users/${escaped_user_id}/preferences?key=${escaped_preference_key}`,
    );

    return (await response.json()).value;
}

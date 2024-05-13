/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
import { getAllJSON, put, uri } from "@tuleap/fetch-result";
import type { Fault } from "@tuleap/fault";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";

export function getAllSections(document_id: number): ResultAsync<readonly ArtidocSection[], Fault> {
    return getAllJSON(uri`/api/artidoc/${document_id}/sections`, {
        params: {
            limit: 50,
        },
    });
}

export function putArtifactDescription(
    artifact_id: number,
    new_description: string,
): ResultAsync<unknown, Fault> {
    return put(
        uri`/api/artifacts/${artifact_id}`,
        {},
        {
            values: [
                {
                    field_id: 111,
                    format: "html",
                    label: "Original Submission",
                    value: new_description,
                },
            ],
        },
    );
}

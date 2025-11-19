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
import {
    del,
    getAllJSON,
    putResponse,
    uri,
    getJSON,
    patchResponse,
    postJSON,
} from "@tuleap/fetch-result";
import type { Fault } from "@tuleap/fault";
import type { FileIdentifier } from "@tuleap/file-upload";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import type { PositionForSection } from "@/sections/save/SectionsPositionsForSaveRetriever";
import type { Level } from "@/sections/levels/SectionsNumberer";
import type {
    ConfigurationField,
    TrackerForFields,
} from "@/sections/readonly-fields/AvailableReadonlyFields";

export function putConfiguration(
    document_id: number,
    selected_tracker_id: number,
    selected_fields: ConfigurationField[],
): ResultAsync<Response, Fault> {
    return putResponse(
        uri`/api/artidoc/${document_id}/configuration`,
        {},
        {
            selected_tracker_ids: [selected_tracker_id],
            fields: selected_fields.map((field) => ({
                field_id: field.field_id,
                display_type: field.display_type,
            })),
        },
    );
}

export function reorderSections(
    document_id: number,
    section_id: string,
    direction: "before" | "after",
    compared_to: string,
): ResultAsync<Response, Fault> {
    return patchResponse(
        uri`/api/artidoc/${document_id}/sections`,
        {},
        {
            order: {
                ids: [section_id],
                direction,
                compared_to,
            },
        },
    );
}

export function createSectionFromExistingArtifact(
    artidoc_id: number,
    artifact_id: number,
    position: PositionForSection,
    level: number,
): ResultAsync<ArtidocSection, Fault> {
    return postJSON<ArtidocSection>(uri`/api/artidoc_sections`, {
        artidoc_id,
        section: {
            import: {
                artifact: { id: artifact_id },
                level,
            },
            position,
        },
    });
}

export function createSection(
    artidoc_id: number,
    title: string,
    description: string,
    position: PositionForSection,
    level: Level,
    type: "freetext" | "artifact",
    attachments: FileIdentifier[],
): ResultAsync<ArtidocSection, Fault> {
    return postJSON<ArtidocSection>(uri`/api/v1/artidoc_sections`, {
        artidoc_id,
        section: {
            content: { title, description, type, attachments, level },
            position,
        },
    });
}

export function getAllSections(document_id: number): ResultAsync<readonly ArtidocSection[], Fault> {
    return getAllJSON<ArtidocSection>(uri`/api/artidoc/${document_id}/sections`, {
        params: {
            limit: 50,
        },
    });
}

export function getVersionedSections(
    document_id: number,
    version_id: number,
): ResultAsync<readonly ArtidocSection[], Fault> {
    return getAllJSON<ArtidocSection>(uri`/api/artidoc_versions/${version_id}`, {
        params: {
            limit: 50,
            artidoc_id: document_id,
        },
    });
}

export function getSection(section_id: string): ResultAsync<ArtidocSection, Fault> {
    return getJSON<ArtidocSection>(uri`/api/artidoc_sections/${section_id}`);
}

export function putSection(
    section_id: string,
    title: string,
    description: string,
    attachments: FileIdentifier[],
    level: Level,
): ResultAsync<Response, Fault> {
    return putResponse(
        uri`/api/artidoc_sections/${section_id}`,
        {},
        {
            title,
            description,
            attachments,
            level,
        },
    );
}

export function deleteSection(section_id: string): ResultAsync<Response, Fault> {
    return del(uri`/api/artidoc_sections/${section_id}`);
}

export function getTracker(tracker_id: number): ResultAsync<TrackerForFields, Fault> {
    return getJSON<TrackerForFields>(uri`/api/trackers/${tracker_id}`);
}

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
import TurndownService from "turndown";
import type { ArtidocSection, ArtifactSection } from "@/helpers/artidoc-section.type";
import { isFreetextSection, isCommonmark, isTitleAString } from "@/helpers/artidoc-section.type";
import type { Tracker } from "@/stores/configuration-store";
import type { PositionForSection } from "@/sections/SectionsPositionsForSaveRetriever";
import type { MergedAttachmentFiles } from "@/sections/SectionAttachmentFilesManager";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";

export function putConfiguration(
    document_id: number,
    selected_tracker_id: number,
): ResultAsync<Response, Fault> {
    return putResponse(
        uri`/api/artidoc/${document_id}/configuration`,
        {},
        {
            selected_tracker_ids: [selected_tracker_id],
        },
    );
}

export function putArtifact(
    artifact_id: number,
    new_title: string,
    title: ArtifactSection["title"],
    new_description: string,
    description_field_id: number,
    merged_attachments: MergedAttachmentFiles,
): ResultAsync<Response, Fault> {
    const values: { field_id: number; value: unknown }[] = [
        {
            field_id: description_field_id,
            value: {
                content: new_description,
                format: "html",
            },
        },
        {
            field_id: title.field_id,
            ...(isTitleAString(title)
                ? { value: new_title }
                : { value: { content: new_title, format: "text" } }),
        },
    ];
    if (merged_attachments && merged_attachments.field_id > 0) {
        values.push({
            field_id: merged_attachments.field_id,
            value: merged_attachments.value,
        });
    }
    return putResponse(
        uri`/api/artifacts/${artifact_id}`,
        {},
        {
            values,
        },
    );
}

export function postArtifact(
    tracker: Tracker,
    new_title: string,
    title: ArtifactSection["title"],
    new_description: string,
    description_field_id: number,
    merged_attachments: MergedAttachmentFiles,
): ResultAsync<{ id: number }, Fault> {
    const values: { field_id: number; value: unknown }[] = [
        {
            field_id: description_field_id,
            value: {
                content: new_description,
                format: "html",
            },
        },
        {
            field_id: title.field_id,
            ...(isTitleAString(title)
                ? { value: new_title }
                : { value: { content: new_title, format: "text" } }),
        },
    ];

    if (merged_attachments && merged_attachments.field_id > 0) {
        values.push({
            field_id: merged_attachments.field_id,
            value: merged_attachments.value,
        });
    }

    return postJSON<{ id: number }>(uri`/api/artifacts`, {
        tracker: { id: tracker.id },
        values,
    });
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

export function createArtifactSection(
    artidoc_id: number,
    artifact_id: number,
    position: PositionForSection,
): ResultAsync<ArtidocSection, Fault> {
    return postJSON<ArtidocSection>(uri`/api/artidoc_sections`, {
        artidoc_id,
        section: {
            artifact: { id: artifact_id },
            position,
            level: 1,
            content: null,
        },
    }).map(injectDisplayTitle);
}

export function createFreetextSection(
    artidoc_id: number,
    title: string,
    description: string,
    position: PositionForSection,
): ResultAsync<ArtidocSection, Fault> {
    return postJSON<ArtidocSection>(uri`/api/v1/artidoc_sections`, {
        artidoc_id,
        section: {
            content: { title, description, type: "freetext" },
            level: 1,
            position,
        },
    }).map(injectDisplayTitle);
}

export function getAllSections(document_id: number): ResultAsync<readonly ArtidocSection[], Fault> {
    return getAllJSON<ArtidocSection>(uri`/api/artidoc/${document_id}/sections`, {
        params: {
            limit: 50,
        },
    }).map((sections: readonly ArtidocSection[]) => sections.map(injectDisplayTitle));
}

export function getSection(section_id: string): ResultAsync<ArtidocSection, Fault> {
    return getJSON<ArtidocSection>(uri`/api/artidoc_sections/${section_id}`).map(
        injectDisplayTitle,
    );
}

export function putSection(
    section_id: string,
    title: string,
    description: string,
): ResultAsync<Response, Fault> {
    return putResponse(
        uri`/api/artidoc_sections/${section_id}`,
        {},
        {
            title,
            description,
            level: 1,
        },
    );
}

export function deleteSection(section_id: string): ResultAsync<Response, Fault> {
    return del(uri`/api/artidoc_sections/${section_id}`);
}

const turndown_service = new TurndownService({ emDelimiter: "*" });

function injectDisplayTitle(section: ArtidocSection): ArtidocSection {
    if (isFreetextSection(section)) {
        return FreetextSectionFactory.override({
            ...section,
            display_title: section.title,
        });
    }

    const title = section.title;
    const display_title = isTitleAString(title)
        ? title.value
        : isCommonmark(title)
          ? title.commonmark
          : title.format === "text"
            ? title.value
            : turndown_service.turndown(title.value);

    return {
        ...section,
        display_title: display_title.replace(/([\r\n]+)/g, " "),
    };
}

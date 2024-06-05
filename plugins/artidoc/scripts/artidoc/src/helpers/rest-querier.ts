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
import { getAllJSON, putResponse, uri, getJSON } from "@tuleap/fetch-result";
import type { Fault } from "@tuleap/fault";
import TurndownService from "turndown";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import { isCommonmark, isTitleAString } from "@/helpers/artidoc-section.type";

type ArtidocSectionFromRest = Omit<ArtidocSection, "display_title">;

export function putArtifact(
    artifact_id: number,
    new_title: string,
    title: ArtidocSection["title"],
    new_description: string,
    description_field_id: number,
): ResultAsync<Response, Fault> {
    return putResponse(
        uri`/api/artifacts/${artifact_id}`,
        {},
        {
            values: [
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
            ],
        },
    );
}

export function getAllSections(document_id: number): ResultAsync<readonly ArtidocSection[], Fault> {
    return getAllJSON<ArtidocSectionFromRest>(uri`/api/artidoc/${document_id}/sections`, {
        params: {
            limit: 50,
        },
    }).map((sections: readonly ArtidocSectionFromRest[]) => sections.map(injectDisplayTitle));
}

export function getSection(section_id: string): ResultAsync<ArtidocSection, Fault> {
    return getJSON<ArtidocSectionFromRest>(uri`/api/artidoc_sections/${section_id}`).map(
        injectDisplayTitle,
    );
}

const turndown_service = new TurndownService({ emDelimiter: "*" });

function injectDisplayTitle(section: ArtidocSectionFromRest): ArtidocSection {
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

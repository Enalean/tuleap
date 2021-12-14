/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import type { ExportDocument } from "../../../../type";
import type { VueGettextProvider } from "../../../vue-gettext-provider";
import type { Table } from "docx";
import {
    HEADER_LEVEL_SECTION_TITLE,
    HEADER_STYLE_SECTION_TITLE,
    MAIN_TITLES_NUMBERING_ID,
} from "./document-properties";
import { Bookmark, TextRun, Paragraph } from "docx";

export function getTraceabilityMatrixTitle(gettext_provider: VueGettextProvider): {
    id: string;
    text: string;
} {
    return {
        id: "matrix",
        text: gettext_provider.$gettext("Traceability matrix"),
    };
}

export function buildTraceabilityMatrix(
    document: ExportDocument,
    gettext_provider: VueGettextProvider
): (Paragraph | Table)[] {
    const title = getTraceabilityMatrixTitle(gettext_provider);

    const section_title = new Paragraph({
        heading: HEADER_LEVEL_SECTION_TITLE,
        style: HEADER_STYLE_SECTION_TITLE,
        numbering: {
            reference: MAIN_TITLES_NUMBERING_ID,
            level: 0,
        },
        children: [
            new Bookmark({
                id: title.id,
                children: [new TextRun(title.text)],
            }),
        ],
    });

    return [section_title];
}

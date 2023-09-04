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

import type { GenericGlobalExportProperties } from "../../../../type";
import { Footer, Header, PageNumber, Paragraph, TabStopPosition, TabStopType, TextRun } from "docx";
import { sprintf } from "sprintf-js";
import type { GettextProvider } from "@tuleap/gettext";

export function buildHeader(
    global_export_properties: GenericGlobalExportProperties,
    document_name: string,
): Header {
    return new Header({
        children: [
            new Paragraph({
                children: [
                    new TextRun({
                        children: [
                            global_export_properties.platform_name,
                            " | ",
                            global_export_properties.project_name,
                        ],
                    }),
                    new TextRun({
                        children: ["\t", document_name],
                    }),
                ],
                tabStops: [
                    {
                        type: TabStopType.RIGHT,
                        position: TabStopPosition.MAX,
                    },
                ],
            }),
        ],
    });
}

export function buildFooter(
    gettext_provider: GettextProvider,
    global_export_properties: GenericGlobalExportProperties,
    exported_formatted_date: string,
): Footer {
    return new Footer({
        children: [
            new Paragraph({
                children: [
                    new TextRun({
                        children: [
                            sprintf(gettext_provider.gettext("Exported on %(date)s by %(user)s"), {
                                date: exported_formatted_date,
                                user: global_export_properties.user_display_name,
                            }),
                        ],
                    }),
                    new TextRun({
                        children: ["\t", PageNumber.CURRENT, " / ", PageNumber.TOTAL_PAGES],
                    }),
                ],
                tabStops: [
                    {
                        type: TabStopType.RIGHT,
                        position: TabStopPosition.MAX,
                    },
                ],
            }),
        ],
    });
}

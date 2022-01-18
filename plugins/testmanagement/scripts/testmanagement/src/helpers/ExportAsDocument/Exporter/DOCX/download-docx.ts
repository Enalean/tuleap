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

import { triggerBlobDownload } from "./trigger-blob-download";
import { File, Packer } from "docx";
import type {
    DateTimeLocaleInformation,
    ExportDocument,
    GettextProvider,
    GlobalExportProperties,
} from "../../../../type";
import { buildCoverPage } from "./cover-builder";
import { properties } from "./document-properties";

export async function downloadDocx(
    document: ExportDocument,
    gettextCatalog: GettextProvider,
    global_export_properties: GlobalExportProperties,
    datetime_locale_information: DateTimeLocaleInformation
): Promise<void> {
    const exported_formatted_date = new Date().toLocaleDateString(
        datetime_locale_information.locale,
        { timeZone: datetime_locale_information.timezone }
    );

    const file = new File({
        ...properties,
        sections: [
            {
                children: [
                    ...(await buildCoverPage(
                        gettextCatalog,
                        global_export_properties,
                        exported_formatted_date
                    )),
                ],
                properties: {
                    titlePage: true,
                },
            },
        ],
    });
    triggerBlobDownload(`${document.name}.docx`, await Packer.toBlob(file));
}

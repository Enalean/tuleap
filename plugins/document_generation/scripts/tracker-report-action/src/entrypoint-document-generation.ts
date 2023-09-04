/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { showLoaderWhileProcessing } from "./show-loader-processing";

export function setupLinkForTheDocumentExport(): void {
    const generate_document_link = document.getElementById(
        "tracker-report-action-generate-document",
    );
    if (!generate_document_link) {
        throw new Error("Missing generate document button");
    }
    generate_document_link.addEventListener("click", async (event): Promise<void> => {
        event.preventDefault();

        const language = document.body.dataset.userLocale;
        if (language === undefined) {
            throw new Error("Not able to find the user language.");
        }

        if (!generate_document_link.dataset.properties) {
            throw new Error("Missing properties dataset");
        }
        const properties = JSON.parse(generate_document_link.dataset.properties);

        await showLoaderWhileProcessing(async (): Promise<void> => {
            const export_document_module = import("./export-document");
            const download_docx_module = import("./Exporter/DOCX/download-docx");
            const gettext_module = import("@tuleap/gettext");

            const { initGettext, getPOFileFromLocaleWithoutExtension } = await gettext_module;

            const gettext_provider = await initGettext(
                language,
                "tracker-report-action",
                (locale) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
            );

            const { startDownloadExportDocument } = await export_document_module;
            const { downloadDocx } = await download_docx_module;

            await startDownloadExportDocument(properties, gettext_provider, downloadDocx);
        });
    });
}

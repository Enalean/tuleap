/*
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

document.addEventListener("DOMContentLoaded", () => {
    const generate_document_link = document.getElementById(
        "tracker-report-action-generate-document"
    );
    if (!generate_document_link) {
        throw new Error("Missing generate document button");
    }
    generate_document_link.addEventListener("click", async (event): Promise<void> => {
        event.preventDefault();

        if (!generate_document_link.dataset.reportId) {
            throw new Error("Missing report ID dataset");
        }
        const report_id = Number(generate_document_link.dataset.reportId);

        const { startDownloadExportDocument } = await import(
            /* webpackChunkName: "document_generation-download-export" */ "./export-document"
        );

        await startDownloadExportDocument(report_id);
    });
});

export {};

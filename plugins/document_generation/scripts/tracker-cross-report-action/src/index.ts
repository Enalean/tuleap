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

import type { GlobalExportProperties } from "./type";
import { downloadXLSXDocument } from "./export-document";
import { downloadXLSX } from "./Exporter/XLSX/download-xlsx";

document.addEventListener("DOMContentLoaded", () => {
    const generate_document_link = document.getElementById(
        "tracker-cross-report-action-generate-document"
    );
    if (!generate_document_link) {
        throw new Error("Missing generate cross tracker document button");
    }

    generate_document_link.addEventListener("click", (event): void => {
        event.preventDefault();

        if (!generate_document_link.dataset.properties) {
            throw new Error("Missing properties dataset");
        }
        const properties: GlobalExportProperties = JSON.parse(
            generate_document_link.dataset.properties
        );

        downloadXLSXDocument(properties, downloadXLSX);
    });
});

export {};

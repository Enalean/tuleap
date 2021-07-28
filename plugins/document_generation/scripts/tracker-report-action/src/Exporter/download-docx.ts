/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import type { ExportDocument } from "../type";
import { AlignmentType, Document, Footer, Packer, PageNumber, Paragraph, TextRun } from "docx";

export async function downloadDocx(document: ExportDocument): Promise<void> {
    const footers = {
        default: new Footer({
            children: [
                new Paragraph({
                    alignment: AlignmentType.CENTER,
                    children: [
                        new TextRun({
                            children: [PageNumber.CURRENT, " / ", PageNumber.TOTAL_PAGES],
                        }),
                    ],
                }),
            ],
        }),
    };

    const paragraphs = [];
    for (const artifact_value of document.fields) {
        paragraphs.push(
            new Paragraph({
                text: artifact_value.field_name + "\n" + artifact_value.field_value,
            })
        );
    }

    const doc = new Document({
        sections: [
            {
                children: [...paragraphs],
                footers,
            },
        ],
    });
    const blob = await Packer.toBlob(doc);
    const file = window.URL.createObjectURL(blob);
    window.location.assign(file);
}

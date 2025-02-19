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

import type { PdfTemplate } from "@tuleap/print-as-pdf";
import { printAsPdf } from "@tuleap/print-as-pdf";
import type CodeMirror from "codemirror";
import { createModal } from "@tuleap/tlp-modal";
import { getAttributeOrThrow } from "@tuleap/dom";

export function initiatePrintPreview(
    style: CodeMirror.EditorFromTextArea,
    title_page_content_editor: CodeMirror.EditorFromTextArea,
    header_content_editor: CodeMirror.EditorFromTextArea,
    footer_content_editor: CodeMirror.EditorFromTextArea,
): void {
    const button = document.getElementById("pdftemplate-print-preview");
    if (!(button instanceof HTMLButtonElement)) {
        return;
    }

    const fake_document = document.getElementById("pdftemplate-print-preview-fake-document");
    if (!(fake_document instanceof HTMLElement)) {
        throw Error("Cannot found fake document");
    }

    const [label, description] = ["input-label", "input-description"].map((id) =>
        document.getElementById(id),
    );
    if (!(label instanceof HTMLInputElement)) {
        throw Error("Cannot found label input");
    }
    if (!(description instanceof HTMLTextAreaElement)) {
        throw Error("Cannot found description textarea");
    }

    const modal = document.getElementById("pdftemplate-admin-template-error-modal");
    if (!modal) {
        throw Error("Cannot find error modal");
    }

    const details = document.getElementById("pdftemplate-admin-template-error-modal-details");
    if (!details) {
        throw Error("Cannot find error modal details");
    }

    button.addEventListener("click", () => {
        const template: PdfTemplate = {
            id: "",
            label: label.value,
            description: description.value,
            style: style.getValue(),
            title_page_content: title_page_content_editor.getValue(),
            header_content: header_content_editor.getValue(),
            footer_content: footer_content_editor.getValue(),
        };

        printAsPdf(
            fake_document,
            template,
            JSON.parse(getAttributeOrThrow(button, "data-variables")),
        ).mapErr((fault) => {
            details.innerText = fault.toString();

            createModal(modal, {
                destroy_on_hide: true,
            }).show();
        });
    });
}

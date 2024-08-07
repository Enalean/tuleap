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

import print from "print-js/src/index.js";
import { ok, err } from "neverthrow";
import type { Result } from "neverthrow";
import DOMPurify from "dompurify";
import { Fault } from "@tuleap/fault";

export interface PdfTemplate {
    readonly id: string;
    readonly label: string;
    readonly description: string;
    readonly style: string;
    readonly title_page_content: string;
    readonly header_content: string;
    readonly footer_content: string;
}

export type PdfTemplateVariables = {
    readonly DOCUMENT_TITLE?: string;
};

const replaceVariables = (html: string, variables: PdfTemplateVariables): string => {
    // eslint-disable-next-line no-template-curly-in-string
    return html.replace("${DOCUMENT_TITLE}", variables.DOCUMENT_TITLE ?? "");
};

const injectContent = (
    printable: HTMLElement,
    container_id: string,
    content: string,
    variables: PdfTemplateVariables,
): Result<null, Fault> => {
    if (content.length === 0) {
        return ok(null);
    }

    const container = printable.querySelector(`#${container_id}`);
    if (!container) {
        return err(Fault.fromMessage(`#${container_id} not found.`));
    }

    // eslint-disable-next-line no-unsanitized/property
    container.innerHTML = DOMPurify.sanitize(replaceVariables(content, variables));
    return ok(null);
};

const processPrint = (printable: HTMLElement, template: PdfTemplate): Result<null, Fault> => {
    try {
        print({
            printable,
            type: "html",
            scanStyles: false,
            style: template.style,
        });
    } catch (e: unknown) {
        if (e instanceof Error) {
            return err(Fault.fromError(e));
        }

        return err(Fault.fromMessage("Unknown error."));
    }

    return ok(null);
};

export const printAsPdf = (
    printable: HTMLElement,
    template: PdfTemplate,
    variables: PdfTemplateVariables,
): Result<null, Fault> =>
    injectContent(printable, "document-title-page", template.title_page_content, variables)
        .andThen(() =>
            injectContent(printable, "document-header", template.header_content, variables),
        )
        .andThen(() =>
            injectContent(printable, "document-footer", template.footer_content, variables),
        )
        .andThen(() => processPrint(printable, template))
        .mapErr((fault: Fault) => Fault.fromMessage(`Failed to print document as pdf: ${fault}`));

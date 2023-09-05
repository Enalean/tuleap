/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

export async function getEmptyOfficeFileFromMimeType(
    locale: string,
    mime_type: string,
): Promise<{ file: File; badge_class: string; extension: string }> {
    let response: Response;
    let extension: string;
    let badge_class: string;
    if ("application/word" === mime_type) {
        extension = "docx";
        badge_class = "document-document-badge";
    } else if ("application/excel" === mime_type) {
        extension = "xlsx";
        badge_class = "document-spreadsheet-badge";
    } else if ("application/powerpoint" === mime_type) {
        extension = "pptx";
        badge_class = "document-presentation-badge";
    } else {
        throw Error("Unsupported mime type " + mime_type);
    }

    /* eslint-disable no-unsanitized/method -- The imports are safe, they will be replaced by the bundler anyway */
    try {
        response = await fetch(
            (await import(`./empty_document_templates/${locale}/new.${extension}`)).default,
        );
    } catch (e) {
        response = await fetch(
            (await import(`./empty_document_templates/en-US/new.${extension}`)).default,
        );
    }
    /* eslint-enable */

    return {
        extension,
        badge_class,
        file: new File([await response.blob()], `document.${extension}`, { type: mime_type }),
    };
}

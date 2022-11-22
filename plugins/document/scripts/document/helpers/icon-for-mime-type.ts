/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 *
 */

import { ICON_EMPTY } from "../constants";

function iconForMimeType(mime_type: string): string {
    const mime_type_lower = mime_type.toLowerCase();
    const parts = mime_type_lower.split("/");
    let icon;
    switch (parts[0]) {
        case "audio":
            icon = "fa-solid fa-file-audio document-audio-icon";
            break;
        case "video":
            icon = "fa-solid fa-file-video document-video-icon";
            break;
        case "image":
            icon = "fa-solid fa-file-image document-image-icon";
            break;
        case "text":
            if (typeof parts[1] !== "undefined" && parts[1] === "html") {
                icon = "fa-solid fa-file-code document-code-icon";
            } else {
                icon = "fa-regular fa-file-lines document-text-icon";
            }
            break;
        case "application":
            icon = "fa-solid fa-file-code document-code-icon";
            if (typeof parts[1] !== "undefined") {
                switch (parts[1]) {
                    case "gzip":
                    case "zip":
                    case "x-tar":
                    case "x-rar":
                    case "x-java-archive":
                    case "x-gzip":
                    case "x-gtar":
                    case "x-compressed":
                        icon = "fa-solid fa-file-zipper document-archive-icon";
                        break;
                    case "pdf":
                        icon = "fa-solid fa-file-pdf document-pdf-icon";
                        break;
                    case "rtf":
                    case "msword":
                    case "vnd.ms-works":
                    case "vnd.openxmlformats-officedocument.wordprocessingml.document":
                    case "word":
                    case "wordperfect5.1":
                    case "vnd.ms-word.document.macroenabled.12":
                    case "vnd.oasis.opendocument.text":
                    case "vnd.oasis.opendocument.text-template":
                    case "vnd.oasis.opendocument.text-web":
                    case "vnd.oasis.opendocument.text-master":
                    case "x-vnd.oasis.opendocument.text":
                    case "vnd.sun.xml.writer":
                    case "vnd.sun.xml.writer.template":
                    case "vnd.sun.xml.writer.global":
                    case "vnd.stardivision.writer":
                    case "vnd.stardivision.writer-global":
                    case "x-starwriter":
                    case "x-soffice":
                        icon = "fa-solid fa-file-word document-document-icon";
                        break;
                    case "powerpoint":
                    case "vnd.ms-powerpoint":
                    case "vnd.ms-powerpoint.presentation.macroenabled.12":
                    case "vnd.openxmlformats-officedocument.presentationml.presentation":
                    case "vnd.sun.xml.impress":
                    case "vnd.sun.xml.impress.template":
                    case "vnd.oasis.opendocument.presentation":
                    case "vnd.oasis.opendocument.presentation-template":
                    case "vnd.stardivision.impress":
                    case "vnd.stardivision.impress-packed":
                    case "x-starimpress":
                        icon = "fa-solid fa-file-powerpoint document-presentation-icon";
                        break;
                    case "excel":
                    case "vnd.ms-excel":
                    case "vnd.ms-excel.sheet.macroenabled.12":
                    case "vnd.openxmlformats-officedocument.spreadsheetml.sheet":
                    case "vnd.sun.xml.calc":
                    case "vnd.sun.xml.calc.template":
                    case "vnd.oasis.opendocument.spreadsheet":
                    case "vnd.oasis.opendocument.spreadsheet-template":
                    case "vnd.stardivision.calc":
                    case "x-starcalc":
                        icon = "fa-solid fa-file-excel document-spreadsheet-icon";
                        break;
                    default:
                        break;
                }
            }
            break;
        default:
            icon = ICON_EMPTY;
    }
    return icon;
}

export { iconForMimeType };

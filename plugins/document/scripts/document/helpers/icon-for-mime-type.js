/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

function iconForMimeType(item) {
    if (!item.file_properties) {
        return "fa-file-o document-empty-icon";
    }
    const mime_type_lower = item.file_properties.file_type.toLowerCase();
    const parts = mime_type_lower.split("/");
    let icon;
    switch (parts[0]) {
        case "audio":
            icon = "fa-file-audio-o document-audio-icon";
            break;
        case "video":
            icon = "fa-file-video-o document-video-icon";
            break;
        case "image":
            icon = "fa-file-image-o document-image-icon";
            break;
        case "text":
            if (typeof parts[1] !== "undefined" && parts[1] === "html") {
                icon = "fa-file-code-o document-code-icon";
            } else {
                icon = "fa-file-text-o document-text-icon";
            }
            break;
        case "application":
            icon = "fa-file-code-o document-code-icon";
            if (typeof parts[1] !== "undefined") {
                switch (parts[1]) {
                    case "zip":
                    case "x-tar":
                    case "x-java-archive":
                    case "x-gzip":
                    case "x-gtar":
                    case "x-compressed":
                        icon = "fa-file-archive-o document-archive-icon";
                        break;
                    case "pdf":
                        icon = "fa-file-pdf-o document-pdf-icon";
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
                        icon = "fa-file-word-o document-document-icon";
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
                        icon = "fa-file-powerpoint-o document-presentation-icon";
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
                        icon = "fa-file-excel-o document-spreadsheet-icon";
                        break;
                    default:
                        break;
                }
            }
            break;
        default:
            icon = "fa-file-o document-empty-icon";
    }
    return icon;
}

export { iconForMimeType };
